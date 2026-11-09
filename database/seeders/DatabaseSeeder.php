<?php

namespace Database\Seeders;

use App\Core\Database;

class DatabaseSeeder
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // 1. Company
        $companyId = $this->db->fetchColumn("SELECT id FROM companies WHERE code = 'CORP'");
        if (!$companyId) {
            $companyId = $this->db->insert('companies', [
                'code' => 'CORP',
                'name' => 'LaundryPro Indonesia',
                'tagline' => 'Smart Laundry Business Management System',
                'email' => 'info@laundrypro.id',
                'phone' => '081234567890',
                'address' => 'Jl. Jenderal Sudirman No. 100, Jakarta Pusat',
                'currency' => 'IDR',
                'settings' => json_encode(['tax_rate' => 0, 'enable_tax' => false, 'loyalty_pts_per_10k' => 1]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Branches
        $branchPusat = $this->db->fetchColumn("SELECT id FROM branches WHERE code = 'PST'");
        if (!$branchPusat) {
            $branchPusat = $this->db->insert('branches', [
                'company_id' => $companyId,
                'code' => 'PST',
                'name' => 'LaundryPro Cabang Pusat (Sudirman)',
                'phone' => '081211112222',
                'email' => 'pusat@laundrypro.id',
                'address' => 'Jl. Jenderal Sudirman No. 100, Jakarta Pusat',
                'is_main' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $branchBarat = $this->db->fetchColumn("SELECT id FROM branches WHERE code = 'BRT'");
        if (!$branchBarat) {
            $branchBarat = $this->db->insert('branches', [
                'company_id' => $companyId,
                'code' => 'BRT',
                'name' => 'LaundryPro Cabang Barat (Kebon Jeruk)',
                'phone' => '081233334444',
                'email' => 'barat@laundrypro.id',
                'address' => 'Jl. Raya Kebon Jeruk No. 45, Jakarta Barat',
                'is_main' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $branchSelatan = $this->db->fetchColumn("SELECT id FROM branches WHERE code = 'SLT'");
        if (!$branchSelatan) {
            $branchSelatan = $this->db->insert('branches', [
                'company_id' => $companyId,
                'code' => 'SLT',
                'name' => 'LaundryPro Cabang Selatan (Fatmawati)',
                'phone' => '081255556666',
                'email' => 'selatan@laundrypro.id',
                'address' => 'Jl. RS Fatmawati No. 88, Jakarta Selatan',
                'is_main' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Permissions
        $permissions = [
            ['name' => 'Lihat Data', 'slug' => 'view', 'group_name' => 'Umum', 'description' => 'Izin melihat daftar dan detail data'],
            ['name' => 'Tambah Data', 'slug' => 'create', 'group_name' => 'Umum', 'description' => 'Izin membuat rekaman baru'],
            ['name' => 'Ubah Data', 'slug' => 'edit', 'group_name' => 'Umum', 'description' => 'Izin mengubah rekaman yang ada'],
            ['name' => 'Hapus Data', 'slug' => 'delete', 'group_name' => 'Umum', 'description' => 'Izin menghapus rekaman'],
            ['name' => 'Setujui Data', 'slug' => 'approve', 'group_name' => 'Umum', 'description' => 'Izin memberikan persetujuan'],
            ['name' => 'Cetak Dokumen', 'slug' => 'print', 'group_name' => 'Umum', 'description' => 'Izin mencetak nota atau invoice'],
            ['name' => 'Ekspor Data', 'slug' => 'export', 'group_name' => 'Umum', 'description' => 'Izin mengekspor laporan'],
            ['name' => 'Batalkan Pesanan', 'slug' => 'cancel', 'group_name' => 'Umum', 'description' => 'Izin membatalkan transaksi'],
            ['name' => 'Kelola Sistem', 'slug' => 'manage', 'group_name' => 'Umum', 'description' => 'Izin manajemen tingkat lanjut'],
            ['name' => 'Akses POS Kasir', 'slug' => 'pos.access', 'group_name' => 'POS', 'description' => 'Izin membuka antarmuka kasir'],
            ['name' => 'Akses Antrean Produksi', 'slug' => 'production.access', 'group_name' => 'Produksi', 'description' => 'Izin mengelola tahapan produksi'],
            ['name' => 'Akses Penugasan Pengiriman', 'slug' => 'delivery.access', 'group_name' => 'Logistik', 'description' => 'Izin mengelola penjemputan/pengantaran'],
            ['name' => 'Akses Akuntansi & Kas', 'slug' => 'accounting.access', 'group_name' => 'Keuangan', 'description' => 'Izin mengelola jurnal dan kas'],
            ['name' => 'Akses Inventaris', 'slug' => 'inventory.access', 'group_name' => 'Gudang', 'description' => 'Izin stok dan barang masuk/keluar'],
            ['name' => 'Akses Laporan', 'slug' => 'reports.access', 'group_name' => 'Laporan', 'description' => 'Izin melihat laporan analitik'],
        ];

        $permMap = [];
        foreach ($permissions as $p) {
            $existing = $this->db->fetch("SELECT id FROM permissions WHERE slug = :s", [':s' => $p['slug']]);
            if ($existing) {
                $permMap[$p['slug']] = (int)$existing['id'];
            } else {
                $permId = $this->db->insert('permissions', array_merge($p, ['created_at' => $now]));
                $permMap[$p['slug']] = $permId;
            }
        }

        // 4. Roles
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Akses konfigurasi penuh, multi-cabang dan sistem', 'is_system' => 1],
            ['name' => 'Owner / Pemilik', 'slug' => 'owner', 'description' => 'Melihat konsolidasi seluruh cabang dan laporan keuangan', 'is_system' => 1],
            ['name' => 'Manager / Kepala Cabang', 'slug' => 'manager', 'description' => 'Mengelola operasional dan staf cabang tertentu', 'is_system' => 1],
            ['name' => 'Kasir', 'slug' => 'cashier', 'description' => 'Membuat pesanan, menerima pembayaran, cetak nota', 'is_system' => 1],
            ['name' => 'Petugas Produksi', 'slug' => 'production', 'description' => 'Memproses cuci, kering, setrika, QC, dan kemas', 'is_system' => 1],
            ['name' => 'Kurir', 'slug' => 'courier', 'description' => 'Menjalankan pickup dan delivery pesanan pelanggan', 'is_system' => 1],
            ['name' => 'Petugas Gudang', 'slug' => 'warehouse', 'description' => 'Mengelola pergerakan stok, transfer, dan stok opname', 'is_system' => 1],
            ['name' => 'Akuntan / Keuangan', 'slug' => 'accountant', 'description' => 'Mengelola buku besar, kas/bank, jurnal, dan laba rugi', 'is_system' => 1],
            ['name' => 'Pelanggan', 'slug' => 'customer', 'description' => 'Akses portal pelanggan untuk order & tracking', 'is_system' => 1],
        ];

        $roleMap = [];
        foreach ($roles as $r) {
            $existing = $this->db->fetch("SELECT id FROM roles WHERE slug = :s", [':s' => $r['slug']]);
            if ($existing) {
                $roleMap[$r['slug']] = (int)$existing['id'];
            } else {
                $roleId = $this->db->insert('roles', array_merge($r, [
                    'company_id' => $companyId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $roleMap[$r['slug']] = $roleId;
            }
        }

        // Assign all permissions to super_admin and owner
        foreach (['super_admin', 'owner'] as $roleKey) {
            foreach ($permMap as $permId) {
                $exists = $this->db->fetchColumn(
                    "SELECT id FROM role_permissions WHERE role_id = :rid AND permission_id = :pid",
                    [':rid' => $roleMap[$roleKey], ':pid' => $permId]
                );
                if (!$exists) {
                    $this->db->insert('role_permissions', [
                        'role_id' => $roleMap[$roleKey],
                        'permission_id' => $permId,
                        'created_at' => $now,
                    ]);
                }
            }
        }

        // Manager permissions
        $managerPerms = ['view', 'create', 'edit', 'approve', 'print', 'export', 'cancel', 'pos.access', 'production.access', 'delivery.access', 'inventory.access', 'reports.access'];
        foreach ($managerPerms as $slug) {
            if (isset($permMap[$slug])) {
                $exists = $this->db->fetchColumn(
                    "SELECT id FROM role_permissions WHERE role_id = :rid AND permission_id = :pid",
                    [':rid' => $roleMap['manager'], ':pid' => $permMap[$slug]]
                );
                if (!$exists) {
                    $this->db->insert('role_permissions', [
                        'role_id' => $roleMap['manager'],
                        'permission_id' => $permMap[$slug],
                        'created_at' => $now,
                    ]);
                }
            }
        }

        // Cashier permissions
        $cashierPerms = ['view', 'create', 'print', 'pos.access'];
        foreach ($cashierPerms as $slug) {
            if (isset($permMap[$slug])) {
                $exists = $this->db->fetchColumn(
                    "SELECT id FROM role_permissions WHERE role_id = :rid AND permission_id = :pid",
                    [':rid' => $roleMap['cashier'], ':pid' => $permMap[$slug]]
                );
                if (!$exists) {
                    $this->db->insert('role_permissions', [
                        'role_id' => $roleMap['cashier'],
                        'permission_id' => $permMap[$slug],
                        'created_at' => $now,
                    ]);
                }
            }
        }

        // Production, Courier, Warehouse, and Accountant permissions
        $otherRolePerms = [
            'production' => ['view', 'edit', 'production.access'],
            'courier' => ['view', 'edit', 'delivery.access'],
            'warehouse' => ['view', 'create', 'edit', 'inventory.access'],
            'accountant' => ['view', 'create', 'edit', 'approve', 'print', 'export', 'accounting.access', 'reports.access'],
        ];
        foreach ($otherRolePerms as $rKey => $pSlugs) {
            if (isset($roleMap[$rKey])) {
                foreach ($pSlugs as $slug) {
                    if (isset($permMap[$slug])) {
                        $exists = $this->db->fetchColumn(
                            "SELECT id FROM role_permissions WHERE role_id = :rid AND permission_id = :pid",
                            [':rid' => $roleMap[$rKey], ':pid' => $permMap[$slug]]
                        );
                        if (!$exists) {
                            $this->db->insert('role_permissions', [
                                'role_id' => $roleMap[$rKey],
                                'permission_id' => $permMap[$slug],
                                'created_at' => $now,
                            ]);
                        }
                    }
                }
            }
        }

        // 5. Users
        $defaultPassword = password_hash('Admin@123', PASSWORD_BCRYPT);
        $users = [
            [
                'username' => 'superadmin',
                'name' => 'Super Administrator',
                'email' => 'superadmin@laundrypro.id',
                'phone' => '081111111111',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['super_admin'],
                'branch_id' => null,
            ],
            [
                'username' => 'owner',
                'name' => 'Budi Santoso (Owner)',
                'email' => 'owner@laundrypro.id',
                'phone' => '081222222222',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['owner'],
                'branch_id' => null,
            ],
            [
                'username' => 'manager.pusat',
                'name' => 'Rian Hidayat (Manager Pusat)',
                'email' => 'manager.pusat@laundrypro.id',
                'phone' => '081333333333',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['manager'],
                'branch_id' => $branchPusat,
            ],
            [
                'username' => 'kasir.pusat',
                'name' => 'Siti Aminah (Kasir Pusat)',
                'email' => 'kasir.pusat@laundrypro.id',
                'phone' => '081444444444',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['cashier'],
                'branch_id' => $branchPusat,
            ],
            [
                'username' => 'operator.pusat',
                'name' => 'Agus Setiawan (Petugas Produksi)',
                'email' => 'operator.pusat@laundrypro.id',
                'phone' => '081555555555',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['production'],
                'branch_id' => $branchPusat,
            ],
            [
                'username' => 'kurir.pusat',
                'name' => 'Doni Pratama (Kurir)',
                'email' => 'kurir.pusat@laundrypro.id',
                'phone' => '081666666666',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['courier'],
                'branch_id' => $branchPusat,
            ],
            [
                'username' => 'gudang.pusat',
                'name' => 'Wahyu Wicaksono (Gudang)',
                'email' => 'gudang.pusat@laundrypro.id',
                'phone' => '081777777777',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['warehouse'],
                'branch_id' => $branchPusat,
            ],
            [
                'username' => 'akuntan',
                'name' => 'Dewi Lestari, SE (Akuntan)',
                'email' => 'akuntan@laundrypro.id',
                'phone' => '081888888888',
                'password_hash' => $defaultPassword,
                'role_id' => $roleMap['accountant'],
                'branch_id' => null,
            ],
        ];

        foreach ($users as $u) {
            $existing = $this->db->fetch("SELECT id FROM users WHERE username = :u", [':u' => $u['username']]);
            if (!$existing) {
                $uid = $this->db->insert('users', array_merge($u, [
                    'company_id' => $companyId,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                if ($u['branch_id']) {
                    $this->db->insert('user_branch_access', [
                        'user_id' => $uid,
                        'branch_id' => $u['branch_id'],
                        'is_default' => 1,
                        'created_at' => $now,
                    ]);
                }
            }
        }

        // 6. Production Stages
        $stages = [
            ['name' => 'Menunggu Antrean', 'slug' => 'waiting', 'sort_order' => 1, 'est_minutes' => 15, 'is_required' => 1],
            ['name' => 'Pencucian', 'slug' => 'washing', 'sort_order' => 2, 'est_minutes' => 45, 'is_required' => 1],
            ['name' => 'Pengeringan', 'slug' => 'drying', 'sort_order' => 3, 'est_minutes' => 40, 'is_required' => 1],
            ['name' => 'Penyetrikaan & Uap', 'slug' => 'ironing', 'sort_order' => 4, 'est_minutes' => 30, 'is_required' => 1],
            ['name' => 'Quality Control (QC)', 'slug' => 'qc', 'sort_order' => 5, 'est_minutes' => 15, 'is_required' => 1],
            ['name' => 'Pengemasan & Packing', 'slug' => 'packaging', 'sort_order' => 6, 'est_minutes' => 20, 'is_required' => 1],
            ['name' => 'Siap Diambil / Diantar', 'slug' => 'ready', 'sort_order' => 7, 'est_minutes' => 0, 'is_required' => 1],
        ];

        foreach ($stages as $stg) {
            $exists = $this->db->fetchColumn("SELECT id FROM production_stages WHERE slug = :s AND company_id = :c", [':s' => $stg['slug'], ':c' => $companyId]);
            if (!$exists) {
                $this->db->insert('production_stages', array_merge($stg, ['company_id' => $companyId, 'created_at' => $now]));
            }
        }

        // 7. Membership Tiers
        $tiers = [
            ['name' => 'Regular Member', 'slug' => 'regular', 'min_spend' => 0, 'min_points' => 0, 'discount_percent' => 0, 'points_multiplier' => 1.0, 'badge_color' => '#64748B', 'description' => 'Anggota baru standar'],
            ['name' => 'Silver Member', 'slug' => 'silver', 'min_spend' => 500000, 'min_points' => 100, 'discount_percent' => 5.0, 'points_multiplier' => 1.2, 'badge_color' => '#94A3B8', 'description' => 'Diskon 5% untuk semua layanan'],
            ['name' => 'Gold Member', 'slug' => 'gold', 'min_spend' => 1500000, 'min_points' => 300, 'discount_percent' => 10.0, 'points_multiplier' => 1.5, 'badge_color' => '#F59E0B', 'description' => 'Diskon 10% dan poin lebih cepat'],
            ['name' => 'Platinum VIP', 'slug' => 'platinum', 'min_spend' => 3500000, 'min_points' => 700, 'discount_percent' => 15.0, 'points_multiplier' => 2.0, 'badge_color' => '#13AFA8', 'description' => 'Diskon 15%, prioritas antrean, gratis antar jemput'],
        ];

        $tierMap = [];
        foreach ($tiers as $t) {
            $exists = $this->db->fetch("SELECT id FROM membership_tiers WHERE slug = :s AND company_id = :c", [':s' => $t['slug'], ':c' => $companyId]);
            if ($exists) {
                $tierMap[$t['slug']] = (int)$exists['id'];
            } else {
                $tid = $this->db->insert('membership_tiers', array_merge($t, ['company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now]));
                $tierMap[$t['slug']] = $tid;
            }
        }

        // 8. Service Categories & Services
        $catKiloan = $this->db->fetchColumn("SELECT id FROM service_categories WHERE slug = 'kiloan'");
        if (!$catKiloan) {
            $catKiloan = $this->db->insert('service_categories', [
                'company_id' => $companyId,
                'name' => 'Laundry Kiloan',
                'slug' => 'kiloan',
                'icon' => 'bi-basket',
                'description' => 'Layanan cuci berbasis timbangan kilogram',
                'sort_order' => 1,
                'is_active' => 1,
                'created_at' => $now,
            ]);
        }

        $catSatuan = $this->db->fetchColumn("SELECT id FROM service_categories WHERE slug = 'satuan'");
        if (!$catSatuan) {
            $catSatuan = $this->db->insert('service_categories', [
                'company_id' => $companyId,
                'name' => 'Laundry Satuan',
                'slug' => 'satuan',
                'icon' => 'bi-tag',
                'description' => 'Layanan perawatan khusus per potong pakaian & perlengkapan rumah',
                'sort_order' => 2,
                'is_active' => 1,
                'created_at' => $now,
            ]);
        }

        $services = [
            ['category_id' => $catKiloan, 'code' => 'CKSR', 'name' => 'Cuci Kering Setrika (Reguler 2 Hari)', 'unit_type' => 'kg', 'base_price' => 8000, 'min_order' => 3.0, 'est_hours' => 48],
            ['category_id' => $catKiloan, 'code' => 'CKSE', 'name' => 'Cuci Kering Setrika (Express 1 Hari)', 'unit_type' => 'kg', 'base_price' => 12000, 'min_order' => 3.0, 'est_hours' => 24],
            ['category_id' => $catKiloan, 'code' => 'CKSP', 'name' => 'Cuci Kering Setrika (Prioritas / Kilat 6 Jam)', 'unit_type' => 'kg', 'base_price' => 18000, 'min_order' => 2.0, 'est_hours' => 6],
            ['category_id' => $catKiloan, 'code' => 'CKL',  'name' => 'Cuci Kering Lipat (Tanpa Setrika)', 'unit_type' => 'kg', 'base_price' => 6000, 'min_order' => 3.0, 'est_hours' => 24],
            ['category_id' => $catKiloan, 'code' => 'STK',  'name' => 'Setrika Uap Saja', 'unit_type' => 'kg', 'base_price' => 5000, 'min_order' => 3.0, 'est_hours' => 24],

            ['category_id' => $catSatuan, 'code' => 'BC-L', 'name' => 'Bed Cover Besar (King/Queen)', 'unit_type' => 'piece', 'base_price' => 35000, 'min_order' => 1.0, 'est_hours' => 48],
            ['category_id' => $catSatuan, 'code' => 'BC-S', 'name' => 'Bed Cover Sedang / Single', 'unit_type' => 'piece', 'base_price' => 25000, 'min_order' => 1.0, 'est_hours' => 48],
            ['category_id' => $catSatuan, 'code' => 'SLM',  'name' => 'Selimut Tebal / Fleece', 'unit_type' => 'piece', 'base_price' => 20000, 'min_order' => 1.0, 'est_hours' => 24],
            ['category_id' => $catSatuan, 'code' => 'JAS',  'name' => 'Jas Pria / Blazer (Dry Clean Care)', 'unit_type' => 'piece', 'base_price' => 35000, 'min_order' => 1.0, 'est_hours' => 48],
            ['category_id' => $catSatuan, 'code' => 'GMS',  'name' => 'Gamis / Gaun Pesta Bordir', 'unit_type' => 'piece', 'base_price' => 30000, 'min_order' => 1.0, 'est_hours' => 48],
            ['category_id' => $catSatuan, 'code' => 'SPTU', 'name' => 'Deep Cleaning Sepatu Sneaker', 'unit_type' => 'piece', 'base_price' => 35000, 'min_order' => 1.0, 'est_hours' => 48],
            ['category_id' => $catSatuan, 'code' => 'BONE', 'name' => 'Boneka Ukuran Sedang', 'unit_type' => 'piece', 'base_price' => 20000, 'min_order' => 1.0, 'est_hours' => 24],
        ];

        foreach ($services as $srv) {
            $exists = $this->db->fetch("SELECT id FROM services WHERE code = :code", [':code' => $srv['code']]);
            $srvId = $exists ? $exists['id'] : $this->db->insert('services', array_merge($srv, ['company_id' => $companyId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));

            // Seed default prices across branches
            foreach ([$branchPusat, $branchBarat, $branchSelatan] as $bId) {
                $bPriceExists = $this->db->fetchColumn("SELECT id FROM branch_service_prices WHERE branch_id = :b AND service_id = :s", [':b' => $bId, ':s' => $srvId]);
                if (!$bPriceExists) {
                    $this->db->insert('branch_service_prices', [
                        'branch_id' => $bId,
                        'service_id' => $srvId,
                        'price' => $srv['base_price'],
                        'min_order' => $srv['min_order'],
                        'est_hours' => $srv['est_hours'],
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // 9. Addons
        $addons = [
            ['name' => 'Parfum Premium Lavender Sunrise', 'price' => 3000, 'unit_type' => 'kg/order', 'description' => 'Aroma tahan hingga 2 minggu'],
            ['name' => 'Ekstra Anti-Bakterial & Anti-Apek', 'price' => 4000, 'unit_type' => 'kg/order', 'description' => 'Formula disinfektan higienis'],
            ['name' => 'Hanger Plastik Tambahan', 'price' => 2500, 'unit_type' => 'piece', 'description' => 'Hanger baju berkualitas'],
            ['name' => 'Kemasan Tas Zipper Kedap Udara', 'price' => 5000, 'unit_type' => 'package', 'description' => 'Kemasan premium eksklusif'],
        ];

        foreach ($addons as $ad) {
            $exists = $this->db->fetchColumn("SELECT id FROM service_addons WHERE name = :n", [':n' => $ad['name']]);
            if (!$exists) {
                $this->db->insert('service_addons', array_merge($ad, ['company_id' => $companyId, 'is_active' => 1, 'created_at' => $now]));
            }
        }

        // 10. Payment Methods
        $payMethods = [
            ['code' => 'CASH', 'name' => 'Uang Tunai (Cash)', 'category' => 'cash'],
            ['code' => 'BCA',  'name' => 'Transfer Bank BCA', 'category' => 'transfer', 'account_number' => '123-456-7890', 'account_holder' => 'PT LaundryPro Mandiri'],
            ['code' => 'MANDIRI', 'name' => 'Transfer Bank Mandiri', 'category' => 'transfer', 'account_number' => '101-00-1234567-8', 'account_holder' => 'PT LaundryPro Mandiri'],
            ['code' => 'QRIS', 'name' => 'QRIS Realtime / Payment Gateway', 'category' => 'qris', 'config' => json_encode(['provider' => 'mock_or_midtrans', 'midtrans_merchant_id' => 'G123456789'])],
            ['code' => 'DEPOSIT', 'name' => 'Saldo Deposit Pelanggan', 'category' => 'deposit'],
        ];

        foreach ($payMethods as $pm) {
            $exists = $this->db->fetchColumn("SELECT id FROM payment_methods WHERE code = :code", [':code' => $pm['code']]);
            if (!$exists) {
                $this->db->insert('payment_methods', array_merge($pm, ['company_id' => $companyId, 'is_active' => 1, 'created_at' => $now]));
            }
        }

        // 11. Chart of Accounts (COA) for double-entry bookkeeping
        $accounts = [
            ['code' => '1101', 'name' => 'Kas Operasional Pusat', 'type' => 'asset', 'sub_type' => 'cash', 'normal_balance' => 'debit'],
            ['code' => '1102', 'name' => 'Kas Kasir Cabang Pusat', 'type' => 'asset', 'sub_type' => 'cash', 'normal_balance' => 'debit'],
            ['code' => '1103', 'name' => 'Kas Kasir Cabang Barat', 'type' => 'asset', 'sub_type' => 'cash', 'normal_balance' => 'debit'],
            ['code' => '1104', 'name' => 'Kas Kasir Cabang Selatan', 'type' => 'asset', 'sub_type' => 'cash', 'normal_balance' => 'debit'],
            ['code' => '1110', 'name' => 'Bank BCA Operasional', 'type' => 'asset', 'sub_type' => 'bank', 'normal_balance' => 'debit'],
            ['code' => '1111', 'name' => 'Bank Mandiri Giro', 'type' => 'asset', 'sub_type' => 'bank', 'normal_balance' => 'debit'],
            ['code' => '1120', 'name' => 'QRIS Gateway Settlement Clearing', 'type' => 'asset', 'sub_type' => 'clearing', 'normal_balance' => 'debit'],
            ['code' => '1130', 'name' => 'Piutang Usaha Pelanggan', 'type' => 'asset', 'sub_type' => 'receivable', 'normal_balance' => 'debit'],
            ['code' => '1140', 'name' => 'Persediaan Deterjen & Kimia Laundry', 'type' => 'asset', 'sub_type' => 'inventory', 'normal_balance' => 'debit'],
            ['code' => '1150', 'name' => 'Persediaan Perlengkapan & Plastik', 'type' => 'asset', 'sub_type' => 'inventory', 'normal_balance' => 'debit'],
            ['code' => '1201', 'name' => 'Aset Tetap - Mesin Cuci & Dryer', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'normal_balance' => 'debit'],
            ['code' => '1202', 'name' => 'Akumulasi Penyusutan Mesin', 'type' => 'asset', 'sub_type' => 'contra_asset', 'normal_balance' => 'credit'],
            ['code' => '2101', 'name' => 'Utang Usaha Supplier', 'type' => 'liability', 'sub_type' => 'payable', 'normal_balance' => 'credit'],
            ['code' => '2102', 'name' => 'Titipan Saldo Deposit Pelanggan', 'type' => 'liability', 'sub_type' => 'customer_deposit', 'normal_balance' => 'credit'],
            ['code' => '3101', 'name' => 'Modal Disetor Pemilik', 'type' => 'equity', 'sub_type' => 'capital', 'normal_balance' => 'credit'],
            ['code' => '3201', 'name' => 'Laba Ditahan', 'type' => 'equity', 'sub_type' => 'retained_earnings', 'normal_balance' => 'credit'],
            ['code' => '4101', 'name' => 'Pendapatan Laundry Kiloan', 'type' => 'revenue', 'sub_type' => 'sales', 'normal_balance' => 'credit'],
            ['code' => '4102', 'name' => 'Pendapatan Laundry Satuan', 'type' => 'revenue', 'sub_type' => 'sales', 'normal_balance' => 'credit'],
            ['code' => '4103', 'name' => 'Pendapatan Ongkos Pengiriman', 'type' => 'revenue', 'sub_type' => 'delivery', 'normal_balance' => 'credit'],
            ['code' => '4104', 'name' => 'Pendapatan Layanan Tambahan (Addon)', 'type' => 'revenue', 'sub_type' => 'sales', 'normal_balance' => 'credit'],
            ['code' => '5101', 'name' => 'Beban Pemakaian Bahan Kimia Laundry', 'type' => 'expense', 'sub_type' => 'cogs', 'normal_balance' => 'debit'],
            ['code' => '5102', 'name' => 'Beban Plastik, Hanger & Packaging', 'type' => 'expense', 'sub_type' => 'cogs', 'normal_balance' => 'debit'],
            ['code' => '5201', 'name' => 'Beban Gaji & Upah Karyawan', 'type' => 'expense', 'sub_type' => 'operational', 'normal_balance' => 'debit'],
            ['code' => '5202', 'name' => 'Beban Listrik, Air, dan Gas LPG', 'type' => 'expense', 'sub_type' => 'operational', 'normal_balance' => 'debit'],
            ['code' => '5203', 'name' => 'Beban Perawatan & Servis Mesin', 'type' => 'expense', 'sub_type' => 'maintenance', 'normal_balance' => 'debit'],
            ['code' => '5204', 'name' => 'Beban Sewa & Pemeliharaan Gedung', 'type' => 'expense', 'sub_type' => 'operational', 'normal_balance' => 'debit'],
            ['code' => '5205', 'name' => 'Beban Operasional & Kantor', 'type' => 'expense', 'sub_type' => 'operational', 'normal_balance' => 'debit'],
        ];

        $accMap = [];
        foreach ($accounts as $acc) {
            $exists = $this->db->fetch("SELECT id FROM accounts WHERE code = :code", [':code' => $acc['code']]);
            if ($exists) {
                $accMap[$acc['code']] = (int)$exists['id'];
            } else {
                $aid = $this->db->insert('accounts', array_merge($acc, ['company_id' => $companyId, 'created_at' => $now]));
                $accMap[$acc['code']] = $aid;
            }
        }

        // Cash Accounts
        $cashAccounts = [
            ['branch_id' => $branchPusat, 'name' => 'Kas Kasir Pusat', 'account_id' => $accMap['1102'], 'account_type' => 'cash', 'balance' => 1500000],
            ['branch_id' => $branchPusat, 'name' => 'Bank BCA Cabang Pusat', 'account_id' => $accMap['1110'], 'account_type' => 'bank', 'balance' => 25000000],
            ['branch_id' => $branchBarat, 'name' => 'Kas Kasir Barat', 'account_id' => $accMap['1103'], 'account_type' => 'cash', 'balance' => 1000000],
            ['branch_id' => $branchSelatan, 'name' => 'Kas Kasir Selatan', 'account_id' => $accMap['1104'], 'account_type' => 'cash', 'balance' => 1000000],
        ];

        foreach ($cashAccounts as $ca) {
            $exists = $this->db->fetchColumn("SELECT id FROM cash_accounts WHERE branch_id = :b AND name = :n", [':b' => $ca['branch_id'], ':n' => $ca['name']]);
            if (!$exists) {
                $this->db->insert('cash_accounts', array_merge($ca, ['company_id' => $companyId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
            }
        }

        // 12. WhatsApp Templates
        $waTemplates = [
            [
                'trigger_event' => 'order_received',
                'title' => 'Nota Pesanan Diterima',
                'message_template' => "Halo Kak {customer_name},\nPesanan laundry Anda dengan No. Nota *{order_no}* telah kami terima di {branch_name}.\n\nTotal Tagihan: *{grand_total}*\nStatus Bayar: *{payment_status}*\nEstimasi Selesai: *{est_completion}*\n\nPantau status cucian Anda secara online melalui tautan berikut:\n{tracking_url}\n\nTerima kasih telah mempercayakan laundry Anda kepada LaundryPro!",
                'variables' => '{customer_name},{order_no},{branch_name},{grand_total},{payment_status},{est_completion},{tracking_url}',
            ],
            [
                'trigger_event' => 'order_processing',
                'title' => 'Pesanan Sedang Dicuci',
                'message_template' => "Halo Kak {customer_name},\nCucian Anda No. *{order_no}* saat ini sedang dalam proses *{stage_name}* dengan standar kebersihan terbaik kami.\n\nLacak proses: {tracking_url}",
                'variables' => '{customer_name},{order_no},{stage_name},{tracking_url}',
            ],
            [
                'trigger_event' => 'order_ready',
                'title' => 'Pesanan Siap Diambil / Diantar',
                'message_template' => "Kabar gembira Kak {customer_name}!\nPesanan cucian Anda No. *{order_no}* telah selesai dikerjakan, wangi, bersih, dan rapi siap diambil di outlet {branch_name}.\n\nSisa Tagihan: *{balance_due}*\nTerima kasih!",
                'variables' => '{customer_name},{order_no},{branch_name},{balance_due}',
            ],
            [
                'trigger_event' => 'payment_received',
                'title' => 'Konfirmasi Pembayaran Diterima',
                'message_template' => "Terima kasih Kak {customer_name},\nPembayaran sebesar *{paid_amount}* untuk No. Nota *{order_no}* via *{payment_method}* telah kami terima dengan status *LUNAS*.\n\nSisa Tagihan: *{balance_due}*",
                'variables' => '{customer_name},{paid_amount},{order_no},{payment_method},{balance_due}',
            ],
            [
                'trigger_event' => 'delivery_departed',
                'title' => 'Kurir Sedang Menuju Lokasi',
                'message_template' => "Halo Kak {customer_name},\nKurir kami *{courier_name}* (No: {courier_phone}) sedang dalam perjalanan mengantar cucian Anda No. *{order_no}* menuju alamat Anda.\n\nMohon pastikan ada penerima di lokasi.",
                'variables' => '{customer_name},{courier_name},{courier_phone},{order_no}',
            ],
        ];

        foreach ($waTemplates as $wt) {
            $exists = $this->db->fetchColumn("SELECT id FROM whatsapp_templates WHERE company_id = :c AND trigger_event = :t", [':c' => $companyId, ':t' => $wt['trigger_event']]);
            if (!$exists) {
                $this->db->insert('whatsapp_templates', array_merge($wt, ['company_id' => $companyId, 'is_active' => 1, 'updated_at' => $now]));
            }
        }

        // WhatsApp Settings Default (Mock provider for development/offline testing, ready for Official/Fonnte/Wablas)
        $waSettingExists = $this->db->fetchColumn("SELECT id FROM whatsapp_settings WHERE company_id = :c", [':c' => $companyId]);
        if (!$waSettingExists) {
            $this->db->insert('whatsapp_settings', [
                'company_id' => $companyId,
                'provider' => 'mock',
                'api_url' => 'https://api.fonnte.com/send',
                'api_key' => 'DEMO_FONNTE_TOKEN',
                'sender_number' => '081234567890',
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        // 13. Inventory Categories & Items
        $invCats = [
            ['name' => 'Deterjen & Sabun Laundry', 'slug' => 'deterjen'],
            ['name' => 'Pewangi & Pelembut (Softener)', 'slug' => 'pewangi'],
            ['name' => 'Kemasan, Plastik & Hanger', 'slug' => 'kemasan'],
            ['name' => 'Peralatan & Kimia Khusus (Spotting)', 'slug' => 'spotting'],
        ];

        $invCatMap = [];
        foreach ($invCats as $ic) {
            $exists = $this->db->fetch("SELECT id FROM inventory_categories WHERE slug = :s", [':s' => $ic['slug']]);
            $invCatMap[$ic['slug']] = $exists ? (int)$exists['id'] : $this->db->insert('inventory_categories', array_merge($ic, ['company_id' => $companyId, 'created_at' => $now]));
        }

        $invItems = [
            ['category_id' => $invCatMap['deterjen'], 'code' => 'DET-LIQ', 'name' => 'Deterjen Cair Konsentrat Low Foam (Jerrycan 20L)', 'unit' => 'liter', 'min_stock' => 10, 'cost_price' => 15000],
            ['category_id' => $invCatMap['pewangi'],  'code' => 'SOFT-LAV', 'name' => 'Softener & Pewangi Aroma Lavender (Jerrycan 20L)', 'unit' => 'liter', 'min_stock' => 10, 'cost_price' => 18000],
            ['category_id' => $invCatMap['kemasan'],  'code' => 'PLS-35',   'name' => 'Plastik Packing Laundry Ukuran 35x50 (Pack 100 Lembar)', 'unit' => 'pack', 'min_stock' => 5, 'cost_price' => 22000],
            ['category_id' => $invCatMap['kemasan'],  'code' => 'HNG-WIRE', 'name' => 'Hanger Kawat Putih Anti Karat (Pack 50 Pcs)', 'unit' => 'pack', 'min_stock' => 5, 'cost_price' => 35000],
            ['category_id' => $invCatMap['spotting'], 'code' => 'RUST-REM', 'name' => 'Spotting Chemical Pembersih Noda Karat (500ml)', 'unit' => 'botol', 'min_stock' => 2, 'cost_price' => 45000],
        ];

        foreach ($invItems as $item) {
            $exists = $this->db->fetch("SELECT id FROM inventory_items WHERE code = :c", [':c' => $item['code']]);
            $itemId = $exists ? (int)$exists['id'] : $this->db->insert('inventory_items', array_merge($item, ['company_id' => $companyId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));

            // Stock in branch pusat
            $bInvExists = $this->db->fetchColumn("SELECT id FROM branch_inventory WHERE branch_id = :b AND item_id = :i", [':b' => $branchPusat, ':i' => $itemId]);
            if (!$bInvExists) {
                $this->db->insert('branch_inventory', [
                    'branch_id' => $branchPusat,
                    'item_id' => $itemId,
                    'current_stock' => 50,
                    'min_stock' => $item['min_stock'],
                    'updated_at' => $now,
                ]);

                // Record initial stock movement
                $this->db->insert('stock_movements', [
                    'company_id' => $companyId,
                    'branch_id' => $branchPusat,
                    'item_id' => $itemId,
                    'type' => 'in',
                    'quantity' => 50,
                    'stock_before' => 0,
                    'stock_after' => 50,
                    'reference_type' => 'initial_seed',
                    'reference_id' => null,
                    'notes' => 'Saldo awal persediaan sistem',
                    'created_at' => $now,
                ]);
            }
        }

        // 14. Machines
        $mTypes = [
            ['name' => 'Mesin Cuci Komersial Front Load', 'capacity_kg' => 15.0, 'description' => 'Mesin cuci heavy duty'],
            ['name' => 'Mesin Pengering (Dryer) Gas Konversi', 'capacity_kg' => 16.0, 'description' => 'Mesin pengering cepat gas LPG'],
            ['name' => 'Setrika Uap Boiler Gas', 'capacity_kg' => 0.0, 'description' => 'Boiler setrika uap kapasitas 25 liter'],
        ];

        $mTypeMap = [];
        foreach ($mTypes as $mt) {
            $exists = $this->db->fetch("SELECT id FROM machine_types WHERE name = :n", [':n' => $mt['name']]);
            $mTypeMap[$mt['name']] = $exists ? (int)$exists['id'] : $this->db->insert('machine_types', array_merge($mt, ['company_id' => $companyId, 'created_at' => $now]));
        }

        $machines = [
            ['code' => 'MC-PST-01', 'name' => 'Washer Maytag 01 (Pusat)', 'type_id' => $mTypeMap['Mesin Cuci Komersial Front Load'], 'brand' => 'Maytag', 'model' => 'MYT-2024', 'capacity_kg' => 15.0, 'operational_status' => 'idle'],
            ['code' => 'MC-PST-02', 'name' => 'Washer LG Commercial 02 (Pusat)', 'type_id' => $mTypeMap['Mesin Cuci Komersial Front Load'], 'brand' => 'LG Commercial', 'model' => 'LG-Titan-C', 'capacity_kg' => 15.0, 'operational_status' => 'idle'],
            ['code' => 'DR-PST-01', 'name' => 'Dryer Speed Queen 01 (Pusat)', 'type_id' => $mTypeMap['Mesin Pengering (Dryer) Gas Konversi'], 'brand' => 'Speed Queen', 'model' => 'SQ-Gas-16', 'capacity_kg' => 16.0, 'operational_status' => 'idle'],
            ['code' => 'ST-PST-01', 'name' => 'Boiler Steam Nagamoto 01', 'type_id' => $mTypeMap['Setrika Uap Boiler Gas'], 'brand' => 'Nagamoto', 'model' => 'NGM-25L', 'capacity_kg' => 0, 'operational_status' => 'idle'],
        ];

        foreach ($machines as $mc) {
            $exists = $this->db->fetchColumn("SELECT id FROM machines WHERE code = :c", [':c' => $mc['code']]);
            if (!$exists) {
                $this->db->insert('machines', array_merge($mc, [
                    'company_id' => $companyId,
                    'branch_id' => $branchPusat,
                    'purchase_date' => '2024-01-15',
                    'condition' => 'good',
                    'total_cycles' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // 15. Demo Customers
        $demoCust = [
            [
                'code' => 'CUST-0001',
                'name' => 'Anisa Rahmawati',
                'whatsapp' => '081299887766',
                'email' => 'anisa@example.com',
                'address' => 'Apartemen Sudirman Tower Lt. 12 No. 12B, Jakarta Pusat',
                'birth_date' => '1995-05-14',
                'join_date' => '2024-01-10',
                'current_tier_id' => $tierMap['silver'],
                'deposit_balance' => 250000,
                'points_balance' => 120,
            ],
            [
                'code' => 'CUST-0002',
                'name' => 'Bambang Trihatmodjo',
                'whatsapp' => '081377889900',
                'email' => 'bambang@example.com',
                'address' => 'Jl. Senopati Dalam No. 18, Jakarta Selatan',
                'birth_date' => '1988-11-20',
                'join_date' => '2024-02-01',
                'current_tier_id' => $tierMap['gold'],
                'deposit_balance' => 500000,
                'points_balance' => 380,
            ],
            [
                'code' => 'CUST-0003',
                'name' => 'Citra Kirana',
                'whatsapp' => '081912345678',
                'email' => 'citra@example.com',
                'address' => 'Jl. Kebon Jeruk Baru No. 22, Jakarta Barat',
                'birth_date' => '1999-08-05',
                'join_date' => '2024-03-15',
                'current_tier_id' => $tierMap['regular'],
                'deposit_balance' => 0,
                'points_balance' => 45,
            ],
        ];

        foreach ($demoCust as $c) {
            $exists = $this->db->fetch("SELECT id FROM customers WHERE whatsapp = :w", [':w' => $c['whatsapp']]);
            if (!$exists) {
                $cid = $this->db->insert('customers', array_merge($c, [
                    'company_id' => $companyId,
                    'favorite_branch_id' => $branchPusat,
                    'status' => 'active',
                    'allow_marketing' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));

                // Customer account for Customer Portal
                $this->db->insert('customer_accounts', [
                    'customer_id' => $cid,
                    'username' => strtolower(explode(' ', $c['name'])[0]),
                    'password_hash' => $defaultPassword,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Default Address
                $this->db->insert('customer_addresses', [
                    'customer_id' => $cid,
                    'label' => 'Alamat Utama',
                    'recipient_name' => $c['name'],
                    'phone' => $c['whatsapp'],
                    'address' => $c['address'],
                    'is_default' => 1,
                    'created_at' => $now,
                ]);

                // Initial Deposit Record
                if ($c['deposit_balance'] > 0) {
                    $this->db->insert('customer_deposits', [
                        'company_id' => $companyId,
                        'customer_id' => $cid,
                        'balance' => $c['deposit_balance'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $this->db->insert('deposit_transactions', [
                        'customer_id' => $cid,
                        'branch_id' => $branchPusat,
                        'order_id' => null,
                        'type' => 'credit',
                        'amount' => $c['deposit_balance'],
                        'balance_after' => $c['deposit_balance'],
                        'description' => 'Top Up Saldo Deposit Awal',
                        'reference_no' => 'DEP-INIT-' . $cid,
                        'created_at' => $now,
                    ]);
                }
            }
        }

        // 16. Vouchers
        $vouchers = [
            [
                'code' => 'BERSIHHEBAT',
                'name' => 'Diskon Pembukaan 10%',
                'type' => 'percent',
                'value' => 10,
                'min_spend' => 30000,
                'max_discount' => 15000,
                'quota' => 100,
                'used_count' => 0,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+90 days')),
                'is_active' => 1,
            ],
            [
                'code' => 'HEMAT20K',
                'name' => 'Potongan Langsung Rp 20.000',
                'type' => 'fixed',
                'value' => 20000,
                'min_spend' => 100000,
                'max_discount' => 20000,
                'quota' => 50,
                'used_count' => 0,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+60 days')),
                'is_active' => 1,
            ],
        ];

        foreach ($vouchers as $v) {
            $exists = $this->db->fetchColumn("SELECT id FROM vouchers WHERE code = :c", [':c' => $v['code']]);
            if (!$exists) {
                $this->db->insert('vouchers', array_merge($v, ['company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now]));
            }
        }

        // 17. Receipt Settings
        $rcptExists = $this->db->fetchColumn("SELECT id FROM receipt_settings WHERE company_id = :c", [':c' => $companyId]);
        if (!$rcptExists) {
            $this->db->insert('receipt_settings', [
                'company_id' => $companyId,
                'branch_id' => null,
                'paper_size' => '58mm',
                'header_text' => "LAUNDRYPRO INDONESIA\nBersih, Wangi, Higienis & Tepat Waktu",
                'footer_text' => "Barang yang tidak diambil dalam 30 hari di luar tanggung jawab kami.\nKomplain maksimal 1x24 jam sejak barang diserahkan disertai nota.",
                'show_logo' => 1,
                'show_qr' => 1,
                'show_barcode' => 0,
                'show_notes' => 1,
                'updated_at' => $now,
            ]);
        }

        // 18. Suppliers
        $suppliers = [
            ['code' => 'SUPP-01', 'name' => 'PT Harum Kimia Pratama', 'phone' => '021-5551234', 'email' => 'sales@harumkimia.com', 'address' => 'Kawasan Industri Pulogadung, Jakarta Timur', 'contact_person' => 'Irfan Hakim'],
            ['code' => 'SUPP-02', 'name' => 'CV Sumber Plastik Makmur', 'phone' => '021-6667890', 'email' => 'order@sumberplastik.id', 'address' => 'Jl. Daan Mogot KM 12, Jakarta Barat', 'contact_person' => 'Hendri'],
        ];

        foreach ($suppliers as $sp) {
            $exists = $this->db->fetchColumn("SELECT id FROM suppliers WHERE code = :c", [':c' => $sp['code']]);
            if (!$exists) {
                $this->db->insert('suppliers', array_merge($sp, ['company_id' => $companyId, 'created_at' => $now, 'updated_at' => $now]));
            }
        }

        // 19. Initial System Settings
        $appSettings = [
            ['key' => 'app_name', 'value' => 'LaundryPro', 'group_name' => 'general', 'description' => 'Nama Aplikasi'],
            ['key' => 'app_tagline', 'value' => 'Smart Laundry Business Management System', 'group_name' => 'general', 'description' => 'Tagline Aplikasi'],
            ['key' => 'enable_qris_gateway', 'value' => '1', 'group_name' => 'payment', 'description' => 'Aktifkan Gateway QRIS'],
            ['key' => 'enable_whatsapp_notifications', 'value' => '1', 'group_name' => 'whatsapp', 'description' => 'Aktifkan Otomatisasi Notifikasi WhatsApp'],
            ['key' => 'tax_rate_percent', 'value' => '0', 'group_name' => 'pos', 'description' => 'Pajak Transaksi (%)'],
        ];

        foreach ($appSettings as $as) {
            $exists = $this->db->fetchColumn("SELECT id FROM app_settings WHERE `key` = :k", [':k' => $as['key']]);
            if (!$exists) {
                $this->db->insert('app_settings', array_merge($as, ['updated_at' => $now]));
            }
        }

        // 20. Expense Categories
        $categories = [
            ['name' => 'Bahan Kimia & Deterjen', 'account_id' => 21, 'description' => 'Pembelian deterjen, softener, dan spotting chemical'],
            ['name' => 'Plastik & Kemasan', 'account_id' => 22, 'description' => 'Plastik packing, hanger, dan selotip'],
            ['name' => 'Gaji & Upah Karyawan', 'account_id' => 23, 'description' => 'Gaji bulanan dan upah harian'],
            ['name' => 'Listrik, Air & Gas LPG', 'account_id' => 24, 'description' => 'Tagihan PLN, PDAM, dan tabung gas pengering'],
            ['name' => 'Servis & Perawatan Mesin', 'account_id' => 25, 'description' => 'Perbaikan mesin cuci, dryer, dan boiler setrika'],
            ['name' => 'Sewa Gedung & Fasilitas', 'account_id' => 26, 'description' => 'Sewa ruko atau tempat usaha'],
            ['name' => 'Operasional & Perlengkapan Kantor', 'account_id' => 27, 'description' => 'Kertas nota thermal, ATK, dan kebersihan'],
        ];

        foreach ($categories as $cat) {
            $exists = $this->db->fetchColumn("SELECT id FROM expense_categories WHERE name = :n", [':n' => $cat['name']]);
            if (!$exists) {
                $this->db->insert('expense_categories', [
                    'company_id' => $companyId,
                    'name' => $cat['name'],
                    'account_id' => $cat['account_id'],
                    'description' => $cat['description'],
                    'is_active' => 1,
                    'created_at' => $now,
                ]);
            }
        }
    }
}
