<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
putenv('DATA_BACKEND=firebase');
putenv('APP_ENV=local');
$namespace = 'stkgears_test_http_' . bin2hex(random_bytes(6));
putenv('FIREBASE_DATA_NAMESPACE=' . $namespace);
require_once __DIR__ . '/../core/firebase.php';

function ensure_http(bool $ok, string $what): void { if (!$ok) throw new Exception($what); }
function http_fixture(int $port, string $route, ?array $payload = null, string $cookie = ''): array {
    $headers = ['Content-Type: application/json'];
    if ($cookie !== '') $headers[] = 'Cookie: ' . $cookie;
    $curl = curl_init('http://127.0.0.1:' . $port . $route);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 45, CURLOPT_HTTPHEADER => $headers]);
    if ($payload !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload)]);
    $raw = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    curl_close($curl);
    ensure_http(is_string($raw) && $status > 0, 'HTTP server unreachable');
    preg_match('/Set-Cookie:\s*(PHPSESSID=[^;\r\n]+)/i', substr($raw, 0, $headerSize), $cookies);
    return ['status' => $status, 'data' => json_decode(substr($raw, $headerSize), true), 'cookie' => $cookies[1] ?? $cookie];
}

$servers = [];
$ports = [];
$idToken = null;
$log = tempnam(sys_get_temp_dir(), 'stk-http-log-');
try {
    $rows = json_decode((string)file_get_contents(__DIR__ . '/../firebase/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $store = ['counters' => ['products' => max(array_column($rows, 'id'))]];
    foreach ($rows as $row) $store['products']['p_' . $row['id']] = $row;
    firebase_database()->request($namespace, 'PUT', $store);
    for ($i = 0; $i < 2; $i++) {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        ensure_http($socket !== false, 'Cannot allocate test port');
        $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $ports[] = $port;
        $servers[] = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', 'public', 'public/index.php'], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, dirname(__DIR__));
    }
    for ($i = 0; $i < 30; $i++) {
        $ready = @fsockopen('127.0.0.1', $ports[1], $errno, $error, 0.2);
        if ($ready) { fclose($ready); break; }
        usleep(100000);
    }
    $products = http_fixture($ports[0], '/api/products/list.php?pageSize=50');
    ensure_http($products['status'] === 200 && count($products['data']['products'] ?? []) === 13, 'HTTP catalog');
    $email = 'qa-http-' . bin2hex(random_bytes(8)) . '@example.com';
    $password = bin2hex(random_bytes(16));
    $registered = http_fixture($ports[0], '/api/user/register.php', ['username' => $email, 'password' => $password, 'confirmPassword' => $password]);
    ensure_http($registered['status'] === 201, 'HTTP registration');
    $identity = firebase_auth_request('signInWithPassword', ['email' => $email, 'password' => $password, 'returnSecureToken' => true]);
    $idToken = $identity['idToken'];
    $login = http_fixture($ports[0], '/api/auth/login.php', ['username' => $email, 'password' => $password], $registered['cookie']);
    ensure_http($login['status'] === 200 && $login['data']['role'] === 'user', 'HTTP login');
    $cookie = $login['cookie'];
    $me = http_fixture($ports[1], '/api/user/me.php', null, $cookie);
    ensure_http($me['data']['authenticated'] === true, 'Session survives a different PHP instance');
    ensure_http(http_fixture($ports[1], '/api/admin/products/list.php', null, $cookie)['status'] === 401, 'User denied admin API');
    $order = http_fixture($ports[1], '/api/orders/create.php', ['customer' => ['name' => 'QA HTTP', 'phone' => '0900000000', 'address' => 'QA fixture'], 'items' => [['id' => $rows[0]['id'], 'quantity' => 1]]], $cookie);
    ensure_http($order['status'] === 201 && !empty($order['data']['orderId']), 'HTTP order creation');
    ensure_http(count(http_fixture($ports[0], '/api/orders/history.php', null, $cookie)['data']['orders'] ?? []) === 1, 'HTTP order history');
    http_fixture($ports[0], '/index.php?p=logout', null, $cookie);
    ensure_http(http_fixture($ports[1], '/api/user/me.php', null, $cookie)['data']['authenticated'] === false, 'HTTP logout invalidates session');
    foreach (['/config/app.php', '/tools/seed-firebase.php', '/firebase/catalog.json', '/.env.firebase.local.json'] as $route) ensure_http(http_fixture($ports[0], $route)['status'] === 404, 'Private route exposed');
    echo "PASS: HTTP catalog, registration, login, cross-instance sessions, admin denial, orders, logout and private-route isolation.\n";
} finally {
    foreach ($servers as $server) if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if ($idToken !== null) firebase_auth_request('delete', ['idToken' => $idToken]);
    firebase_database()->request($namespace, 'DELETE');
    firebase_database()->request($namespace . '_sessions', 'DELETE');
    if (is_file($log)) unlink($log);
}
