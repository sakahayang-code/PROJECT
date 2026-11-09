<?php

declare(strict_types=1);

namespace App\Modules\Delivery;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Core\Validator;

class DeliveryController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('orders.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $statusFilter = trim($_GET['status'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $dateFilter = trim($_GET['date'] ?? date('Y-m-d'));

        $where = "1=1";
        $params = [];

        if ($branchFilter > 0) {
            $where .= " AND dr.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND dr.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        if (!empty($statusFilter)) {
            $where .= " AND dr.status = :status";
            $params[':status'] = $statusFilter;
        }

        if (!empty($typeFilter)) {
            $where .= " AND dr.type = :type";
            $params[':type'] = $typeFilter;
        }

        if (!empty($dateFilter)) {
            $where .= " AND dr.schedule_date = :sdate";
            $params[':sdate'] = $dateFilter;
        }

        $requests = $this->db()->fetchAll(
            "SELECT dr.*, c.name AS customer_name, c.phone AS customer_phone,
                    o.order_no, o.grand_total, o.payment_status,
                    u.name AS courier_name, b.name AS branch_name
             FROM delivery_requests dr
             JOIN customers c ON dr.customer_id = c.id
             JOIN branches b ON dr.branch_id = b.id
             LEFT JOIN orders o ON dr.order_id = o.id
             LEFT JOIN users u ON dr.courier_user_id = u.id
             WHERE {$where}
             ORDER BY dr.schedule_date ASC, dr.schedule_time ASC, dr.id DESC",
            $params
        );

        // Fetch couriers available
        $courierRoleId = $this->db()->fetchColumn("SELECT id FROM roles WHERE slug = 'courier'");
        $couriers = $this->db()->fetchAll(
            "SELECT id, name, phone, branch_id FROM users WHERE is_active = 1 AND (role_id = :rid OR id = :uid) ORDER BY name ASC",
            [':rid' => $courierRoleId ?: 0, ':uid' => Auth::id()]
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $customers = $this->db()->fetchAll("SELECT id, code, name, phone, address FROM customers WHERE is_active = 1 ORDER BY name ASC LIMIT 100");

        // Summary counts
        $counts = [
            'total' => count($requests),
            'pending' => count(array_filter($requests, fn($r) => $r['status'] === 'pending')),
            'assigned' => count(array_filter($requests, fn($r) => $r['status'] === 'assigned')),
            'on_the_way' => count(array_filter($requests, fn($r) => $r['status'] === 'on_the_way')),
            'completed' => count(array_filter($requests, fn($r) => in_array($r['status'], ['picked_up', 'delivered']))),
        ];

        $this->view('delivery.index', [
            'title' => 'Manajemen Pickup & Delivery',
            'activeMenu' => 'delivery_list',
            'requests' => $requests,
            'couriers' => $couriers,
            'branches' => $branches,
            'customers' => $customers,
            'counts' => $counts,
            'selectedBranch' => $branchFilter,
            'selectedStatus' => $statusFilter,
            'selectedType' => $typeFilter,
            'selectedDate' => $dateFilter,
        ]);
    }

    public function store(): void
    {
        Permission::abortUnless('orders.create');

        $v = Validator::make($_POST, [
            'customer_id' => 'required',
            'type' => 'required',
            'address_text' => 'required',
            'schedule_date' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $branchId = (int)($_POST['branch_id'] ?? (Auth::getActiveBranchId() ?: 1));
        $branch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $branchId]);
        if (!$branch) {
            $this->back('Cabang tidak valid.', 'error');
        }

        $customerId = (int)$_POST['customer_id'];
        $orderId = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $type = in_array($_POST['type'], ['pickup', 'delivery']) ? $_POST['type'] : 'pickup';
        $address = trim($_POST['address_text']);
        $scheduleDate = $_POST['schedule_date'];
        $scheduleTime = trim($_POST['schedule_time'] ?? '') ?: '09:00 - 12:00';
        $fee = (int)str_replace(['.', ','], '', $_POST['fee'] ?? '0');
        $notes = trim($_POST['notes'] ?? '');
        $courierId = !empty($_POST['courier_user_id']) ? (int)$_POST['courier_user_id'] : null;
        $now = date('Y-m-d H:i:s');

        $initialStatus = $courierId ? 'assigned' : 'pending';

        $this->db()->transaction(function() use (
            $branch, $branchId, $orderId, $customerId, $type, $address,
            $scheduleDate, $scheduleTime, $initialStatus, $courierId, $fee, $notes, $now
        ) {
            $reqId = $this->db()->insert('delivery_requests', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'type' => $type,
                'address_text' => $address,
                'schedule_date' => $scheduleDate,
                'schedule_time' => $scheduleTime,
                'status' => $initialStatus,
                'courier_user_id' => $courierId,
                'fee' => $fee,
                'notes' => $notes ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Status history
            $this->db()->insert('delivery_status_history', [
                'delivery_request_id' => $reqId,
                'from_status' => null,
                'to_status' => $initialStatus,
                'user_id' => Auth::id(),
                'notes' => 'Permintaan dibuat ' . ($courierId ? 'dan langsung ditugaskan' : ''),
                'created_at' => $now,
            ]);

            if ($courierId) {
                $this->db()->insert('delivery_assignments', [
                    'delivery_request_id' => $reqId,
                    'courier_user_id' => $courierId,
                    'assigned_at' => $now,
                    'status' => 'assigned',
                    'notes' => 'Penugasan awal',
                ]);
            }
        });

        $this->back('Permintaan pickup/delivery berhasil dibuat.', 'success');
    }

    public function assignCourier(): void
    {
        Permission::abortUnless('orders.edit');

        $reqId = (int)($_POST['delivery_request_id'] ?? 0);
        $courierId = (int)($_POST['courier_user_id'] ?? 0);

        if ($reqId <= 0 || $courierId <= 0) {
            $this->back('Pilih tugas dan kurir yang valid.', 'error');
        }

        $request = $this->db()->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $reqId]);
        if (!$request) {
            $this->back('Permintaan tugas tidak ditemukan.', 'error');
        }

        $courier = $this->db()->fetch("SELECT * FROM users WHERE id = :id AND is_active = 1", [':id' => $courierId]);
        if (!$courier) {
            $this->back('Kurir tidak ditemukan atau akun tidak aktif.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $oldStatus = $request['status'];

        $this->db()->transaction(function() use ($reqId, $courierId, $oldStatus, $now, $courier) {
            $this->db()->update('delivery_requests', [
                'courier_user_id' => $courierId,
                'status' => 'assigned',
                'updated_at' => $now,
            ], 'id = :id', [':id' => $reqId]);

            $this->db()->insert('delivery_assignments', [
                'delivery_request_id' => $reqId,
                'courier_user_id' => $courierId,
                'assigned_at' => $now,
                'status' => 'assigned',
                'notes' => "Ditugaskan ke {$courier['name']}",
            ]);

            $this->db()->insert('delivery_status_history', [
                'delivery_request_id' => $reqId,
                'from_status' => $oldStatus,
                'to_status' => 'assigned',
                'user_id' => Auth::id(),
                'notes' => "Ditugaskan ke {$courier['name']}",
                'created_at' => $now,
            ]);
        });

        $this->back("Tugas berhasil ditugaskan ke kurir {$courier['name']}.", 'success');
    }

    public function updateStatus(): void
    {
        $reqId = (int)($_POST['delivery_request_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $allowedStatuses = ['assigned', 'on_the_way', 'picked_up', 'delivered', 'failed', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses)) {
            $this->back('Status pengiriman tidak valid.', 'error');
        }

        $request = $this->db()->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $reqId]);
        if (!$request) {
            $this->back('Permintaan pengiriman tidak ditemukan.', 'error');
        }

        // Branch check
        if (!Auth::hasGlobalAccess() && !in_array($request['branch_id'], Auth::getAllowedBranchIds())) {
            $this->back('Anda tidak memiliki akses ke cabang ini.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $oldStatus = $request['status'];

        $this->db()->transaction(function() use ($request, $reqId, $oldStatus, $newStatus, $notes, $now) {
            $this->db()->update('delivery_requests', [
                'status' => $newStatus,
                'updated_at' => $now,
            ], 'id = :id', [':id' => $reqId]);

            $this->db()->insert('delivery_status_history', [
                'delivery_request_id' => $reqId,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'user_id' => Auth::id(),
                'notes' => $notes ?: "Status diubah menjadi {$newStatus}",
                'created_at' => $now,
            ]);

            // If delivered with an order, complete the order
            if ($newStatus === 'delivered' && !empty($request['order_id'])) {
                $this->db()->update('orders', [
                    'production_status' => 'completed',
                    'actual_completion_at' => $now,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $request['order_id']]);
            }
        });

        $this->back("Status pengiriman berhasil diperbarui menjadi {$newStatus}.", 'success');
    }

    public function uploadProof(): void
    {
        $reqId = (int)($_POST['delivery_request_id'] ?? 0);
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($reqId <= 0 || empty($recipientName)) {
            $this->back('Harap isi nama penerima/penyerah barang.', 'error');
        }

        $request = $this->db()->fetch("SELECT * FROM delivery_requests WHERE id = :id", [':id' => $reqId]);
        if (!$request) {
            $this->back('Permintaan tugas tidak ditemukan.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $photoPath = '/assets/img/proof-default.svg';

        // Check if an actual file was uploaded
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 2) . '/public/storage/delivery';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $fileName = 'proof_' . $reqId . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . '/' . $fileName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                    $photoPath = '/storage/delivery/' . $fileName;
                }
            }
        }

        $targetStatus = ($request['type'] === 'pickup') ? 'picked_up' : 'delivered';

        $this->db()->transaction(function() use ($request, $reqId, $recipientName, $photoPath, $notes, $targetStatus, $now) {
            $this->db()->insert('delivery_proofs', [
                'delivery_request_id' => $reqId,
                'photo_path' => $photoPath,
                'recipient_name' => $recipientName,
                'signed_at' => $now,
                'notes' => $notes ?: null,
                'created_at' => $now,
            ]);

            $this->db()->update('delivery_requests', [
                'status' => $targetStatus,
                'updated_at' => $now,
            ], 'id = :id', [':id' => $reqId]);

            $this->db()->insert('delivery_status_history', [
                'delivery_request_id' => $reqId,
                'from_status' => $request['status'],
                'to_status' => $targetStatus,
                'user_id' => Auth::id(),
                'notes' => "Bukti serah terima diunggah oleh {$recipientName}. " . $notes,
                'created_at' => $now,
            ]);

            if ($targetStatus === 'delivered' && !empty($request['order_id'])) {
                $this->db()->update('orders', [
                    'production_status' => 'completed',
                    'actual_completion_at' => $now,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $request['order_id']]);
            }
        });

        $this->back("Bukti serah terima berhasil disimpan. Status: {$targetStatus}.", 'success');
    }

    public function tasks(): void
    {
        $userId = Auth::id();
        $date = $_GET['date'] ?? date('Y-m-d');

        $where = "dr.schedule_date = :d";
        $params = [':d' => $date];

        // If courier or non-admin, filter by assigned courier
        if (!Auth::hasRole('super_admin') && !Auth::hasRole('owner') && !Auth::hasRole('manager')) {
            $where .= " AND dr.courier_user_id = :u";
            $params[':u'] = $userId;
        }

        $tasks = $this->db()->fetchAll(
            "SELECT dr.*, c.name AS customer_name, c.phone AS customer_phone,
                    o.order_no, o.grand_total, o.payment_status,
                    b.name AS branch_name
             FROM delivery_requests dr
             JOIN customers c ON dr.customer_id = c.id
             JOIN branches b ON dr.branch_id = b.id
             LEFT JOIN orders o ON dr.order_id = o.id
             WHERE {$where}
             ORDER BY dr.status ASC, dr.schedule_time ASC",
            $params
        );

        $this->view('delivery.tasks', [
            'title' => 'Tugas Kurir Saya',
            'activeMenu' => 'delivery_tasks',
            'tasks' => $tasks,
            'selectedDate' => $date,
        ]);
    }
}
