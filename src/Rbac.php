<?php

declare(strict_types=1);

namespace App;

/**
 * Central permission map. Keeping permissions in one place makes the
 * role model easy to audit.
 */
final class Rbac
{
    /** permission => roles allowed to use it */
    private const MAP = [
        'letter.view'    => ['admin', 'verifikator', 'staff'],
        'letter.create'  => ['admin', 'staff'],
        'letter.submit'  => ['admin', 'staff'],
        'letter.verify'  => ['admin', 'verifikator'],
        'letter.approve' => ['admin'],
        'letter.reject'  => ['admin', 'verifikator'],
        'letter.pdf'     => ['admin', 'verifikator', 'staff'],
        'audit.view'     => ['admin', 'verifikator'],
    ];

    public static function allows(string $role, string $permission): bool
    {
        return in_array($role, self::MAP[$permission] ?? [], true);
    }
}
