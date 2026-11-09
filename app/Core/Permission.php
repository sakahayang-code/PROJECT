<?php

namespace App\Core;

class Permission
{
    public const VIEW = 'view';
    public const CREATE = 'create';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const APPROVE = 'approve';
    public const PRINT = 'print';
    public const EXPORT = 'export';
    public const CANCEL = 'cancel';
    public const MANAGE = 'manage';

    public static function check(string $permission): bool
    {
        return Auth::hasPermission($permission);
    }

    public static function abortUnless(string $permission): void
    {
        if (!self::check($permission)) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                Response::json(['error' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.'], 403);
                exit;
            }
            http_response_code(403);
            require dirname(__DIR__, 2) . '/resources/views/errors/403.php';
            exit;
        }
    }
}
