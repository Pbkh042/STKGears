<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

final class FirebaseServiceException extends Exception {}

function firebase_http(string $url, string $method = 'GET', ?string $body = null, array $headers = []): array
{
    $responseHeaders = [];
    // Keep connection/TLS reuse within this PHP request, with fresh options
    // and headers for each call (Database, Auth and OAuth use different hosts).
    static $curl = null;
    $curl ??= curl_init();
    curl_reset($curl);
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        },
    ]);
    if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if ($response === false || $status === 0) throw new FirebaseServiceException('Firebase is temporarily unavailable.');
    return ['status' => $status, 'data' => json_decode($response, true), 'headers' => $responseHeaders];
}

final class FirebaseDatabase
{
    public ?array $storeCache = null;
    private string $baseUrl;
    private array $account;
    private string $databaseSecret;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(app_env('FIREBASE_DATABASE_URL'), '/');
        if (!preg_match('~^https://[a-z0-9.-]+\.(?:firebaseio\.com|firebasedatabase\.app)$~', $this->baseUrl)) {
            throw new FirebaseServiceException('Invalid Firebase database configuration.');
        }
        $this->databaseSecret = app_env('FIREBASE_DATABASE_SECRET');
        if ($this->databaseSecret !== '') {
            $this->account = [];
            return;
        }
        $account = json_decode(app_env('FIREBASE_SERVICE_ACCOUNT_JSON'), true);
        if (!is_array($account) || empty($account['private_key']) || empty($account['client_email'])
            || !str_ends_with((string)$account['client_email'], '.iam.gserviceaccount.com')) {
            throw new FirebaseServiceException('Firebase server credentials are not configured.');
        }
        $this->account = $account;
    }

    private function accessToken(): string
    {
        if ($this->token !== null) return $this->token;
        $cachePath = sys_get_temp_dir() . '/stk-firebase-' . hash('sha256', $this->account['client_email'] . ($this->account['private_key_id'] ?? '')) . '.json';
        $cached = is_file($cachePath) ? json_decode((string)file_get_contents($cachePath), true) : null;
        if (is_array($cached) && ($cached['expires'] ?? 0) > time() + 60 && !empty($cached['token'])) {
            return $this->token = $cached['token'];
        }
        $encode = static fn(string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $encode(json_encode([
            'iss' => $this->account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time() - 30,
            'exp' => time() + 3500,
        ]));
        $unsigned = $header . '.' . $claims;
        if (!openssl_sign($unsigned, $signature, $this->account['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new FirebaseServiceException('Cannot authenticate Firebase server.');
        }
        $response = firebase_http('https://oauth2.googleapis.com/token', 'POST', http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . $encode($signature),
        ]), ['Content-Type: application/x-www-form-urlencoded']);
        if ($response['status'] !== 200 || empty($response['data']['access_token'])) {
            throw new FirebaseServiceException('Firebase server authentication failed.');
        }
        $this->token = $response['data']['access_token'];
        file_put_contents($cachePath, json_encode(['token' => $this->token, 'expires' => time() + (int)$response['data']['expires_in']]), LOCK_EX);
        @chmod($cachePath, 0600);
        return $this->token;
    }

    public function request(string $path, string $method = 'GET', mixed $data = null, array $headers = [], array $query = []): array
    {
        $segments = array_map('rawurlencode', explode('/', trim($path, '/')));
        $url = $this->baseUrl . '/' . implode('/', $segments) . '.json';
        if ($this->databaseSecret !== '') $query['auth'] = $this->databaseSecret;
        if ($query !== []) $url .= '?' . http_build_query($query);
        $result = firebase_http($url, $method, $method === 'GET' ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), array_merge([
            ...($this->databaseSecret === '' ? ['Authorization: Bearer ' . $this->accessToken()] : []),
            'Content-Type: application/json',
        ], $headers));
        if (!in_array($result['status'], [200, 204, 412], true)) {
            throw new FirebaseServiceException('Firebase database request failed (HTTP ' . $result['status'] . ').');
        }
        return $result;
    }

    public function transaction(string $path, callable $update): mixed
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $snapshot = $this->request($path, 'GET', null, ['X-Firebase-ETag: true']);
            $etag = $snapshot['headers']['etag'] ?? '';
            if ($etag === '') throw new FirebaseServiceException('Missing Firebase transaction version.');
            $value = $snapshot['data'];
            $result = $update($value);
            $written = $this->request($path, 'PUT', $value, ['If-Match: ' . $etag]);
            if ($written['status'] !== 412) {
                $this->storeCache = null;
                return $result;
            }
            usleep(random_int(20000, 80000));
        }
        throw new FirebaseServiceException('Firebase transaction is busy. Please retry.');
    }
}

