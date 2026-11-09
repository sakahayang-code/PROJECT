<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Portal\PortalController;
use App\Middleware\CustomerAuthMiddleware;

class Phase10Test
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "=== Running Phase 10 Tests: Customer Portal & PWA ===\n";

        $this->testCustomerRegistrationAndWelcomePoints();
        $this->testCustomerAuthenticationAndMiddlewareIsolation();
        $this->testOnlinePickupRequest();
        $this->testOnlineQrisPaymentGeneration();
        $this->testOrderReviewAndOwnershipSecurity();
        $this->testPwaManifestAndServiceWorker();

        echo "=== All Phase 10 Tests Passed Successfully! ===\n\n";
    }

    private function testCustomerRegistrationAndWelcomePoints(): void
    {
        echo "Testing Customer Registration & Welcome Points... ";

        Session::start();
        $testPhone = '081399' . rand(100000, 999999);
        $testPass = 'Rahasia@123';

        $_POST = [
            'name' => 'Anisa Pujiastuti',
            'whatsapp' => $testPhone,
            'email' => 'anisa' . rand(100, 999) . '@test.id',
            'branch_id' => '1',
            'address' => 'Jl. Kemang Timur No. 12, Jakarta Selatan',
            'password' => $testPass,
        ];

        ob_start();
        $controller = new PortalController();
        $controller->doRegister();
        ob_end_clean();

        $cleanPhone = preg_replace('/[^0-9]/', '', $testPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $customer = $this->db->fetch("SELECT * FROM customers WHERE whatsapp = :w", [':w' => $cleanPhone]);
        if (!$customer) {
            throw new \Exception("Customer was not registered in database.");
        }
        if (!str_starts_with($customer['code'], 'CUST-')) {
            throw new \Exception("Customer code format mismatch. Expected CUST-*, got: " . $customer['code']);
        }
        if ((int)$customer['points_balance'] !== 20) {
            throw new \Exception("Customer welcome points mismatch. Expected 20, got: " . $customer['points_balance']);
        }

        // Verify account
        $account = $this->db->fetch("SELECT * FROM customer_accounts WHERE customer_id = :cid", [':cid' => $customer['id']]);
        if (!$account || !password_verify($testPass, $account['password_hash'])) {
            throw new \Exception("Customer account password hash verification failed.");
        }

        // Verify loyalty ledger
        $ledger = $this->db->fetch("SELECT * FROM loyalty_transactions WHERE customer_id = :cid", [':cid' => $customer['id']]);
        if (!$ledger || (int)$ledger['points'] !== 20) {
            throw new \Exception("Welcome points ledger entry was not created.");
        }

        echo "PASS\n";
    }

    private function testCustomerAuthenticationAndMiddlewareIsolation(): void
    {
        echo "Testing Customer Authentication & Middleware Protection... ";

        // Logout customer
        Auth::logoutCustomer();
        if (Auth::checkCustomer()) {
            throw new \Exception("Customer should be logged out.");
        }

        // Check middleware rejects unauthenticated access
        $middleware = new CustomerAuthMiddleware();
        $allowed = $middleware->handle();
        if ($allowed !== false) {
            throw new \Exception("CustomerAuthMiddleware should reject unauthenticated customer access.");
        }

        // Login as customer
        $customer = $this->db->fetch("SELECT * FROM customers ORDER BY id DESC LIMIT 1");
        $account = $this->db->fetch("SELECT * FROM customer_accounts WHERE customer_id = :cid", [':cid' => $customer['id']]);

        $_POST = [
            'identifier' => $customer['whatsapp'],
            'password' => 'Rahasia@123',
        ];

        ob_start();
        $controller = new PortalController();
        $controller->doLogin();
        ob_end_clean();

        if (!Auth::checkCustomer() || Auth::customerId() !== (int)$customer['id']) {
            throw new \Exception("Customer login failed. Authenticated customer ID mismatch.");
        }

        // Now middleware should pass
        $allowedAfterLogin = $middleware->handle();
        if ($allowedAfterLogin !== true) {
            throw new \Exception("CustomerAuthMiddleware should permit authenticated customer access.");
        }

        echo "PASS\n";
    }

    private function testOnlinePickupRequest(): void
    {
        echo "Testing Online Pickup Request Submission... ";

        $customer = Auth::customer();
        $branchId = 1;

        $_POST = [
            'branch_id' => (string)$branchId,
            'address_text' => 'Apartemen Ciputra World Tower 2 Unit 1502',
            'schedule_date' => date('Y-m-d'),
            'schedule_time' => '13:00 - 16:00',
            'notes' => 'Tolong telepon sebelum sampai',
        ];

        ob_start();
        $controller = new PortalController();
        $controller->storePickup();
        ob_end_clean();

        $delivReq = $this->db->fetch(
            "SELECT * FROM delivery_requests WHERE customer_id = :cid AND address_text LIKE '%Ciputra%' ORDER BY id DESC LIMIT 1",
            [':cid' => $customer['id']]
        );

        if (!$delivReq || $delivReq['type'] !== 'pickup' || $delivReq['status'] !== 'pending') {
            throw new \Exception("Pickup request was not properly submitted.");
        }

        echo "PASS\n";
    }

    private function testOnlineQrisPaymentGeneration(): void
    {
        echo "Testing Online QRIS Payment Generation from Portal... ";

        $customer = Auth::customer();
        $branch = $this->db->fetch("SELECT * FROM branches WHERE id = 1");
        $now = date('Y-m-d H:i:s');
        $orderNo = 'LP-PORTAL-' . rand(100, 999);
        $total = 45000;

        $orderId = $this->db->insert('orders', [
            'company_id' => $branch['company_id'],
            'branch_id' => $branch['id'],
            'customer_id' => $customer['id'],
            'user_id' => 1,
            'order_no' => $orderNo,
            'tracking_token' => bin2hex(random_bytes(16)),
            'order_type' => 'regular',
            'est_completion_at' => $now,
            'total_weight' => 2.0,
            'total_pieces' => 4,
            'subtotal' => $total,
            'addon_total' => 0,
            'discount_total' => 0,
            'voucher_discount' => 0,
            'tax_amount' => 0,
            'grand_total' => $total,
            'paid_amount' => 0,
            'balance_due' => $total,
            'payment_status' => 'unpaid',
            'production_status' => 'received',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $_POST = ['order_id' => (string)$orderId];

        ob_start();
        $controller = new PortalController();
        $controller->payQris();
        $output = ob_get_clean();

        $json = json_decode($output, true);
        if (!($json['success'] ?? false) || empty($json['qr_string']) || (int)$json['amount'] !== $total) {
            throw new \Exception("payQris did not return expected QRIS transaction response. Output: " . $output);
        }

        echo "PASS\n";
    }

    private function testOrderReviewAndOwnershipSecurity(): void
    {
        echo "Testing Order Review and Customer Ownership Security... ";

        $customer = Auth::customer();
        $order = $this->db->fetch("SELECT * FROM orders WHERE customer_id = :cid ORDER BY id DESC LIMIT 1", [':cid' => $customer['id']]);

        // 1. Post review for own order: Should succeed
        $_POST = [
            'order_id' => (string)$order['id'],
            'rating' => '5',
            'comment' => 'Cucian sangat wangi dan rapi! Kurir ramah.',
        ];

        ob_start();
        $controller = new PortalController();
        $controller->storeReview();
        ob_end_clean();

        $rev = $this->db->fetch("SELECT * FROM order_reviews WHERE order_id = :oid", [':oid' => $order['id']]);
        if (!$rev || (int)$rev['rating'] !== 5 || $rev['comment'] !== 'Cucian sangat wangi dan rapi! Kurir ramah.') {
            throw new \Exception("Order review was not recorded.");
        }

        // 2. Attempt to review someone else's order: Should be rejected
        $otherOrder = $this->db->fetch("SELECT * FROM orders WHERE customer_id != :cid LIMIT 1", [':cid' => $customer['id']]);
        if ($otherOrder) {
            $_POST = [
                'order_id' => (string)$otherOrder['id'],
                'rating' => '1',
                'comment' => 'Percobaan review ilegal',
            ];

            ob_start();
            $controller->storeReview();
            ob_end_clean();

            $illegalRev = $this->db->fetch("SELECT * FROM order_reviews WHERE order_id = :oid AND customer_id = :cid", [':oid' => $otherOrder['id'], ':cid' => $customer['id']]);
            if ($illegalRev) {
                throw new \Exception("Security breach: Customer was able to submit review for someone else's order!");
            }
        }

        echo "PASS\n";
    }

    private function testPwaManifestAndServiceWorker(): void
    {
        echo "Testing PWA Manifest & Service Worker Assets... ";

        $manifestFile = dirname(__DIR__) . '/public/manifest.json';
        if (!file_exists($manifestFile)) {
            throw new \Exception("PWA manifest.json not found.");
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (!$manifest || (($manifest['short_name'] ?? '') !== 'LaundryPro' && !str_contains($manifest['name'] ?? '', 'LaundryPro'))) {
            throw new \Exception("manifest.json is invalid or name mismatch.");
        }

        $swFile = dirname(__DIR__) . '/public/service-worker.js';
        if (!file_exists($swFile)) {
            throw new \Exception("service-worker.js not found.");
        }

        $offlineFile = dirname(__DIR__) . '/public/offline.html';
        if (!file_exists($offlineFile)) {
            throw new \Exception("offline.html not found.");
        }

        echo "PASS\n";
    }
}

$test = new Phase10Test();
$test->runAll();
