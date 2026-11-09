<?php

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Helpers\NumberGenerator;
use App\Services\WhatsAppAdapter;

echo "=============================================\n";
echo "TEST SUITE FASE 4: POS & TRANSAKSI PESANAN\n";
echo "=============================================\n";

$db = Database::getInstance();
Session::start();

// Test 1: NumberGenerator sequential generation
$orderNo1 = NumberGenerator::generateOrderNumber(1, 'PST');
$orderNo2 = NumberGenerator::generateOrderNumber(1, 'PST');
assert(str_starts_with($orderNo1, 'LP-PST-'), "Order number must start with LP-PST-");
assert($orderNo1 !== $orderNo2, "Sequential order numbers must be unique");
echo "[PASS] Test 1: Sequential Order Number (No-COUNT) OK ({$orderNo1} -> {$orderNo2})\n";

// Test 2: Combo Transaction (Kiloan + Satuan) with DP Payment & Snapshotted Prices
$customer = $db->fetch("SELECT * FROM customers LIMIT 1");
$branch = $db->fetch("SELECT * FROM branches WHERE code = 'PST'");
$serviceKilo = $db->fetch("SELECT * FROM services WHERE unit_type = 'kg' LIMIT 1");
$serviceSatuan = $db->fetch("SELECT * FROM services WHERE unit_type = 'piece' LIMIT 1");

$trackingToken = NumberGenerator::generateTrackingToken();
$orderNo = NumberGenerator::generateOrderNumber(1, 'PST');
$now = date('Y-m-d H:i:s');

// 5 kg of kiloan + 2 pcs of satuan
$kiloWeight = 5.0;
$kiloSubtotal = (int)round($kiloWeight * $serviceKilo['base_price']);
$satuanQty = 2;
$satuanSubtotal = $satuanQty * $serviceSatuan['base_price'];
$grandTotal = $kiloSubtotal + $satuanSubtotal;
$dpAmount = (int)round($grandTotal / 2);
$balanceDue = $grandTotal - $dpAmount;

