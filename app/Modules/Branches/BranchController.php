<?php

namespace App\Modules\Branches;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class BranchController extends Controller
{
    public function switchBranch(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id === 0) {
            if (Auth::hasGlobalAccess()) {
                \App\Core\Session::set('auth_active_branch_id', null);
                $this->back('Beralih ke tampilan Konsolidasi Semua Cabang.', 'info');
            } else {
                $this->back('Akses ditolak. Anda tidak berwenang melihat seluruh cabang.', 'error');
            }
        }

        if (Auth::canAccessBranch($id)) {
            Auth::setActiveBranchId($id);
            $branch = $this->db()->fetch("SELECT name FROM branches WHERE id = :id", [':id' => $id]);
            $this->back('Beralih ke cabang: ' . ($branch['name'] ?? 'Cabang'), 'success');
        }

        $this->back('Anda tidak memiliki akses ke cabang yang dipilih.', 'error');
    }

    public function index(): void
    {
        if (!Auth::hasRole(['super_admin', 'owner', 'manager'])) {
            Permission::abortUnless('manage');
        }

        $branches = $this->db()->fetchAll(
            "SELECT b.*, 
                    (SELECT COUNT(*) FROM users WHERE branch_id = b.id) AS user_count,
                    (SELECT COUNT(*) FROM orders WHERE branch_id = b.id) AS order_count
             FROM branches b
             ORDER BY b.is_main DESC, b.name ASC"
        );

        $this->view('branches.index', [
            'title' => 'Manajemen Cabang',
            'activeMenu' => 'branches',
            'branches' => $branches,
        ]);
    }

    public function store(): void
    {
        Permission::abortUnless('manage');

        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $isMain = isset($_POST['is_main']) ? 1 : 0;

        if (empty($code) || empty($name)) {
            $this->back('Kode cabang dan nama cabang wajib diisi.', 'error');
        }

        $existing = $this->db()->fetch("SELECT id FROM branches WHERE code = :code", [':code' => $code]);
        if ($existing) {
            $this->back('Kode cabang sudah terdaftar. Gunakan kode lain.', 'error');
        }

        $branchId = $this->db()->insert('branches', [
            'company_id' => $this->companyId(),
            'code' => $code,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'is_main' => $isMain,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Copy default services pricing for the new branch
        $services = $this->db()->fetchAll("SELECT id, base_price, min_order, est_hours FROM services WHERE is_active = 1");
        foreach ($services as $srv) {
            $this->db()->insert('branch_service_prices', [
                'branch_id' => $branchId,
                'service_id' => $srv['id'],
                'price' => $srv['base_price'],
                'min_order' => $srv['min_order'],
                'est_hours' => $srv['est_hours'],
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->logActivity('create', 'branches', $branchId, "Membuat cabang baru: [{$code}] {$name}");
        $this->redirect(url('/branches'), 'Cabang baru berhasil ditambahkan!');
    }

    public function update(int|string $id): void
    {
        Permission::abortUnless('manage');

        $id = (int)$id;
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $this->back('Nama cabang wajib diisi.', 'error');
        }

        $this->db()->update('branches', [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);

        $this->logActivity('update', 'branches', $id, "Memperbarui cabang ID {$id}: {$name}");
        $this->redirect(url('/branches'), 'Data cabang berhasil diperbarui!');
    }
}
