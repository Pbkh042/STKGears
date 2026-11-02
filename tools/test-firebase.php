<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
putenv('DATA_BACKEND=firebase');
$fixture = 'stkgears_test_' . bin2hex(random_bytes(8));
putenv('FIREBASE_DATA_NAMESPACE=' . $fixture);
require_once __DIR__ . '/../services/product_service.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../core/firebase_session.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new Exception($message);
}
function rejected(callable $action): void {
    try { $action(); } catch (RuntimeException) { return; }
    throw new Exception('Expected business validation failure.');
}

$identity = null;
$identityToken = null;
$success = false;
$password = bin2hex(random_bytes(16));
$email = 'qa-' . bin2hex(random_bytes(8)) . '@example.com';
// No output until PHP session operations have finished.
session_start();
try {
    $rows = json_decode((string)file_get_contents(__DIR__ . '/../firebase/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $store = ['counters' => ['products' => max(array_column($rows, 'id'))]];
    foreach ($rows as $row) $store['products']['p_' . $row['id']] = $row;
    firebase_database()->request($fixture, 'PUT', $store);
    check(count_products_public() === 13, 'Catalog count');
    check(count(list_products_public('', 'all', 'price-asc', 3, 0)) === 3, 'Pagination');
    $products = list_products_public('', 'all', 'price-asc', 13);
    check($products[0]['price'] <= $products[12]['price'], 'Price sorting');
    check(count_products_public($products[0]['sku']) === 1, 'SKU search');

    $user = register_user($email, $password, $password);
    $identity = firebase_auth_request('signInWithPassword', ['email' => $email, 'password' => $password, 'returnSecureToken' => true]);
    $identityToken = $identity['idToken'];
    rejected(fn() => register_user($email, $password, $password));
    rejected(fn() => login_account($email, 'incorrect-password'));
    $oldId = session_id();
    $login = login_account($email, $password);
    check($login['role'] === 'user' && session_id() !== $oldId, 'Login/session rotation');
    firebase_validate_session();
    check((int)$_SESSION['user_id'] === $user['id'], 'Authenticated session');
    rejected(fn() => login_admin($email, $password));

    $id = $products[0]['id'];
    $stock = $products[0]['stock'];
    check($stock >= 2, 'Fixture requires stock');
    $customer = ['name' => 'QA temporary fixture', 'phone' => '0900000000', 'address' => 'QA test address'];
    rejected(fn() => create_order_from_cart($user['id'], $customer, [
        ['id' => $id, 'quantity' => $stock, 'color' => 'A'],
        ['id' => $id, 'quantity' => 1, 'color' => 'B'],
    ]));
    check(find_product_by_id($id)['stock'] === $stock, 'Failed order leaves stock unchanged');
    $order = create_order_from_cart($user['id'], $customer, [['id' => $id, 'quantity' => 2]]);
    check(find_product_by_id($id)['stock'] === $stock - 2, 'Order decrements stock');
    check(get_order_user_detail($user['id'] + 1, $order) === null, 'Order ownership');
    rejected(fn() => cancel_order_user($user['id'] + 1, $order));
    cancel_order_user($user['id'], $order);
    rejected(fn() => cancel_order_user($user['id'], $order));
    check(find_product_by_id($id)['stock'] === $stock, 'Cancellation restores stock once');
    rejected(fn() => update_order_status_admin($order, ORDER_STATUS_OPTIONS[1]));

    $version = firebase_database()->request($fixture . '/etag_check', 'GET', null, ['X-Firebase-ETag: true']);
    firebase_database()->request($fixture . '/etag_check', 'PUT', ['v' => 1]);
    $stale = firebase_database()->request($fixture . '/etag_check', 'PUT', ['v' => 2], ['If-Match: ' . $version['headers']['etag']]);
    check($stale['status'] === 412, 'Stale transaction rejected');

    $sid = bin2hex(random_bytes(16));
    $first = new FirebaseSessionHandler(300);
    check($first->read($sid) === '', 'New shared session');
    $first->write($sid, 'user_id|i:123;');
    $second = new FirebaseSessionHandler(300);
    check($second->validateId($sid) && $second->read($sid) === 'user_id|i:123;', 'Cross-instance shared session');
    $second->destroy($sid);
    check(!$first->validateId($sid), 'Logout removes shared session');
    $expired = bin2hex(random_bytes(16));
    firebase_database()->request($fixture . '_sessions/' . hash('sha256', $expired), 'PUT', ['data' => base64_encode('old'), 'expires_at' => time() - 1]);
    check(!$first->validateId($expired), 'Expired session rejected');
    check($first->gc(300) >= 1, 'Expired session cleanup');

    $newPassword = bin2hex(random_bytes(16));
    $oldSession = $_SESSION;
    change_user_password($user['id'], $password, $newPassword, $newPassword);
    $identityToken = $_SESSION['firebase_id_token'];
    $_SESSION = $oldSession;
    firebase_validate_session();
    check(empty($_SESSION['user_id']), 'Password change invalidates old sessions');
    rejected(fn() => login_account($email, $password));
    check(login_account($email, $newPassword)['role'] === 'user', 'New password authenticates');
    $identityToken = $_SESSION['firebase_id_token'];
    $success = true;
} finally {
    session_write_close();
    if ($identityToken !== null) firebase_auth_request('delete', ['idToken' => $identityToken]);
    firebase_database()->request($fixture, 'DELETE');
    firebase_database()->request($fixture . '_sessions', 'DELETE');
}
if ($success) echo "PASS: Firebase catalog, Auth, session rotation/persistence/expiry, password change, stock, ownership, cancellation and ETag conflicts. Fixtures removed.\n";
