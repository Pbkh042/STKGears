<?php
declare(strict_types=1);

// Run only against a disposable, loopback-only MySQL instance. No production
// credentials, migrations or user accounts are used by this integration test.
if (PHP_SAPI !== 'cli' || getenv('DB_HOST') !== '127.0.0.1'
    || !preg_match('/^stkgears_test_[a-f0-9]+$/', getenv('DB_NAME') ?: '')) {
    throw new RuntimeException('An isolated local test database is required.');
}

require_once __DIR__ . '/cloud-schema.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/mysql_session.php';
require_once __DIR__ . '/../services/auth_service.php';

function verify_storage(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$setup = new PDO('mysql:host=127.0.0.1;port=' . DB_PORT, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo = db();
foreach (cloud_schema_statements() as $statement) {
    $pdo->exec($statement);
}
$products = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
verify_storage($products > 0, 'product catalog imported');
foreach (['users', 'admin_users', 'orders', 'order_items'] as $table) {
    verify_storage((int)$pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === 0, 'no legacy data in ' . $table);
}
verify_storage((int)$pdo->query('SELECT COUNT(*) FROM products WHERE created_by IS NOT NULL')->fetchColumn() === 0, 'no legacy administrator references');

ini_set('session.use_cookies', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_probability', '0');
function new_test_session(?string $id = null): void
{
    session_set_save_handler(new MysqlSessionHandler(new_database_connection(), 300), true);
    session_id($id ?? '');
    verify_storage(session_start(), 'session starts');
}

register_user('qa_cloud_user', 'qa-storage-test-123', 'qa-storage-test-123');
new_test_session();
$guestId = session_id();
$result = login_account('qa_cloud_user', 'qa-storage-test-123');
$loginId = session_id();
verify_storage($loginId !== $guestId && $result['role'] === 'user', 'login rotates the session ID and selects the correct role');
session_write_close();

new_test_session($loginId);
verify_storage(session_id() === $loginId && ($_SESSION['user_username'] ?? '') === 'qa_cloud_user', 'login survives a fresh connection and session handler');
verify_storage(!isset($_SESSION['admin_user_id']), 'user session has no administrator role');
session_destroy();

new_test_session($loginId);
verify_storage(session_id() !== $loginId && empty($_SESSION), 'logout rejects reuse of the old session ID');
$_SESSION['marker'] = 'expires';
$expiresId = session_id();
session_write_close();
$expire = $pdo->prepare('UPDATE app_sessions SET expires_at = ? WHERE id_hash = ?');
$expire->execute([time() - 1, hash('sha256', $expiresId)]);
new_test_session($expiresId);
verify_storage(session_id() !== $expiresId && empty($_SESSION), 'expired sessions cannot be restored');
session_write_close();

// Both the row state and locks live in MySQL, rather than container files.
$handler = new MysqlSessionHandler(new_database_connection(), 300);
$handler->read('qa-serialized-session');
$lock = 'stk:' . substr(hash('sha256', 'qa-serialized-session'), 0, 60);
$probe = new_database_connection()->prepare('SELECT GET_LOCK(?, 0)');
$probe->execute([$lock]);
verify_storage((int)$probe->fetchColumn() === 0, 'simultaneous connection cannot acquire the same session lock');
$handler->close();
$free = $pdo->prepare('SELECT IS_FREE_LOCK(?)');
$free->execute([$lock]);
verify_storage((int)$free->fetchColumn() === 1, 'session lock is released');
$handler->gc(300);
verify_storage(!$handler->validateId($expiresId), 'expired sessions are removed');

echo 'PASS: imported ' . $products . ' products without legacy accounts/orders; registration, login, ID rotation, cross-connection persistence, logout, expiry and concurrency locking.' . PHP_EOL;
