<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

const PRODUCT_CATEGORIES = [
    'keyboard_mech',
    'keyboard_hall',
    'mouse_wireless',
    'mouse_wired',
    'pad_mouse',
    'accessory_switch',
    'accessory_keycap',
];

function make_slug(string $name): string
{
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    $converted = is_string($converted) ? $converted : $name;
    $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $converted));
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'product';
}
// Hàm tạo slug thân thiện URL từ tên sản phẩm.

function category_label(string $category): string
{
    return match ($category) {
        'keyboard_mech' => 'Bàn phím cơ',
        'keyboard_hall' => 'Bàn phím Hall Effect',
        'mouse_wireless' => 'Chuột không dây',
        'mouse_wired' => 'Chuột có dây',
        'pad_mouse' => 'Pad chuột',
        'accessory_switch' => 'Switch',
        'accessory_keycap' => 'Keycap',
        default => $category,
    };
}
// Hàm đổi mã danh mục sang nhãn tiếng Việt để hiển thị UI.

function validate_image_list(string $galleryRaw): array
{
    $galleryRaw = trim($galleryRaw);
    if ($galleryRaw === '') {
        return [];
    }

    $parts = preg_split('/[\r\n,;]+/u', $galleryRaw) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts)));
    if (count($parts) > 8) {
        throw new RuntimeException('Tối đa 8 ảnh phụ cho một sản phẩm.');
    }
    foreach ($parts as $url) {
        if (mb_strlen($url) > 1500 || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Danh sách ảnh phụ chứa URL không hợp lệ.');
        }
    }

    return $parts;
}
// Hàm chuẩn hóa danh sách ảnh phụ từ chuỗi URL, ngăn dữ liệu lỗi.

function validate_product_input(array $input): array
{
    $name = trim((string)($input['name'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $brand = trim((string)($input['brand'] ?? 'STK Gears'));
    $description = trim((string)($input['description'] ?? ''));
    $technicalSpecs = trim((string)($input['technical_specs'] ?? $input['technicalSpecs'] ?? ''));

    $price = isset($input['price']) ? (int)$input['price'] : 0;
    $oldPriceRaw = trim((string)($input['old_price'] ?? $input['oldPrice'] ?? ''));
    $oldPrice = $oldPriceRaw !== '' ? (int)$oldPriceRaw : null;
    $stock = isset($input['stock']) ? (int)$input['stock'] : -1;

    $image = trim((string)($input['image_url'] ?? $input['image'] ?? ''));
    $galleryRaw = trim((string)($input['gallery'] ?? $input['gallery_urls'] ?? ''));
    $colors = trim((string)($input['colors'] ?? $input['color_options'] ?? ''));

    if ($name === '' || mb_strlen($name) > 180) {
        throw new RuntimeException('Tên sản phẩm không hợp lệ.');
    }
    if (!in_array($category, PRODUCT_CATEGORIES, true)) {
        throw new RuntimeException('Danh mục không hợp lệ.');
    }
    if ($brand === '' || mb_strlen($brand) > 80) {
        throw new RuntimeException('Thương hiệu không hợp lệ.');
    }
    if ($price <= 0) {
        throw new RuntimeException('Giá bán phải lớn hơn 0.');
    }
    if ($stock < 0) {
        throw new RuntimeException('Tồn kho không được âm.');
    }
    if ($image === '' || mb_strlen($image) > 1500 || !filter_var($image, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Link ảnh chính không hợp lệ.');
    }
    if (mb_strlen($description) > 8000 || mb_strlen($technicalSpecs) > 8000) {
        throw new RuntimeException('Mô tả hoặc thông số kỹ thuật quá dài.');
    }

    if ($oldPrice !== null && $oldPrice <= $price) {
        $oldPrice = null;
    }

    $galleryList = validate_image_list($galleryRaw);

    return [
        'name' => $name,
        'category' => $category,
        'brand' => $brand,
        'description' => $description,
        'technical_specs' => $technicalSpecs,
        'price' => $price,
        'old_price' => $oldPrice,
        'stock' => $stock,
        'image_url' => $image,
        'gallery_json' => count($galleryList) > 0 ? json_encode($galleryList, JSON_UNESCAPED_UNICODE) : null,
        'color_options' => $colors !== '' ? $colors : null,
    ];
}
// Hàm kiểm tra và chuẩn hóa dữ liệu sản phẩm trước khi ghi DB.



