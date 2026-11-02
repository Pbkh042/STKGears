<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/firebase.php';
require_once __DIR__ . '/../../core/product.php';

function hydrate_product_row(array $row): array
{
    return [
        'id' => (int)$row['id'], 'sku' => (string)$row['sku'], 'slug' => (string)$row['slug'],
        'name' => (string)$row['name'], 'category' => (string)$row['category'], 'brand' => (string)$row['brand'],
        'description' => (string)($row['description'] ?? ''), 'technicalSpecs' => (string)($row['technical_specs'] ?? ''),
        'price' => (int)$row['price'], 'oldPrice' => isset($row['old_price']) ? (int)$row['old_price'] : null,
        'stock' => (int)$row['stock'], 'image' => (string)$row['image_url'],
        'gallery' => empty($row['gallery_json']) ? [] : (json_decode((string)$row['gallery_json'], true) ?: []),
        'colors' => (string)($row['color_options'] ?? ''),
    ];
}

function firebase_product_rows(string $search = '', string $category = 'all', bool $admin = false): array
{
    $rows = array_values(firebase_store_read()['products'] ?? []);
    $needle = firebase_normalize_search(trim($search));
    return array_values(array_filter($rows, static function (array $row) use ($needle, $category, $admin): bool {
        if (!$admin && $category !== '' && $category !== 'all' && $row['category'] !== $category) return false;
        $fields = [$row['name'], $row['sku'], $row['brand']];
        if ($admin) $fields[] = $row['category'];
        return $needle === '' || str_contains(firebase_normalize_search(implode(' ', $fields)), $needle);
    }));
}

function count_products_public(string $search = '', string $category = 'all'): int
{
    return count(firebase_product_rows($search, $category));
}

function list_products_public(string $search = '', string $category = 'all', string $sort = 'popular', int $limit = 10, int $offset = 0): array
{
    $rows = firebase_product_rows($search, $category);
    usort($rows, static function (array $a, array $b) use ($sort): int {
        $comparison = match ($sort) {
            'price-asc' => $a['price'] <=> $b['price'],
            'price-desc' => $b['price'] <=> $a['price'],
            default => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')),
        };
        return $comparison !== 0 ? $comparison : ($b['id'] <=> $a['id']);
    });
    return array_map('hydrate_product_row', array_slice($rows, max(0, $offset), max(1, $limit)));
}

function count_products_admin(string $search = ''): int
{
    return count(firebase_product_rows($search, 'all', true));
}

function list_products_admin(string $search = '', int $limit = 10, int $offset = 0): array
{
    $rows = firebase_product_rows($search, 'all', true);
    usort($rows, static fn(array $a, array $b): int => $b['id'] <=> $a['id']);
    return array_map('hydrate_product_row', array_slice($rows, max(0, $offset), max(1, $limit)));
}

function find_product_by_id(int $id): ?array
{
    $row = firebase_store_read()['products']['p_' . $id] ?? null;
    return $row === null ? null : hydrate_product_row($row);
}

function create_product(array $input, int $createdBy): int
{
    $data = validate_product_input($input);
    return firebase_store_transaction(static function (array &$store) use ($data, $createdBy): int {
        if (($store['users']['u_' . $createdBy]['role'] ?? '') !== 'admin') throw new RuntimeException('Unauthorized.');
        $id = firebase_next_id($store, 'products');
        $slug = make_slug($data['name']);
        $base = $slug;
        $counter = 1;
        $slugs = array_column(array_values($store['products'] ?? []), 'slug');
        while (in_array($slug, $slugs, true)) $slug = $base . '-' . ++$counter;
        $store['products']['p_' . $id] = $data + [
            'id' => $id, 'sku' => 'STK' . str_pad((string)$id, 4, '0', STR_PAD_LEFT), 'slug' => $slug,
            'created_by' => $createdBy, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s'),
        ];
        return $id;
    });
}

function update_product(int $id, array $input): void
{
    $data = validate_product_input($input);
    firebase_store_transaction(static function (array &$store) use ($id, $data): void {
        if (!isset($store['products']['p_' . $id])) throw new RuntimeException('Không tìm thấy sản phẩm.');
        $store['products']['p_' . $id] = array_replace($store['products']['p_' . $id], $data, ['updated_at' => gmdate('Y-m-d H:i:s')]);
    });
}

function delete_product(int $id): void
{
    firebase_store_transaction(static function (array &$store) use ($id): void {
        foreach ($store['orders'] ?? [] as $order) {
            foreach ($order['items'] ?? [] as $item) {
                if ((int)$item['product_id'] === $id) throw new RuntimeException('Sản phẩm đã thuộc đơn hàng và không thể xóa.');
            }
        }
        unset($store['products']['p_' . $id]);
    });
}

function list_related_products(string $category, int $id): array
{
    $rows = array_values(array_filter(firebase_product_rows('', $category), static fn(array $row): bool => (int)$row['id'] !== $id));
    usort($rows, static fn(array $a, array $b): int => $b['id'] <=> $a['id']);
    return array_map('hydrate_product_row', array_slice($rows, 0, 4));
}
