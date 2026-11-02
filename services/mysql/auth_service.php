<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../config/database.php';

function clear_role_session(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_username'],
        $_SESSION['admin_user_id'],
        $_SESSION['admin_username']
    );
}
// Hàm xóa quyền cũ để một phiên chỉ thuộc về một loại tài khoản.

function start_user_session(array $user): void
{
    session_regenerate_id(true);
    clear_role_session();
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_username'] = (string)$user['username'];
}

function start_admin_session(array $user): void
{
    session_regenerate_id(true);
    clear_role_session();
    $_SESSION['admin_user_id'] = (int)$user['id'];
    $_SESSION['admin_username'] = (string)$user['username'];
}

function login_user(string $username, string $password): array
{
    if ($username === '' || $password === '' || mb_strlen($username) > 80) {
        throw new RuntimeException('Username and password are required.');
    }

    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        throw new RuntimeException('Invalid username or password.');
    }

    start_user_session($user);

    return ['id' => (int)$user['id'], 'username' => (string)$user['username']];
}
// Hàm đăng nhập user thường, kiểm tra mật khẩu và khởi tạo session an toàn.

function login_admin(string $username, string $password): array
{
    if ($username === '' || $password === '' || mb_strlen($username) > 80) {
        throw new RuntimeException('Username and password are required.');
    }

    $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        throw new RuntimeException('Invalid username or password.');
    }

    start_admin_session($user);

    return ['id' => (int)$user['id'], 'username' => (string)$user['username']];
}
// Hàm đăng nhập admin, chỉ cho phép tài khoản trong bảng quản trị.

function login_account(string $username, string $password): array
{
    if ($username === '' || $password === '' || mb_strlen($username) > 80) {
        throw new RuntimeException('Tên đăng nhập và mật khẩu là bắt buộc.');
    }

    $pdo = db();
    $adminStmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1');
    $adminStmt->execute(['username' => $username]);
    $admin = $adminStmt->fetch();

    if ($admin) {
        if (!password_verify($password, (string)$admin['password_hash'])) {
            throw new RuntimeException('Tên đăng nhập hoặc mật khẩu không đúng.');
        }
        start_admin_session($admin);
        return ['role' => 'admin', 'user' => ['id' => (int)$admin['id'], 'username' => (string)$admin['username']]];
    }

    $userStmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1');
    $userStmt->execute(['username' => $username]);
    $user = $userStmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        throw new RuntimeException('Tên đăng nhập hoặc mật khẩu không đúng.');
    }

    start_user_session($user);
    return ['role' => 'user', 'user' => ['id' => (int)$user['id'], 'username' => (string)$user['username']]];
}
// Hàm đăng nhập chung: tài khoản admin được ưu tiên và xác định quyền trên máy chủ.

function register_user(string $username, string $password, string $confirmPassword): array
{
    if ($username === '' || $password === '' || $confirmPassword === '') {
        throw new RuntimeException('Vui lòng nhập đầy đủ thông tin đăng ký.');
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        throw new RuntimeException('Tên đăng nhập phải từ 3 đến 50 ký tự.');
    }
    if (!preg_match('/^[A-Za-z0-9_.]+$/', $username)) {
        throw new RuntimeException('Tên đăng nhập chỉ gồm chữ, số, dấu chấm hoặc gạch dưới.');
    }
    if (mb_strlen($password) < 8) {
        throw new RuntimeException('Mật khẩu phải có ít nhất 8 ký tự.');
    }
    if ($password !== $confirmPassword) {
        throw new RuntimeException('Mật khẩu xác nhận không khớp.');
    }

    $pdo = db();
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $check->execute(['username' => $username]);
    if ($check->fetch()) {
        throw new RuntimeException('Tên đăng nhập đã tồn tại.');
    }

    $checkAdmin = $pdo->prepare('SELECT id FROM admin_users WHERE username = :username LIMIT 1');
    $checkAdmin->execute(['username' => $username]);
    if ($checkAdmin->fetch()) {
        throw new RuntimeException('Tên đăng nhập đã tồn tại.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => $passwordHash,
    ]);

    return ['id' => (int)$pdo->lastInsertId(), 'username' => $username];
}
// Hàm đăng ký user mới với validate dữ liệu và chống trùng username.

function change_user_password(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
{
    if ($userId <= 0) {
        throw new RuntimeException('Phiên đăng nhập không hợp lệ.');
    }
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        throw new RuntimeException('Vui lòng nhập đầy đủ thông tin.');
    }
    if (mb_strlen($newPassword) < 8) {
        throw new RuntimeException('Mật khẩu mới phải có ít nhất 8 ký tự.');
    }
    if ($newPassword !== $confirmPassword) {
        throw new RuntimeException('Mật khẩu xác nhận không khớp.');
    }
    if ($currentPassword === $newPassword) {
        throw new RuntimeException('Mật khẩu mới phải khác mật khẩu hiện tại.');
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($currentPassword, (string)$row['password_hash'])) {
        throw new RuntimeException('Mật khẩu hiện tại không đúng.');
    }

    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $update->execute([
        'id' => $userId,
        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
    ]);
}
// Hàm đổi mật khẩu user với xác thực mật khẩu cũ và ràng buộc bảo mật cơ bản.



