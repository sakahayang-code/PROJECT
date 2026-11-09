<?php

namespace App\Modules\Orders;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Helpers\NumberGenerator;
use App\Services\WhatsAppAdapter;

class PosController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('pos.access');

        $branchId = Auth::getActiveBranchId();
        if (!$branchId) {
            // Pick first available branch
            $allowed = Auth::getAllowedBranchIds();
            $branchId = !empty($allowed) ? $allowed[0] : 1;
        }

        $branch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $branchId]);

        // Fetch services with branch-specific prices
        $services = $this->db()->fetchAll(
            "SELECT s.id, s.code, s.name, s.unit_type, s.category_id, sc.name AS category_name,
                    COALESCE(bsp.price, s.base_price) AS price,
                    COALESCE(bsp.min_order, s.min_order) AS min_order,
                    COALESCE(bsp.est_hours, s.est_hours) AS est_hours
             FROM services s
             JOIN service_categories sc ON s.category_id = sc.id
             LEFT JOIN branch_service_prices bsp ON s.id = bsp.service_id AND bsp.branch_id = :b
             WHERE s.is_active = 1 AND (bsp.is_active IS NULL OR bsp.is_active = 1)
             ORDER BY s.category_id ASC, s.name ASC",
            [':b' => $branchId]
        );

        $categories = $this->db()->fetchAll("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY sort_order ASC");
        $addons = $this->db()->fetchAll("SELECT * FROM service_addons WHERE is_active = 1 ORDER BY id ASC");
        $paymentMethods = $this->db()->fetchAll("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY id ASC");
        $branches = $this->db()->fetchAll("SELECT id, code, name FROM branches WHERE is_active = 1");

        $this->view('pos.index', [
            'title' => 'Point of Sale (Kasir)',
            'activeMenu' => 'pos',
            'branch' => $branch,
            'services' => $services,
            'categories' => $categories,
            'addons' => $addons,
            'paymentMethods' => $paymentMethods,
            'branches' => $branches,
        ], 'layouts/admin');
    }

    public function checkout(): void
    {
        Permission::abortUnless('pos.access');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $customerId = (int)($input['customer_id'] ?? 0);
        $branchId = (int)($input['branch_id'] ?? Auth::getActiveBranchId());
        $orderType = in_array($input['order_type'] ?? '', ['regular', 'express', 'priority']) ? $input['order_type'] : 'regular';
        $items = $input['items'] ?? [];
        $selectedAddons = $input['addons'] ?? [];
        $voucherCode = trim($input['voucher_code'] ?? '');
        $paymentMethodId = (int)($input['payment_method_id'] ?? 0);
        $paidAmount = (int)str_replace(['.', ','], '', (string)($input['paid_amount'] ?? 0));
        $notes = trim($input['notes'] ?? '');

        if (!$customerId || empty($items)) {
            $this->error('Pelanggan dan minimal 1 item layanan harus diisi.');
        }

        $customer = $this->db()->fetch("SELECT c.*, mt.discount_percent FROM customers c LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id WHERE c.id = :id", [':id' => $customerId]);
        if (!$customer) {
            $this->error('Pelanggan tidak valid.');
        }

        $branch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $branchId]);
        if (!$branch) {
            $this->error('Cabang tidak valid.');
        }

        // Calculation variables
        $subtotal = 0;
        $totalWeight = 0.0;
        $totalPieces = 0;
        $maxEstHours = 24;
        $parsedItems = [];

        foreach ($items as $item) {
            $serviceId = (int)($item['service_id'] ?? 0);
            $service = $this->db()->fetch(
                "SELECT s.*, COALESCE(bsp.price, s.base_price) AS branch_price,
                        COALESCE(bsp.min_order, s.min_order) AS min_order,
                        COALESCE(bsp.est_hours, s.est_hours) AS est_hours
                 FROM services s
                 LEFT JOIN branch_service_prices bsp ON s.id = bsp.service_id AND bsp.branch_id = :b
                 WHERE s.id = :id",
                [':b' => $branchId, ':id' => $serviceId]
            );

            if (!$service) continue;

            $unitPrice = (int)$service['branch_price'];
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $weight = max(0.0, (float)($item['weight'] ?? 0.0));

            if ($service['unit_type'] === 'kg') {
                $calcWeight = max($weight, (float)$service['min_order']);
                $itemSubtotal = (int)round($calcWeight * $unitPrice);
                $totalWeight += $weight;
            } else {
                $itemSubtotal = (int)($qty * $unitPrice);
                $totalPieces += $qty;
            }

            if ((int)$service['est_hours'] > $maxEstHours) {
                $maxEstHours = (int)$service['est_hours'];
            }

            $subtotal += $itemSubtotal;
            $parsedItems[] = [
                'service_id' => $service['id'],
                'service_name' => $service['name'],
                'unit_type' => $service['unit_type'],
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'weight' => $weight,
                'subtotal' => $itemSubtotal,
                'notes' => $item['notes'] ?? '',
                'details' => $item['details'] ?? [],
            ];
        }

        // Addons calculation
        $addonTotal = 0;
        $parsedAddons = [];
        if (!empty($selectedAddons)) {
            $addonRows = $this->db()->fetchAll("SELECT * FROM service_addons WHERE is_active = 1");
            $addonLookup = array_column($addonRows, null, 'id');
            foreach ($selectedAddons as $adId) {
                if (isset($addonLookup[$adId])) {
                    $addonTotal += (int)$addonLookup[$adId]['price'];
                    $parsedAddons[] = $addonLookup[$adId]['name'];
                }
            }
        }

        // Membership discount
        $discountPercent = (float)($customer['discount_percent'] ?? 0);
        $discountTotal = (int)round(($subtotal * $discountPercent) / 100);

        // Voucher discount
        $voucherDiscount = 0;
        if (!empty($voucherCode)) {
            $today = date('Y-m-d');
            $voucher = $this->db()->fetch(
                "SELECT * FROM vouchers WHERE code = :c AND is_active = 1 AND start_date <= :d1 AND end_date >= :d2",
                [':c' => $voucherCode, ':d1' => $today, ':d2' => $today]
            );

            if ($voucher && $subtotal >= (int)$voucher['min_spend'] && ($voucher['quota'] == 0 || $voucher['used_count'] < $voucher['quota'])) {
                if ($voucher['type'] === 'percent') {
                    $voucherDiscount = (int)round(($subtotal * $voucher['value']) / 100);
                    if ($voucher['max_discount'] > 0 && $voucherDiscount > $voucher['max_discount']) {
                        $voucherDiscount = (int)$voucher['max_discount'];
                    }
                } else {
                    $voucherDiscount = (int)$voucher['value'];
                }
            }
        }

        // Order speed multiplier for estimated completion
        if ($orderType === 'express') {
            $maxEstHours = max(6, (int)round($maxEstHours / 2));
        } elseif ($orderType === 'priority') {
            $maxEstHours = 6;
        }

        $estCompletion = date('Y-m-d H:i:s', strtotime("+{$maxEstHours} hours"));

        // Grand Total Calculation
        $grandTotal = max(0, ($subtotal + $addonTotal) - ($discountTotal + $voucherDiscount));
        $paid = min($paidAmount, $grandTotal);
        $balanceDue = max(0, $grandTotal - $paid);

        $paymentStatus = 'unpaid';
        if ($paid >= $grandTotal) {
            $paymentStatus = 'paid';
        } elseif ($paid > 0) {
            $paymentStatus = 'partial';
        }

        // Atomic transaction execution
        $now = date('Y-m-d H:i:s');
        $db = $this->db();
        $orderId = 0;
        $orderNo = '';

        $db->transaction(function() use (
            $db, $branch, $branchId, $customer, $customerId, $orderType, $estCompletion,
            $totalWeight, $totalPieces, $subtotal, $addonTotal, $discountTotal, $voucherCode,
            $voucherDiscount, $grandTotal, $paid, $balanceDue, $paymentStatus, $notes,
            $parsedItems, $parsedAddons, $paymentMethodId, $now, &$orderId, &$orderNo
        ) {
            // Generate sequence LP-[BRANCH]-[YYYYMMDD]-[SEQ]
            $orderNo = NumberGenerator::generateOrderNumber($branchId, $branch['code']);
            $trackingToken = NumberGenerator::generateTrackingToken();

            $orderId = $db->insert('orders', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'user_id' => Auth::id() ?: 1,
                'order_no' => $orderNo,
                'tracking_token' => $trackingToken,
                'order_type' => $orderType,
                'est_completion_at' => $estCompletion,
                'total_weight' => $totalWeight,
                'total_pieces' => $totalPieces,
                'subtotal' => $subtotal,
                'addon_total' => $addonTotal,
                'discount_total' => $discountTotal,
                'voucher_code' => $voucherCode ?: null,
                'voucher_discount' => $voucherDiscount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus,
                'production_status' => 'received',
                'notes' => $notes ?: (!empty($parsedAddons) ? 'Addons: ' . implode(', ', $parsedAddons) : null),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Insert order items
            foreach ($parsedItems as $pItem) {
                $orderItemId = $db->insert('order_items', [
                    'order_id' => $orderId,
                    'service_id' => $pItem['service_id'],
                    'service_name' => $pItem['service_name'],
                    'unit_type' => $pItem['unit_type'],
                    'unit_price' => $pItem['unit_price'],
                    'quantity' => $pItem['quantity'],
                    'weight' => $pItem['weight'],
                    'subtotal' => $pItem['subtotal'],
                    'notes' => $pItem['notes'],
                    'created_at' => $now,
                ]);

                // Item garment details (color, brand, defect)
                if (!empty($pItem['details'])) {
                    foreach ($pItem['details'] as $det) {
                        $db->insert('order_item_details', [
                            'order_item_id' => $orderItemId,
                            'item_name' => $det['item_name'] ?? 'Pakaian',
                            'color' => $det['color'] ?? null,
                            'brand' => $det['brand'] ?? null,
                            'defect_notes' => $det['defect_notes'] ?? null,
                            'quantity' => (int)($det['quantity'] ?? 1),
                            'created_at' => $now,
                        ]);
                    }
                }
            }

            // QR Code record
            $db->insert('order_qr_codes', [
                'order_id' => $orderId,
                'code' => $orderNo,
                'token' => $trackingToken,
                'created_at' => $now,
            ]);

            // Status history
            $db->insert('order_status_history', [
                'order_id' => $orderId,
                'from_status' => null,
                'to_status' => 'received',
                'notes' => 'Pesanan diterima di kasir',
                'changed_by_user_id' => Auth::id(),
                'created_at' => $now,
            ]);

            // Initialize Production Job in 'waiting' stage
            $firstStageId = (int)$db->fetchColumn("SELECT id FROM production_stages WHERE slug = 'waiting'");
            $db->insert('production_jobs', [
                'order_id' => $orderId,
                'current_stage_id' => $firstStageId ?: 1,
                'status' => 'waiting',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Voucher usage record
            if ($voucherDiscount > 0 && !empty($voucherCode)) {
                $vRow = $db->fetch("SELECT id FROM vouchers WHERE code = :c", [':c' => $voucherCode]);
                if ($vRow) {
                    $db->insert('voucher_usages', [
                        'voucher_id' => $vRow['id'],
                        'order_id' => $orderId,
                        'customer_id' => $customerId,
                        'discount_amount' => $voucherDiscount,
                        'used_at' => $now,
                    ]);
                    $db->getConnection()->exec("UPDATE vouchers SET used_count = used_count + 1 WHERE id = {$vRow['id']}");
                }
            }

            // Process Payment if amount paid > 0
            if ($paid > 0 && $paymentMethodId > 0) {
                $payMethod = $db->fetch("SELECT * FROM payment_methods WHERE id = :id", [':id' => $paymentMethodId]);
                $paymentNo = NumberGenerator::generatePaymentNumber($branchId, $branch['code']);

                $db->insert('payments', [
                    'company_id' => $branch['company_id'],
                    'branch_id' => $branchId,
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'payment_no' => $paymentNo,
                    'amount' => $paid,
                    'payment_method_id' => $paymentMethodId,
                    'payment_type' => ($paid >= $grandTotal) ? 'full' : 'dp',
                    'status' => 'success',
                    'reference_no' => $paymentNo,
                    'received_by_user_id' => Auth::id(),
                    'paid_at' => $now,
                    'created_at' => $now,
                ]);

                // Deduct customer deposit if paid via deposit
                if ($payMethod && $payMethod['category'] === 'deposit') {
                    if ($customer['deposit_balance'] < $paid) {
                        throw new \Exception('Saldo deposit pelanggan tidak mencukupi untuk pembayaran ini.');
                    }
                    $newDepBalance = $customer['deposit_balance'] - $paid;
                    $db->update('customers', ['deposit_balance' => $newDepBalance, 'updated_at' => $now], 'id = :id', [':id' => $customerId]);

                    $db->insert('deposit_transactions', [
                        'customer_id' => $customerId,
                        'branch_id' => $branchId,
                        'order_id' => $orderId,
                        'type' => 'debit',
                        'amount' => $paid,
                        'balance_after' => $newDepBalance,
                        'description' => "Pembayaran Pesanan No. {$orderNo}",
                        'reference_no' => $orderNo,
                        'created_at' => $now,
                    ]);
                }

                // Double-Entry Accounting Journal Posting
                $cashAcc = $db->fetch("SELECT * FROM cash_accounts WHERE branch_id = :b AND account_type = 'cash' LIMIT 1", [':b' => $branchId]);
                $revAcc = $db->fetch("SELECT id FROM accounts WHERE code = '4101'"); // Pendapatan Laundry

                if ($cashAcc && $revAcc) {
                    $jId = $db->insert('journal_entries', [
                        'company_id' => $branch['company_id'],
                        'branch_id' => $branchId,
                        'entry_no' => 'JV-' . $orderNo,
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'payment',
                        'reference_id' => $orderId,
                        'reference_no' => $orderNo,
                        'description' => "Penerimaan pembayaran pesanan {$orderNo} ({$customer['name']})",
                        'total_debit' => $paid,
                        'total_credit' => $paid,
                        'is_balanced' => 1,
                        'status' => 'posted',
                        'created_by' => Auth::id(),
                        'created_at' => $now,
                    ]);

                    // Debit: Kas Kasir / Bank
                    $db->insert('journal_entry_lines', [
                        'journal_entry_id' => $jId,
                        'account_id' => $cashAcc['account_id'],
                        'debit' => $paid,
                        'credit' => 0,
                        'memo' => "Penerimaan kas dari {$customer['name']}",
                        'created_at' => $now,
                    ]);

                    // Credit: Pendapatan Laundry
                    $db->insert('journal_entry_lines', [
                        'journal_entry_id' => $jId,
                        'account_id' => $revAcc['id'],
                        'debit' => 0,
                        'credit' => $paid,
                        'memo' => "Pendapatan jasa laundry {$orderNo}",
                        'created_at' => $now,
                    ]);

                    // Update cash account
                    $db->update('cash_accounts', ['balance' => $cashAcc['balance'] + $paid], 'id = :id', [':id' => $cashAcc['id']]);
                }
            }

            // Award Loyalty Points (e.g. 1 point per Rp 10.000 spent)
            if ($grandTotal >= 10000) {
                $multiplier = (float)($customer['points_multiplier'] ?? 1.0);
                $ptsEarned = (int)floor(($grandTotal / 10000) * $multiplier);
                if ($ptsEarned > 0) {
                    $newPts = $customer['points_balance'] + $ptsEarned;
                    $db->update('customers', ['points_balance' => $newPts, 'updated_at' => $now], 'id = :id', [':id' => $customerId]);

                    $db->insert('loyalty_transactions', [
                        'company_id' => $branch['company_id'],
                        'customer_id' => $customerId,
                        'order_id' => $orderId,
                        'type' => 'earn',
                        'points' => $ptsEarned,
                        'balance_after' => $newPts,
                        'description' => "Poin transaksi pesanan No. {$orderNo}",
                        'reference_no' => $orderNo,
                        'created_at' => $now,
                    ]);
                }
            }

            // Enqueue WhatsApp notification
            $wa = new WhatsAppAdapter();
            $wa->enqueue(
                $branch['company_id'],
                $branchId,
                $customer['whatsapp'],
                $customer['name'],
                'order_received',
                [
                    'customer_name' => $customer['name'],
                    'order_no' => $orderNo,
                    'branch_name' => $branch['name'],
                    'grand_total' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
                    'payment_status' => strtoupper($paymentStatus),
                    'est_completion' => date('d M Y H:i', strtotime($estCompletion)),
                    'tracking_url' => url('/track/' . $trackingToken),
                ],
                'orders',
                $orderId
            );
        });

        $this->logActivity('create_order', 'orders', $orderId, "Membuat pesanan kasir No. {$orderNo} (Total: Rp " . number_format($grandTotal) . ")");

        $this->json([
            'success' => true,
            'message' => "Transaksi {$orderNo} berhasil disimpan!",
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'print_url' => url('/orders/' . $orderId . '/print'),
            'show_url' => url('/orders/' . $orderId),
        ]);
    }
}
