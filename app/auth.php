<?php
declare(strict_types=1);

function current_admin(): ?array
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function require_admin(): void
{
    if (current_admin() === null) {
        flash('error', 'Please sign in to continue.');
        redirect('admin/login.php');
    }
}

function attempt_login(string $email, string $password): bool
{
    if ($password === '' || strlen($password) > 72) {
        return false;
    }

    $statement = db()->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $admin = $statement->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $update = db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
    $_SESSION['admin'] = [
        'id' => (int) $admin['id'],
        'name' => $admin['name'],
        'email' => $admin['email'],
    ];
    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
