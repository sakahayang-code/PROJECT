<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Accounting\AccountingController;
use App\Services\PaymentGatewayAdapter;

class Phase6Test
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "=== Running Phase 6 Tests: Keuangan & Akuntansi ===\n";

        $this->testChartOfAccountsAndCashAccounts();
        $this->testRecordExpenseAndDoubleEntryJournals();
        $this->testPaymentGatewayQrisAndWebhookIdempotency();
        $this->testFinancialStatementsBalance();

        echo "=== All Phase 6 Tests Passed Successfully! ===\n\n";
    }

    private function testChartOfAccountsAndCashAccounts(): void
    {
        echo "Testing Chart of Accounts and Cash Accounts... ";

        // Verify standard COA accounts exist: 1101, 1120, 2101, 3101, 4101, 5101, 5205
        $codes = ['1101', '1120', '2101', '3101', '4101', '5101', '5205'];
        foreach ($codes as $code) {
            $acc = $this->db->fetch("SELECT * FROM accounts WHERE code = :c", [':c' => $code]);
            if (!$acc) {
                throw new \Exception("Required COA account code {$code} not found in database.");
            }
        }

        // Verify cash accounts exist for branches
        $cashAccounts = $this->db->fetchAll("SELECT * FROM cash_accounts");
        if (count($cashAccounts) < 3) {
            throw new \Exception("Expected at least 3 operational cash/bank accounts, found " . count($cashAccounts));
        }

        echo "PASS\n";
    }

    private function testRecordExpenseAndDoubleEntryJournals(): void
    {
        echo "Testing Expense Recording and Double-Entry Journal Creation... ";

        // Log in as Super Admin
        Session::start();
        $user = $this->db->fetch("SELECT * FROM users WHERE username = 'superadmin'");
        Auth::login($user);

        // Pick branch 1 and its primary cash account
        $branchId = 1;
        $branch = $this->db->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $branchId]);
        $cashAcc = $this->db->fetch("SELECT * FROM cash_accounts WHERE branch_id = :b LIMIT 1", [':b' => $branchId]);
        $category = $this->db->fetch("SELECT * FROM expense_categories WHERE is_active = 1 LIMIT 1");

        $initialBalance = (int)$cashAcc['balance'];
        $expenseAmount = 150000; // Rp 150.000

        // Ensure sufficient balance for test
        if ($initialBalance < $expenseAmount) {
            $this->db->update('cash_accounts', ['balance' => $initialBalance + 500000], 'id = :id', [':id' => $cashAcc['id']]);
            $cashAcc['balance'] += 500000;
            $initialBalance = $cashAcc['balance'];
        }

        // Mock POST request to storeExpense
        $_POST = [
            'branch_id' => (string)$branchId,
            'category_id' => (string)$category['id'],
            'cash_account_id' => (string)$cashAcc['id'],
            'amount' => '150.000',
            'expense_date' => date('Y-m-d'),
            'paid_to' => 'Toko Plastik & Kimia Barokah',
            'description' => 'Pembelian deterjen cair dan plastik laundry 5 roll',
        ];

        // Call storeExpense via output buffering since back() may redirect
        ob_start();
        try {
            $controller = new AccountingController();
            $controller->storeExpense();
        } catch (\Throwable $e) {
            // Expected redirect or header exit
        }
        ob_end_clean();

        // Verify expense created
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE branch_id = :b AND amount = :a ORDER BY id DESC LIMIT 1",
            [':b' => $branchId, ':a' => $expenseAmount]
        );
        if (!$expense) {
            throw new \Exception("Expense record was not found in database.");
        }

        // Verify cash account balance was deducted accurately
        $updatedCashAcc = $this->db->fetch("SELECT * FROM cash_accounts WHERE id = :id", [':id' => $cashAcc['id']]);
        if ((int)$updatedCashAcc['balance'] !== ($initialBalance - $expenseAmount)) {
            throw new \Exception("Cash account balance deduction mismatch. Expected " . ($initialBalance - $expenseAmount) . ", got " . $updatedCashAcc['balance']);
        }

        // Verify cash transaction was logged
        $cashTx = $this->db->fetch(
            "SELECT * FROM cash_transactions WHERE cash_account_id = :ca ORDER BY id DESC LIMIT 1",
            [':ca' => $cashAcc['id']]
        );
        if (!$cashTx || (int)$cashTx['amount'] !== $expenseAmount || $cashTx['type'] !== 'out') {
            throw new \Exception("Cash transaction 'out' record not properly logged.");
        }

        // Verify double-entry journal entry was posted and balanced
        $journal = $this->db->fetch(
            "SELECT * FROM journal_entries WHERE reference_type = 'expense' AND reference_id = :rid",
            [':rid' => $expense['id']]
        );
        if (!$journal) {
            throw new \Exception("Journal entry was not created for expense.");
        }
        if ((int)$journal['is_balanced'] !== 1 || (int)$journal['total_debit'] !== $expenseAmount || (int)$journal['total_credit'] !== $expenseAmount) {
            throw new \Exception("Journal entry is not balanced. Debit: {$journal['total_debit']}, Credit: {$journal['total_credit']}");
        }

        // Verify debit and credit lines
        $lines = $this->db->fetchAll("SELECT * FROM journal_entry_lines WHERE journal_entry_id = :jid", [':jid' => $journal['id']]);
        if (count($lines) !== 2) {
            throw new \Exception("Expected 2 journal entry lines, found " . count($lines));
        }

        $sumDebit = array_sum(array_column($lines, 'debit'));
        $sumCredit = array_sum(array_column($lines, 'credit'));
        if ($sumDebit !== $expenseAmount || $sumCredit !== $expenseAmount) {
            throw new \Exception("Journal lines sums do not match expense amount.");
        }

        echo "PASS\n";
    }

    private function testPaymentGatewayQrisAndWebhookIdempotency(): void
    {
        echo "Testing QRIS Payment Generation and Webhook Idempotency... ";

        // Create dedicated fresh test order
        $branch = $this->db->fetch("SELECT * FROM branches WHERE id = 1");
        $customer = $this->db->fetch("SELECT * FROM customers LIMIT 1");
        $now = date('Y-m-d H:i:s');
        $testOrderNo = 'LP-TEST-' . date('His') . '-' . rand(100, 999);
        $totalAmt = 75000;

        $orderId = $this->db->insert('orders', [
            'company_id' => $branch['company_id'],
            'branch_id' => $branch['id'],
            'customer_id' => $customer['id'],
            'user_id' => 1,
            'order_no' => $testOrderNo,
            'tracking_token' => bin2hex(random_bytes(16)),
            'order_type' => 'regular',
            'est_completion_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'total_weight' => 2.5,
            'total_pieces' => 5,
            'subtotal' => $totalAmt,
            'addon_total' => 0,
            'discount_total' => 0,
            'voucher_discount' => 0,
            'tax_amount' => 0,
            'grand_total' => $totalAmt,
            'paid_amount' => 0,
            'balance_due' => $totalAmt,
            'payment_status' => 'unpaid',
            'production_status' => 'received',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $order = $this->db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
        $customerName = $customer['name'] ?? 'Pelanggan Test';

        $adapter = new PaymentGatewayAdapter();
        $qrisResult = $adapter->createQrisTransaction($order['id'], $order['balance_due'], $customerName);

        if (empty($qrisResult['transaction_id']) || empty($qrisResult['qr_string'])) {
            throw new \Exception("Failed to generate QRIS transaction.");
        }

        $txId = $qrisResult['transaction_id'];

        // Simulate incoming webhook from Payment Gateway
        $webhookPayload = [
            'transaction_id' => $txId,
            'order_no' => $order['order_no'],
            'amount' => $order['balance_due'],
            'status' => 'paid',
            'payment_type' => 'qris',
            'transaction_time' => date('Y-m-d H:i:s'),
        ];
        $signature = hash_hmac('sha256', $txId . $order['order_no'] . $order['balance_due'], 'laundrypro_secret_key');

        // First webhook call: should process successfully
        $res1 = $adapter->handleWebhook($webhookPayload, $signature);
        if (!($res1['success'] ?? false)) {
            throw new \Exception("Webhook handling failed: " . ($res1['message'] ?? 'Unknown error'));
        }

        // Verify order is now paid
        $updatedOrder = $this->db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $order['id']]);
        if ($updatedOrder['payment_status'] !== 'paid' || (int)$updatedOrder['balance_due'] !== 0) {
            throw new \Exception("Order payment status was not updated to 'paid'. Current: " . $updatedOrder['payment_status']);
        }

        // Verify payment record created
        $payment = $this->db->fetch(
            "SELECT * FROM payments WHERE order_id = :oid AND reference_no = :ref",
            [':oid' => $order['id'], ':ref' => $txId]
        );
        if (!$payment) {
            throw new \Exception("Payment record was not created for QRIS webhook.");
        }

        // Verify journal entry created for QRIS payment
        $journal = $this->db->fetch(
            "SELECT * FROM journal_entries WHERE reference_type = 'payment' AND reference_id = :pid",
            [':pid' => $payment['id']]
        );
        if (!$journal || (int)$journal['is_balanced'] !== 1) {
            throw new \Exception("Journal entry for QRIS payment missing or unbalanced.");
        }

        // Second webhook call: should be idempotent and not create duplicate payments or journals
        $paymentCountBefore = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM payments WHERE order_id = :oid", [':oid' => $order['id']]);
        $journalCountBefore = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM journal_entries WHERE reference_type = 'payment'");

        $res2 = $adapter->handleWebhook($webhookPayload, $signature);
        if (!($res2['success'] ?? false)) {
            throw new \Exception("Idempotent webhook call returned failure.");
        }

        $paymentCountAfter = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM payments WHERE order_id = :oid", [':oid' => $order['id']]);
        $journalCountAfter = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM journal_entries WHERE reference_type = 'payment'");

        if ($paymentCountAfter !== $paymentCountBefore || $journalCountAfter !== $journalCountBefore) {
            throw new \Exception("Webhook is not idempotent! Duplicate payment or journal entries were created.");
        }

        echo "PASS\n";
    }

    private function testFinancialStatementsBalance(): void
    {
        echo "Testing Financial Statements and Balance Sheet Integrity... ";

        // Verify all journal entries in the database are balanced
        $unbalanced = $this->db->fetchAll("SELECT * FROM journal_entries WHERE total_debit != total_credit OR is_balanced != 1");
        if (!empty($unbalanced)) {
            throw new \Exception("Found " . count($unbalanced) . " unbalanced journal entries in database!");
        }

        // Calculate total debits and credits across all journal lines in the system
        $totals = $this->db->fetch("SELECT SUM(debit) AS total_debit, SUM(credit) AS total_credit FROM journal_entry_lines");
        if ((int)$totals['total_debit'] !== (int)$totals['total_credit']) {
            throw new \Exception("Total debit (" . $totals['total_debit'] . ") does not equal total credit (" . $totals['total_credit'] . ") across all journal lines!");
        }

        echo "PASS\n";
    }
}

$test = new Phase6Test();
$test->runAll();
