<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/firebase.php';

function clear_role_session(): void
{
    foreach (['user_id', 'user_username', 'admin_user_id', 'admin_username', 'firebase_uid', 'firebase_id_token', 'firebase_refresh_token', 'firebase_expires_at', 'firebase_checked_at', 'auth_version'] as $key) unset($_SESSION[$key]);
}

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

function firebase_profile_for_identity(array $identity): array
{
    $uid = (string)$identity['localId'];
    return firebase_store_transaction(static function (array &$store) use ($uid, $identity): array {
        $index = hash('sha256', $uid);
        $existingId = (int)($store['auth_uids'][$index] ?? 0);
        if ($existingId > 0 && isset($store['users']['u_' . $existingId])) {
            return $store['users']['u_' . $existingId];
        }
        $id = firebase_next_id($store, 'users');
        $profile = ['id' => $id, 'firebase_uid' => $uid, 'username' => (string)$identity['email'],
            'email' => (string)$identity['email'], 'role' => 'user', 'auth_version' => 0,
            'created_at' => gmdate('Y-m-d H:i:s')];
        $store['users']['u_' . $id] = $profile;
        $store['auth_uids'][$index] = $id;
        return $profile;
    });
}

function firebase_save_auth_session(array $identity, array $profile): void
{
    $_SESSION['firebase_uid'] = (string)$identity['localId'];
    $_SESSION['firebase_id_token'] = (string)$identity['idToken'];
    $_SESSION['firebase_refresh_token'] = (string)$identity['refreshToken'];
    $_SESSION['firebase_expires_at'] = time() + (int)$identity['expiresIn'];
    $_SESSION['firebase_checked_at'] = time();
    $_SESSION['auth_version'] = (int)($profile['auth_version'] ?? 0);
}

function firebase_login(string $email, string $password, ?string $requiredRole = null): array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '') throw new RuntimeException('Vui lòng nhập email và mật khẩu hợp lệ.');
    $identity = firebase_auth_request('signInWithPassword', ['email' => $email, 'password' => $password, 'returnSecureToken' => true]);
    $profile = firebase_profile_for_identity($identity);
    $role = ($profile['role'] ?? '') === 'admin' ? 'admin' : 'user';
    if ($requiredRole !== null && $role !== $requiredRole) throw new RuntimeException('Tài khoản không có quyền truy cập.');
    if ($role === 'admin') start_admin_session($profile); else start_user_session($profile);
    firebase_save_auth_session($identity, $profile);
    return ['role' => $role, 'user' => ['id' => (int)$profile['id'], 'username' => (string)$profile['username']]];
}

function login_user(string $username, string $password): array
{
    return firebase_login($username, $password, 'user')['user'];
}

function login_admin(string $username, string $password): array
{
    return firebase_login($username, $password, 'admin')['user'];
}

function login_account(string $username, string $password): array
{
    return firebase_login($username, $password);
}

function register_user(string $username, string $password, string $confirmPassword): array
{
    $email = strtolower(trim($username));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) throw new RuntimeException('Email không hợp lệ.');
    if (mb_strlen($password) < 8 || strlen($password) > 4096) throw new RuntimeException('Mật khẩu phải có ít nhất 8 ký tự.');
    if ($password !== $confirmPassword) throw new RuntimeException('Mật khẩu xác nhận không khớp.');
    $identity = firebase_auth_request('signUp', ['email' => $email, 'password' => $password, 'returnSecureToken' => true]);
    try {
        $profile = firebase_profile_for_identity($identity);
    } catch (Throwable $error) {
        try { firebase_auth_request('delete', ['idToken' => $identity['idToken']]); } catch (Throwable) { error_log('Firebase registration needs reconciliation.'); }
        throw $error;
    }
    return ['id' => (int)$profile['id'], 'username' => (string)$profile['username']];
}

function change_user_password(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
{
    if ($userId <= 0 || $currentPassword === '') throw new RuntimeException('Phiên đăng nhập không hợp lệ.');
    if (mb_strlen($newPassword) < 8 || strlen($newPassword) > 4096) throw new RuntimeException('Mật khẩu mới phải có ít nhất 8 ký tự.');
    if ($newPassword !== $confirmPassword) throw new RuntimeException('Mật khẩu xác nhận không khớp.');
    if ($currentPassword === $newPassword) throw new RuntimeException('Mật khẩu mới phải khác mật khẩu hiện tại.');
    $profile = firebase_store_read()['users']['u_' . $userId] ?? null;
    if (!$profile) throw new RuntimeException('Không tìm thấy tài khoản.');
    $identity = firebase_auth_request('signInWithPassword', ['email' => $profile['email'], 'password' => $currentPassword, 'returnSecureToken' => true]);
    if ($identity['localId'] !== $profile['firebase_uid']) throw new RuntimeException('Tài khoản không hợp lệ.');
    $changed = firebase_auth_request('update', ['idToken' => $identity['idToken'], 'password' => $newPassword, 'returnSecureToken' => true]);
    $profile = firebase_store_transaction(static function (array &$store) use ($userId): array {
        $store['users']['u_' . $userId]['auth_version'] = (int)($store['users']['u_' . $userId]['auth_version'] ?? 0) + 1;
        return $store['users']['u_' . $userId];
    });
    $changed['localId'] = $profile['firebase_uid'];
    firebase_save_auth_session($changed, $profile);
    session_regenerate_id(true);
}

function firebase_validate_session(): void
{
    $id = (int)($_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? 0);
    if ($id === 0) return;
    if (empty($_SESSION['firebase_uid']) || empty($_SESSION['firebase_id_token'])) {
        clear_role_session();
        session_regenerate_id(true);
        return;
    }
    $profile = firebase_database()->request(firebase_namespace() . '/users/u_' . $id)['data'];
    $role = !empty($_SESSION['admin_user_id']) ? 'admin' : 'user';
    if (!$profile || ($profile['firebase_uid'] ?? '') !== $_SESSION['firebase_uid']
        || ($profile['role'] ?? '') !== $role || (int)($profile['auth_version'] ?? 0) !== (int)($_SESSION['auth_version'] ?? -1)) {
        clear_role_session();
        session_regenerate_id(true);
        return;
    }
    try {
        if ((int)($_SESSION['firebase_expires_at'] ?? 0) < time() + 60) {
            $fresh = firebase_refresh_auth((string)($_SESSION['firebase_refresh_token'] ?? ''));
            if ($fresh['user_id'] !== $_SESSION['firebase_uid']) throw new RuntimeException('Invalid identity.');
            $_SESSION['firebase_id_token'] = $fresh['id_token'];
            $_SESSION['firebase_refresh_token'] = $fresh['refresh_token'];
            $_SESSION['firebase_expires_at'] = time() + (int)$fresh['expires_in'];
        }
        if ((int)($_SESSION['firebase_checked_at'] ?? 0) < time() - 60) {
            $lookup = firebase_auth_request('lookup', ['idToken' => $_SESSION['firebase_id_token']]);
            if (($lookup['users'][0]['localId'] ?? '') !== $_SESSION['firebase_uid'] || !empty($lookup['users'][0]['disabled'])) throw new RuntimeException('Invalid identity.');
            $_SESSION['firebase_checked_at'] = time();
        }
    } catch (RuntimeException) {
        clear_role_session();
        session_regenerate_id(true);
    }
}
