<?php
declare(strict_types=1);

require_once __DIR__ . '/../response.php';
$anonymous = ['authenticated' => false];
// A visitor without a session cookie cannot have a signed-in identity.
// Avoid creating database-backed sessions just to display a login link.
$cookie = $_COOKIE[session_name()] ?? null;
if (!is_string($cookie) || $cookie === '') {
    json_ok(['admin' => $anonymous, 'user' => $anonymous]);
}
require_once __DIR__ . '/../common.php';
function account_session_state(string $idKey, string $nameKey): array
{
    if (empty($_SESSION[$idKey])) return ['authenticated' => false];
    return ['authenticated' => true, 'user' => [
        'id' => (int)$_SESSION[$idKey], 'username' => (string)($_SESSION[$nameKey] ?? ''),
    ]];
}
json_ok([
    'admin' => account_session_state('admin_user_id', 'admin_username'),
    'user' => account_session_state('user_id', 'user_username'),
]);
