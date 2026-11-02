<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../core/firebase.php';
if (data_backend() !== 'firebase') {
    fwrite(STDERR, "Set the Firebase server environment before running this migration.\n");
    exit(1);
}

function renamed_product_sku(string $sku): string
{
    return strncasecmp($sku, 'DNT', 3) === 0 ? 'STK' . substr($sku, 3) : $sku;
}

$rename = static function (array &$store): array {
    $counts = ['products' => 0, 'orderItems' => 0];
    $seen = [];
    foreach ($store['products'] ?? [] as $product) {
        $next = renamed_product_sku((string)$product['sku']);
        $uniqueKey = strtoupper($next);
        if (isset($seen[$uniqueKey])) throw new RuntimeException('SKU collision: ' . $next . '. Nothing was changed.');
        $seen[$uniqueKey] = true;
    }
    foreach ($store['products'] ?? [] as $key => $product) {
        $next = renamed_product_sku((string)$product['sku']);
        if ($next !== $product['sku']) {
            $store['products'][$key]['sku'] = $next;
            $counts['products']++;
        }
    }
    foreach ($store['orders'] ?? [] as $orderKey => $order) {
        foreach ($order['items'] ?? [] as $itemKey => $item) {
            $old = (string)($item['product_sku'] ?? '');
            $next = renamed_product_sku($old);
            if ($next !== $old) {
                $store['orders'][$orderKey]['items'][$itemKey]['product_sku'] = $next;
                $counts['orderItems']++;
            }
        }
    }
    return $counts;
};
try {
    $apply = in_array('--apply', $argv, true);
    if ($apply) $counts = firebase_store_transaction($rename);
    else { $store = firebase_store_read(); $counts = $rename($store); }
    echo json_encode(['applied' => $apply] + $counts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, $error instanceof RuntimeException ? $error->getMessage() . PHP_EOL : "Firebase SKU migration failed.\n");
    exit(1);
}
