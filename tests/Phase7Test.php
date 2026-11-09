<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Delivery\DeliveryController;

class Phase7Test
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "=== Running Phase 7 Tests: Pickup & Delivery ===\n";

        $this->testCreatePickupRequest();
        $this->testCourierAssignment();
        $this->testStatusTransitionToOnTheWay();
        $this->testProofUploadAndCompletion();
        $this->testDeliveryWithOrderCompletion();

        echo "=== All Phase 7 Tests Passed Successfully! ===\n\n";
    }

    private function testCreatePickupRequest(): void
    {
        echo "Testing Create Pickup Request... ";

        Session::start();
        $admin = $this->db->fetch("SELECT * FROM users WHERE username = 'superadmin'");
        Auth::login($admin);

        $customer = $this->db->fetch("SELECT * FROM customers LIMIT 1");
        $branch = $this->db->fetch("SELECT * FROM branches WHERE id = 1");

        $_POST = [
            'branch_id' => (string)$branch['id'],
            'customer_id' => (string)$customer['id'],
            'type' => 'pickup',
            'address_text' => 'Jl. Tebet Raya No. 45, Jakarta Selatan',
            'schedule_date' => date('Y-m-d'),
            'schedule_time' => '10:00 - 12:00',
            'fee' => '15.000',
            'notes' => 'Pagar hitam, tolong bel rumah',
        ];

        ob_start();
        $controller = new DeliveryController();
        $controller->store();
        ob_end_clean();

        $req = $this->db->fetch(
            "SELECT * FROM delivery_requests WHERE customer_id = :c AND address_text LIKE '%Tebet%' ORDER BY id DESC LIMIT 1",
            [':c' => $customer['id']]
        );

        if (!$req) {
            throw new \Exception("Delivery request was not created in database.");
        }
        if ($req['status'] !== 'pending') {
            throw new \Exception("Initial delivery request status should be 'pending', got: " . $req['status']);
        }
        if ((int)$req['fee'] !== 15000) {
            throw new \Exception("Fee was not saved properly. Expected 15000, got: " . $req['fee']);
        }

        // Verify status history
        $hist = $this->db->fetch(
            "SELECT * FROM delivery_status_history WHERE delivery_request_id = :id",
            [':id' => $req['id']]
        );
        if (!$hist || $hist['to_status'] !== 'pending') {
            throw new \Exception("Initial status history was not logged.");
        }

        echo "PASS\n";
    }

    private function testCourierAssignment(): void
    {
        echo "Testing Courier Assignment... ";

        $req = $this->db->fetch("SELECT * FROM delivery_requests WHERE status = 'pending' ORDER BY id DESC LIMIT 1");
        if (!$req) {
            throw new \Exception("No pending delivery request available for test.");
        }

        $courier = $this->db->fetch("SELECT * FROM users WHERE username = 'kurir.pusat'");
        if (!$courier) {
            throw new \Exception("Courier user 'kurir.pusat' not found in database.");
        }

        $_POST = [
            'delivery_request_id' => (string)$req['id'],
            'courier_user_id' => (string)$courier['id'],
        ];

        ob_start();
        $controller = new DeliveryController();
        $controller->assignCourier();
        ob_end_clean();

        $updatedReq = $this->db->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $req['id']]);
        if ($updatedReq['status'] !== 'assigned' || (int)$updatedReq['courier_user_id'] !== (int)$courier['id']) {
            throw new \Exception("Delivery request assignment failed. Status: {$updatedReq['status']}, Courier: {$updatedReq['courier_user_id']}");
        }

        // Verify assignment record
        $assignment = $this->db->fetch(
            "SELECT * FROM delivery_assignments WHERE delivery_request_id = :id AND courier_user_id = :u",
            [':id' => $req['id'], ':u' => $courier['id']]
        );
        if (!$assignment) {
            throw new \Exception("Record in delivery_assignments table was not created.");
        }

        echo "PASS\n";
    }

    private function testStatusTransitionToOnTheWay(): void
    {
        echo "Testing Status Transition to On The Way... ";

        $req = $this->db->fetch("SELECT * FROM delivery_requests WHERE status = 'assigned' ORDER BY id DESC LIMIT 1");
        if (!$req) {
            throw new \Exception("No assigned delivery request available for test.");
        }

        $_POST = [
            'delivery_request_id' => (string)$req['id'],
            'status' => 'on_the_way',
            'notes' => 'Kurir sedang dalam perjalanan menuju lokasi pelanggan',
        ];

        ob_start();
        $controller = new DeliveryController();
        $controller->updateStatus();
        ob_end_clean();

        $updatedReq = $this->db->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $req['id']]);
        if ($updatedReq['status'] !== 'on_the_way') {
            throw new \Exception("Status did not update to 'on_the_way', got: " . $updatedReq['status']);
        }

        // Check history
        $hist = $this->db->fetch(
            "SELECT * FROM delivery_status_history WHERE delivery_request_id = :id AND to_status = 'on_the_way' ORDER BY id DESC LIMIT 1",
            [':id' => $req['id']]
        );
        if (!$hist) {
            throw new \Exception("Status history for on_the_way was not recorded.");
        }

        echo "PASS\n";
    }

    private function testProofUploadAndCompletion(): void
    {
        echo "Testing Proof of Delivery / Pickup Upload... ";

        $req = $this->db->fetch("SELECT * FROM delivery_requests WHERE status = 'on_the_way' AND type = 'pickup' ORDER BY id DESC LIMIT 1");
        if (!$req) {
            throw new \Exception("No pickup request in on_the_way state.");
        }

        $_POST = [
            'delivery_request_id' => (string)$req['id'],
            'recipient_name' => 'Ibu Siti Khadijah',
            'notes' => 'Pakaian kotor diserahkan 2 kantong plastik besar',
        ];

        ob_start();
        $controller = new DeliveryController();
        $controller->uploadProof();
        ob_end_clean();

        $updatedReq = $this->db->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $req['id']]);
        if ($updatedReq['status'] !== 'picked_up') {
            throw new \Exception("Pickup request status should be 'picked_up', got: " . $updatedReq['status']);
        }

        $proof = $this->db->fetch("SELECT * FROM delivery_proofs WHERE delivery_request_id = :id", [':id' => $req['id']]);
        if (!$proof || $proof['recipient_name'] !== 'Ibu Siti Khadijah') {
            throw new \Exception("Proof record not created or recipient name mismatch.");
        }

        echo "PASS\n";
    }

    private function testDeliveryWithOrderCompletion(): void
    {
        echo "Testing Delivery Completion with Auto-Completing Order... ";

        // Create an order in 'ready' status
        $branch = $this->db->fetch("SELECT * FROM branches WHERE id = 1");
        $customer = $this->db->fetch("SELECT * FROM customers LIMIT 1");
        $now = date('Y-m-d H:i:s');
        $orderNo = 'LP-DELIV-' . date('His');

        $orderId = $this->db->insert('orders', [
            'company_id' => $branch['company_id'],
            'branch_id' => $branch['id'],
            'customer_id' => $customer['id'],
            'user_id' => 1,
            'order_no' => $orderNo,
            'tracking_token' => bin2hex(random_bytes(16)),
            'order_type' => 'regular',
            'est_completion_at' => $now,
            'total_weight' => 3.0,
            'total_pieces' => 6,
            'subtotal' => 30000,
            'addon_total' => 0,
            'discount_total' => 0,
            'voucher_discount' => 0,
            'tax_amount' => 0,
            'grand_total' => 30000,
            'paid_amount' => 30000,
            'balance_due' => 0,
            'payment_status' => 'paid',
            'production_status' => 'ready',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create a delivery request for this order
        $reqId = $this->db->insert('delivery_requests', [
            'company_id' => $branch['company_id'],
            'branch_id' => $branch['id'],
            'order_id' => $orderId,
            'customer_id' => $customer['id'],
            'type' => 'delivery',
            'address_text' => 'Apartemen Sudirman Tower A No. 1201',
            'schedule_date' => date('Y-m-d'),
            'schedule_time' => '13:00 - 15:00',
            'status' => 'on_the_way',
            'courier_user_id' => 6,
            'fee' => 10000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $_POST = [
            'delivery_request_id' => (string)$reqId,
            'recipient_name' => 'Bapak Budi (Security Lobby)',
            'notes' => 'Diserahkan langsung di lobby dengan segel rapi',
        ];

        ob_start();
        $controller = new DeliveryController();
        $controller->uploadProof();
        ob_end_clean();

        // Check delivery request is delivered
        $deliv = $this->db->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $reqId]);
        if ($deliv['status'] !== 'delivered') {
            throw new \Exception("Delivery status should be 'delivered', got: " . $deliv['status']);
        }

        // Check order was automatically marked 'completed'
        $completedOrder = $this->db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
        if ($completedOrder['production_status'] !== 'completed') {
            throw new \Exception("Order production_status should be 'completed', got: " . $completedOrder['production_status']);
        }
        if (empty($completedOrder['actual_completion_at'])) {
            throw new \Exception("Order actual_completion_at was not populated.");
        }

        echo "PASS\n";
    }
}

$test = new Phase7Test();
$test->runAll();
