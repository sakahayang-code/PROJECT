<?php

namespace App\Modules\Services;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class ServiceController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('view');

        $services = $this->db()->fetchAll(
            "SELECT s.*, sc.name AS category_name
             FROM services s
             JOIN service_categories sc ON s.category_id = sc.id
             ORDER BY s.category_id ASC, s.name ASC"
        );

        $categories = $this->db()->fetchAll("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY sort_order ASC");
        $addons = $this->db()->fetchAll("SELECT * FROM service_addons ORDER BY id ASC");

        $this->view('services.index', [
            'title' => 'Layanan & Tarif Laundry',
            'activeMenu' => 'services',
            'services' => $services,
            'categories' => $categories,
            'addons' => $addons,
        ]);
    }

    public function store(): void
    {
        Permission::abortUnless('create');

        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $unitType = $_POST['unit_type'] ?? 'kg';
        $basePrice = (int)str_replace(['.', ','], '', $_POST['base_price'] ?? '0');
        $minOrder = (float)($_POST['min_order'] ?? 1.0);
        $estHours = (int)($_POST['est_hours'] ?? 24);
        $description = trim($_POST['description'] ?? '');

        if (empty($code) || empty($name) || empty($categoryId) || $basePrice <= 0) {
            $this->back('Harap lengkapi semua data wajib layanan.', 'error');
        }

        $exists = $this->db()->fetch("SELECT id FROM services WHERE code = :c", [':c' => $code]);
        if ($exists) {
            $this->back('Kode layanan sudah digunakan. Buat kode lain.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $serviceId = $this->db()->insert('services', [
            'company_id' => $this->companyId(),
            'category_id' => $categoryId,
            'code' => $code,
            'name' => $name,
            'unit_type' => $unitType,
            'base_price' => $basePrice,
            'min_order' => $minOrder,
            'est_hours' => $estHours,
            'description' => $description,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Auto replicate to branch prices for all branches
        $branches = $this->db()->fetchAll("SELECT id FROM branches WHERE is_active = 1");
        foreach ($branches as $br) {
            $this->db()->insert('branch_service_prices', [
                'branch_id' => $br['id'],
                'service_id' => $serviceId,
                'price' => $basePrice,
                'min_order' => $minOrder,
                'est_hours' => $estHours,
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        $this->logActivity('create', 'services', $serviceId, "Menambahkan layanan baru: [{$code}] {$name}");
        $this->redirect(url('/services'), 'Layanan baru berhasil ditambahkan!');
    }

    public function update(int|string $id): void
    {
        Permission::abortUnless('edit');

        $id = (int)$id;
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $unitType = $_POST['unit_type'] ?? 'kg';
        $basePrice = (int)str_replace(['.', ','], '', $_POST['base_price'] ?? '0');
        $minOrder = (float)($_POST['min_order'] ?? 1.0);
        $estHours = (int)($_POST['est_hours'] ?? 24);
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $this->db()->update('services', [
            'category_id' => $categoryId,
            'name' => $name,
            'unit_type' => $unitType,
            'base_price' => $basePrice,
            'min_order' => $minOrder,
            'est_hours' => $estHours,
            'description' => $description,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);

        $this->logActivity('update', 'services', $id, "Memperbarui master layanan ID {$id}: {$name}");
        $this->redirect(url('/services'), 'Data layanan berhasil diperbarui!');
    }

    public function pricing(): void
    {
        Permission::abortUnless('view');

        $branchId = (int)($_GET['branch_id'] ?? (Auth::getActiveBranchId() ?: 1));
        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1");

        $prices = $this->db()->fetchAll(
            "SELECT bsp.*, s.name as service_name, s.code as service_code, s.unit_type, s.base_price, sc.name as category_name
             FROM branch_service_prices bsp
             JOIN services s ON bsp.service_id = s.id
             JOIN service_categories sc ON s.category_id = sc.id
             WHERE bsp.branch_id = :b
             ORDER BY sc.id, s.name",
            [':b' => $branchId]
        );

        $this->view('services.pricing', [
            'title' => 'Matriks Harga Per Cabang',
            'activeMenu' => 'services',
            'prices' => $prices,
            'branches' => $branches,
            'selectedBranchId' => $branchId,
        ]);
    }

    public function updateBranchPrice(): void
    {
        Permission::abortUnless('edit');

        $branchId = (int)$_POST['branch_id'];
        $serviceId = (int)$_POST['service_id'];
        $price = (int)str_replace(['.', ','], '', $_POST['price'] ?? '0');
        $minOrder = (float)($_POST['min_order'] ?? 1.0);
        $estHours = (int)($_POST['est_hours'] ?? 24);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $current = $this->db()->fetch(
            "SELECT price FROM branch_service_prices WHERE branch_id = :b AND service_id = :s",
            [':b' => $branchId, ':s' => $serviceId]
        );

        $now = date('Y-m-d H:i:s');
        if ($current) {
            if ((int)$current['price'] !== $price) {
                // Log price history
                $this->db()->insert('service_price_history', [
                    'service_id' => $serviceId,
                    'branch_id' => $branchId,
                    'old_price' => $current['price'],
                    'new_price' => $price,
                    'changed_by' => Auth::id(),
                    'created_at' => $now,
                ]);
            }

            $this->db()->update('branch_service_prices', [
                'price' => $price,
                'min_order' => $minOrder,
                'est_hours' => $estHours,
                'is_active' => $isActive,
                'updated_at' => $now,
            ], 'branch_id = :b AND service_id = :s', [':b' => $branchId, ':s' => $serviceId]);
        } else {
            $this->db()->insert('branch_service_prices', [
                'branch_id' => $branchId,
                'service_id' => $serviceId,
                'price' => $price,
                'min_order' => $minOrder,
                'est_hours' => $estHours,
                'is_active' => $isActive,
                'updated_at' => $now,
            ]);
        }

        $this->logActivity('update_branch_price', 'branch_service_prices', $serviceId, "Mengubah tarif cabang ID {$branchId} layanan {$serviceId} menjadi Rp {$price}");
        $this->redirect(url('/services/pricing?branch_id=' . $branchId), 'Tarif cabang berhasil diperbarui!');
    }

    public function storeAddon(): void
    {
        Permission::abortUnless('create');

        $name = trim($_POST['name'] ?? '');
        $price = (int)str_replace(['.', ','], '', $_POST['price'] ?? '0');
        $unitType = $_POST['unit_type'] ?? 'item';
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || $price <= 0) {
            $this->back('Nama dan tarif layanan tambahan wajib diisi.', 'error');
        }

        $this->db()->insert('service_addons', [
            'company_id' => $this->companyId(),
            'name' => $name,
            'price' => $price,
            'unit_type' => $unitType,
            'description' => $description,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->redirect(url('/services'), 'Layanan tambahan (Add-on) berhasil ditambahkan!');
    }
}
