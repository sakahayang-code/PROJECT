<?php

declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Core\Validator;
use App\Helpers\NumberGenerator;

class InventoryController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('inventory.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $catFilter = (int)($_GET['category_id'] ?? 0);
        $search = trim($_GET['search'] ?? '');

        $where = "1=1";
        $params = [];

        if ($branchFilter > 0) {
            $where .= " AND bi.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND bi.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        if ($catFilter > 0) {
            $where .= " AND ii.category_id = :cat";
            $params[':cat'] = $catFilter;
        }

        if (!empty($search)) {
            $where .= " AND (ii.name LIKE :s OR ii.code LIKE :s)";
            $params[':s'] = "%{$search}%";
        }

        $stockItems = $this->db()->fetchAll(
            "SELECT bi.*, ii.code, ii.name, ii.unit, ii.cost_price, ic.name AS category_name, b.name AS branch_name
             FROM branch_inventory bi
             JOIN inventory_items ii ON bi.item_id = ii.id
             JOIN inventory_categories ic ON ii.category_id = ic.id
             JOIN branches b ON bi.branch_id = b.id
             WHERE {$where}
             ORDER BY b.id ASC, (bi.current_stock <= bi.min_stock) DESC, ii.name ASC",
            $params
        );

        $categories = $this->db()->fetchAll("SELECT * FROM inventory_categories ORDER BY name ASC");
        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $items = $this->db()->fetchAll("SELECT id, code, name, unit FROM inventory_items WHERE is_active = 1 ORDER BY name ASC");

        $stats = [
            'total_sku' => (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1"),
            'low_stock' => count(array_filter($stockItems, fn($i) => $i['current_stock'] <= $i['min_stock'])),
            'total_items' => count($stockItems),
            'suppliers' => (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM suppliers"),
        ];

        $this->view('inventory.index', [
            'title' => 'Stok & Inventaris Barang',
            'activeMenu' => 'inventory_stock',
            'stockItems' => $stockItems,
            'categories' => $categories,
            'branches' => $branches,
            'items' => $items,
            'stats' => $stats,
            'selectedBranch' => $branchFilter,
            'selectedCategory' => $catFilter,
            'search' => $search,
        ]);
    }

    public function storeItem(): void
    {
        Permission::abortUnless('inventory.create');

        $v = Validator::make($_POST, [
            'name' => 'required',
            'category_id' => 'required',
            'unit' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (empty($code)) {
            $code = 'INV-' . strtoupper(substr(uniqid(), -6));
        }

        $name = trim($_POST['name']);
        $catId = (int)$_POST['category_id'];
        $unit = trim($_POST['unit']);
        $minStock = (float)($_POST['min_stock'] ?? 5);
        $costPrice = (int)str_replace(['.', ','], '', $_POST['cost_price'] ?? '0');
        $desc = trim($_POST['description'] ?? '');
        $companyId = Auth::companyId() ?: 1;
        $now = date('Y-m-d H:i:s');

        $this->db()->transaction(function() use ($companyId, $catId, $code, $name, $unit, $minStock, $costPrice, $desc, $now) {
            $itemId = $this->db()->insert('inventory_items', [
                'company_id' => $companyId,
                'category_id' => $catId,
                'code' => $code,
                'name' => $name,
                'unit' => $unit,
                'min_stock' => $minStock,
                'cost_price' => $costPrice,
                'description' => $desc ?: null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Initialize stock for all branches
            $branches = $this->db()->fetchAll("SELECT id FROM branches");
            foreach ($branches as $b) {
                $this->db()->insert('branch_inventory', [
                    'branch_id' => $b['id'],
                    'item_id' => $itemId,
                    'current_stock' => 0,
                    'min_stock' => $minStock,
                    'updated_at' => $now,
                ]);
            }
        });

        $this->back("Barang inventaris {$name} ({$code}) berhasil ditambahkan.", 'success');
    }

    public function updateStock(): void
    {
        Permission::abortUnless('inventory.edit');

        $branchId = (int)($_POST['branch_id'] ?? (Auth::getActiveBranchId() ?: 1));
        $itemId = (int)($_POST['item_id'] ?? 0);
        $type = in_array($_POST['type'], ['in', 'out']) ? $_POST['type'] : 'in';
        $qty = abs((float)($_POST['quantity'] ?? 0));
        $notes = trim($_POST['notes'] ?? 'Penyesuaian stok manual');

        if ($itemId <= 0 || $qty <= 0) {
            $this->back('Pilih barang dan jumlah stok yang valid.', 'error');
        }

        $inv = $this->db()->fetch(
            "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $itemId]
        );

        if (!$inv) {
            $now = date('Y-m-d H:i:s');
            $this->db()->insert('branch_inventory', [
                'branch_id' => $branchId,
                'item_id' => $itemId,
                'current_stock' => 0,
                'min_stock' => 5,
                'updated_at' => $now,
            ]);
            $inv = $this->db()->fetch("SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i", [':b' => $branchId, ':i' => $itemId]);
        }

        $stockBefore = (float)$inv['current_stock'];
        if ($type === 'out' && $stockBefore < $qty) {
            $this->back("Stok tidak mencukupi (Tersedia: {$stockBefore}).", 'error');
        }

        $stockAfter = ($type === 'in') ? ($stockBefore + $qty) : ($stockBefore - $qty);
        $now = date('Y-m-d H:i:s');
        $companyId = Auth::companyId() ?: 1;

        $this->db()->transaction(function() use ($companyId, $branchId, $itemId, $inv, $type, $qty, $stockBefore, $stockAfter, $notes, $now) {
            $this->db()->update('branch_inventory', [
                'current_stock' => $stockAfter,
                'updated_at' => $now,
            ], 'id = :id', [':id' => $inv['id']]);

            $this->db()->insert('stock_movements', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'item_id' => $itemId,
                'type' => $type,
                'quantity' => $qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => $notes,
                'user_id' => Auth::id(),
                'created_at' => $now,
            ]);
        });

        $this->back("Stok berhasil diperbarui. Stok akhir: {$stockAfter}.", 'success');
    }

    public function movements(): void
    {
        Permission::abortUnless('inventory.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $typeFilter = trim($_GET['type'] ?? '');
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $where = "sm.created_at >= :s AND sm.created_at <= :e";
        $params = [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59'];

        if ($branchFilter > 0) {
            $where .= " AND sm.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND sm.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        if (!empty($typeFilter)) {
            $where .= " AND sm.type = :t";
            $params[':t'] = $typeFilter;
        }

        $movements = $this->db()->fetchAll(
            "SELECT sm.*, ii.code AS item_code, ii.name AS item_name, ii.unit,
                    b.name AS branch_name, u.name AS user_name
             FROM stock_movements sm
             JOIN inventory_items ii ON sm.item_id = ii.id
             JOIN branches b ON sm.branch_id = b.id
             LEFT JOIN users u ON sm.user_id = u.id
             WHERE {$where}
             ORDER BY sm.id DESC
             LIMIT 100",
            $params
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $this->view('inventory.movements', [
            'title' => 'Riwayat Mutasi & Pergerakan Stok',
            'activeMenu' => 'inventory_movements',
            'movements' => $movements,
            'branches' => $branches,
            'selectedBranch' => $branchFilter,
            'selectedType' => $typeFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function opname(): void
    {
        Permission::abortUnless('inventory.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));

        $where = "1=1";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND so.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $opnames = $this->db()->fetchAll(
            "SELECT so.*, b.name AS branch_name, u.name AS conducted_by_name,
                    COUNT(soi.id) AS total_items,
                    SUM(ABS(soi.difference)) AS total_discrepancy
             FROM stock_opnames so
             JOIN branches b ON so.branch_id = b.id
             LEFT JOIN users u ON so.conducted_by = u.id
             LEFT JOIN stock_opname_items soi ON soi.opname_id = so.id
             WHERE {$where}
             GROUP BY so.id
             ORDER BY so.id DESC",
            $params
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $items = $this->db()->fetchAll("SELECT id, code, name, unit FROM inventory_items WHERE is_active = 1 ORDER BY name ASC");

        $this->view('inventory.opname', [
            'title' => 'Stok Opname (Penyesuaian Fisik)',
            'activeMenu' => 'inventory_opname',
            'opnames' => $opnames,
            'branches' => $branches,
            'items' => $items,
            'selectedBranch' => $branchFilter,
        ]);
    }

    public function storeOpname(): void
    {
        Permission::abortUnless('inventory.edit');

        $branchId = (int)($_POST['branch_id'] ?? (Auth::getActiveBranchId() ?: 1));
        $itemId = (int)($_POST['item_id'] ?? 0);
        $physicalStock = (float)($_POST['physical_stock'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Stok opname rutin');

        if ($itemId <= 0 || $physicalStock < 0) {
            $this->back('Pilih barang dan jumlah stok fisik valid.', 'error');
        }

        $branch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $branchId]);
        $inv = $this->db()->fetch(
            "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $itemId]
        );

        $systemStock = $inv ? (float)$inv['current_stock'] : 0.0;
        $difference = $physicalStock - $systemStock;
        $now = date('Y-m-d H:i:s');
        $opnameNo = NumberGenerator::generateOpnameNumber($branchId, $branch['code']);

        $this->db()->transaction(function() use ($branch, $branchId, $itemId, $inv, $systemStock, $physicalStock, $difference, $reason, $opnameNo, $now) {
            $opnameId = $this->db()->insert('stock_opnames', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'opname_no' => $opnameNo,
                'opname_date' => date('Y-m-d'),
                'status' => 'completed',
                'conducted_by' => Auth::id(),
                'notes' => $reason,
                'created_at' => $now,
            ]);

            $this->db()->insert('stock_opname_items', [
                'opname_id' => $opnameId,
                'item_id' => $itemId,
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'difference' => $difference,
                'reason' => $reason,
                'created_at' => $now,
            ]);

            // Update physical stock into branch inventory
            if ($inv) {
                $this->db()->update('branch_inventory', [
                    'current_stock' => $physicalStock,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $inv['id']]);
            } else {
                $this->db()->insert('branch_inventory', [
                    'branch_id' => $branchId,
                    'item_id' => $itemId,
                    'current_stock' => $physicalStock,
                    'min_stock' => 5,
                    'updated_at' => $now,
                ]);
            }

            // Record stock movement
            $this->db()->insert('stock_movements', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'item_id' => $itemId,
                'type' => 'opname',
                'quantity' => abs($difference),
                'stock_before' => $systemStock,
                'stock_after' => $physicalStock,
                'reference_type' => 'opname',
                'reference_id' => $opnameId,
                'notes' => "Opname {$opnameNo}: Selisih {$difference} ({$reason})",
                'user_id' => Auth::id(),
                'created_at' => $now,
            ]);
        });

        $this->back("Stok opname {$opnameNo} berhasil disimpan. Selisih: {$difference}.", 'success');
    }

    public function transfers(): void
    {
        Permission::abortUnless('inventory.view');

        $transfers = $this->db()->fetchAll(
            "SELECT st.*, fb.name AS from_branch_name, tb.name AS to_branch_name, u.name AS requester_name
             FROM stock_transfers st
             JOIN branches fb ON st.from_branch_id = fb.id
             JOIN branches tb ON st.to_branch_id = tb.id
             LEFT JOIN users u ON st.requested_by = u.id
             ORDER BY st.id DESC"
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $items = $this->db()->fetchAll("SELECT id, code, name, unit FROM inventory_items WHERE is_active = 1 ORDER BY name ASC");

        $this->view('inventory.transfers', [
            'title' => 'Transfer Stok Antar Cabang',
            'activeMenu' => 'inventory_transfers',
            'transfers' => $transfers,
            'branches' => $branches,
            'items' => $items,
        ]);
    }

    public function storeTransfer(): void
    {
        Permission::abortUnless('inventory.edit');

        $fromBranchId = (int)$_POST['from_branch_id'];
        $toBranchId = (int)$_POST['to_branch_id'];
        $itemId = (int)$_POST['item_id'];
        $qty = abs((float)($_POST['quantity'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');

        if ($fromBranchId <= 0 || $toBranchId <= 0 || $fromBranchId === $toBranchId) {
            $this->back('Pilih cabang asal dan tujuan yang berbeda.', 'error');
        }

        if ($itemId <= 0 || $qty <= 0) {
            $this->back('Pilih barang dan jumlah transfer yang valid.', 'error');
        }

        $fromInv = $this->db()->fetch(
            "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $fromBranchId, ':i' => $itemId]
        );

        if (!$fromInv || (float)$fromInv['current_stock'] < $qty) {
            $avail = $fromInv ? $fromInv['current_stock'] : 0;
            $this->back("Stok cabang asal tidak mencukupi (Tersedia: {$avail}).", 'error');
        }

        $fromBranch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $fromBranchId]);
        $toBranch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $toBranchId]);
        $now = date('Y-m-d H:i:s');
        $trfNo = NumberGenerator::generateTransferNumber($fromBranchId, $fromBranch['code']);

        $this->db()->transaction(function() use ($fromBranch, $toBranch, $fromBranchId, $toBranchId, $itemId, $fromInv, $qty, $notes, $trfNo, $now) {
            // Create transfer header
            $trfId = $this->db()->insert('stock_transfers', [
                'company_id' => $fromBranch['company_id'],
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'transfer_no' => $trfNo,
                'status' => 'pending',
                'requested_by' => Auth::id(),
                'sent_date' => date('Y-m-d'),
                'notes' => $notes ?: null,
                'created_at' => $now,
            ]);

            // Create transfer item
            $this->db()->insert('stock_transfer_items', [
                'transfer_id' => $trfId,
                'item_id' => $itemId,
                'quantity' => $qty,
                'received_qty' => 0,
                'notes' => $notes,
                'created_at' => $now,
            ]);

            // Deduct stock immediately from source branch
            $newFromStock = (float)$fromInv['current_stock'] - $qty;
            $this->db()->update('branch_inventory', ['current_stock' => $newFromStock, 'updated_at' => $now], 'id = :id', [':id' => $fromInv['id']]);

            // Record movement transfer_out
            $this->db()->insert('stock_movements', [
                'company_id' => $fromBranch['company_id'],
                'branch_id' => $fromBranchId,
                'item_id' => $itemId,
                'type' => 'transfer_out',
                'quantity' => $qty,
                'stock_before' => (float)$fromInv['current_stock'],
                'stock_after' => $newFromStock,
                'reference_type' => 'transfer',
                'reference_id' => $trfId,
                'notes' => "Transfer ke {$toBranch['name']} ({$trfNo})",
                'user_id' => Auth::id(),
                'created_at' => $now,
            ]);
        });

        $this->back("Permintaan transfer {$trfNo} berhasil dibuat dan stok cabang asal telah dikurangi.", 'success');
    }

    public function receiveTransfer(): void
    {
        Permission::abortUnless('inventory.edit');

        $transferId = (int)($_POST['transfer_id'] ?? 0);
        $transfer = $this->db()->fetch("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $transferId]);

        if (!$transfer || $transfer['status'] !== 'pending') {
            $this->back('Data transfer tidak ditemukan atau sudah diproses.', 'error');
        }

        $items = $this->db()->fetchAll("SELECT * FROM stock_transfer_items WHERE transfer_id = :id", [':id' => $transferId]);
        $now = date('Y-m-d H:i:s');

        $this->db()->transaction(function() use ($transfer, $transferId, $items, $now) {
            foreach ($items as $ti) {
                $toInv = $this->db()->fetch(
                    "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
                    [':b' => $transfer['to_branch_id'], ':i' => $ti['item_id']]
                );

                $stockBefore = $toInv ? (float)$toInv['current_stock'] : 0.0;
                $stockAfter = $stockBefore + (float)$ti['quantity'];

                if ($toInv) {
                    $this->db()->update('branch_inventory', [
                        'current_stock' => $stockAfter,
                        'updated_at' => $now,
                    ], 'id = :id', [':id' => $toInv['id']]);
                } else {
                    $this->db()->insert('branch_inventory', [
                        'branch_id' => $transfer['to_branch_id'],
                        'item_id' => $ti['item_id'],
                        'current_stock' => $stockAfter,
                        'min_stock' => 5,
                        'updated_at' => $now,
                    ]);
                }

                // Update transfer item received qty
                $this->db()->update('stock_transfer_items', ['received_qty' => $ti['quantity']], 'id = :id', [':id' => $ti['id']]);

                // Record movement transfer_in
                $this->db()->insert('stock_movements', [
                    'company_id' => $transfer['company_id'],
                    'branch_id' => $transfer['to_branch_id'],
                    'item_id' => $ti['item_id'],
                    'type' => 'transfer_in',
                    'quantity' => (float)$ti['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => 'transfer',
                    'reference_id' => $transferId,
                    'notes' => "Penerimaan transfer {$transfer['transfer_no']}",
                    'user_id' => Auth::id(),
                    'created_at' => $now,
                ]);
            }

            $this->db()->update('stock_transfers', [
                'status' => 'received',
                'received_date' => date('Y-m-d'),
            ], 'id = :id', [':id' => $transferId]);
        });

        $this->back("Transfer {$transfer['transfer_no']} berhasil diterima dan stok cabang tujuan bertambah.", 'success');
    }

    public function suppliers(): void
    {
        Permission::abortUnless('inventory.view');

        $suppliers = $this->db()->fetchAll(
            "SELECT s.*, COUNT(po.id) AS total_po
             FROM suppliers s
             LEFT JOIN purchase_orders po ON po.supplier_id = s.id
             GROUP BY s.id
             ORDER BY s.name ASC"
        );

        $this->view('inventory.suppliers', [
            'title' => 'Pemasok & Supplier',
            'activeMenu' => 'inventory_suppliers',
            'suppliers' => $suppliers,
        ]);
    }

    public function storeSupplier(): void
    {
        Permission::abortUnless('inventory.create');

        $v = Validator::make($_POST, [
            'name' => 'required',
            'code' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $code = strtoupper(trim($_POST['code']));
        $exists = $this->db()->fetchColumn("SELECT id FROM suppliers WHERE code = :c", [':c' => $code]);
        if ($exists) {
            $this->back('Kode supplier sudah terdaftar.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->insert('suppliers', [
            'company_id' => Auth::companyId() ?: 1,
            'code' => $code,
            'name' => trim($_POST['name']),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->back("Supplier {$code} berhasil ditambahkan.", 'success');
    }
}
