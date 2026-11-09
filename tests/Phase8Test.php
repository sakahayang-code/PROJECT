<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Membership\MembershipController;
use App\Modules\WhatsApp\WhatsAppController;
use App\Services\WhatsAppAdapter;

class Phase8Test
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "=== Running Phase 8 Tests: CRM, Membership, Vouchers & WhatsApp ===\n";

        $this->testMembershipTiersAndBenefits();
        $this->testLoyaltyPointsAdjustmentAndLedger();
        $this->testVoucherCreationAndToggle();
        $this->testWhatsAppTemplatesAndQueueWorker();

        echo "=== All Phase 8 Tests Passed Successfully! ===\n\n";
    }

    private function testMembershipTiersAndBenefits(): void
    {
        echo "Testing Membership Tiers and Benefit Updates... ";

        Session::start();
        $admin = $this->db->fetch("SELECT * FROM users WHERE username = 'superadmin'");
        Auth::login($admin);

        // Check standard tiers: regular, silver, gold, platinum
        $slugs = ['regular', 'silver', 'gold', 'platinum'];
        foreach ($slugs as $slug) {
            $tier = $this->db->fetch("SELECT * FROM membership_tiers WHERE slug = :s", [':s' => $slug]);
            if (!$tier) {
                throw new \Exception("Default membership tier '{$slug}' not found in database.");
            }
        }

        // Update Silver tier benefits: 5% discount, 500.000 min spend
        $silver = $this->db->fetch("SELECT * FROM membership_tiers WHERE slug = 'silver'");
        $_POST = [
            'tier_id' => (string)$silver['id'],
            'min_spend' => '500.000',
            'min_points' => '100',
            'discount_percent' => '5.0',
            'points_multiplier' => '1.2',
            'description' => 'Member Silver hemat 5% setiap cuci kiloan dan satuan',
        ];

        ob_start();
        $controller = new MembershipController();
        $controller->updateTier();
        ob_end_clean();

        $updatedSilver = $this->db->fetch("SELECT * FROM membership_tiers WHERE id = :id", [':id' => $silver['id']]);
        if ((int)$updatedSilver['min_spend'] !== 500000 || (float)$updatedSilver['discount_percent'] !== 5.0) {
            throw new \Exception("Silver tier was not updated accurately.");
        }

        echo "PASS\n";
    }

    private function testLoyaltyPointsAdjustmentAndLedger(): void
    {
        echo "Testing Loyalty Points Adjustment and Ledger Integrity... ";

        $customer = $this->db->fetch("SELECT * FROM customers LIMIT 1");
        $initialBalance = (int)$customer['points_balance'];
        $pointsToAdd = 75;

        // Add 75 points
        $_POST = [
            'customer_id' => (string)$customer['id'],
            'type' => 'earn',
            'points' => (string)$pointsToAdd,
            'description' => 'Bonus promo weekend',
        ];

        ob_start();
        $controller = new MembershipController();
        $controller->adjustPoints();
        ob_end_clean();

        $updatedCustomer = $this->db->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $customer['id']]);
        if ((int)$updatedCustomer['points_balance'] !== ($initialBalance + $pointsToAdd)) {
            throw new \Exception("Customer points balance mismatch. Expected " . ($initialBalance + $pointsToAdd) . ", got: " . $updatedCustomer['points_balance']);
        }

        // Verify ledger record
        $ledger = $this->db->fetch(
            "SELECT * FROM loyalty_transactions WHERE customer_id = :c ORDER BY id DESC LIMIT 1",
            [':c' => $customer['id']]
        );
        if (!$ledger || (int)$ledger['points'] !== $pointsToAdd || (int)$ledger['balance_after'] !== ($initialBalance + $pointsToAdd)) {
            throw new \Exception("Loyalty transaction ledger record missing or incorrect.");
        }

        // Deduct 25 points
        $pointsToDeduct = 25;
        $_POST = [
            'customer_id' => (string)$customer['id'],
            'type' => 'redeem',
            'points' => (string)$pointsToDeduct,
            'description' => 'Penukaran diskon cuci',
        ];

        ob_start();
        $controller->adjustPoints();
        ob_end_clean();

        $finalCustomer = $this->db->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $customer['id']]);
        if ((int)$finalCustomer['points_balance'] !== ($initialBalance + $pointsToAdd - $pointsToDeduct)) {
            throw new \Exception("Customer points deduction mismatch.");
        }

        echo "PASS\n";
    }

    private function testVoucherCreationAndToggle(): void
    {
        echo "Testing Voucher Creation and Status Toggle... ";

        $code = 'TESTPROMO' . rand(100, 999);
        $_POST = [
            'code' => $code,
            'name' => 'Voucher Diskon Test Spesial',
            'type' => 'fixed',
            'value' => '10.000',
            'min_spend' => '50.000',
            'max_discount' => '10.000',
            'quota' => '25',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+30 days')),
        ];

        ob_start();
        $controller = new MembershipController();
        $controller->storeVoucher();
        ob_end_clean();

        $voucher = $this->db->fetch("SELECT * FROM vouchers WHERE code = :c", [':c' => $code]);
        if (!$voucher) {
            throw new \Exception("Voucher was not created in database.");
        }
        if ((int)$voucher['value'] !== 10000 || (int)$voucher['quota'] !== 25 || (int)$voucher['is_active'] !== 1) {
            throw new \Exception("Voucher attributes mismatch.");
        }

        // Toggle to inactive
        $_POST = ['voucher_id' => (string)$voucher['id']];
        ob_start();
        $controller->toggleVoucher();
        ob_end_clean();

        $toggled = $this->db->fetch("SELECT * FROM vouchers WHERE id = :id", [':id' => $voucher['id']]);
        if ((int)$toggled['is_active'] !== 0) {
            throw new \Exception("Voucher was not toggled to inactive.");
        }

        // Toggle back to active
        ob_start();
        $controller->toggleVoucher();
        ob_end_clean();

        $activeAgain = $this->db->fetch("SELECT * FROM vouchers WHERE id = :id", [':id' => $voucher['id']]);
        if ((int)$activeAgain['is_active'] !== 1) {
            throw new \Exception("Voucher was not reactivated.");
        }

        echo "PASS\n";
    }

    private function testWhatsAppTemplatesAndQueueWorker(): void
    {
        echo "Testing WhatsApp Templates and Queue Processing... ";

        // Verify templates exist
        $templates = $this->db->fetchAll("SELECT * FROM whatsapp_templates");
        if (empty($templates)) {
            throw new \Exception("No WhatsApp templates found in database.");
        }

        // Update template
        $tmpl = $templates[0];
        $newBody = "Halo {customer_name}! Pesanan Anda {order_no} sebesar {grand_total} di {branch_name} sudah diproses.";
        $_POST = [
            'template_id' => (string)$tmpl['id'],
            'message_template' => $newBody,
            'is_active' => '1',
        ];

        ob_start();
        $waController = new WhatsAppController();
        $waController->updateTemplate();
        ob_end_clean();

        $updatedTmpl = $this->db->fetch("SELECT * FROM whatsapp_templates WHERE id = :id", [':id' => $tmpl['id']]);
        if ($updatedTmpl['message_template'] !== $newBody) {
            throw new \Exception("WhatsApp template text was not updated.");
        }

        // Test sending a manual message
        $testPhone = '081299887766';
        $testMsg = 'Pemberitahuan: LaundryPro cabang Pusat buka normal jam 08:00 - 20:00 WIB.';
        $_POST = [
            'phone_number' => $testPhone,
            'recipient_name' => 'Budi Testing',
            'message' => $testMsg,
        ];

        ob_start();
        $waController->sendMessage();
        ob_end_clean();

        // Check queue item created and processed
        $cleanPhone = '6281299887766';
        $queueItem = $this->db->fetch(
            "SELECT * FROM whatsapp_queue WHERE phone_number = :p ORDER BY id DESC LIMIT 1",
            [':p' => $cleanPhone]
        );
        if (!$queueItem) {
            throw new \Exception("WhatsApp queue item was not created.");
        }
        if ($queueItem['status'] !== 'sent') {
            throw new \Exception("Queue item should be 'sent' via mock provider, got: " . $queueItem['status']);
        }

        // Check delivery log
        $log = $this->db->fetch(
            "SELECT * FROM whatsapp_logs WHERE queue_id = :qid",
            [':qid' => $queueItem['id']]
        );
        if (!$log || $log['status'] !== 'sent') {
            throw new \Exception("WhatsApp delivery log was not created.");
        }

        echo "PASS\n";
    }
}

$test = new Phase8Test();
$test->runAll();
