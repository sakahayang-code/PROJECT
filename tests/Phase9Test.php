<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Inventory\InventoryController;
use App\Modules\Machines\MachineController;

class Phase9Test
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "=== Running Phase 9 Tests: Inventaris & Mesin Operasional ===\n";

        $this->testMasterItemAndBranchStock();
        $this->testStockAdjustmentAndMovements();
        $this->testStockOpnameAndDiscrepancy();
        $this->testInterBranchStockTransfer();
        $this->testMachineManagementAndMaintenance();

        echo "=== All Phase 9 Tests Passed Successfully! ===\n\n";
    }

    private function testMasterItemAndBranchStock(): void
    {
        echo "Testing Master Item Creation & Branch Stock Auto-Setup... ";

        Session::start();
        $admin = $this->db->fetch("SELECT * FROM users WHERE username = 'superadmin'");
        Auth::login($admin);

        $cat = $this->db->fetch("SELECT * FROM inventory_categories LIMIT 1");
        $code = 'TEST-SKU-' . rand(100, 999);

        $_POST = [
            'code' => $code,
            'name' => 'Deterjen Ramah Lingkungan Test',
            'category_id' => (string)$cat['id'],
            'unit' => 'Liter',
            'min_stock' => '10',
            'cost_price' => '18.500',
            'description' => 'Formula eco-friendly konsentrat',
        ];

        ob_start();
        $controller = new InventoryController();
        $controller->storeItem();
        ob_end_clean();

        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE code = :c", [':c' => $code]);
        if (!$item) {
            throw new \Exception("Inventory item was not created in database.");
        }
        if ((int)$item['cost_price'] !== 18500 || (float)$item['min_stock'] !== 10.0) {
            throw new \Exception("Item attributes mismatch.");
        }

        // Verify stock records initialized for each branch
        $branchCount = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM branches");
        $invCount = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM branch_inventory WHERE item_id = :id", [':id' => $item['id']]);
        if ($invCount !== $branchCount) {
            throw new \Exception("Branch inventory not initialized for all branches. Expected {$branchCount}, got {$invCount}");
        }

        echo "PASS\n";
    }

    private function testStockAdjustmentAndMovements(): void
    {
        echo "Testing Stock In/Out Adjustment & Movements Ledger... ";

        $item = $this->db->fetch("SELECT * FROM inventory_items ORDER BY id DESC LIMIT 1");
        $branchId = 1;

        // 1. Stock In: Add 25 liters
        $_POST = [
            'branch_id' => (string)$branchId,
            'item_id' => (string)$item['id'],
            'type' => 'in',
            'quantity' => '25.0',
            'notes' => 'Pembelian stok awal dari supplier',
        ];

        ob_start();
        $controller = new InventoryController();
        $controller->updateStock();
        ob_end_clean();

        $invAfterIn = $this->db->fetch(
            "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $item['id']]
        );
        if ((float)$invAfterIn['current_stock'] !== 25.0) {
            throw new \Exception("Stock in calculation mismatch. Expected 25.0, got: " . $invAfterIn['current_stock']);
        }

        // Check movement record
        $movIn = $this->db->fetch(
            "SELECT * FROM stock_movements WHERE branch_id = :b AND item_id = :i AND type = 'in' ORDER BY id DESC LIMIT 1",
            [':b' => $branchId, ':i' => $item['id']]
        );
        if (!$movIn || (float)$movIn['quantity'] !== 25.0 || (float)$movIn['stock_after'] !== 25.0) {
            throw new \Exception("Stock movement 'in' log was not properly created.");
        }

        // 2. Stock Out: Deduct 5 liters
        $_POST = [
            'branch_id' => (string)$branchId,
            'item_id' => (string)$item['id'],
            'type' => 'out',
            'quantity' => '5.0',
            'notes' => 'Pemakaian harian cucian kiloan',
        ];

        ob_start();
        $controller->updateStock();
        ob_end_clean();

        $invAfterOut = $this->db->fetch(
            "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $item['id']]
        );
        if ((float)$invAfterOut['current_stock'] !== 20.0) {
            throw new \Exception("Stock out calculation mismatch. Expected 20.0, got: " . $invAfterOut['current_stock']);
        }

        echo "PASS\n";
    }

    private function testStockOpnameAndDiscrepancy(): void
    {
        echo "Testing Stock Opname & Discrepancy Adjustment... ";

        $item = $this->db->fetch("SELECT * FROM inventory_items ORDER BY id DESC LIMIT 1");
        $branchId = 1;

        // Current system stock is 20.0. Physical count shows 18.5 (selisih -1.5).
        $_POST = [
            'branch_id' => (string)$branchId,
            'item_id' => (string)$item['id'],
            'physical_stock' => '18.5',
            'reason' => 'Tumpah 1.5 Liter saat penuangan ke dispenser mesin',
        ];

        ob_start();
        $controller = new InventoryController();
        $controller->storeOpname();
        ob_end_clean();

        $invAfterOpname = $this->db->fetch(
            "SELECT * FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $item['id']]
        );
        if ((float)$invAfterOpname['current_stock'] !== 18.5) {
            throw new \Exception("Stock was not adjusted to physical stock. Expected 18.5, got: " . $invAfterOpname['current_stock']);
        }

        // Verify opname item
        $opItem = $this->db->fetch(
            "SELECT * FROM stock_opname_items WHERE item_id = :i ORDER BY id DESC LIMIT 1",
            [':i' => $item['id']]
        );
        if (!$opItem || (float)$opItem['difference'] !== -1.5) {
            throw new \Exception("Opname discrepancy calculation mismatch. Expected -1.5, got: " . ($opItem['difference'] ?? 'null'));
        }

        echo "PASS\n";
    }

    private function testInterBranchStockTransfer(): void
    {
        echo "Testing Inter-Branch Stock Transfer (Send & Receive)... ";

        $item = $this->db->fetch("SELECT * FROM inventory_items ORDER BY id DESC LIMIT 1");
        $fromBranch = 1;
        $toBranch = 2;

        $fromStockBefore = (float)$this->db->fetchColumn(
            "SELECT current_stock FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $fromBranch, ':i' => $item['id']]
        );
        $toStockBefore = (float)$this->db->fetchColumn(
            "SELECT current_stock FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $toBranch, ':i' => $item['id']]
        );

        $transferQty = 5.0;

        // 1. Send Transfer
        $_POST = [
            'from_branch_id' => (string)$fromBranch,
            'to_branch_id' => (string)$toBranch,
            'item_id' => (string)$item['id'],
            'quantity' => (string)$transferQty,
            'notes' => 'Bantuan stok deterjen untuk outlet Barat',
        ];

        ob_start();
        $controller = new InventoryController();
        $controller->storeTransfer();
        ob_end_clean();

        // Check source stock deducted immediately
        $fromStockAfter = (float)$this->db->fetchColumn(
            "SELECT current_stock FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $fromBranch, ':i' => $item['id']]
        );
        if ($fromStockAfter !== ($fromStockBefore - $transferQty)) {
            throw new \Exception("Source branch stock was not deducted on transfer dispatch.");
        }

        $trf = $this->db->fetch(
            "SELECT * FROM stock_transfers WHERE from_branch_id = :fb AND to_branch_id = :tb ORDER BY id DESC LIMIT 1",
            [':fb' => $fromBranch, ':tb' => $toBranch]
        );
        if (!$trf || $trf['status'] !== 'pending') {
            throw new \Exception("Transfer record was not created with 'pending' status.");
        }

        // 2. Receive Transfer
        $_POST = ['transfer_id' => (string)$trf['id']];
        ob_start();
        $controller->receiveTransfer();
        ob_end_clean();

        // Check destination stock increased
        $toStockAfter = (float)$this->db->fetchColumn(
            "SELECT current_stock FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $toBranch, ':i' => $item['id']]
        );
        if ($toStockAfter !== ($toStockBefore + $transferQty)) {
            throw new \Exception("Destination branch stock was not credited on transfer receive.");
        }

        $updatedTrf = $this->db->fetch("SELECT * FROM stock_transfers WHERE id = :id", [':id' => $trf['id']]);
        if ($updatedTrf['status'] !== 'received') {
            throw new \Exception("Transfer status should be 'received', got: " . $updatedTrf['status']);
        }

        echo "PASS\n";
    }

    private function testMachineManagementAndMaintenance(): void
    {
        echo "Testing Machine Management, Servicing & Fault Reporting... ";

        $branch = $this->db->fetch("SELECT * FROM branches WHERE id = 1");
        $type = $this->db->fetch("SELECT * FROM machine_types LIMIT 1");
        $machineCode = 'MCH-TEST-' . rand(100, 999);

        // 1. Create Machine
        $_POST = [
            'code' => $machineCode,
            'name' => 'Mesin Cuci Heavy Duty Test',
            'branch_id' => (string)$branch['id'],
            'type_id' => (string)$type['id'],
            'brand' => 'LG Commercial',
            'model' => 'Giant C Max',
            'capacity_kg' => '13.0',
            'serial_number' => 'LG-SN-' . uniqid(),
            'purchase_date' => date('Y-m-d'),
        ];

        ob_start();
        $mchController = new MachineController();
        $mchController->storeMachine();
        ob_end_clean();

        $machine = $this->db->fetch("SELECT * FROM machines WHERE code = :c", [':c' => $machineCode]);
        if (!$machine || (float)$machine['capacity_kg'] !== 13.0 || $machine['operational_status'] !== 'idle') {
            throw new \Exception("Machine creation failed or attributes mismatch.");
        }

        // 2. Schedule Maintenance
        $_POST = [
            'machine_id' => (string)$machine['id'],
            'title' => 'Servis Berkala & Pembersihan Saluran Pembuangan',
            'maintenance_type' => 'repair',
            'scheduled_date' => date('Y-m-d'),
            'cost' => '150.000',
            'technician_name' => 'Pak Joko Servis',
            'notes' => 'Pembersihan kerak kalsium',
        ];

        ob_start();
        $mchController->storeMaintenance();
        ob_end_clean();

        $maint = $this->db->fetch("SELECT * FROM machine_maintenance WHERE machine_id = :id ORDER BY id DESC LIMIT 1", [':id' => $machine['id']]);
        if (!$maint || $maint['status'] !== 'scheduled') {
            throw new \Exception("Maintenance was not scheduled.");
        }

        // Machine status should reflect maintenance
        $mchUnderMaint = $this->db->fetch("SELECT * FROM machines WHERE id = :id", [':id' => $machine['id']]);
        if ($mchUnderMaint['operational_status'] !== 'maintenance') {
            throw new \Exception("Machine operational status was not set to 'maintenance'.");
        }

        // 3. Complete Maintenance
        $_POST = [
            'maintenance_id' => (string)$maint['id'],
            'cost' => '150.000',
            'action_taken' => 'Ganti selang pembuangan dan pembersihan drum tuntas',
            'parts_replaced' => 'Selang 1.5 meter',
        ];

        ob_start();
        $mchController->completeMaintenance();
        ob_end_clean();

        $mchReady = $this->db->fetch("SELECT * FROM machines WHERE id = :id", [':id' => $machine['id']]);
        if ($mchReady['operational_status'] !== 'idle' || $mchReady['condition'] !== 'good') {
            throw new \Exception("Machine operational status was not restored to 'idle'.");
        }

        // 4. Report Fault
        $_POST = [
            'machine_id' => (string)$machine['id'],
            'severity' => 'high',
            'description' => 'Getaran kuat saat spin cycle pengeringan',
        ];

        ob_start();
        $mchController->reportFault();
        ob_end_clean();

        $fault = $this->db->fetch("SELECT * FROM machine_faults WHERE machine_id = :id ORDER BY id DESC LIMIT 1", [':id' => $machine['id']]);
        if (!$fault || $fault['severity'] !== 'high') {
            throw new \Exception("Machine fault was not recorded.");
        }

        $mchDamaged = $this->db->fetch("SELECT * FROM machines WHERE id = :id", [':id' => $machine['id']]);
        if ($mchDamaged['condition'] !== 'needs_repair') {
            throw new \Exception("Machine condition was not updated to 'needs_repair'.");
        }

        echo "PASS\n";
    }
}

$test = new Phase9Test();
$test->runAll();
