<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Authentication + authorization helpers backed by the session.
 */
final class Auth
{
    /** Attempt login. Returns true on success. */
    public static function attempt(string $username, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, username, password_hash, role, is_active
               FROM users WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Prevent session fixation.
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'name'     => $user['name'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function role(): string
    {
        return $_SESSION['user']['role'] ?? '';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('Please sign in to continue.', 'info');
            redirect('/login');
        }
    }

    /** Abort with 403 unless the current user has the permission. */
    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!Rbac::allows(self::role(), $permission)) {
            http_response_code(403);
            throw new RuntimeException('You are not allowed to perform this action.');
        }
    }

    public static function can(string $permission): bool
    {
        return self::check() && Rbac::allows(self::role(), $permission);
    }
}
