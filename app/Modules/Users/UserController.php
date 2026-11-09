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
             ORDER BY u.id ASC"
        );

        $roles = $this->db()->fetchAll("SELECT * FROM roles ORDER BY id ASC");
        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY name ASC");

        $this->view('users.index', [
            'title' => 'Manajemen Pengguna',
            'activeMenu' => 'users',
            'users' => $users,
            'roles' => $roles,
            'branches' => $branches,
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
            $this->back('Harap lengkapi semua kolom wajib.', 'error');
        }

        $exists = $this->db()->fetch("SELECT id FROM users WHERE username = :u", [':u' => $username]);
        if ($exists) {
            $this->back('Username telah terpakai. Pilih username lain.', 'error');
        }

        $userId = $this->db()->insert('users', [
            'company_id' => $this->companyId(),
            'branch_id' => $branchId,
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
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

        $this->logActivity('create', 'users', $userId, "Membuat pengguna baru: {$username} ({$name})");
        $this->redirect(url('/users'), 'Pengguna baru berhasil ditambahkan!');
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
            'email' => $email,
            'phone' => $phone,
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

        $this->logActivity('update', 'users', $id, "Memperbarui data pengguna ID {$id}");
        $this->redirect(url('/users'), 'Pengguna berhasil diperbarui!');
    }
}
