<?php

declare(strict_types=1);

namespace App\Modules\Portal;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Validator;
use App\Services\PaymentGatewayAdapter;
use App\Helpers\NumberGenerator;

class PortalController extends Controller
{
    public function login(): void
    {
        if (Auth::checkCustomer()) {
            $this->redirect(url('/portal'));
        }

        $this->view('portal.login', [
            'title' => 'Masuk ke Portal Pelanggan',
        ], 'layouts/portal');
    }

    public function doLogin(): void
    {
        $v = Validator::make($_POST, [
            'identifier' => 'required',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $identifier = trim($_POST['identifier']);
        $password = $_POST['password'];

        if (Auth::attemptCustomer($identifier, $password)) {
            $this->redirect(url('/portal'));
        }

        $this->back('Nomor WhatsApp / Email atau Password salah.', 'error');
    }

    public function register(): void
    {
        if (Auth::checkCustomer()) {
            $this->redirect(url('/portal'));
        }

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $this->view('portal.register', [
            'title' => 'Daftar Akun Pelanggan SimLaundry',
            'branches' => $branches,
        ], 'layouts/portal');
    }

    public function doRegister(): void
    {
        $v = Validator::make($_POST, [
            'name' => 'required',
            'whatsapp' => 'required',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $_POST['whatsapp']);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        // Check unique phone
        $exists = $this->db()->fetchColumn("SELECT id FROM customers WHERE whatsapp = :w", [':w' => $cleanPhone]);
        if ($exists) {
            $this->back('Nomor WhatsApp ini sudah terdaftar. Silakan login atau gunakan nomor lain.', 'error');
        }

        $name = trim($_POST['name']);
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : 1;
        $username = $cleanPhone;
        $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        // Regular tier
        $regularTierId = (int)$this->db()->fetchColumn("SELECT id FROM membership_tiers WHERE slug = 'regular' LIMIT 1") ?: 1;

        $customerId = 0;
        $this->db()->transaction(function() use (
            $branchId, $cleanPhone, $name, $email, $address, $regularTierId,
            $username, $passwordHash, $now, &$customerId
        ) {
            $code = 'CUST-' . date('y') . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            $customerId = $this->db()->insert('customers', [
                'company_id' => 1,
                'favorite_branch_id' => $branchId,
                'code' => $code,
                'name' => $name,
                'whatsapp' => $cleanPhone,
                'email' => $email ?: null,
                'address' => $address ?: null,
                'join_date' => date('Y-m-d'),
                'status' => 'active',
                'deposit_balance' => 0,
                'points_balance' => 20, // 20 bonus welcome points
                'current_tier_id' => $regularTierId,
                'allow_marketing' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->db()->insert('customer_accounts', [
                'customer_id' => $customerId,
                'username' => $username,
                'password_hash' => $passwordHash,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Welcome points log
            $this->db()->insert('loyalty_transactions', [
                'company_id' => 1,
                'customer_id' => $customerId,
                'order_id' => null,
                'type' => 'earn',
                'points' => 20,
                'balance_after' => 20,
                'description' => 'Bonus selamat datang pendaftaran akun baru',
                'reference_no' => 'WELCOME-' . $customerId,
                'created_at' => $now,
            ]);

            if (!empty($address)) {
                $this->db()->insert('customer_addresses', [
                    'customer_id' => $customerId,
                    'label' => 'Alamat Utama',
                    'recipient_name' => $name,
                    'phone' => $cleanPhone,
                    'address' => $address,
                    'is_default' => 1,
                    'created_at' => $now,
                ]);
            }
        });

        Auth::loginCustomer($customerId);
        $this->redirect(url('/portal'));
    }

    public function logout(): void
    {
        Auth::logoutCustomer();
        $this->redirect(url('/portal/login'));
    }

    public function index(): void
    {
        $customer = Auth::customer();
        $customerId = (int)($customer['id'] ?? 0);

        // Active orders for this customer
        $activeOrders = $this->db()->fetchAll(
            "SELECT o.*, b.name AS branch_name
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             WHERE o.customer_id = :cid AND o.production_status NOT IN ('completed', 'cancelled')
             ORDER BY o.id DESC",
            [':cid' => $customerId]
        );

        // Recent completed orders
        $recentOrders = $this->db()->fetchAll(
            "SELECT o.*, b.name AS branch_name, r.rating, r.comment AS review_comment
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             LEFT JOIN order_reviews r ON r.order_id = o.id
             WHERE o.customer_id = :cid
             ORDER BY o.id DESC
             LIMIT 5",
            [':cid' => $customerId]
        );

        // Active vouchers
        $vouchers = $this->db()->fetchAll(
            "SELECT * FROM vouchers WHERE is_active = 1 AND end_date >= :today ORDER BY id DESC LIMIT 4",
            [':today' => date('Y-m-d')]
        );

        // Popular services
        $services = $this->db()->fetchAll(
            "SELECT s.*, sc.name AS category_name
             FROM services s
             JOIN service_categories sc ON s.category_id = sc.id
             WHERE s.is_active = 1
             ORDER BY (CASE WHEN s.unit_type = 'kg' THEN 1 ELSE 0 END) DESC, s.name ASC
             LIMIT 6"
        );

        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1 ORDER BY id ASC");

        $this->view('portal.index', [
            'title' => 'Dashboard Pelanggan',
            'customer' => $customer,
            'activeOrders' => $activeOrders,
            'recentOrders' => $recentOrders,
            'vouchers' => $vouchers,
            'services' => $services,
            'branches' => $branches,
        ], 'layouts/portal');
    }

    public function orders(): void
    {
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];

        $orders = $this->db()->fetchAll(
            "SELECT o.*, b.name AS branch_name, r.rating, r.comment AS review_comment
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             LEFT JOIN order_reviews r ON r.order_id = o.id
             WHERE o.customer_id = :cid
             ORDER BY o.id DESC",
            [':cid' => $customerId]
        );

        $this->view('portal.orders', [
            'title' => 'Riwayat & Status Pesanan Saya',
            'customer' => $customer,
            'orders' => $orders,
        ], 'layouts/portal');
    }

    public function createOrder(): void
    {
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];

        $branches = $this->db()->fetchAll("SELECT id, name, address, phone FROM branches WHERE is_active = 1");
        $addresses = $this->db()->fetchAll("SELECT * FROM customer_addresses WHERE customer_id = :cid", [':cid' => $customerId]);
        $services = $this->db()->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY (CASE WHEN unit_type = 'kg' THEN 1 ELSE 0 END) DESC, name ASC");

        $this->view('portal.create_order', [
            'title' => 'Pesan Laundry & Permintaan Penjemputan',
            'customer' => $customer,
            'branches' => $branches,
            'addresses' => $addresses,
            'services' => $services,
        ], 'layouts/portal');
    }

    public function storePickup(): void
    {
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];

        $v = Validator::make($_POST, [
            'branch_id' => 'required',
            'address_text' => 'required',
            'schedule_date' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $branchId = (int)$_POST['branch_id'];
        $address = trim($_POST['address_text']);
        $date = $_POST['schedule_date'];
        $time = trim($_POST['schedule_time'] ?? '09:00 - 12:00');
        $notes = trim($_POST['notes'] ?? '');
        $now = date('Y-m-d H:i:s');

        $this->db()->transaction(function() use ($customerId, $branchId, $address, $date, $time, $notes, $now) {
            $reqId = $this->db()->insert('delivery_requests', [
                'company_id' => 1,
                'branch_id' => $branchId,
                'order_id' => null,
                'customer_id' => $customerId,
                'type' => 'pickup',
                'address_text' => $address,
                'schedule_date' => $date,
                'schedule_time' => $time,
                'status' => 'pending',
                'courier_user_id' => null,
                'fee' => 0,
                'notes' => $notes ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->db()->insert('delivery_status_history', [
                'delivery_request_id' => $reqId,
                'from_status' => null,
                'to_status' => 'pending',
                'user_id' => null,
                'notes' => 'Permintaan penjemputan online oleh pelanggan',
                'created_at' => $now,
            ]);
        });

        $this->redirect(url('/portal'), 'Permintaan penjemputan pakaian kotor berhasil dikirim! Kurir kami akan segera menjemput ke alamat Anda.', 'success');
    }

    public function payQris(): void
    {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];

        $order = $this->db()->fetch(
            "SELECT * FROM orders WHERE id = :id AND customer_id = :cid",
            [':id' => $orderId, ':cid' => $customerId]
        );

        if (!$order) {
            $this->back('Pesanan tidak ditemukan.', 'error');
            return;
        }

        if ((int)$order['balance_due'] <= 0 || $order['payment_status'] === 'paid') {
            $this->back('Pesanan ini sudah lunas.', 'info');
            return;
        }

        $adapter = new PaymentGatewayAdapter();
        $res = $adapter->createQrisTransaction($orderId, (int)$order['balance_due'], $customer['name']);

        $this->json([
            'success' => true,
            'transaction_id' => $res['transaction_id'],
            'qr_string' => $res['qr_string'],
            'amount' => $res['amount'],
            'expires_at' => $res['expires_at'],
        ]);
    }

    public function storeReview(): void
    {
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];
        $orderId = (int)($_POST['order_id'] ?? 0);
        $rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');

        // Verify order belongs to customer
        $order = $this->db()->fetch(
            "SELECT * FROM orders WHERE id = :id AND customer_id = :cid",
            [':id' => $orderId, ':cid' => $customerId]
        );

        if (!$order) {
            $this->back('Pesanan tidak ditemukan.', 'error');
            return;
        }

        $exists = $this->db()->fetchColumn("SELECT id FROM order_reviews WHERE order_id = :oid", [':oid' => $orderId]);
        $now = date('Y-m-d H:i:s');

        if ($exists) {
            $this->db()->update('order_reviews', [
                'rating' => $rating,
                'comment' => $comment,
            ], 'id = :id', [':id' => $exists]);
        } else {
            $this->db()->insert('order_reviews', [
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'rating' => $rating,
                'comment' => $comment ?: null,
                'created_at' => $now,
            ]);
        }

        $this->redirect(url('/portal/orders'), 'Terima kasih atas ulasan dan rating kepuasan yang Anda berikan!', 'success');
    }

    public function profile(): void
    {
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];

        $addresses = $this->db()->fetchAll(
            "SELECT * FROM customer_addresses WHERE customer_id = :cid ORDER BY is_default DESC, id DESC",
            [':cid' => $customerId]
        );

        $pointsLedger = $this->db()->fetchAll(
            "SELECT * FROM loyalty_transactions WHERE customer_id = :cid ORDER BY id DESC LIMIT 20",
            [':cid' => $customerId]
        );

        $this->view('portal.profile', [
            'title' => 'Profil & Akun Saya',
            'customer' => $customer,
            'addresses' => $addresses,
            'pointsLedger' => $pointsLedger,
        ], 'layouts/portal');
    }

    public function updateProfile(): void
    {
        $customer = Auth::customer();
        $customerId = (int)$customer['id'];

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $this->back('Nama lengkap wajib diisi.', 'error', '/portal/profile');
        }

        $this->db()->update('customers', [
            'name' => $name,
            'email' => $email ?: null,
            'address' => $address ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $customerId]);

        $this->redirect(url('/portal/profile'), 'Profil akun berhasil diperbarui.', 'success');
    }
}
