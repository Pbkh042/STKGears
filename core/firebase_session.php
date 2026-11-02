<?php
declare(strict_types=1);

require_once __DIR__ . '/firebase.php';

final class FirebaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private string $owner;
    private ?string $lockedId = null;

    public function __construct(private int $lifetime)
    {
        $this->owner = bin2hex(random_bytes(16));
    }

    private function path(string $id): string
    {
        return firebase_namespace() . '_sessions/' . hash('sha256', $id);
    }

    private function acquire(string $id): array
    {
        if ($this->lockedId !== null && $this->lockedId !== $id) $this->close();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $node = firebase_database()->transaction($this->path($id), function (&$node): array {
                    $node = is_array($node) ? $node : [];
                    if (!empty($node['lease_owner']) && $node['lease_owner'] !== $this->owner && ($node['lease_until'] ?? 0) > time()) throw new RuntimeException('Session is busy.');
                    $node['lease_owner'] = $this->owner;
                    $node['lease_until'] = time() + 30;
                    $node['expires_at'] ??= time() + $this->lifetime;
                    return $node;
                });
                $this->lockedId = $id;
                return $node;
            } catch (RuntimeException $error) {
                if ($attempt === 4) throw new FirebaseServiceException('Session is busy. Please retry.');
                usleep(150000);
            }
        }
        throw new FirebaseServiceException('Cannot acquire session.');
    }

    public function open(string $path, string $name): bool { return true; }

    public function close(): bool
    {
        if ($this->lockedId === null) return true;
        $id = $this->lockedId;
        firebase_database()->transaction($this->path($id), function (&$node): void {
            if (is_array($node) && ($node['lease_owner'] ?? '') === $this->owner) {
                unset($node['lease_owner'], $node['lease_until']);
                if (empty($node['data'])) $node = null;
            }
        });
        $this->lockedId = null;
        return true;
    }

    public function read(string $id): string|false
    {
        $node = $this->acquire($id);
        return ($node['expires_at'] ?? 0) > time() ? (base64_decode((string)($node['data'] ?? ''), true) ?: '') : '';
    }

    public function write(string $id, string $data): bool
    {
        if ($this->lockedId !== $id) $this->acquire($id);
        firebase_database()->transaction($this->path($id), function (&$node) use ($data): void {
            if (($node['lease_owner'] ?? '') !== $this->owner) throw new FirebaseServiceException('Session lease expired.');
            $node = $data === '' ? null : ['data' => base64_encode($data), 'expires_at' => time() + $this->lifetime];
        });
        $this->lockedId = null;
        return true;
    }

    public function destroy(string $id): bool
    {
        if ($this->lockedId !== $id) $this->acquire($id);
        firebase_database()->transaction($this->path($id), function (&$node): void {
            if (($node['lease_owner'] ?? '') !== $this->owner) throw new FirebaseServiceException('Session lease expired.');
            $node = null;
        });
        $this->lockedId = null;
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $root = firebase_namespace() . '_sessions';
        $expired = firebase_database()->request($root, 'GET', null, [], ['orderBy' => '"expires_at"', 'endAt' => time(), 'limitToFirst' => 100])['data'] ?? [];
        $count = 0;
        foreach ($expired as $key => $value) {
            $removed = firebase_database()->transaction($root . '/' . $key, static function (&$node): bool {
                if (is_array($node) && ($node['expires_at'] ?? PHP_INT_MAX) <= time() && ($node['lease_until'] ?? 0) <= time()) {
                    $node = null;
                    return true;
                }
                return false;
            });
            if ($removed) $count++;
        }
        return $count;
    }

    public function validateId(string $id): bool
    {
        $node = firebase_database()->request($this->path($id))['data'];
        return is_array($node) && !empty($node['data']) && ($node['expires_at'] ?? 0) > time();
    }

    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
}
