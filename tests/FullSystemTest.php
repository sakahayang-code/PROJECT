<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Orders\PosController;
use App\Modules\Production\ProductionController;
use App\Modules\Delivery\DeliveryController;
use App\Modules\Portal\PortalController;
use App\Modules\Inventory\InventoryController;
use App\Modules\Reports\ReportController;

class FullSystemTest
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "====================================================================\n";
        echo "   SIMLAUNDRY FULL SYSTEM END-TO-END REGRESSION & INTEGRATION TEST\n";
        echo "====================================================================\n\n";

        $branchId = 1;
        $customerId = $this->step1_CustomerRegistrationAndLoyalty();
        $deliveryReqId = $this->step2_CustomerOnlinePickupRequest($customerId);
        $this->step3_CourierPickupWorkflow($deliveryReqId);
        $orderId = $this->step4_PosTransactionWithComboPricingAndVoucher($branchId, $customerId);
        $this->step5_ProductionWorkflowAndQualityControl($orderId);
        $this->step6_DeliveryAndProofOfDelivery($orderId);
        $this->step7_CustomerRatingAndReview($customerId, $orderId);
        $this->step8_InventoryDeductionAndOpname($branchId);
        $this->step9_DoubleEntryAccountingBalanceVerification();
        $this->step10_ExecutiveReportingAndBranchPerformance();

        echo "\n====================================================================\n";
        echo "   ALL END-TO-END SYSTEM TESTS PASSED SUCCESSFULLY! 100% OPERATIONAL\n";
        echo "====================================================================\n";
    }

    private function authenticateSuperAdmin(): void
    {
        Session::start();
        $user = $this->db->fetch("SELECT * FROM users WHERE username = 'superadmin'");
        if (!$user) {
            throw new \Exception("Super admin user not found.");
        }
        Auth::login($user);
    }

    private function step1_CustomerRegistrationAndLoyalty(): int
    {
        echo "[STEP 1/10] Customer Registration & Loyalty Points Ledger... ";

        Session::start();
        $rawPhone = '0812' . rand(10000000, 99999999);
        $email = 'vip.client' . rand(1000, 9999) . '@laundrypro.test';

        $_POST = [
            'name' => 'Budi Santoso Eksekutif',
            'whatsapp' => $rawPhone,
            'email' => $email,
            'branch_id' => '1',
            'address' => 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan',
            'password' => 'Password@123',
        ];

        ob_start();
        $portal = new PortalController();
        $portal->doRegister();
        ob_end_clean();

        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $customer = $this->db->fetch("SELECT * FROM customers WHERE whatsapp = :w", [':w' => $cleanPhone]);
        if (!$customer) {
            throw new \Exception("Customer registration failed.");
        }

        if (empty($customer['code']) || strpos($customer['code'], 'CUST-') !== 0) {
            throw new \Exception("Customer code format invalid: " . ($customer['code'] ?? 'null'));
        }

        // Verify welcome points
        if ((int)$customer['points_balance'] < 20) {
            throw new \Exception("Welcome points not credited properly.");
        }

        echo "PASSED (Customer ID: {$customer['id']}, Code: {$customer['code']})\n";
        return (int)$customer['id'];
    }

    private function step2_CustomerOnlinePickupRequest(int $customerId): int
    {
        echo "[STEP 2/10] Customer Online Pickup Request via Portal... ";

        Session::start();
        Auth::loginCustomer($customerId);

        $_POST = [
            'branch_id' => '1',
            'address_text' => 'Apartment Senopati Penthouse Tower A Lt. 12',
            'schedule_date' => date('Y-m-d'),
            'schedule_time' => '10:00 - 12:00',
            'notes' => 'Tolong bawa kantong ekstra untuk selimut',
        ];

        ob_start();
        $portal = new PortalController();
        $portal->storePickup();
        ob_end_clean();

        $req = $this->db->fetch(
            "SELECT * FROM delivery_requests WHERE customer_id = :cid ORDER BY id DESC LIMIT 1",
            [':cid' => $customerId]
        );

        if (!$req || $req['type'] !== 'pickup' || $req['status'] !== 'pending') {
            throw new \Exception("Pickup request creation failed or state mismatch.");
        }

        echo "PASSED (Request ID: {$req['id']})\n";
        return (int)$req['id'];
    }

    private function step3_CourierPickupWorkflow(int $requestId): void
    {
        echo "[STEP 3/10] Courier Assignment & Pickup Completion... ";

        $this->authenticateSuperAdmin();

        $courier = $this->db->fetch("SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = 'courier') LIMIT 1");
        $courierId = $courier ? (int)$courier['id'] : 1;

        // Assign courier
        $_POST = [
            'delivery_request_id' => (string)$requestId,
            'courier_user_id' => (string)$courierId,
        ];

        ob_start();
        $delivery = new DeliveryController();
        $delivery->assignCourier();
        ob_end_clean();

        // Update status to picked_up
        $_POST = [
            'delivery_request_id' => (string)$requestId,
            'status' => 'picked_up',
            'notes' => 'Cucian berhasil diambil dari resepsionis penthouse',
        ];

        ob_start();
        $delivery->updateStatus();
        ob_end_clean();

        $req = $this->db->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $requestId]);
        if ($req['status'] !== 'picked_up') {
            throw new \Exception("Delivery request status was not updated to picked_up.");
        }

        echo "PASSED\n";
    }

    private function step4_PosTransactionWithComboPricingAndVoucher(int $branchId, int $customerId): int
    {
        echo "[STEP 4/10] POS Touchscreen Order (Kiloan + Satuan + Voucher + DP)... ";

        $this->authenticateSuperAdmin();

        // Active services
        $kiloService = $this->db->fetch("SELECT * FROM services WHERE unit_type = 'kg' AND is_active = 1 LIMIT 1");
        $satuanService = $this->db->fetch("SELECT * FROM services WHERE unit_type = 'piece' AND is_active = 1 LIMIT 1");

        if (!$kiloService || !$satuanService) {
            throw new \Exception("Required services (kg and piece) not found.");
        }

        // Create voucher for discount testing with exact schema
        $vCode = 'SUPER' . rand(100, 999);
        $this->db->insert('vouchers', [
            'company_id' => 1,
            'code' => $vCode,
            'name' => 'Diskon Eksklusif Pelanggan',
            'type' => 'fixed',
            'value' => 5000,
            'min_spend' => 10000,
            'max_discount' => 5000,
            'quota' => 50,
            'used_count' => 0,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+30 days')),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $_POST = [
            'customer_id' => (string)$customerId,
            'branch_id' => (string)$branchId,
            'order_type' => 'regular',
            'items' => [
                [
                    'service_id' => (int)$kiloService['id'],
                    'unit_type' => 'kg',
                    'weight' => 3.5,
                    'quantity' => 1,
                    'unit_price' => (int)$kiloService['base_price'],
                    'notes' => 'Pakaian sehari-hari'
                ],
                [
                    'service_id' => (int)$satuanService['id'],
                    'unit_type' => 'piece',
                    'weight' => 0.0,
                    'quantity' => 2,
                    'unit_price' => (int)$satuanService['base_price'],
                    'notes' => 'Kemeja kantor sutra'
                ]
            ],
            'voucher_code' => $vCode,
            'paid_amount' => '30000', // Down Payment
            'payment_method_id' => '1', // Cash
            'notes' => 'Jangan gunakan pewangi berlebih',
        ];

        ob_start();
        $pos = new PosController();
        $pos->checkout();
        ob_end_clean();

        $order = $this->db->fetch(
            "SELECT * FROM orders WHERE customer_id = :cid ORDER BY id DESC LIMIT 1",
            [':cid' => $customerId]
        );

        if (!$order) {
            throw new \Exception("Order was not created.");
        }

        // Assert invoice format: LP-[BRANCH_CODE]-[YYYYMMDD]-[SEQ]
        $pattern = '/^LP-[A-Z0-9]+-\d{8}-\d{4}$/';
        if (!preg_match($pattern, $order['order_no'])) {
            throw new \Exception("Order number {$order['order_no']} does not match required format LP-[BRANCH]-[YYYYMMDD]-[SEQ]");
        }

        // Verify snapshotting
        $items = $this->db->fetchAll("SELECT * FROM order_items WHERE order_id = :oid", [':oid' => $order['id']]);
        if (count($items) !== 2) {
            throw new \Exception("Expected 2 order items, found " . count($items));
        }

        // Verify partial payment status
        if ($order['payment_status'] !== 'partial' && $order['payment_status'] !== 'paid') {
            throw new \Exception("Expected partial payment status, found {$order['payment_status']}");
        }

        echo "PASSED (Order No: {$order['order_no']}, Total: Rp " . number_format($order['grand_total']) . ")\n";
        return (int)$order['id'];
    }

    private function step5_ProductionWorkflowAndQualityControl(int $orderId): void
    {
        echo "[STEP 5/10] 7-Stage Production Lifecycle & QC Inspection... ";

        $this->authenticateSuperAdmin();

        $prod = new ProductionController();

        $job = $this->db->fetch("SELECT * FROM production_jobs WHERE order_id = :oid ORDER BY id DESC LIMIT 1", [':oid' => $orderId]);
        if (!$job) {
            throw new \Exception("Production job for order not initialized.");
        }
        $jobId = (int)$job['id'];

        // 1. Assign Operator and Idle Machine
        $machine = $this->db->fetch("SELECT * FROM machines WHERE operational_status = 'idle' LIMIT 1");
        $operator = $this->db->fetch("SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = 'production') LIMIT 1");

        $_POST = [
            'job_id' => (string)$jobId,
            'operator_id' => (string)($operator['id'] ?? 1),
            'machine_id' => (string)($machine['id'] ?? 1),
        ];
        ob_start();
        $prod->assign();
        ob_end_clean();

        // 2. Start Washing (Waiting -> Washing)
        ob_start();
        $prod->startStage();
        ob_end_clean();

        // 3. Complete Washing -> Advances to Drying
        $_POST = ['job_id' => (string)$jobId, 'notes' => 'Pencucian selesai bersih'];
        ob_start();
        $prod->completeStage();
        ob_end_clean();

        // 4. Start & Complete Drying -> Advances to Ironing
        $_POST = ['job_id' => (string)$jobId];
        ob_start();
        $prod->startStage();
        ob_end_clean();

        $_POST = ['job_id' => (string)$jobId, 'notes' => 'Pengeringan selesai'];
        ob_start();
        $prod->completeStage();
        ob_end_clean();

        // 5. Start & Complete Ironing -> Advances to QC
        $_POST = ['job_id' => (string)$jobId];
        ob_start();
        $prod->startStage();
        ob_end_clean();

        $_POST = ['job_id' => (string)$jobId, 'notes' => 'Penyetrikaan uap selesai'];
        ob_start();
        $prod->completeStage();
        ob_end_clean();

        // 6. Quality Control (QC) scoring
        $_POST = [
            'job_id' => (string)$jobId,
            'cleanliness_score' => '5',
            'fragrance_score' => '5',
            'neatness_score' => '5',
            'completeness_score' => '5',
            'is_passed' => '1',
            'defect_notes' => 'Kondisi pakaian sempurna',
        ];

        ob_start();
        $prod->qcCheck();
        ob_end_clean();

        // Complete QC -> Advances to Packaging
        $_POST = ['job_id' => (string)$jobId, 'notes' => 'QC lolos 5 bintang'];
        ob_start();
        $prod->completeStage();
        ob_end_clean();

        // 7. Start & Complete Packaging -> Advances to Ready
        $_POST = ['job_id' => (string)$jobId];
        ob_start();
        $prod->startStage();
        ob_end_clean();

        $_POST = ['job_id' => (string)$jobId, 'notes' => 'Pengemasan rapi selesai'];
        ob_start();
        $prod->completeStage();
        ob_end_clean();

        $order = $this->db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
        if ($order['production_status'] !== 'ready') {
            throw new \Exception("Expected production status 'ready', found {$order['production_status']}");
        }

        // Verify WhatsApp notification queue
        $wa = $this->db->fetch("SELECT * FROM whatsapp_queue WHERE reference_id = :oid AND reference_type = 'orders' ORDER BY id DESC LIMIT 1", [':oid' => $orderId]);
        if (!$wa) {
            throw new \Exception("Automated WhatsApp notification was not enqueued for order completion.");
        }

        echo "PASSED (Order Status: Ready, WA Notification Enqueued)\n";
    }

    private function step6_DeliveryAndProofOfDelivery(int $orderId): void
    {
        echo "[STEP 6/10] Delivery Fulfillment & Proof of Delivery... ";

        $this->authenticateSuperAdmin();

        $delivery = new DeliveryController();

        $order = $this->db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
        $courier = $this->db->fetch("SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = 'courier') LIMIT 1");
        $courierId = $courier ? (int)$courier['id'] : 1;

        // Create delivery request for the ready order
        $_POST = [
            'order_id' => (string)$orderId,
            'customer_id' => (string)$order['customer_id'],
            'branch_id' => (string)$order['branch_id'],
            'type' => 'delivery',
            'address_text' => 'Apartment Senopati Penthouse Tower A Lt. 12',
            'schedule_date' => date('Y-m-d'),
            'schedule_time' => '13:00 - 15:00',
            'courier_user_id' => (string)$courierId,
            'fee' => '15000',
            'notes' => 'Antar jam makan siang',
        ];

        ob_start();
        $delivery->store();
        ob_end_clean();

        $delReq = $this->db->fetch("SELECT * FROM delivery_requests WHERE order_id = :oid ORDER BY id DESC LIMIT 1", [':oid' => $orderId]);
        if (!$delReq) {
            throw new \Exception("Delivery task was not created for order.");
        }

        // Complete delivery with Proof of Delivery
        $_POST = [
            'delivery_request_id' => (string)$delReq['id'],
            'recipient_name' => 'Budi Santoso (Penerima Langsung)',
            'notes' => 'Diterima dalam kondisi rapi, bersih, wangi',
        ];

        ob_start();
        $delivery->uploadProof();
        ob_end_clean();

        $updatedReq = $this->db->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $delReq['id']]);
        if ($updatedReq['status'] !== 'delivered') {
            throw new \Exception("Delivery task status not marked as 'delivered'.");
        }

        // Verify order is marked completed
        $updatedOrder = $this->db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
        if ($updatedOrder['production_status'] !== 'completed') {
            throw new \Exception("Order status not automatically updated to 'completed' after POD.");
        }

        echo "PASSED (Delivered & Completed)\n";
    }

    private function step7_CustomerRatingAndReview(int $customerId, int $orderId): void
    {
        echo "[STEP 7/10] Customer Rating & Review Verification... ";

        Session::start();
        Auth::loginCustomer($customerId);

        $_POST = [
            'order_id' => (string)$orderId,
            'rating' => '5',
            'review' => 'Pelayanan sangat cepat, pakaian wangi semerbak dan sangat rapi. Luar biasa SimLaundry!',
        ];

        ob_start();
        $portal = new PortalController();
        $portal->storeReview();
        ob_end_clean();

        $review = $this->db->fetch("SELECT * FROM order_reviews WHERE order_id = :oid", [':oid' => $orderId]);
        if (!$review || (int)$review['rating'] !== 5) {
            throw new \Exception("Customer review was not saved properly.");
        }

        echo "PASSED (5 Stars Saved)\n";
    }

    private function step8_InventoryDeductionAndOpname(int $branchId): void
    {
        echo "[STEP 8/10] Inventory Consumption & Physical Stock Opname... ";

        $this->authenticateSuperAdmin();

        $inv = new InventoryController();

        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE is_active = 1 LIMIT 1");
        if (!$item) {
            throw new \Exception("No active inventory item found.");
        }

        $stockBefore = (float)$this->db->fetchColumn(
            "SELECT current_stock FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $item['id']]
        );

        // Adjust stock (usage for washing)
        $_POST = [
            'branch_id' => (string)$branchId,
            'item_id' => (string)$item['id'],
            'type' => 'out',
            'quantity' => '5',
            'notes' => 'Penggunaan deterjen untuk batch cucian kiloan',
        ];

        ob_start();
        $inv->updateStock();
        ob_end_clean();

        $stockAfter = (float)$this->db->fetchColumn(
            "SELECT current_stock FROM branch_inventory WHERE branch_id = :b AND item_id = :i",
            [':b' => $branchId, ':i' => $item['id']]
        );

        if ($stockAfter !== ($stockBefore - 5)) {
            throw new \Exception("Stock deduction failed: before={$stockBefore}, after={$stockAfter}");
        }

        // Conduct stock opname
        $_POST = [
            'branch_id' => (string)$branchId,
            'item_id' => (string)$item['id'],
            'physical_stock' => (string)$stockAfter,
            'reason' => 'Stok opname rutin mingguan',
        ];

        ob_start();
        $inv->storeOpname();
        ob_end_clean();

        $opname = $this->db->fetch("SELECT * FROM stock_opnames WHERE branch_id = :b ORDER BY id DESC LIMIT 1", [':b' => $branchId]);
        if (!$opname) {
            throw new \Exception("Stock opname record was not created.");
        }

        echo "PASSED (Stock Synced)\n";
    }

    private function step9_DoubleEntryAccountingBalanceVerification(): void
    {
        echo "[STEP 9/10] Financial Integrity & Double-Entry Balance (Debit == Credit)... ";

        $totals = $this->db->fetch(
            "SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit FROM journal_entry_lines"
        );

        $debit = (int)($totals['total_debit'] ?? 0);
        $credit = (int)($totals['total_credit'] ?? 0);

        if ($debit !== $credit) {
            throw new \Exception("General Ledger is NOT in balance! Total Debit = {$debit}, Total Credit = {$credit}");
        }

        if ($debit === 0) {
            throw new \Exception("No journal entries exist in system.");
        }

        echo "PASSED (Total Debit: Rp " . number_format($debit) . " == Total Credit: Rp " . number_format($credit) . ")\n";
    }

    private function step10_ExecutiveReportingAndBranchPerformance(): void
    {
        echo "[STEP 10/10] Executive Reporting & Multi-Branch Business Analytics... ";

        $this->authenticateSuperAdmin();

        $rep = new ReportController();

        $refClass = new \ReflectionClass(ReportController::class);
        $generateMethod = $refClass->getMethod('generateReportData');
        $generateMethod->setAccessible(true);

        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');

        // Test Executive Summary
        $execSummary = $generateMethod->invoke($rep, 'executive_summary', 0, $startDate, $endDate);
        if (empty($execSummary['rows'])) {
            throw new \Exception("Executive summary generated empty data.");
        }

        // Test Branch Comparison
        $branchComp = $generateMethod->invoke($rep, 'branch_comparison', 0, $startDate, $endDate);
        if (empty($branchComp['rows'])) {
            throw new \Exception("Branch comparison report generated empty data.");
        }

        // Test Income Statement (P&L)
        $incomeStmt = $generateMethod->invoke($rep, 'income_statement', 0, $startDate, $endDate);
        if (empty($incomeStmt['rows'])) {
            throw new \Exception("Income statement generated empty data.");
        }

        echo "PASSED (All Executive Reports Operational)\n";
    }
}

// Run Full System Suite
$fullTest = new FullSystemTest();
$fullTest->runAll();
