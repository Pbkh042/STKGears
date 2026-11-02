<?php
declare(strict_types=1);

final class MysqlSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private ?string $lockName = null;

    public function __construct(private PDO $pdo, private int $lifetime)
    {
    }

    private function lock(string $id): void
    {
        $name = 'stk:' . substr(hash('sha256', $id), 0, 60);
        if ($this->lockName === $name) {
            return;
        }
        $this->close();
        $statement = $this->pdo->prepare('SELECT GET_LOCK(?, 5)');
        $statement->execute([$name]);
        if ((int)$statement->fetchColumn() !== 1) {
            throw new RuntimeException('Cannot acquire session lock.');
        }
        $this->lockName = $name;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        if ($this->lockName !== null) {
            $statement = $this->pdo->prepare('SELECT RELEASE_LOCK(?)');
            $statement->execute([$this->lockName]);
            $this->lockName = null;
        }
        return true;
    }

    public function read(string $id): string|false
    {
        $this->lock($id);
        $statement = $this->pdo->prepare('SELECT data FROM app_sessions WHERE id_hash = ? AND expires_at > ?');
        $statement->execute([hash('sha256', $id), time()]);
        $data = $statement->fetchColumn();
        return $data === false ? '' : (string)$data;
    }

    public function write(string $id, string $data): bool
    {
        $this->lock($id);
        $statement = $this->pdo->prepare(
            'INSERT INTO app_sessions (id_hash, data, expires_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)'
        );
        return $statement->execute([hash('sha256', $id), $data, time() + $this->lifetime]);
    }

    public function destroy(string $id): bool
    {
        $this->lock($id);
        $statement = $this->pdo->prepare('DELETE FROM app_sessions WHERE id_hash = ?');
        return $statement->execute([hash('sha256', $id)]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $statement = $this->pdo->prepare('DELETE FROM app_sessions WHERE expires_at <= ?');
        $statement->execute([time()]);
        return $statement->rowCount();
    }

    public function validateId(string $id): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM app_sessions WHERE id_hash = ? AND expires_at > ?');
        $statement->execute([hash('sha256', $id), time()]);
        return $statement->fetchColumn() !== false;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }
}