function firebase_database(): FirebaseDatabase
{
    static $database = null;
    return $database ??= new FirebaseDatabase();
}

function firebase_namespace(): string
{
    $name = app_env('FIREBASE_DATA_NAMESPACE', 'stkgears');
    if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/', $name)) throw new FirebaseServiceException('Invalid data namespace.');
    return $name;
}

function firebase_store_read(): array
{
    $db = firebase_database();
    if ($db->storeCache === null) $db->storeCache = $db->request(firebase_namespace())['data'] ?? [];
    return $db->storeCache;
}

function firebase_store_transaction(callable $update): mixed
{
    return firebase_database()->transaction(firebase_namespace(), static function (&$store) use ($update) {
        $store = is_array($store) ? $store : [];
        return $update($store);
    });
}

function firebase_next_id(array &$store, string $counter): int
{
    return $store['counters'][$counter] = (int)($store['counters'][$counter] ?? 0) + 1;
}

function firebase_auth_request(string $operation, array $input): array
{
    $allowed = ['signUp', 'signInWithPassword', 'lookup', 'update', 'delete'];
    if (!in_array($operation, $allowed, true)) throw new FirebaseServiceException('Unsupported authentication operation.');
    $response = firebase_http('https://identitytoolkit.googleapis.com/v1/accounts:' . $operation . '?key=' . rawurlencode(app_env('FIREBASE_WEB_API_KEY')), 'POST', json_encode($input, JSON_THROW_ON_ERROR), ['Content-Type: application/json']);
    if ($response['status'] === 200) return $response['data'];
    $code = explode(' : ', (string)($response['data']['error']['message'] ?? ''))[0];
    $message = match ($code) {
        'EMAIL_EXISTS' => 'Email này đã được đăng ký.',
        'INVALID_EMAIL' => 'Email không hợp lệ.',
        'INVALID_LOGIN_CREDENTIALS', 'EMAIL_NOT_FOUND', 'INVALID_PASSWORD' => 'Email hoặc mật khẩu không đúng.',
        'USER_DISABLED' => 'Tài khoản đã bị vô hiệu hóa.',
        'TOO_MANY_ATTEMPTS_TRY_LATER' => 'Có quá nhiều lần thử. Vui lòng thử lại sau.',
        'TOKEN_EXPIRED', 'INVALID_ID_TOKEN', 'USER_NOT_FOUND' => 'Phiên đăng nhập đã hết hạn.',
        default => null,
    };
    if ($message !== null) throw new RuntimeException($message);
    throw new FirebaseServiceException('Firebase authentication request failed.');
}

function firebase_refresh_auth(string $refreshToken): array
{
    $response = firebase_http('https://securetoken.googleapis.com/v1/token?key=' . rawurlencode(app_env('FIREBASE_WEB_API_KEY')), 'POST', http_build_query([
        'grant_type' => 'refresh_token', 'refresh_token' => $refreshToken,
    ]), ['Content-Type: application/x-www-form-urlencoded']);
    if ($response['status'] >= 400 && $response['status'] < 500) throw new RuntimeException('Phiên đăng nhập đã hết hạn.');
    if ($response['status'] !== 200 || empty($response['data']['id_token'])) throw new FirebaseServiceException('Authentication refresh failed.');
    return $response['data'];
}

function firebase_normalize_search(string $value): string
{
    $value = str_replace(['đ', 'Đ'], ['d', 'D'], $value);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return strtolower($ascii === false ? $value : $ascii);
}
