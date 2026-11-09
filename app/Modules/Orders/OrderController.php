<?php

namespace App\Modules\Orders;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class OrderController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('view');

        $activeBranchId = Auth::getActiveBranchId();
        $search = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $paymentStatus = $_GET['payment_status'] ?? '';
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $where = "1=1";
        $params = [];

        if ($branchFilter > 0) {
            $where .= " AND o.branch_id = :branch_id";
            $params[':branch_id'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND o.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        if (!empty($search)) {
            $where .= " AND (o.order_no LIKE :q OR c.name LIKE :q OR c.whatsapp LIKE :q)";
            $params[':q'] = "%{$search}%";
        }

        if (!empty($status)) {
            $where .= " AND o.production_status = :status";
            $params[':status'] = $status;
        }

        if (!empty($paymentStatus)) {
            $where .= " AND o.payment_status = :payment_status";
            $params[':payment_status'] = $paymentStatus;
        }

        if (!empty($startDate) && !empty($endDate)) {
            $where .= " AND date(o.created_at) BETWEEN :s AND :e";
            $params[':s'] = $startDate;
            $params[':e'] = $endDate;
        }

        $orders = $this->db()->fetchAll(
            "SELECT o.*, c.name AS customer_name, c.whatsapp AS customer_phone, b.name AS branch_name, u.name AS cashier_name
             FROM orders o
             JOIN customers c ON o.customer_id = c.id
             JOIN branches b ON o.branch_id = b.id
             JOIN users u ON o.user_id = u.id
             WHERE {$where}
             ORDER BY o.id DESC
             LIMIT 100",
            $params
        );

        $branches = $this->db()->fetchAll("SELECT id, name, code FROM branches WHERE is_active = 1");

        $this->view('orders.index', [
            'title' => 'Manajemen Pesanan',
            'activeMenu' => 'orders',
            'orders' => $orders,
            'branches' => $branches,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPayment' => $paymentStatus,
            'selectedBranch' => $branchFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function show(int|string $id): void
    {
        Permission::abortUnless('view');

        $id = (int)$id;
        $order = $this->db()->fetch(
            "SELECT o.*, c.name AS customer_name, c.whatsapp AS customer_phone, c.address AS customer_address,
                    b.name AS branch_name, b.phone AS branch_phone, b.address AS branch_address,
                    u.name AS cashier_name, qr.token AS qr_token
             FROM orders o
             JOIN customers c ON o.customer_id = c.id
             JOIN branches b ON o.branch_id = b.id
             JOIN users u ON o.user_id = u.id
             LEFT JOIN order_qr_codes qr ON o.id = qr.order_id
             WHERE o.id = :id",
            [':id' => $id]
        );

        if (!$order) {
            $this->redirect(url('/orders'), 'Pesanan tidak ditemukan.', 'error');
        }

        // Branch scoping check
        if (!Auth::canAccessBranch((int)$order['branch_id'])) {
            Permission::abortUnless('manage');
        }

        $items = $this->db()->fetchAll(
            "SELECT oi.* FROM order_items oi WHERE oi.order_id = :id ORDER BY oi.id ASC",
            [':id' => $id]
        );

        // Fetch garment details for items
        foreach ($items as &$item) {
            $item['details'] = $this->db()->fetchAll(
                "SELECT * FROM order_item_details WHERE order_item_id = :item_id",
                [':item_id' => $item['id']]
            );
        }
        unset($item);

        $payments = $this->db()->fetchAll(
            "SELECT p.*, pm.name AS method_name, u.name AS receiver_name
             FROM payments p
             JOIN payment_methods pm ON p.payment_method_id = pm.id
             LEFT JOIN users u ON p.received_by_user_id = u.id
             WHERE p.order_id = :id
             ORDER BY p.id ASC",
            [':id' => $id]
        );

        $statusHistory = $this->db()->fetchAll(
            "SELECT h.*, u.name AS user_name
             FROM order_status_history h
             LEFT JOIN users u ON h.changed_by_user_id = u.id
             WHERE h.order_id = :id
             ORDER BY h.id ASC",
            [':id' => $id]
        );

        $productionJob = $this->db()->fetch(
            "SELECT pj.*, ps.name AS stage_name, u.name AS operator_name, m.name AS machine_name
             FROM production_jobs pj
             LEFT JOIN production_stages ps ON pj.current_stage_id = ps.id
             LEFT JOIN users u ON pj.assigned_user_id = u.id
             LEFT JOIN machines m ON pj.machine_id = m.id
             WHERE pj.order_id = :id",
            [':id' => $id]
        );

        $cancellation = $this->db()->fetch(
            "SELECT oc.*, u.name AS requested_by_name, ua.name AS approved_by_name
             FROM order_cancellations oc
             LEFT JOIN users u ON oc.user_id = u.id
             LEFT JOIN users ua ON oc.approved_by = ua.id
             WHERE oc.order_id = :id",
            [':id' => $id]
        );

        $this->view('orders.show', [
            'title' => 'Nota Pesanan: ' . $order['order_no'],
            'activeMenu' => 'orders',
            'order' => $order,
            'items' => $items,
            'payments' => $payments,
            'statusHistory' => $statusHistory,
            'productionJob' => $productionJob,
            'cancellation' => $cancellation,
        ]);
    }

    public function cancel(int|string $id): void
    {
        Permission::abortUnless('cancel');

        $id = (int)$id;
        $reason = trim($_POST['reason'] ?? '');
        $refundAmount = (int)str_replace(['.', ','], '', $_POST['refund_amount'] ?? '0');

        if (empty($reason)) {
            $this->back('Alasan pembatalan pesanan wajib dicatat untuk audit trail.', 'error');
        }

        $order = $this->db()->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $id]);
        if (!$order) {
            $this->back('Pesanan tidak ditemukan.', 'error');
        }

        if ($order['production_status'] === 'cancelled') {
            $this->back('Pesanan ini sudah dibatalkan sebelumnya.', 'warning');
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->transaction(function() use ($id, $order, $reason, $refundAmount, $now) {
            // Update order status to cancelled
            $this->db()->update('orders', [
                'production_status' => 'cancelled',
                'cancelled_at' => $now,
                'cancellation_reason' => $reason,
                'cancelled_by' => Auth::id(),
                'updated_at' => $now,
            ], 'id = :id', [':id' => $id]);

            // Record cancellation audit
            $this->db()->insert('order_cancellations', [
                'order_id' => $id,
                'user_id' => Auth::id() ?: 1,
                'reason' => $reason,
                'refund_amount' => $refundAmount,
                'approved_by' => Auth::id(),
                'created_at' => $now,
            ]);

            // Record status history
            $this->db()->insert('order_status_history', [
                'order_id' => $id,
                'from_status' => $order['production_status'],
                'to_status' => 'cancelled',
                'notes' => "Dibatalkan: {$reason}",
                'changed_by_user_id' => Auth::id(),
                'created_at' => $now,
            ]);

            // Post reversing journal if refund occurs
            if ($refundAmount > 0) {
                $cashAcc = $this->db()->fetch("SELECT * FROM cash_accounts WHERE branch_id = :b AND account_type = 'cash' LIMIT 1", [':b' => $order['branch_id']]);
                $revAcc = $this->db()->fetch("SELECT id FROM accounts WHERE code = '4101'");

                if ($cashAcc && $revAcc) {
                    $jId = $this->db()->insert('journal_entries', [
                        'company_id' => $order['company_id'],
                        'branch_id' => $order['branch_id'],
                        'entry_no' => 'REV-' . $order['order_no'],
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'refund',
                        'reference_id' => $id,
                        'reference_no' => $order['order_no'],
                        'description' => "Jurnal Pembalik Pembatalan Pesanan {$order['order_no']}",
                        'total_debit' => $refundAmount,
                        'total_credit' => $refundAmount,
                        'is_balanced' => 1,
                        'status' => 'posted',
                        'created_by' => Auth::id(),
                        'created_at' => $now,
                    ]);

                    // Debit: Pendapatan Laundry (mengurangi pendapatan)
                    $this->db()->insert('journal_entry_lines', [
                        'journal_entry_id' => $jId,
                        'account_id' => $revAcc['id'],
                        'debit' => $refundAmount,
                        'credit' => 0,
                        'memo' => "Pembalik pendapatan laundry {$order['order_no']}",
                        'created_at' => $now,
                    ]);

                    // Credit: Kas Kasir (kas keluar refund)
                    $this->db()->insert('journal_entry_lines', [
                        'journal_entry_id' => $jId,
                        'account_id' => $cashAcc['account_id'],
                        'debit' => 0,
                        'credit' => $refundAmount,
                        'memo' => "Pengembalian dana pelanggan {$order['order_no']}",
                        'created_at' => $now,
                    ]);

                    $this->db()->update('cash_accounts', ['balance' => $cashAcc['balance'] - $refundAmount], 'id = :id', [':id' => $cashAcc['id']]);
                }
            }
        });

        $this->logActivity('cancel_order', 'orders', $id, "Membatalkan pesanan No. {$order['order_no']}. Alasan: {$reason}");
        $this->redirect(url('/orders/' . $id), "Pesanan {$order['order_no']} berhasil dibatalkan dengan audit trail tercatat.", 'info');
    }

    public function print(int|string $id): void
    {
        Permission::abortUnless('print');

        $id = (int)$id;
        $order = $this->db()->fetch(
            "SELECT o.*, c.name AS customer_name, c.whatsapp AS customer_phone, c.address AS customer_address,
                    b.name AS branch_name, b.phone AS branch_phone, b.address AS branch_address,
                    u.name AS cashier_name, qr.token AS qr_token, comp.name AS company_name, comp.tagline AS company_tagline
             FROM orders o
             JOIN customers c ON o.customer_id = c.id
             JOIN branches b ON o.branch_id = b.id
             JOIN users u ON o.user_id = u.id
             JOIN companies comp ON o.company_id = comp.id
             LEFT JOIN order_qr_codes qr ON o.id = qr.order_id
             WHERE o.id = :id",
            [':id' => $id]
        );

        if (!$order) {
            exit('Nota pesanan tidak ditemukan.');
        }

        $items = $this->db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = :id",
            [':id' => $id]
        );

        $receiptSetting = $this->db()->fetch("SELECT * FROM receipt_settings WHERE company_id = :c LIMIT 1", [':c' => $order['company_id']]);
        $paperSize = $_GET['size'] ?? ($receiptSetting['paper_size'] ?? '58mm');

        $this->view('orders.print', [
            'order' => $order,
            'items' => $items,
            'setting' => $receiptSetting,
            'paperSize' => $paperSize,
            'trackingUrl' => url('/track/' . $order['qr_token']),
        ], 'layouts/print');
    }

    public function track(string $token): void
    {
        // Public order tracking view by secure random 32-character token
        $qr = $this->db()->fetch("SELECT order_id FROM order_qr_codes WHERE token = :t", [':t' => $token]);
        if (!$qr) {
            http_response_code(404);
            $this->view('orders.track_not_found', ['token' => $token], 'layouts/portal');
            return;
        }

        $orderId = (int)$qr['order_id'];
        $order = $this->db()->fetch(
            "SELECT o.id, o.order_no, o.order_type, o.est_completion_at, o.actual_completion_at,
                    o.total_weight, o.total_pieces, o.grand_total, o.paid_amount, o.balance_due,
                    o.payment_status, o.production_status, o.created_at,
                    c.name AS customer_name, b.name AS branch_name, b.phone AS branch_phone, b.address AS branch_address
             FROM orders o
             JOIN customers c ON o.customer_id = c.id
             JOIN branches b ON o.branch_id = b.id
             WHERE o.id = :id",
            [':id' => $orderId]
        );

        $items = $this->db()->fetchAll(
            "SELECT service_name, unit_type, quantity, weight FROM order_items WHERE order_id = :id",
            [':id' => $orderId]
        );

        $stages = $this->db()->fetchAll("SELECT * FROM production_stages ORDER BY sort_order ASC");
        $currentJob = $this->db()->fetch("SELECT * FROM production_jobs WHERE order_id = :id", [':id' => $orderId]);

        $this->view('orders.track', [
            'title' => 'Pelacakan Status Laundry: ' . $order['order_no'],
            'order' => $order,
            'items' => $items,
            'stages' => $stages,
            'currentJob' => $currentJob,
        ], 'layouts/portal');
    }
}
