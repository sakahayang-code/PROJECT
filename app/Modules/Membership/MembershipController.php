<?php

declare(strict_types=1);

namespace App\Modules\Membership;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Core\Validator;

class MembershipController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('customers.view');

        $tiers = $this->db()->fetchAll(
            "SELECT mt.*, COUNT(c.id) AS member_count
             FROM membership_tiers mt
             LEFT JOIN customers c ON c.current_tier_id = mt.id
             GROUP BY mt.id
             ORDER BY mt.min_spend ASC"
        );

        // Recent loyalty transactions
        $transactions = $this->db()->fetchAll(
            "SELECT lt.*, c.name AS customer_name, c.whatsapp AS customer_phone, c.whatsapp AS phone, c.code AS customer_code
             FROM loyalty_transactions lt
             JOIN customers c ON lt.customer_id = c.id
             ORDER BY lt.id DESC
             LIMIT 50"
        );

        $totalPointsIssued = (int)$this->db()->fetchColumn("SELECT SUM(points) FROM loyalty_transactions WHERE type = 'earn'");
        $totalPointsRedeemed = (int)$this->db()->fetchColumn("SELECT SUM(points) FROM loyalty_transactions WHERE type = 'redeem'");

        $this->view('membership.index', [
            'title' => 'Membership & Poin Loyalitas',
            'activeMenu' => 'membership_tiers',
            'tiers' => $tiers,
            'transactions' => $transactions,
            'totalPointsIssued' => $totalPointsIssued,
            'totalPointsRedeemed' => $totalPointsRedeemed,
        ]);
    }

    public function updateTier(): void
    {
        Permission::abortUnless('manage.settings');

        $tierId = (int)($_POST['tier_id'] ?? 0);
        $minSpend = (int)str_replace(['.', ','], '', $_POST['min_spend'] ?? '0');
        $minPoints = (int)($_POST['min_points'] ?? 0);
        $discountPercent = (float)($_POST['discount_percent'] ?? 0);
        $pointsMultiplier = (float)($_POST['points_multiplier'] ?? 1.0);
        $description = trim($_POST['description'] ?? '');

        $this->db()->update('membership_tiers', [
            'min_spend' => $minSpend,
            'min_points' => $minPoints,
            'discount_percent' => $discountPercent,
            'points_multiplier' => $pointsMultiplier,
            'description' => $description,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $tierId]);

        $this->redirect(url('/membership'), 'Pengaturan level membership berhasil diperbarui.', 'success');
    }

    public function adjustPoints(): void
    {
        Permission::abortUnless('customers.edit');

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $type = in_array($_POST['type'], ['earn', 'redeem', 'adjust']) ? $_POST['type'] : 'adjust';
        $points = abs((int)($_POST['points'] ?? 0));
        $description = trim($_POST['description'] ?? 'Penyesuaian poin manual oleh staf');

        if ($customerId <= 0 || $points <= 0) {
            $this->back('Pilih pelanggan dan masukkan jumlah poin yang valid.', 'error');
        }

        $customer = $this->db()->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $customerId]);
        if (!$customer) {
            $this->back('Pelanggan tidak ditemukan.', 'error');
        }

        $currentPoints = (int)$customer['points_balance'];
        if ($type === 'redeem' && $currentPoints < $points) {
            $this->back("Poin pelanggan tidak mencukupi (Saat ini: {$currentPoints} poin).", 'error');
        }

        $newPoints = ($type === 'redeem') ? ($currentPoints - $points) : ($currentPoints + $points);
        $now = date('Y-m-d H:i:s');

        $this->db()->transaction(function() use ($customer, $customerId, $type, $points, $newPoints, $description, $now) {
            // Update customer balance
            $this->db()->update('customers', [
                'points_balance' => $newPoints,
                'updated_at' => $now,
            ], 'id = :id', [':id' => $customerId]);

            // Insert loyalty transaction ledger
            $this->db()->insert('loyalty_transactions', [
                'company_id' => $customer['company_id'],
                'customer_id' => $customerId,
                'order_id' => null,
                'type' => $type,
                'points' => ($type === 'redeem') ? -$points : $points,
                'balance_after' => $newPoints,
                'description' => $description,
                'reference_no' => 'MANUAL-' . date('YmdHis'),
                'created_at' => $now,
            ]);
        });

        $this->redirect(url('/membership'), "Poin pelanggan berhasil disesuaikan. Saldo baru: {$newPoints} poin.", 'success');
    }

    public function vouchers(): void
    {
        Permission::abortUnless('manage.settings');

        $vouchers = $this->db()->fetchAll(
            "SELECT v.*, COUNT(vu.id) AS actual_used
             FROM vouchers v
             LEFT JOIN voucher_usages vu ON vu.voucher_id = v.id
             GROUP BY v.id
             ORDER BY v.id DESC"
        );

        $this->view('membership.vouchers', [
            'title' => 'Voucher Diskon & Promo',
            'activeMenu' => 'membership_vouchers',
            'vouchers' => $vouchers,
        ]);
    }

    public function storeVoucher(): void
    {
        Permission::abortUnless('manage.settings');

        $v = Validator::make($_POST, [
            'code' => 'required',
            'name' => 'required',
            'type' => 'required',
            'value' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $code = strtoupper(trim($_POST['code']));
        $exists = $this->db()->fetchColumn("SELECT id FROM vouchers WHERE code = :c", [':c' => $code]);
        if ($exists) {
            $this->back('Kode voucher sudah digunakan, buat kode lain.', 'error');
        }

        $value = (int)str_replace(['.', ','], '', $_POST['value']);
        $minSpend = (int)str_replace(['.', ','], '', $_POST['min_spend'] ?? '0');
        $maxDiscount = (int)str_replace(['.', ','], '', $_POST['max_discount'] ?? '0');
        $quota = (int)($_POST['quota'] ?? 100);
        $type = in_array($_POST['type'], ['percent', 'fixed']) ? $_POST['type'] : 'fixed';
        $now = date('Y-m-d H:i:s');

        $this->db()->insert('vouchers', [
            'company_id' => Auth::companyId() ?: 1,
            'code' => $code,
            'name' => trim($_POST['name']),
            'type' => $type,
            'value' => $value,
            'min_spend' => $minSpend,
            'max_discount' => $maxDiscount,
            'quota' => $quota,
            'used_count' => 0,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->redirect(url('/membership/vouchers'), "Voucher {$code} berhasil dibuat.", 'success');
    }

    public function toggleVoucher(): void
    {
        Permission::abortUnless('manage.settings');

        $voucherId = (int)($_POST['voucher_id'] ?? 0);
        $voucher = $this->db()->fetch("SELECT * FROM vouchers WHERE id = :id", [':id' => $voucherId]);
        if (!$voucher) {
            $this->back('Voucher tidak ditemukan.', 'error', '/membership/vouchers');
        }

        $newActive = $voucher['is_active'] ? 0 : 1;
        $this->db()->update('vouchers', [
            'is_active' => $newActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $voucherId]);

        $statusMsg = $newActive ? 'diaktifkan' : 'dinonaktifkan';
        $this->redirect(url('/membership/vouchers'), "Voucher {$voucher['code']} berhasil {$statusMsg}.", 'success');
    }
}
