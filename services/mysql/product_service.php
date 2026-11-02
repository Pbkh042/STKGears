<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/product.php';

function hydrate_product_row(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'sku' => (string)$row['sku'],
        'slug' => (string)$row['slug'],
        'name' => (string)$row['name'],
        'category' => (string)$row['category'],
        'brand' => (string)$row['brand'],
        'description' => (string)($row['description'] ?? ''),
        'technicalSpecs' => (string)($row['technical_specs'] ?? ''),
        'price' => (int)$row['price'],
        'oldPrice' => $row['old_price'] !== null ? (int)$row['old_price'] : null,
        'stock' => (int)$row['stock'],
        'image' => (string)$row['image_url'],
        'gallery' => $row['gallery_json'] ? (json_decode((string)$row['gallery_json'], true) ?: []) : [],
        'colors' => (string)($row['color_options'] ?? ''),
    ];
}
// Hàm map dữ liệu DB thành cấu trúc chuẩn để frontend/backend dùng chung.

function count_products_public(string $search = '', string $category = 'all'): int
{
    $sql = 'SELECT COUNT(*) FROM products WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (name LIKE :search_name OR sku LIKE :search_sku OR brand LIKE :search_brand)';
        $like = '%' . $search . '%';
        $params['search_name'] = $like;
        $params['search_sku'] = $like;
        $params['search_brand'] = $like;
    }
    if ($category !== 'all' && $category !== '') {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
// Hàm đếm tổng sản phẩm cho trang public theo filter tìm kiếm/danh mục.

function list_products_public(string $search = '', string $category = 'all', string $sort = 'popular', int $limit = 10, int $offset = 0): array
{
    $sortSql = match ($sort) {
        'price-asc' => 'price ASC, id DESC',
        'price-desc' => 'price DESC, id DESC',
        default => 'created_at DESC, id DESC',
    };

    $sql = 'SELECT id, sku, slug, name, category, brand, description, technical_specs, price, old_price, stock, image_url, gallery_json, color_options
            FROM products WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (name LIKE :search_name OR sku LIKE :search_sku OR brand LIKE :search_brand)';
        $like = '%' . $search . '%';
        $params['search_name'] = $like;
        $params['search_sku'] = $like;
        $params['search_brand'] = $like;
    }
    if ($category !== 'all' && $category !== '') {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }

    $sql .= " ORDER BY {$sortSql} LIMIT :limit OFFSET :offset";
    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return array_map('hydrate_product_row', $stmt->fetchAll());
}
// Hàm lấy danh sách sản phẩm public có phân trang/lọc/sắp xếp.

