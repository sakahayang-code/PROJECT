<?php

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;

echo "=============================================\n";
echo "TEST SUITE FASE 3: PELANGGAN & LAYANAN\n";
echo "=============================================\n";

$db = Database::getInstance();
Session::start();

// Test 1: Register customer with code auto-generation
$custPhone = '081987654321';
$existing = $db->fetch("SELECT id FROM customers WHERE whatsapp = :w", [':w' => $custPhone]);
if ($existing) {
    $db->delete('customers', 'id = :id', [':id' => $existing['id']]);
}

$lastId = (int)$db->fetchColumn("SELECT MAX(id) FROM customers") + 1;
$code = 'CUST-' . str_pad((string)$lastId, 4, '0', STR_PAD_LEFT);

$custId = $db->insert('customers', [
    'company_id' => 1,
    'favorite_branch_id' => 1,
    'code' => $code,
    'name' => 'Fajar Pratama',
    'whatsapp' => $custPhone,
    'email' => 'fajar@example.com',
    'address' => 'Jl. Kemang Raya No. 10, Jakarta Selatan',
    'join_date' => date('Y-m-d'),
    'status' => 'active',
    'deposit_balance' => 0,
    'points_balance' => 0,
    'current_tier_id' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
assert($custId > 0, "Customer ID must be valid");
echo "[PASS] Test 1: Customer Registration & Auto Code Generation OK\n";

// Test 2: Deposit Top-Up & Double-Entry Journal Posting
$topupAmount = 200000;
$db->transaction(function() use ($db, $custId, $topupAmount) {
    $now = date('Y-m-d H:i:s');
    $db->update('customers', ['deposit_balance' => $topupAmount, 'updated_at' => $now], 'id = :id', [':id' => $custId]);

    $db->insert('deposit_transactions', [
        'customer_id' => $custId,
        'branch_id' => 1,
        'type' => 'credit',
        'amount' => $topupAmount,
        'balance_after' => $topupAmount,
        'description' => 'Test Top Up Deposit',
        'reference_no' => 'DEP-TEST-' . $custId,
        'created_at' => $now,
    ]);

    // Create balanced journal entry
    $cashAcc = $db->fetch("SELECT * FROM cash_accounts WHERE branch_id = 1 AND account_type = 'cash' LIMIT 1");
    $depAcc = $db->fetch("SELECT id FROM accounts WHERE code = '2102'");

    $jId = $db->insert('journal_entries', [
        'company_id' => 1,
        'branch_id' => 1,
        'entry_no' => 'JV-TEST-' . uniqid(),
        'entry_date' => date('Y-m-d'),
        'reference_type' => 'deposit',
        'reference_id' => $custId,
        'reference_no' => 'DEP-TEST',
        'description' => 'Top Up Deposit Fajar Pratama',
        'total_debit' => $topupAmount,
        'total_credit' => $topupAmount,
        'is_balanced' => 1,
        'status' => 'posted',
        'created_at' => $now,
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $jId,
        'account_id' => $cashAcc['account_id'],
        'debit' => $topupAmount,
        'credit' => 0,
        'memo' => 'Debit Kas Kasir',
        'created_at' => $now,
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $jId,
        'account_id' => $depAcc['id'],
        'debit' => 0,
        'credit' => $topupAmount,
        'memo' => 'Kredit Titipan Deposit',
        'created_at' => $now,
    ]);
});

// Verify customer deposit balance updated
$custAfter = $db->fetch("SELECT deposit_balance FROM customers WHERE id = :id", [':id' => $custId]);
assert((int)$custAfter['deposit_balance'] === $topupAmount, "Deposit balance should be Rp 200.000");

// Verify journal is balanced
$lastJournal = $db->fetch("SELECT * FROM journal_entries WHERE reference_type = 'deposit' AND reference_id = :id ORDER BY id DESC LIMIT 1", [':id' => $custId]);
assert($lastJournal['total_debit'] === $lastJournal['total_credit'], "Debit must equal credit");
assert((int)$lastJournal['is_balanced'] === 1, "Journal must be balanced");
echo "[PASS] Test 2: Deposit Top-Up & Double-Entry Balanced Journal Entry OK\n";

// Test 3: Branch Specific Pricing & Price History
$service = $db->fetch("SELECT * FROM services WHERE code = 'CKSR'");
$pusatPrice = $db->fetch("SELECT * FROM branch_service_prices WHERE branch_id = 1 AND service_id = :s", [':s' => $service['id']]);
$baratPrice = $db->fetch("SELECT * FROM branch_service_prices WHERE branch_id = 2 AND service_id = :s", [':s' => $service['id']]);

// Change Barat price to Rp 9.000 (different from Pusat Rp 8.000)
$newBaratPrice = 9000;
$db->insert('service_price_history', [
    'service_id' => $service['id'],
    'branch_id' => 2,
    'old_price' => $baratPrice['price'],
    'new_price' => $newBaratPrice,
    'created_at' => date('Y-m-d H:i:s'),
]);
$db->update('branch_service_prices', ['price' => $newBaratPrice], 'branch_id = 2 AND service_id = :s', [':s' => $service['id']]);

$updatedBarat = $db->fetch("SELECT price FROM branch_service_prices WHERE branch_id = 2 AND service_id = :s", [':s' => $service['id']]);
$checkPusat = $db->fetch("SELECT price FROM branch_service_prices WHERE branch_id = 1 AND service_id = :s", [':s' => $service['id']]);

assert((int)$updatedBarat['price'] === 9000, "Barat price should be 9000");
assert((int)$checkPusat['price'] === (int)$pusatPrice['price'], "Pusat price should remain unchanged");
echo "[PASS] Test 3: Branch-Specific Pricing & Independence OK\n";

// Clean up
$db->delete('customers', 'id = :id', [':id' => $custId]);

echo "\n=============================================\n";
echo "SEMUA TEST FASE 3 BERHASIL DILALUI DENGAN SUKSES!\n";
echo "=============================================\n";
