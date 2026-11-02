<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../core/firebase.php';

try {
    $catalog = json_decode((string)file_get_contents(__DIR__ . '/../firebase/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $count = firebase_store_transaction(static function (array &$store) use ($catalog): int {
        if (!empty($store['products'])) throw new RuntimeException('A catalog already exists. Nothing was overwritten.');
        $maximum = 0;
        foreach ($catalog as $row) {
            $store['products']['p_' . $row['id']] = $row;
            $maximum = max($maximum, (int)$row['id']);
        }
        $store['counters']['products'] = $maximum;
        $store['schema_version'] = 1;
        $store['catalog_seeded_at'] = gmdate('c');
        return count($catalog);
    });
    echo 'Imported ' . $count . ' products into Firebase. No legacy accounts or orders were copied.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, $error instanceof RuntimeException ? $error->getMessage() . PHP_EOL : 'Firebase import failed. Verify the server credentials and database access.' . PHP_EOL);
    exit(1);
}
