<?php

namespace App\Modules\Customers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class CustomerController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('view');

        $search = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $tierId = (int)($_GET['tier_id'] ?? 0);

        $where = "1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (c.name LIKE :q OR c.whatsapp LIKE :q OR c.code LIKE :q OR c.email LIKE :q)";
            $params[':q'] = "%{$search}%";
        }
        if (!empty($status)) {
            $where .= " AND c.status = :status";
            $params[':status'] = $status;
        }
        if ($tierId > 0) {
            $where .= " AND c.current_tier_id = :tier_id";
            $params[':tier_id'] = $tierId;
        }

        $customers = $this->db()->fetchAll(
            "SELECT c.*, mt.name AS tier_name, mt.badge_color,
                    (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) AS total_orders,
                    (SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE customer_id = c.id AND production_status != 'cancelled') AS total_spent
             FROM customers c
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             WHERE {$where}
             ORDER BY c.id DESC",
            $params
        );

        $tiers = $this->db()->fetchAll("SELECT * FROM membership_tiers ORDER BY min_spend ASC");
        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1");

        $this->view('customers.index', [
            'title' => 'Data Pelanggan (CRM)',
            'activeMenu' => 'customers',
            'customers' => $customers,
            'tiers' => $tiers,
            'branches' => $branches,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedTier' => $tierId,
        ]);
    }

    public function search(): void
    {
        // JSON endpoint for POS autocomplete
        $q = trim($_GET['q'] ?? '');
        if (empty($q)) {
            $this->json([]);
        }

        $customers = $this->db()->fetchAll(
            "SELECT c.id, c.code, c.name, c.whatsapp, c.address, c.deposit_balance, c.points_balance,
                    mt.name AS tier_name, mt.discount_percent
             FROM customers c
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             WHERE c.status = 'active' AND (c.name LIKE :q OR c.whatsapp LIKE :q OR c.code LIKE :q)
             LIMIT 10",
            [':q' => "%{$q}%"]
        );

        $this->json($customers);
    }

    public function store(): void
    {
        Permission::abortUnless('create');

        $name = trim($_POST['name'] ?? '');
        $whatsapp = preg_replace('/[^0-9]/', '', trim($_POST['whatsapp'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $birthDate = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
        $notes = trim($_POST['notes'] ?? '');
        $favoriteBranchId = !empty($_POST['favorite_branch_id']) ? (int)$_POST['favorite_branch_id'] : $this->branchId();
        $isAjax = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

        if (empty($name) || empty($whatsapp)) {
            if ($isAjax) {
                $this->error('Nama dan nomor WhatsApp wajib diisi.');
            }
            $this->back('Nama dan nomor WhatsApp wajib diisi.', 'error');
        }

        // Check unique whatsapp
        $exists = $this->db()->fetch("SELECT id FROM customers WHERE whatsapp = :w", [':w' => $whatsapp]);
        if ($exists) {
            if ($isAjax) {
                $this->error('Nomor WhatsApp sudah terdaftar pada pelanggan lain.');
            }
            $this->back('Nomor WhatsApp sudah terdaftar pada pelanggan lain.', 'error');
        }

        // Generate customer code: CUST-0001
        $lastId = (int)$this->db()->fetchColumn("SELECT MAX(id) FROM customers") + 1;
        $code = 'CUST-' . str_pad((string)$lastId, 4, '0', STR_PAD_LEFT);

        $defaultTierId = (int)$this->db()->fetchColumn("SELECT id FROM membership_tiers WHERE slug = 'regular'");
        $now = date('Y-m-d H:i:s');

        $customerId = $this->db()->insert('customers', [
            'company_id' => $this->companyId(),
            'favorite_branch_id' => $favoriteBranchId,
            'code' => $code,
            'name' => $name,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'address' => $address,
            'birth_date' => $birthDate,
            'join_date' => date('Y-m-d'),
            'status' => 'active',
            'deposit_balance' => 0,
            'points_balance' => 0,
            'current_tier_id' => $defaultTierId ?: null,
            'notes' => $notes,
            'allow_marketing' => isset($_POST['allow_marketing']) ? 1 : 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Default address
        if (!empty($address)) {
            $this->db()->insert('customer_addresses', [
                'customer_id' => $customerId,
                'label' => 'Alamat Utama',
                'recipient_name' => $name,
                'phone' => $whatsapp,
                'address' => $address,
                'is_default' => 1,
                'created_at' => $now,
            ]);
        }

        // Auto customer portal account
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $name)[0])) . $customerId;
        $this->db()->insert('customer_accounts', [
            'customer_id' => $customerId,
            'username' => $username,
            'password_hash' => password_hash('Pelanggan@123', PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->logActivity('create', 'customers', $customerId, "Mendaftarkan pelanggan baru: [{$code}] {$name} ({$whatsapp})");

        if ($isAjax) {
            $customer = $this->db()->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $customerId]);
            $this->json(['success' => true, 'customer' => $customer]);
        }

        $this->redirect(url('/customers'), "Pelanggan baru {$name} ({$code}) berhasil didaftarkan!");
    }

    public function show(int|string $id): void
    {
        Permission::abortUnless('view');

        $id = (int)$id;
        $customer = $this->db()->fetch(
            "SELECT c.*, mt.name AS tier_name, mt.badge_color, mt.discount_percent, b.name AS favorite_branch_name, ca.username
             FROM customers c
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             LEFT JOIN branches b ON c.favorite_branch_id = b.id
             LEFT JOIN customer_accounts ca ON c.id = ca.customer_id
             WHERE c.id = :id",
            [':id' => $id]
        );

        if (!$customer) {
            $this->redirect(url('/customers'), 'Data pelanggan tidak ditemukan.', 'error');
        }

        $orders = $this->db()->fetchAll(
            "SELECT o.*, b.name AS branch_name
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             WHERE o.customer_id = :id
             ORDER BY o.id DESC",
            [':id' => $id]
        );

        $deposits = $this->db()->fetchAll(
            "SELECT * FROM deposit_transactions WHERE customer_id = :id ORDER BY id DESC",
            [':id' => $id]
        );

        $points = $this->db()->fetchAll(
            "SELECT * FROM loyalty_transactions WHERE customer_id = :id ORDER BY id DESC",
            [':id' => $id]
        );

        $addresses = $this->db()->fetchAll(
            "SELECT * FROM customer_addresses WHERE customer_id = :id ORDER BY is_default DESC, id ASC",
            [':id' => $id]
        );

        $this->view('customers.show', [
            'title' => 'Detail Pelanggan: ' . $customer['name'],
            'activeMenu' => 'customers',
            'customer' => $customer,
            'orders' => $orders,
            'deposits' => $deposits,
            'points' => $points,
            'addresses' => $addresses,
        ]);
    }

    public function topupDeposit(int|string $id): void
    {
        Permission::abortUnless('edit');

        $id = (int)$id;
        $amount = (int)str_replace(['.', ','], '', $_POST['amount'] ?? '0');
        $notes = trim($_POST['notes'] ?? 'Top Up Saldo Deposit');

        if ($amount <= 0) {
            $this->back('Nominal deposit harus lebih dari 0.', 'error');
        }

        $customer = $this->db()->fetch("SELECT * FROM customers WHERE id = :id", [':id' => $id]);
        if (!$customer) {
            $this->back('Pelanggan tidak ditemukan.', 'error');
        }

        $newBalance = $customer['deposit_balance'] + $amount;
        $now = date('Y-m-d H:i:s');
        $branchId = $this->branchId() ?: ($customer['favorite_branch_id'] ?: 1);

        $this->db()->transaction(function() use ($id, $amount, $newBalance, $notes, $now, $branchId, $customer) {
            $this->db()->update('customers', ['deposit_balance' => $newBalance, 'updated_at' => $now], 'id = :id', [':id' => $id]);

            $this->db()->insert('deposit_transactions', [
                'customer_id' => $id,
                'branch_id' => $branchId,
                'order_id' => null,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $notes,
                'reference_no' => 'TOPUP-' . date('YmdHis') . '-' . $id,
                'created_at' => $now,
            ]);

            // Double-entry accounting: Debit Kas Kasir, Credit Titipan Deposit Pelanggan (2102)
            $cashAccount = $this->db()->fetch("SELECT * FROM cash_accounts WHERE branch_id = :b AND account_type = 'cash' LIMIT 1", [':b' => $branchId]);
            if ($cashAccount) {
                // Post balanced journal
                $entryNo = 'JV-DEP-' . date('Ymd') . '-' . substr(uniqid(), -4);
                $journalId = $this->db()->insert('journal_entries', [
                    'company_id' => $this->companyId(),
                    'branch_id' => $branchId,
                    'entry_no' => $entryNo,
                    'entry_date' => date('Y-m-d'),
                    'reference_type' => 'deposit',
                    'reference_id' => $id,
                    'reference_no' => 'TOPUP-' . $id,
                    'description' => "Penerimaan Top Up Deposit Pelanggan: {$customer['name']}",
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => 1,
                    'status' => 'posted',
                    'created_by' => Auth::id(),
                    'created_at' => $now,
                ]);

                // Debit Cash
                $this->db()->insert('journal_entry_lines', [
                    'journal_entry_id' => $journalId,
                    'account_id' => $cashAccount['account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Kas masuk top up deposit {$customer['name']}",
                    'created_at' => $now,
                ]);

                // Credit Liability: Titipan Deposit (2102)
                $depAccount = $this->db()->fetch("SELECT id FROM accounts WHERE code = '2102'");
                if ($depAccount) {
                    $this->db()->insert('journal_entry_lines', [
                        'journal_entry_id' => $journalId,
                        'account_id' => $depAccount['id'],
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => "Utang titipan deposit {$customer['name']}",
                        'created_at' => $now,
                    ]);
                }

                // Update cash account balance
                $this->db()->update('cash_accounts', ['balance' => $cashAccount['balance'] + $amount], 'id = :id', [':id' => $cashAccount['id']]);
            }
        });

        $this->logActivity('topup_deposit', 'customers', $id, "Top up deposit pelanggan {$customer['name']} sebesar Rp " . number_format($amount));
        $this->redirect(url('/customers/' . $id), "Top up deposit sebesar " . format_rupiah($amount) . " berhasil!");
    }
}