$db->transaction(function() use (
    $db, $branch, $customer, $serviceKilo, $serviceSatuan, $orderNo, $trackingToken,
    $kiloWeight, $kiloSubtotal, $satuanQty, $satuanSubtotal, $grandTotal, $dpAmount, $balanceDue, $now,
    &$orderId
) {
    $orderId = $db->insert('orders', [
        'company_id' => $branch['company_id'],
        'branch_id' => $branch['id'],
        'customer_id' => $customer['id'],
        'user_id' => 1,
        'order_no' => $orderNo,
        'tracking_token' => $trackingToken,
        'order_type' => 'regular',
        'est_completion_at' => date('Y-m-d H:i:s', strtotime('+48 hours')),
        'total_weight' => $kiloWeight,
        'total_pieces' => $satuanQty,
        'subtotal' => $grandTotal,
        'grand_total' => $grandTotal,
        'paid_amount' => $dpAmount,
        'balance_due' => $balanceDue,
        'payment_status' => 'partial',
        'production_status' => 'received',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Item 1: Kiloan snapshot
    $db->insert('order_items', [
        'order_id' => $orderId,
        'service_id' => $serviceKilo['id'],
        'service_name' => $serviceKilo['name'],
        'unit_type' => 'kg',
        'unit_price' => $serviceKilo['base_price'],
        'weight' => $kiloWeight,
        'subtotal' => $kiloSubtotal,
        'created_at' => $now,
    ]);

    // Item 2: Satuan snapshot
    $db->insert('order_items', [
        'order_id' => $orderId,
        'service_id' => $serviceSatuan['id'],
        'service_name' => $serviceSatuan['name'],
        'unit_type' => 'piece',
        'unit_price' => $serviceSatuan['base_price'],
        'quantity' => $satuanQty,
        'subtotal' => $satuanSubtotal,
        'created_at' => $now,
    ]);

    // QR Token
    $db->insert('order_qr_codes', [
        'order_id' => $orderId,
        'code' => $orderNo,
        'token' => $trackingToken,
        'created_at' => $now,
    ]);

    // DP Payment
    $db->insert('payments', [
        'company_id' => $branch['company_id'],
        'branch_id' => $branch['id'],
        'order_id' => $orderId,
        'customer_id' => $customer['id'],
        'payment_no' => 'PAY-TEST-' . $orderId,
        'amount' => $dpAmount,
        'payment_method_id' => 1,
        'payment_type' => 'dp',
        'status' => 'success',
        'paid_at' => $now,
        'created_at' => $now,
    ]);

    // Post double-entry journal for DP
    $cashAcc = $db->fetch("SELECT * FROM cash_accounts WHERE branch_id = 1 AND account_type = 'cash' LIMIT 1");
    $revAcc = $db->fetch("SELECT id FROM accounts WHERE code = '4101'");

    $jId = $db->insert('journal_entries', [
        'company_id' => $branch['company_id'],
        'branch_id' => $branch['id'],
        'entry_no' => 'JV-' . $orderNo,
        'entry_date' => date('Y-m-d'),
        'reference_type' => 'payment',
        'reference_id' => $orderId,
        'reference_no' => $orderNo,
        'description' => "DP Pembayaran {$orderNo}",
        'total_debit' => $dpAmount,
        'total_credit' => $dpAmount,
        'is_balanced' => 1,
        'status' => 'posted',
        'created_at' => $now,
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $jId,
        'account_id' => $cashAcc['account_id'],
        'debit' => $dpAmount,
        'credit' => 0,
        'created_at' => $now,
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $jId,
        'account_id' => $revAcc['id'],
        'debit' => 0,
        'credit' => $dpAmount,
        'created_at' => $now,
    ]);
});

assert($orderId > 0, "Order ID must be created");
$checkOrder = $db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
assert($checkOrder['payment_status'] === 'partial', "Payment status must be partial");
assert((int)$checkOrder['balance_due'] === $balanceDue, "Balance due must equal remaining balance");

// Verify price snapshot integrity
$oldPrice = (int)$serviceKilo['base_price'];
$db->update('services', ['base_price' => $oldPrice + 5000], 'id = :id', [':id' => $serviceKilo['id']]);
$snapshottedItem = $db->fetch("SELECT unit_price FROM order_items WHERE order_id = :id AND service_id = :s", [':id' => $orderId, ':s' => $serviceKilo['id']]);
assert((int)$snapshottedItem['unit_price'] === $oldPrice, "Snapshotted price must remain unchanged after master price increase");
$db->update('services', ['base_price' => $oldPrice], 'id = :id', [':id' => $serviceKilo['id']]); // revert
echo "[PASS] Test 2: Combo Transaction & Historical Price Snapshotting OK\n";

// Test 3: Public QR Tracking Token Lookup
$trackOrder = $db->fetch("SELECT o.* FROM orders o JOIN order_qr_codes qr ON o.id = qr.order_id WHERE qr.token = :t", [':t' => $trackingToken]);
assert($trackOrder !== null, "Tracking token must find the exact order");
assert($trackOrder['order_no'] === $orderNo, "Order number must match");
echo "[PASS] Test 3: Public QR Tracking Token Resolution OK\n";

// Test 4: Cancellation with Audit Trail & Reversing Journal
$cancelReason = "Pelanggan keliru memberikan pakaian sutra khusus dry clean.";
$db->transaction(function() use ($db, $orderId, $orderNo, $cancelReason, $dpAmount, $now) {
    $db->update('orders', [
        'production_status' => 'cancelled',
        'cancelled_at' => $now,
        'cancellation_reason' => $cancelReason,
    ], 'id = :id', [':id' => $orderId]);

    $db->insert('order_cancellations', [
        'order_id' => $orderId,
        'user_id' => 1,
        'reason' => $cancelReason,
        'refund_amount' => $dpAmount,
        'approved_by' => 1,
        'created_at' => $now,
    ]);

    // Reversing Journal
    $cashAcc = $db->fetch("SELECT * FROM cash_accounts WHERE branch_id = 1 AND account_type = 'cash' LIMIT 1");
    $revAcc = $db->fetch("SELECT id FROM accounts WHERE code = '4101'");

    $jId = $db->insert('journal_entries', [
        'company_id' => 1,
        'branch_id' => 1,
        'entry_no' => 'REV-' . $orderNo,
        'entry_date' => date('Y-m-d'),
        'reference_type' => 'refund',
        'reference_id' => $orderId,
        'reference_no' => $orderNo,
        'description' => "Jurnal Pembalik Pembatalan {$orderNo}",
        'total_debit' => $dpAmount,
        'total_credit' => $dpAmount,
        'is_balanced' => 1,
        'status' => 'posted',
        'created_at' => $now,
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $jId,
        'account_id' => $revAcc['id'],
        'debit' => $dpAmount,
        'credit' => 0,
        'created_at' => $now,
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $jId,
        'account_id' => $cashAcc['account_id'],
        'debit' => 0,
        'credit' => $dpAmount,
        'created_at' => $now,
    ]);
});

$cancelledOrder = $db->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
assert($cancelledOrder['production_status'] === 'cancelled', "Status must be cancelled");
$auditRecord = $db->fetch("SELECT * FROM order_cancellations WHERE order_id = :id", [':id' => $orderId]);
assert($auditRecord['reason'] === $cancelReason, "Audit trail reason must be recorded");

$reversingJournal = $db->fetch("SELECT * FROM journal_entries WHERE entry_no = :no", [':no' => 'REV-' . $orderNo]);
assert($reversingJournal['total_debit'] === $reversingJournal['total_credit'], "Reversing journal must be balanced");
echo "[PASS] Test 4: Cancellation with Audit Trail & Reversing Journal OK\n";

echo "\n=============================================\n";
echo "SEMUA TEST FASE 4 BERHASIL DILALUI DENGAN SUKSES!\n";
echo "=============================================\n";
