<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

// Local defaults remain usable with XAMPP. Production credentials belong in
// the hosting provider's environment variables, never in the repository.
function app_env(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

define('APP_ENV', app_env('APP_ENV', 'local'));
define('DB_HOST', app_env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)app_env('DB_PORT', '3306'));
define('DB_NAME', app_env('DB_NAME', 'stkgear'));
define('DB_USER', app_env('DB_USER', 'root'));
define('DB_PASS', app_env('DB_PASS'));

function database_is_configured(): bool
{
    if (APP_ENV !== 'production') {
        return true;
    }

    return app_env('DB_HOST') !== ''
        && app_env('DB_NAME') !== ''
        && app_env('DB_USER') !== '';
}



