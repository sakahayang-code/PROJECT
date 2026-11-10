<?php

namespace App\Modules\Users;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class UserController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('manage');

        $users = $this->db()->fetchAll(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug, b.name AS branch_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN branches b ON u.branch_id = b.id
             ORDER BY u.role_id ASC, u.id ASC"
        );

        $customerAccounts = $this->db()->fetchAll(
            "SELECT ca.*, c.name AS customer_name, c.code AS customer_code, c.whatsapp AS customer_phone,
                    c.email AS customer_email, c.deposit_balance, c.points_balance,
                    mt.name AS tier_name, mt.badge_color, b.name AS branch_name
             FROM customer_accounts ca
             JOIN customers c ON ca.customer_id = c.id
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             LEFT JOIN branches b ON c.favorite_branch_id = b.id
             ORDER BY ca.id DESC"
        );

        $customersWithoutAccount = $this->db()->fetchAll(
            "SELECT c.id, c.name, c.code, c.whatsapp, c.email
             FROM customers c
             LEFT JOIN customer_accounts ca ON c.id = ca.customer_id
             WHERE ca.id IS NULL
             ORDER BY c.name ASC"
        );

        $roles = $this->db()->fetchAll(
            "SELECT r.*, COUNT(rp.id) AS total_perms
             FROM roles r
             LEFT JOIN role_permissions rp ON r.id = rp.role_id
             GROUP BY r.id
             ORDER BY r.id ASC"
        );

        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY name ASC");

        $loginLogs = $this->db()->fetchAll(
            "SELECT ll.*, u.name AS user_name, u.username AS u_username
             FROM login_logs ll
             LEFT JOIN users u ON ll.user_id = u.id
             ORDER BY ll.id DESC LIMIT 30"
        );

        $stats = [
            'total_staff' => count($users),
            'active_staff' => count(array_filter($users, fn($u) => (int)$u['is_active'] === 1)),
            'total_customer_accounts' => count($customerAccounts),
            'active_customer_accounts' => count(array_filter($customerAccounts, fn($ca) => (int)$ca['is_active'] === 1)),
            'total_roles' => count($roles),
        ];

        $this->view('users.index', [
            'title' => 'Pusat Kelola Akun & Pengguna',
            'activeMenu' => 'users',
            'users' => $users,
            'customerAccounts' => $customerAccounts,
            'customersWithoutAccount' => $customersWithoutAccount,
            'roles' => $roles,
            'branches' => $branches,
            'loginLogs' => $loginLogs,
            'stats' => $stats,
        ]);
    }

    public function store(): void
    {
        Permission::abortUnless('manage');

        $username = strtolower(trim($_POST['username'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $branchAccess = $_POST['branch_access'] ?? [];

        if (empty($username) || empty($name) || empty($password) || empty($roleId)) {
            $this->back('Harap lengkapi semua kolom wajib.', 'error', '/users');
            return;
        }

        $exists = $this->db()->fetch("SELECT id FROM users WHERE username = :u", [':u' => $username]);
        if ($exists) {
            $this->back('Username telah terpakai. Pilih username lain.', 'error', '/users');
            return;
        }

        $userId = $this->db()->insert('users', [
            'company_id' => $this->companyId(),
            'branch_id' => $branchId,
            'username' => $username,
            'name' => $name,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role_id' => $roleId,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Save branch access
        if ($branchId) {
            $branchAccess[] = $branchId;
        }
        $branchAccess = array_unique(array_filter(array_map('intval', (array)$branchAccess)));
        foreach ($branchAccess as $bId) {
            $this->db()->insert('user_branch_access', [
                'user_id' => $userId,
                'branch_id' => $bId,
                'is_default' => ($bId === $branchId) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->logActivity('create', 'users', $userId, "Membuat akun staf baru: {$username} ({$name})");
        $this->redirect(url('/users'), "Akun staf {$name} (@{$username}) berhasil ditambahkan!");
    }

    public function update(int|string $id): void
    {
        Permission::abortUnless('manage');

        $id = (int)$id;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';
        $branchAccess = $_POST['branch_access'] ?? [];

        $updateData = [
            'name' => $name,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'role_id' => $roleId,
            'branch_id' => $branchId,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($password)) {
            $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->db()->update('users', $updateData, 'id = :id', [':id' => $id]);

        // Sync branch access
        $this->db()->delete('user_branch_access', 'user_id = :uid', [':uid' => $id]);
        if ($branchId) {
            $branchAccess[] = $branchId;
        }
        $branchAccess = array_unique(array_filter(array_map('intval', (array)$branchAccess)));
        foreach ($branchAccess as $bId) {
            $this->db()->insert('user_branch_access', [
                'user_id' => $id,
                'branch_id' => $bId,
                'is_default' => ($bId === $branchId) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->logActivity('update', 'users', $id, "Memperbarui data akun pengguna ID {$id}");
        $this->redirect(url('/users'), 'Data akun pengguna berhasil diperbarui!');
    }

    public function resetPassword(int|string $id): void
    {
        Permission::abortUnless('manage');

        $id = (int)$id;
        $newPassword = trim($_POST['new_password'] ?? '');

        if (strlen($newPassword) < 6) {
            $this->back('Kata sandi baru minimal 6 karakter.', 'error', '/users');
            return;
        }

        $user = $this->db()->fetch("SELECT * FROM users WHERE id = :id", [':id' => $id]);
        if (!$user) {
            $this->back('Pengguna tidak ditemukan.', 'error', '/users');
            return;
        }

        $this->db()->update('users', [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);

        $this->logActivity('reset_password', 'users', $id, "Mereset kata sandi akun {$user['username']}");
        $this->redirect(url('/users'), "Kata sandi untuk pengguna {$user['name']} (@{$user['username']}) berhasil direset!");
    }

    public function delete(int|string $id): void
    {
        Permission::abortUnless('manage');

        $id = (int)$id;
        if ($id === Auth::id()) {
            $this->back('Anda tidak dapat menonaktifkan akun Anda sendiri.', 'error', '/users');
            return;
        }

        $user = $this->db()->fetch("SELECT * FROM users WHERE id = :id", [':id' => $id]);
        if (!$user) {
            $this->back('Pengguna tidak ditemukan.', 'error', '/users');
            return;
        }

        // Prevent disabling main super administrator
        if ($user['role_id'] == 1 && $user['username'] === 'superadmin') {
            $this->back('Akun Super Administrator utama sistem dilindungi dan tidak dapat dinonaktifkan.', 'error', '/users');
            return;
        }

        $newActive = $user['is_active'] ? 0 : 1;
        $this->db()->update('users', [
            'is_active' => $newActive,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', [':id' => $id]);

        $statusMsg = $newActive ? 'diaktifkan kembali' : 'dinonaktifkan';
        $this->logActivity('toggle_status', 'users', $id, "Status akun pengguna {$user['username']} diubah menjadi {$statusMsg}");
        $this->redirect(url('/users'), "Akun {$user['name']} (@{$user['username']}) berhasil {$statusMsg}.");
    }

    public function createCustomerAccount(): void
    {
        Permission::abortUnless('manage');

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = trim($_POST['password'] ?? 'Pelanggan@123');

        if ($customerId <= 0) {
            $this->back('Pilih pelanggan yang valid.', 'error', '/users');
            return;
        }

        $customer = $this->db()->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $customerId]);
        if (!$customer) {
            $this->back('Data pelanggan tidak ditemukan.', 'error', '/users');
            return;
        }

        $exists = $this->db()->fetch("SELECT id FROM customer_accounts WHERE customer_id = :cid", [':cid' => $customerId]);
        if ($exists) {
            $this->back('Pelanggan ini sudah memiliki akun portal aktif.', 'error', '/users');
            return;
        }

        if (empty($username)) {
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $customer['name'])[0])) . $customerId;
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->insert('customer_accounts', [
            'customer_id' => $customerId,
            'username' => $username,
            'password_hash' => password_hash($password ?: 'Pelanggan@123', PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->logActivity('create_customer_account', 'customer_accounts', $customerId, "Membuat akun portal pelanggan untuk {$customer['name']}");
        $this->redirect(url('/users'), "Akun portal untuk {$customer['name']} berhasil dibuat! (Username: {$username})");
    }

    public function resetCustomerPassword(int|string $id): void
    {
        Permission::abortUnless('manage');

        $id = (int)$id;
        $newPassword = trim($_POST['new_password'] ?? 'Pelanggan@123');
        if (empty($newPassword)) {
            $newPassword = 'Pelanggan@123';
        }

        $account = $this->db()->fetch(
            "SELECT ca.*, c.name FROM customer_accounts ca JOIN customers c ON ca.customer_id = c.id WHERE ca.id = :id",
            [':id' => $id]
        );
        if (!$account) {
            $this->back('Akun portal pelanggan tidak ditemukan.', 'error', '/users');
            return;
        }

        $this->db()->update('customer_accounts', [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);

        $this->logActivity('reset_customer_password', 'customer_accounts', $id, "Mereset kata sandi portal pelanggan {$account['name']}");
        $this->redirect(url('/users'), "Kata sandi portal untuk {$account['name']} berhasil direset menjadi: {$newPassword}");
    }

    public function toggleCustomerAccount(int|string $id): void
    {
        Permission::abortUnless('manage');

        $id = (int)$id;
        $account = $this->db()->fetch(
            "SELECT ca.*, c.name FROM customer_accounts ca JOIN customers c ON ca.customer_id = c.id WHERE ca.id = :id",
            [':id' => $id]
        );
        if (!$account) {
            $this->back('Akun portal pelanggan tidak ditemukan.', 'error', '/users');
            return;
        }

        $newStatus = $account['is_active'] ? 0 : 1;
        $this->db()->update('customer_accounts', [
            'is_active' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);

        $statusMsg = $newStatus ? 'diaktifkan kembali' : 'dinonaktifkan/diblokir';
        $this->logActivity('toggle_customer_status', 'customer_accounts', $id, "Status akun portal {$account['name']} diubah menjadi {$statusMsg}");
        $this->redirect(url('/users'), "Akun portal untuk {$account['name']} berhasil {$statusMsg}.");
    }
}
