<?php

namespace App\Modules\Users;

use App\Core\Controller;
use App\Core\Permission;

class RoleController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('manage');

        $roles = $this->db()->fetchAll(
            "SELECT r.*, COUNT(rp.id) AS permission_count,
                    (SELECT COUNT(*) FROM users WHERE role_id = r.id) AS user_count
             FROM roles r
             LEFT JOIN role_permissions rp ON r.id = rp.role_id
             GROUP BY r.id
             ORDER BY r.id ASC"
        );

        $permissions = $this->db()->fetchAll("SELECT * FROM permissions ORDER BY group_name, name");
        $groupedPermissions = [];
        foreach ($permissions as $p) {
            $groupedPermissions[$p['group_name']][] = $p;
        }

        $this->view('users.roles', [
            'title' => 'Role & Hak Akses (RBAC)',
            'activeMenu' => 'users',
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    public function permissions(int|string $roleId): void
    {
        $roleId = (int)$roleId;
        $role = $this->db()->fetch("SELECT * FROM roles WHERE id = :id", [':id' => $roleId]);
        if (!$role) {
            $this->error('Role tidak ditemukan', null, 404);
        }

        $rolePerms = $this->db()->fetchAll(
            "SELECT permission_id FROM role_permissions WHERE role_id = :rid",
            [':rid' => $roleId]
        );
        $assigned = array_map('intval', array_column($rolePerms, 'permission_id'));

        $this->json(['role' => $role, 'permissions' => $assigned]);
    }

    public function updatePermissions(int|string $roleId): void
    {
        Permission::abortUnless('manage');

        $roleId = (int)$roleId;
        $permIds = $_POST['permissions'] ?? [];

        $this->db()->delete('role_permissions', 'role_id = :rid', [':rid' => $roleId]);

        $now = date('Y-m-d H:i:s');
        foreach ($permIds as $pid) {
            $this->db()->insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => (int)$pid,
                'created_at' => $now,
            ]);
        }

        $this->logActivity('update_role_permissions', 'roles', $roleId, "Memperbarui izin hak akses untuk role ID {$roleId}");
        $this->redirect(url('/roles'), 'Hak akses role berhasil diperbarui!');
    }
}
