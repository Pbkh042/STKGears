<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/cloud-schema.php';
require_once __DIR__ . '/../config/database.php';

$step = 0;
try {
    $statements = cloud_schema_statements();
    if (in_array('--check', $argv, true)) {
        echo json_encode(['valid' => true, 'statements' => count($statements), 'imports' => 'schema, products, session table; no legacy accounts or orders']) . PHP_EOL;
        exit;
    }
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $key) {
        if (app_env($key) === '') {
            throw new RuntimeException('Missing required environment variable: ' . $key);
        }
    }
    if (app_env('DB_SSL_CA') === '' && app_env('DB_SSL_CA_PEM') === '') {
        throw new RuntimeException('Configure DB_SSL_CA or DB_SSL_CA_PEM before importing.');
    }

    $pdo = new_database_connection();
    if ($pdo->query('SHOW TABLES')->fetchColumn() !== false) {
        throw new RuntimeException('Target database is not empty. Import stopped without changing tables.');
    }
    foreach ($statements as $index => $statement) {
        $step = $index + 1;
        $pdo->exec($statement);
    }
    $products = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    echo 'Import complete: ' . $products . ' products; shared sessions ready; no legacy users or orders copied.' . PHP_EOL;
} catch (PDOException $error) {
    fwrite(STDERR, 'Database operation failed at step ' . $step . ' (SQLSTATE ' . $error->getCode() . '). Check connectivity/schema before retrying; MySQL DDL is not rolled back.' . PHP_EOL);
    exit(1);
} catch (RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
