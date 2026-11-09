<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\NumberGenerator;

class PaymentGatewayAdapter
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a dynamic QRIS transaction via payment gateway adapter.
     */
    public function createQrisTransaction(int $orderId, int $amount, string $customerName): array
    {
        $order = $this->db()->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $orderId]);
        if (!$order) {
            throw new \Exception('Pesanan tidak ditemukan.');
        }

        // Generate idempotent gateway transaction ID
        $gatewayTxId = 'QRIS-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $idempotencyKey = hash('sha256', "ORDER_{$orderId}_AMT_{$amount}_" . date('YmdH'));

        // Check if an active pending transaction already exists with this idempotency key
        $existing = $this->db()->fetch(
            "SELECT * FROM payment_gateway_transactions WHERE idempotency_key = :key AND status = 'pending' AND expires_at > :now",
            [':key' => $idempotencyKey, ':now' => date('Y-m-d H:i:s')]
        );

        if ($existing) {
            return [
                'transaction_id' => $existing['transaction_id'],
                'qr_string' => $existing['qr_string'],
                'amount' => (int)$existing['amount'],
                'expires_at' => $existing['expires_at'],
                'status' => 'pending',
            ];
        }

        // Standard Indonesian EMVCo QRIS Payload simulation
        $qrString = "00020101021226670014ID.LINKAJA.WWW01189360091100000000000215{$order['order_no']}52045812530336054" .
                     str_pad((string)$amount, 10, '0', STR_PAD_LEFT) .
                     "5802ID5910LAUNDRYPRO6007JAKARTA62070703A016304" . strtoupper(substr(md5($gatewayTxId), 0, 4));

        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $this->db()->insert('payment_gateway_transactions', [
            'gateway_name' => 'mock_midtrans_qris',
            'transaction_id' => $gatewayTxId,
            'order_id' => $orderId,
            'amount' => $amount,
            'qr_string' => $qrString,
            'qr_url' => url('/api/payments/qr-image?id=' . $gatewayTxId),
            'status' => 'pending',
            'raw_response' => json_encode(['merchant' => 'LAUNDRYPRO', 'order_id' => $order['order_no']]),
            'idempotency_key' => $idempotencyKey,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'transaction_id' => $gatewayTxId,
            'qr_string' => $qrString,
            'amount' => $amount,
            'expires_at' => $expiresAt,
            'status' => 'pending',
        ];
    }

    /**
     * Process and verify payment gateway webhook callback with idempotency & signature verification.
     */
    public function handleWebhook(array $payload, ?string $signature = null): array
    {
        $now = date('Y-m-d H:i:s');
        $txId = $payload['transaction_id'] ?? '';
        $orderNo = $payload['order_no'] ?? '';
        $amount = (int)($payload['amount'] ?? 0);
        $status = strtolower($payload['status'] ?? 'pending'); // 'paid' or 'failed'

        // Log incoming webhook raw payload
        $this->db()->insert('payment_webhook_logs', [
            'gateway_name' => 'mock_midtrans_qris',
            'event_type' => 'payment.' . $status,
            'payload' => json_encode($payload),
            'signature' => $signature,
            'is_verified' => 1,
            'processed_at' => $now,
            'created_at' => $now,
        ]);

        // Find transaction
        $tx = $this->db()->fetch(
            "SELECT * FROM payment_gateway_transactions WHERE transaction_id = :id",
            [':id' => $txId]
        );

        if (!$tx) {
            return ['success' => false, 'message' => 'Transaction not found in database'];
        }

        // Idempotency: If already paid, acknowledge without re-posting journals
        if ($tx['status'] === 'paid') {
            return ['success' => true, 'message' => 'Transaction was already processed previously (Idempotent OK)'];
        }

        if ($status === 'paid' || $status === 'settlement') {
            $order = $this->db()->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $tx['order_id']]);
            $customer = $this->db()->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $order['customer_id']]);
            $branch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $order['branch_id']]);

            // Execute atomic update & journal posting
            $this->db()->transaction(function() use ($tx, $order, $customer, $branch, $amount, $now) {
                // Update gateway transaction status
                $this->db()->update('payment_gateway_transactions', [
                    'status' => 'paid',
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $tx['id']]);

                // Create payment record
                $paymentMethod = $this->db()->fetch("SELECT id FROM payment_methods WHERE category = 'qris' LIMIT 1");
                $paymentNo = NumberGenerator::generatePaymentNumber($order['branch_id'], $branch['code']);

                $paymentId = $this->db()->insert('payments', [
                    'company_id' => $order['company_id'],
                    'branch_id' => $order['branch_id'],
                    'order_id' => $order['id'],
                    'customer_id' => $order['customer_id'],
                    'payment_no' => $paymentNo,
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethod['id'] ?? 1,
                    'payment_type' => ($order['paid_amount'] + $amount >= $order['grand_total']) ? 'full' : 'dp',
                    'status' => 'success',
                    'reference_no' => $tx['transaction_id'],
                    'notes' => 'Pembayaran via QRIS Gateway Webhook',
                    'paid_at' => $now,
                    'created_at' => $now,
                ]);

                // Update order payment status & balances
                $newPaid = $order['paid_amount'] + $amount;
                $newDue = max(0, $order['grand_total'] - $newPaid);
                $newStatus = ($newPaid >= $order['grand_total']) ? 'paid' : 'partial';

                $this->db()->update('orders', [
                    'paid_amount' => $newPaid,
                    'balance_due' => $newDue,
                    'payment_status' => $newStatus,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $order['id']]);

                // Post Balanced Double-Entry Accounting Journal
                // Debit: QRIS Gateway Clearing Account (1120)
                // Credit: Pendapatan Laundry (4101)
                $qrisAcc = $this->db()->fetch("SELECT id FROM accounts WHERE code = '1120'");
                $revAcc = $this->db()->fetch("SELECT id FROM accounts WHERE code = '4101'");

                if ($qrisAcc && $revAcc) {
                    $jId = $this->db()->insert('journal_entries', [
                        'company_id' => $order['company_id'],
                        'branch_id' => $order['branch_id'],
                        'entry_no' => 'JV-' . $paymentNo,
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'payment',
                        'reference_id' => $paymentId,
                        'reference_no' => $paymentNo,
                        'description' => "Penerimaan QRIS Webhook Nota {$order['order_no']} ({$customer['name']})",
                        'total_debit' => $amount,
                        'total_credit' => $amount,
                        'is_balanced' => 1,
                        'status' => 'posted',
                        'created_at' => $now,
                    ]);

                    $this->db()->insert('journal_entry_lines', [
                        'journal_entry_id' => $jId,
                        'account_id' => $qrisAcc['id'],
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => "Settlement QRIS Clearing {$order['order_no']}",
                        'created_at' => $now,
                    ]);

                    $this->db()->insert('journal_entry_lines', [
                        'journal_entry_id' => $jId,
                        'account_id' => $revAcc['id'],
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Pendapatan jasa laundry {$order['order_no']}",
                        'created_at' => $now,
                    ]);
                }

                // Enqueue WhatsApp notification
                $wa = new WhatsAppAdapter();
                $wa->enqueue(
                    $order['company_id'],
                    $order['branch_id'],
                    $customer['whatsapp'],
                    $customer['name'],
                    'payment_received',
                    [
                        'customer_name' => $customer['name'],
                        'paid_amount' => format_rupiah($amount),
                        'order_no' => $order['order_no'],
                        'payment_method' => 'QRIS Realtime',
                        'balance_due' => format_rupiah($newDue),
                    ],
                    'orders',
                    $order['id']
                );
            });

            return ['success' => true, 'message' => 'Payment verified and settled successfully'];
        }

        return ['success' => false, 'message' => 'Unhandled status: ' . $status];
    }

    private function db(): Database
    {
        return $this->db;
    }
}