function count_products_admin(string $search = ''): int
{
    $sql = 'SELECT COUNT(*) FROM products';
    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE (name LIKE :search_name OR sku LIKE :search_sku OR brand LIKE :search_brand OR category LIKE :search_category)';
        $like = '%' . $search . '%';
        $params['search_name'] = $like;
        $params['search_sku'] = $like;
        $params['search_brand'] = $like;
        $params['search_category'] = $like;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
// Hàm đếm tổng sản phẩm cho trang quản trị.

function list_products_admin(string $search = '', int $limit = 10, int $offset = 0): array
{
    $sql = 'SELECT id, sku, slug, name, category, brand, description, technical_specs, price, old_price, stock, image_url, gallery_json, color_options
            FROM products';
    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE (name LIKE :search_name OR sku LIKE :search_sku OR brand LIKE :search_brand OR category LIKE :search_category)';
        $like = '%' . $search . '%';
        $params['search_name'] = $like;
        $params['search_sku'] = $like;
        $params['search_brand'] = $like;
        $params['search_category'] = $like;
    }

    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset';
    $stmt = db()->prepare($sql);

    if (isset($params['search_name'])) {
        $stmt->bindValue(':search_name', $params['search_name'], PDO::PARAM_STR);
        $stmt->bindValue(':search_sku', $params['search_sku'], PDO::PARAM_STR);
        $stmt->bindValue(':search_brand', $params['search_brand'], PDO::PARAM_STR);
        $stmt->bindValue(':search_category', $params['search_category'], PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return array_map('hydrate_product_row', $stmt->fetchAll());
}
// Hàm lấy danh sách sản phẩm cho admin, hỗ trợ search toàn DB và phân trang.

function find_product_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, sku, slug, name, category, brand, description, technical_specs, price, old_price, stock, image_url, gallery_json, color_options
         FROM products WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ? hydrate_product_row($row) : null;
}
// Hàm lấy chi tiết một sản phẩm theo ID.

function build_next_sku(PDO $pdo): string
{
    $latest = $pdo->query('SELECT id FROM products ORDER BY id DESC LIMIT 1')->fetchColumn();
    $next = ((int)$latest) + 1;
    return 'STK' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}
// Hàm sinh SKU theo thứ tự tăng dần để admin dễ quản lý sản phẩm.

function create_product(array $input, int $createdBy): int
{
    $data = validate_product_input($input);
    $pdo = db();

    $baseSlug = make_slug($data['name']);
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $check = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');
        $check->execute(['slug' => $slug]);
        if (!$check->fetch()) {
            break;
        }
        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }

    $sku = build_next_sku($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO products
        (sku, slug, name, category, brand, description, technical_specs, price, old_price, stock, image_url, gallery_json, color_options, created_by)
        VALUES
        (:sku, :slug, :name, :category, :brand, :description, :technical_specs, :price, :old_price, :stock, :image_url, :gallery_json, :color_options, :created_by)'
    );

    $stmt->execute([
        'sku' => $sku,
        'slug' => $slug,
        'name' => $data['name'],
        'category' => $data['category'],
        'brand' => $data['brand'],
        'description' => $data['description'],
        'technical_specs' => $data['technical_specs'],
        'price' => $data['price'],
        'old_price' => $data['old_price'],
        'stock' => $data['stock'],
        'image_url' => $data['image_url'],
        'gallery_json' => $data['gallery_json'],
        'color_options' => $data['color_options'],
        'created_by' => $createdBy,
    ]);

    return (int)$pdo->lastInsertId();
}
// Hàm tạo sản phẩm mới và tự động sinh SKU + slug không trùng.

function update_product(int $id, array $input): void
{
    $data = validate_product_input($input);
    $stmt = db()->prepare(
        'UPDATE products
         SET name = :name,
             category = :category,
             brand = :brand,
             description = :description,
             technical_specs = :technical_specs,
             price = :price,
             old_price = :old_price,
             stock = :stock,
             image_url = :image_url,
             gallery_json = :gallery_json,
             color_options = :color_options
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $id,
        'name' => $data['name'],
        'category' => $data['category'],
        'brand' => $data['brand'],
        'description' => $data['description'],
        'technical_specs' => $data['technical_specs'],
        'price' => $data['price'],
        'old_price' => $data['old_price'],
        'stock' => $data['stock'],
        'image_url' => $data['image_url'],
        'gallery_json' => $data['gallery_json'],
        'color_options' => $data['color_options'],
    ]);
}
// Hàm cập nhật sản phẩm với đầy đủ trường mô tả/thông số/ảnh/màu.

function delete_product(int $id): void
{
    $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
// Hàm xóa sản phẩm khỏi hệ thống.

function list_related_products(string $category, int $id): array
{
    $stmt = db()->prepare(
        'SELECT id, sku, slug, name, category, brand, description, technical_specs, price, old_price, stock, image_url, gallery_json, color_options
         FROM products
         WHERE category = :category AND id <> :id
         ORDER BY id DESC
         LIMIT 4'
    );
    $stmt->execute(['category' => $category, 'id' => $id]);
    return array_map('hydrate_product_row', $stmt->fetchAll());
}
// Hàm lấy các sản phẩm liên quan cùng danh mục.



