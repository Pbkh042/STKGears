<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../core/mysql_session.php';

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $lifetime = max(300, (int)app_env('SESSION_LIFETIME', '7200'));
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)$lifetime);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Every instance shares sessions through the selected database provider.
    if (data_backend() === 'firebase' && database_is_configured()) {
        require_once __DIR__ . '/../core/firebase_session.php';
        session_set_save_handler(new FirebaseSessionHandler($lifetime), true);
    } elseif (APP_ENV === 'production' && database_is_configured()) {
        session_set_save_handler(new MysqlSessionHandler(new_database_connection(), $lifetime), true);
    }

    if (!session_start()) {
        throw new RuntimeException('Cannot start session.');
    }
    if (data_backend() === 'firebase' && database_is_configured()) {
        require_once __DIR__ . '/../services/auth_service.php';
        firebase_validate_session();
    }
}
