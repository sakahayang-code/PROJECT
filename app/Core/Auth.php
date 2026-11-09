<?php

namespace App\Core;

class Auth
{
    private static ?array $currentUser = null;
    private static ?array $currentCustomer = null;
    private static ?array $userPermissions = null;
    private static ?array $allowedBranchIds = null;

    public static function attempt(string $username, string $password, ?string $ip = null, ?string $userAgent = null): bool
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, c.name AS company_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             JOIN companies c ON u.company_id = c.id
             WHERE (u.username = :u1 OR u.email = :u2 OR u.phone = :u3) AND u.is_active = 1",
            [':u1' => $username, ':u2' => $username, ':u3' => $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Log failure
            try {
                $db->insert('login_logs', [
                    'user_id' => $user['id'] ?? null,
                    'username' => $username,
                    'ip_address' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
                    'user_agent' => substr($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'status' => 'failed',
                    'failure_reason' => $user ? 'invalid_password' : 'user_not_found',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
            return false;
        }

        // Success: regenerate session
        Session::regenerate(true);
        Session::set('auth_user_id', $user['id']);
        Session::set('auth_company_id', $user['company_id']);
        Session::set('auth_role_slug', $user['role_slug']);
        Session::set('auth_active_branch_id', $user['branch_id']);

        // Update last login
        $db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $user['id']]);

        // Log success
        try {
            $db->insert('login_logs', [
                'user_id' => $user['id'],
                'username' => $username,
                'ip_address' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
                'user_agent' => substr($userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'status' => 'success',
                'failure_reason' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}

        self::$currentUser = $user;
        self::loadPermissions($user['role_id']);
        self::loadBranchAccess($user['id'], $user['role_slug'], $user['company_id']);

        return true;
    }

    public static function login(array $user): void
    {
        Session::set('auth_user_id', $user['id']);
        Session::set('auth_company_id', $user['company_id']);
        Session::set('auth_role_slug', $user['role_slug'] ?? 'super_admin');
        Session::set('auth_active_branch_id', $user['branch_id']);

        self::$currentUser = null;
        self::$userPermissions = null;
        self::$allowedBranchIds = null;
    }

    public static function check(): bool
    {
        return Session::has('auth_user_id');
    }

    public static function id(): ?int
    {
        return Session::get('auth_user_id');
    }

    public static function companyId(): int
    {
        return (int)(Session::get('auth_company_id') ?: (self::user()['company_id'] ?? 1));
    }

    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $userId = self::id();
        if (!$userId) {
            return null;
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, c.name AS company_name, b.name AS branch_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             JOIN companies c ON u.company_id = c.id
             LEFT JOIN branches b ON u.branch_id = b.id
             WHERE u.id = :id AND u.is_active = 1",
            [':id' => $userId]
        );

        if (!$user) {
            self::logout();
            return null;
        }

        self::$currentUser = $user;
        self::loadPermissions($user['role_id']);
        self::loadBranchAccess($user['id'], $user['role_slug'], $user['company_id']);

        return self::$currentUser;
    }

    private static function loadPermissions(int $roleId): void
    {
        if (self::$userPermissions !== null) {
            return;
        }

        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT p.slug
             FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id",
            [':role_id' => $roleId]
        );

        self::$userPermissions = array_column($rows, 'slug');
    }

    private static function loadBranchAccess(int $userId, string $roleSlug, int $companyId): void
    {
        if (self::$allowedBranchIds !== null) {
            return;
        }

        $db = Database::getInstance();

        // Super Admin & Owner have access to all branches of their company
        if (in_array($roleSlug, ['super_admin', 'owner'], true)) {
            $branches = $db->fetchAll(
                "SELECT id FROM branches WHERE company_id = :company_id AND is_active = 1",
                [':company_id' => $companyId]
            );
            self::$allowedBranchIds = array_map('intval', array_column($branches, 'id'));
            return;
        }

        // Branch-specific users
        $rows = $db->fetchAll(
            "SELECT branch_id FROM user_branch_access WHERE user_id = :user_id",
            [':user_id' => $userId]
        );
        $ids = array_map('intval', array_column($rows, 'branch_id'));

        // If user has a default branch_id in users table, make sure it's included
        if (self::$currentUser && !empty(self::$currentUser['branch_id'])) {
            $ids[] = (int)self::$currentUser['branch_id'];
        }

        self::$allowedBranchIds = array_values(array_unique($ids));
    }

    public static function hasPermission(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        // Super Admin has all permissions unconditionally
        if ($user['role_slug'] === 'super_admin') {
            return true;
        }

        if (self::$userPermissions === null) {
            self::loadPermissions($user['role_id']);
        }

        $userPerms = self::$userPermissions ?? [];

        // Exact match
        if (in_array($permission, $userPerms, true)) {
            return true;
        }

        // If user has 'manage', they have broad management rights
        if (in_array('manage', $userPerms, true)) {
            if (str_starts_with($permission, 'manage.') || str_starts_with($permission, 'audit.') || str_starts_with($permission, 'settings.')) {
                return true;
            }
        }

        // Category aliases for granular permissions mapped to baseline permissions
        $aliases = [
            'reports.view' => ['reports.access', 'view'],
            'inventory.view' => ['inventory.access', 'view'],
            'inventory.create' => ['inventory.access', 'create'],
            'inventory.edit' => ['inventory.access', 'edit'],
            'machines.view' => ['inventory.access', 'production.access', 'view'],
            'machines.edit' => ['inventory.access', 'production.access', 'edit', 'manage'],
            'orders.view' => ['view', 'pos.access', 'production.access', 'delivery.access'],
            'orders.create' => ['create', 'pos.access'],
            'orders.edit' => ['edit', 'pos.access', 'delivery.access'],
            'customers.view' => ['view', 'pos.access'],
            'customers.edit' => ['edit', 'pos.access'],
            'manage.settings' => ['manage'],
            'manage.backup' => ['manage'],
            'audit.view' => ['view', 'manage'],
        ];

        if (isset($aliases[$permission])) {
            foreach ($aliases[$permission] as $alt) {
                if (in_array($alt, $userPerms, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($user['role_slug'], $roles, true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::hasRole('super_admin');
    }

    public static function isOwner(): bool
    {
        return self::hasRole('owner');
    }

    public static function hasGlobalAccess(): bool
    {
        return self::hasRole(['super_admin', 'owner']);
    }

    public static function getActiveBranchId(): ?int
    {
        $active = Session::get('auth_active_branch_id');
        if ($active) {
            return (int)$active;
        }
        $user = self::user();
        return $user['branch_id'] ? (int)$user['branch_id'] : null;
    }

    public static function setActiveBranchId(int $branchId): bool
    {
        if (!self::canAccessBranch($branchId)) {
            return false;
        }
        Session::set('auth_active_branch_id', $branchId);
        return true;
    }

    public static function canAccessBranch(int $branchId): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if (self::hasGlobalAccess()) {
            return true;
        }

        if (self::$allowedBranchIds === null) {
            self::loadBranchAccess($user['id'], $user['role_slug'], $user['company_id']);
        }

        return in_array($branchId, self::$allowedBranchIds ?? [], true);
    }

    public static function getAllowedBranchIds(): array
    {
        $user = self::user();
        if (!$user) {
            return [];
        }

        if (self::$allowedBranchIds === null) {
            self::loadBranchAccess($user['id'], $user['role_slug'], $user['company_id']);
        }

        return self::$allowedBranchIds ?? [];
    }

    public static function logout(): void
    {
        Session::remove('auth_user_id');
        Session::remove('auth_company_id');
        Session::remove('auth_role_slug');
        Session::remove('auth_active_branch_id');
        self::$currentUser = null;
        self::$userPermissions = null;
        self::$allowedBranchIds = null;
        Session::regenerate(true);
    }

    /* -------------------------------------------------------------
     * Customer Portal Authentication
     * -----------------------------------------------------------*/

    public static function attemptCustomer(string $identifier, string $password): bool
    {
        $db = Database::getInstance();
        $account = $db->fetch(
            "SELECT ca.*, c.name, c.whatsapp, c.email, c.company_id, c.points_balance, c.deposit_balance, c.current_tier_id
             FROM customer_accounts ca
             JOIN customers c ON ca.customer_id = c.id
             WHERE (ca.username = :i1 OR c.whatsapp = :i2 OR c.email = :i3) AND ca.is_active = 1",
            [':i1' => $identifier, ':i2' => $identifier, ':i3' => $identifier]
        );

        if (!$account || !password_verify($password, $account['password_hash'])) {
            return false;
        }

        Session::regenerate(true);
        Session::set('portal_customer_id', $account['customer_id']);
        self::$currentCustomer = null;
        return true;
    }

    public static function loginCustomer(int $customerId): void
    {
        Session::set('portal_customer_id', $customerId);
        self::$currentCustomer = null;
    }

    public static function checkCustomer(): bool
    {
        return Session::has('portal_customer_id');
    }

    public static function customerId(): ?int
    {
        return Session::get('portal_customer_id');
    }

    public static function customer(): ?array
    {
        if (self::$currentCustomer !== null) {
            return self::$currentCustomer;
        }

        $id = self::customerId();
        if (!$id) {
            return null;
        }

        $db = Database::getInstance();
        $customer = $db->fetch(
            "SELECT c.*, ca.username, mt.name AS tier_name, mt.discount_percent, mt.badge_color
             FROM customers c
             LEFT JOIN customer_accounts ca ON c.id = ca.customer_id
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             WHERE c.id = :id AND c.status = 'active'",
            [':id' => $id]
        );

        self::$currentCustomer = $customer;
        return self::$currentCustomer;
    }

    public static function logoutCustomer(): void
    {
        Session::remove('portal_customer_id');
        self::$currentCustomer = null;
        Session::regenerate(true);
    }
}
