<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/app.php';

function new_database_connection(): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ];

    $caPath = app_env('DB_SSL_CA');
    $caPem = app_env('DB_SSL_CA_PEM');
    if ($caPem !== '') {
        if (openssl_x509_read($caPem) === false) {
            throw new RuntimeException('Invalid database CA certificate.');
        }
        $caPath = sys_get_temp_dir() . '/stkgears-ca-' . hash('sha256', $caPem) . '.pem';
        if (!is_file($caPath) && file_put_contents($caPath, $caPem, LOCK_EX) === false) {
            throw new RuntimeException('Cannot prepare database CA certificate.');
        }
    }

    if ($caPath !== '') {
        if (!is_readable($caPath)) {
            throw new RuntimeException('Database CA certificate is not readable.');
        }
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    } elseif (APP_ENV === 'production') {
        throw new RuntimeException('A verified TLS connection is required for the production database.');
    }

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

function db(): PDO
{
    static $pdo = null;
    if (!$pdo instanceof PDO) {
        $pdo = new_database_connection();
    }
    return $pdo;
}
// Hàm tạo/kế thừa kết nối PDO duy nhất cho toàn ứng dụng.



