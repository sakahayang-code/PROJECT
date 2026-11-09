<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class ReportController extends Controller
{
    private array $reportRegistry = [
        // Penjualan & Kasir
        'sales_daily' => ['title' => 'Laporan Penjualan Harian', 'category' => 'sales'],
        'sales_weekly' => ['title' => 'Laporan Penjualan Mingguan', 'category' => 'sales'],
        'sales_monthly' => ['title' => 'Laporan Penjualan Bulanan', 'category' => 'sales'],
        'sales_yearly' => ['title' => 'Laporan Penjualan Tahunan', 'category' => 'sales'],
        'sales_branch' => ['title' => 'Laporan Penjualan per Cabang', 'category' => 'sales'],
        'sales_cashier' => ['title' => 'Laporan Penjualan per Kasir', 'category' => 'sales'],
        'sales_service' => ['title' => 'Laporan Penjualan per Layanan', 'category' => 'sales'],
        'sales_kilo_vs_satuan' => ['title' => 'Laporan Penjualan Kiloan vs Satuan', 'category' => 'sales'],

        // Operasional & Produksi
        'orders_active' => ['title' => 'Laporan Pesanan Aktif (Dalam Proses)', 'category' => 'operations'],
        'orders_completed' => ['title' => 'Laporan Pesanan Selesai / Diserahkan', 'category' => 'operations'],
        'orders_late' => ['title' => 'Laporan Pesanan Terlambat (Overdue)', 'category' => 'operations'],
        'orders_cancelled' => ['title' => 'Laporan Pembatalan Pesanan & Audit', 'category' => 'operations'],
        'productivity' => ['title' => 'Laporan Produktivitas Produksi Staf', 'category' => 'operations'],
        'machine_performance' => ['title' => 'Laporan Kinerja & Siklus Mesin', 'category' => 'operations'],
        'machine_utilization' => ['title' => 'Laporan Utilisasi Mesin', 'category' => 'operations'],
        'courier_tasks' => ['title' => 'Laporan Tugas Penjemputan & Kurir', 'category' => 'operations'],

        // CRM & Pelanggan
        'customers_new' => ['title' => 'Laporan Pelanggan Baru Terdaftar', 'category' => 'crm'],
        'customers_inactive' => ['title' => 'Laporan Pelanggan Tidak Aktif (>30 Hari)', 'category' => 'crm'],
        'voucher_usage' => ['title' => 'Laporan Penggunaan Voucher Diskon', 'category' => 'crm'],
        'loyalty_points' => ['title' => 'Laporan Poin Loyalitas & Liabilitas', 'category' => 'crm'],

        // Keuangan & Kas
        'cash_receipts' => ['title' => 'Laporan Penerimaan Kas & Bank', 'category' => 'finance'],
        'expenses' => ['title' => 'Laporan Pengeluaran Beban Biaya', 'category' => 'finance'],
        'receivables' => ['title' => 'Laporan Piutang Pelanggan (Belum Lunas)', 'category' => 'finance'],
        'purchases' => ['title' => 'Laporan Pembelian Barang & PO Supplier', 'category' => 'finance'],

        // Inventaris & Gudang
        'stock_current' => ['title' => 'Laporan Posisi Stok Inventaris Saat Ini', 'category' => 'inventory'],
        'stock_movements' => ['title' => 'Laporan Log Mutasi Stok Masuk & Keluar', 'category' => 'inventory'],
        'stock_low' => ['title' => 'Laporan Peringatan Stok Menipis', 'category' => 'inventory'],

        // Laba Rugi & Komparasi Eksekutif
        'income_statement' => ['title' => 'Laporan Laba Rugi Operasional (P&L)', 'category' => 'executive'],
        'branch_comparison' => ['title' => 'Laporan Perbandingan Performa Multi-Cabang', 'category' => 'executive'],
        'executive_summary' => ['title' => 'Laporan Ringkasan Eksekutif Bisnis', 'category' => 'executive'],
    ];

    public function index(): void
    {
        Permission::abortUnless('reports.view');

        $this->view('reports.index', [
            'title' => 'Pusat Laporan & Analisis Bisnis (30 Laporan)',
            'activeMenu' => 'reports_index',
            'reports' => $this->reportRegistry,
        ]);
    }

    public function show(string $slug): void
    {
        Permission::abortUnless('reports.view');

        if (!isset($this->reportRegistry[$slug])) {
            $this->redirect(url('/reports'));
        }

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $export = $_GET['export'] ?? null;

        $reportMeta = $this->reportRegistry[$slug];
        $data = $this->generateReportData($slug, $branchFilter, $startDate, $endDate);

        if ($export === 'csv') {
            $this->exportCsv($slug, $reportMeta['title'], $data);
            return;
        }

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $layout = ($export === 'print') ? 'layouts/print' : 'layouts/admin';

        $this->view('reports.show', [
            'title' => $reportMeta['title'],
            'activeMenu' => 'reports_index',
            'slug' => $slug,
            'reportMeta' => $reportMeta,
            'data' => $data,
            'branches' => $branches,
            'selectedBranch' => $branchFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isPrint' => ($export === 'print'),
        ], $layout);
    }

    private function generateReportData(string $slug, int $branchFilter, string $startDate, string $endDate): array
    {
        $branchClause = "";
        $params = [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59'];

        if ($branchFilter > 0) {
            $branchClause = " AND o.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $branchClause = " AND o.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        return match ($slug) {
            'sales_daily' => $this->reportSalesDaily($branchClause, $params),
            'sales_weekly' => $this->reportSalesWeekly($branchClause, $params),
            'sales_monthly' => $this->reportSalesMonthly($branchClause, $params),
            'sales_yearly' => $this->reportSalesYearly($branchClause, $params),
            'sales_branch' => $this->reportSalesBranch($startDate, $endDate),
            'sales_cashier' => $this->reportSalesCashier($branchClause, $params),
            'sales_service' => $this->reportSalesService($branchClause, $params),
            'sales_kilo_vs_satuan' => $this->reportSalesKiloVsSatuan($branchClause, $params),
            'orders_active' => $this->reportOrdersActive($branchClause, $params),
            'orders_completed' => $this->reportOrdersCompleted($branchClause, $params),
            'orders_late' => $this->reportOrdersLate($branchClause),
            'orders_cancelled' => $this->reportOrdersCancelled($branchClause, $params),
            'productivity' => $this->reportProductivity($branchClause, $params),
            'machine_performance' => $this->reportMachinePerformance($branchFilter),
            'machine_utilization' => $this->reportMachineUtilization($branchFilter),
            'courier_tasks' => $this->reportCourierTasks($branchFilter, $startDate, $endDate),
            'customers_new' => $this->reportCustomersNew($startDate, $endDate),
            'customers_inactive' => $this->reportCustomersInactive(),
            'voucher_usage' => $this->reportVoucherUsage($startDate, $endDate),
            'loyalty_points' => $this->reportLoyaltyPoints(),
            'cash_receipts' => $this->reportCashReceipts($branchFilter, $startDate, $endDate),
            'expenses' => $this->reportExpenses($branchFilter, $startDate, $endDate),
            'receivables' => $this->reportReceivables($branchClause),
            'purchases' => $this->reportPurchases($branchFilter, $startDate, $endDate),
            'stock_current' => $this->reportStockCurrent($branchFilter),
            'stock_movements' => $this->reportStockMovements($branchFilter, $startDate, $endDate),
            'stock_low' => $this->reportStockLow($branchFilter),
            'income_statement' => $this->reportIncomeStatement($branchFilter, $startDate, $endDate),
            'branch_comparison' => $this->reportBranchComparison($startDate, $endDate),
            'executive_summary' => $this->reportExecutiveSummary($branchFilter, $startDate, $endDate),
            default => ['headers' => [], 'rows' => [], 'summary' => []],
        };
    }

    private function reportSalesDaily(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT substr(o.created_at, 1, 10) AS order_date,
                    COUNT(o.id) AS total_orders,
                    SUM(o.total_weight) AS total_weight,
                    SUM(o.total_pieces) AS total_pieces,
                    SUM(o.subtotal) AS subtotal,
                    SUM(o.discount_total + o.voucher_discount) AS total_discount,
                    SUM(o.grand_total) AS grand_total,
                    SUM(o.paid_amount) AS total_paid
             FROM orders o
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY substr(o.created_at, 1, 10)
             ORDER BY order_date DESC",
            $params
        );

        return [
            'headers' => ['Tanggal', 'Jumlah Nota', 'Total Berat (Kg)', 'Total Pcs', 'Subtotal', 'Diskon', 'Grand Total', 'Penerimaan'],
            'rows' => $rows,
            'summary' => [
                'Total Transaksi' => array_sum(array_column($rows, 'total_orders')),
                'Total Omset' => format_rupiah(array_sum(array_column($rows, 'grand_total'))),
                'Total Terbayar' => format_rupiah(array_sum(array_column($rows, 'total_paid'))),
            ],
        ];
    }

    private function reportSalesWeekly(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT strftime('%Y-W%W', o.created_at) AS week_period,
                    COUNT(o.id) AS total_orders,
                    SUM(o.grand_total) AS grand_total,
                    SUM(o.paid_amount) AS total_paid
             FROM orders o
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY week_period
             ORDER BY week_period DESC",
            $params
        );

        return [
            'headers' => ['Minggu ke (Tahun-W)', 'Jumlah Nota', 'Grand Total', 'Total Terbayar'],
            'rows' => $rows,
            'summary' => [
                'Total Omset' => format_rupiah(array_sum(array_column($rows, 'grand_total'))),
            ],
        ];
    }

    private function reportSalesMonthly(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT substr(o.created_at, 1, 7) AS month_period,
                    COUNT(o.id) AS total_orders,
                    SUM(o.grand_total) AS grand_total,
                    SUM(o.paid_amount) AS total_paid,
                    SUM(o.balance_due) AS total_due
             FROM orders o
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY month_period
             ORDER BY month_period DESC",
            $params
        );

        return [
            'headers' => ['Periode Bulan', 'Jumlah Nota', 'Grand Total', 'Sudah Dibayar', 'Sisa Piutang'],
            'rows' => $rows,
            'summary' => [
                'Total Omset' => format_rupiah(array_sum(array_column($rows, 'grand_total'))),
                'Total Piutang' => format_rupiah(array_sum(array_column($rows, 'total_due'))),
            ],
        ];
    }

    private function reportSalesYearly(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT substr(o.created_at, 1, 4) AS year_period,
                    COUNT(o.id) AS total_orders,
                    SUM(o.grand_total) AS grand_total,
                    SUM(o.paid_amount) AS total_paid
             FROM orders o
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY year_period
             ORDER BY year_period DESC",
            $params
        );

        return [
            'headers' => ['Tahun', 'Total Nota', 'Grand Total', 'Total Pembayaran'],
            'rows' => $rows,
            'summary' => ['Total Omset' => format_rupiah(array_sum(array_column($rows, 'grand_total')))],
        ];
    }

    private function reportSalesBranch(string $startDate, string $endDate): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT b.name AS branch_name,
                    COUNT(o.id) AS total_orders,
                    SUM(o.grand_total) AS grand_total,
                    SUM(o.paid_amount) AS total_paid,
                    SUM(o.balance_due) AS total_due
             FROM branches b
             LEFT JOIN orders o ON o.branch_id = b.id AND o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e
             GROUP BY b.id
             ORDER BY grand_total DESC",
            [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59']
        );

        return [
            'headers' => ['Cabang Outlet', 'Jumlah Nota', 'Omset Penjualan', 'Sudah Dibayar', 'Piutang'],
            'rows' => $rows,
            'summary' => [
                'Total Omset Seluruh Cabang' => format_rupiah(array_sum(array_column($rows, 'grand_total'))),
            ],
        ];
    }

    private function reportSalesCashier(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT u.name AS cashier_name,
                    b.name AS branch_name,
                    COUNT(o.id) AS total_orders,
                    SUM(o.grand_total) AS total_sales
             FROM users u
             JOIN orders o ON o.user_id = u.id
             JOIN branches b ON o.branch_id = b.id
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY u.id
             ORDER BY total_sales DESC",
            $params
        );

        return [
            'headers' => ['Nama Kasir / Staf', 'Cabang', 'Jumlah Transaksi', 'Total Penjualan'],
            'rows' => $rows,
            'summary' => ['Total Penjualan' => format_rupiah(array_sum(array_column($rows, 'total_sales')))],
        ];
    }

    private function reportSalesService(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT s.name AS service_name,
                    sc.name AS category_name,
                    COUNT(oi.id) AS times_ordered,
                    SUM(oi.quantity) AS total_quantity,
                    SUM(oi.subtotal) AS total_revenue
             FROM order_items oi
             JOIN services s ON oi.service_id = s.id
             JOIN service_categories sc ON s.category_id = sc.id
             JOIN orders o ON oi.order_id = o.id
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY s.id
             ORDER BY total_revenue DESC",
            $params
        );

        return [
            'headers' => ['Layanan', 'Kategori', 'Frekuensi Pesan', 'Total Qty / Kg', 'Total Pendapatan'],
            'rows' => $rows,
            'summary' => ['Total Pendapatan Layanan' => format_rupiah(array_sum(array_column($rows, 'total_revenue')))],
        ];
    }

    private function reportSalesKiloVsSatuan(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT CASE WHEN oi.unit_type = 'kg' OR s.unit_type = 'kg' THEN 'Kiloan' ELSE 'Satuan' END AS service_type,
                    COUNT(oi.id) AS times_ordered,
                    SUM(CASE WHEN oi.unit_type = 'kg' THEN oi.weight ELSE oi.quantity END) AS total_volume,
                    SUM(oi.subtotal) AS total_revenue
             FROM order_items oi
             JOIN services s ON oi.service_id = s.id
             JOIN orders o ON oi.order_id = o.id
             WHERE o.production_status != 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             GROUP BY (CASE WHEN oi.unit_type = 'kg' OR s.unit_type = 'kg' THEN 'Kiloan' ELSE 'Satuan' END)",
            $params
        );

        return [
            'headers' => ['Jenis Layanan', 'Frekuensi Transaksi', 'Total Volume (Kg/Pcs)', 'Total Pendapatan'],
            'rows' => $rows,
            'summary' => ['Total Pendapatan' => format_rupiah(array_sum(array_column($rows, 'total_revenue')))],
        ];
    }

    private function reportOrdersActive(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT o.order_no, b.name AS branch_name, c.name AS customer_name,
                    o.production_status, o.est_completion_at, o.grand_total, o.balance_due
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             JOIN customers c ON o.customer_id = c.id
             WHERE o.production_status NOT IN ('completed', 'cancelled') {$branchClause}
             ORDER BY o.est_completion_at ASC",
            isset($params[':b']) ? [':b' => $params[':b']] : []
        );

        return [
            'headers' => ['No. Nota', 'Cabang', 'Pelanggan', 'Tahap Produksi', 'Target Selesai', 'Grand Total', 'Sisa Tagihan'],
            'rows' => $rows,
            'summary' => ['Pesanan Aktif Berjalan' => count($rows)],
        ];
    }

    private function reportOrdersCompleted(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT o.order_no, b.name AS branch_name, c.name AS customer_name,
                    o.actual_completion_at, o.grand_total, o.paid_amount
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             JOIN customers c ON o.customer_id = c.id
             WHERE o.production_status = 'completed' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             ORDER BY o.actual_completion_at DESC",
            $params
        );

        return [
            'headers' => ['No. Nota', 'Cabang', 'Pelanggan', 'Waktu Diserahkan', 'Total Nilai', 'Total Dibayar'],
            'rows' => $rows,
            'summary' => ['Total Pesanan Selesai' => count($rows)],
        ];
    }

    private function reportOrdersLate(string $branchClause): array
    {
        $now = date('Y-m-d H:i:s');
        $rows = $this->db()->fetchAll(
            "SELECT o.order_no, b.name AS branch_name, c.name AS customer_name, c.whatsapp,
                    o.production_status, o.est_completion_at, o.grand_total
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             JOIN customers c ON o.customer_id = c.id
             WHERE o.production_status NOT IN ('completed', 'cancelled') AND o.est_completion_at < :now {$branchClause}
             ORDER BY o.est_completion_at ASC",
            [':now' => $now]
        );

        return [
            'headers' => ['No. Nota', 'Cabang', 'Pelanggan', 'Nomor WA', 'Tahap Sekarang', 'Tenggat Waktu', 'Tagihan'],
            'rows' => $rows,
            'summary' => ['Pesanan Mengalami Keterlambatan' => count($rows)],
        ];
    }

    private function reportOrdersCancelled(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT o.order_no, b.name AS branch_name, c.name AS customer_name,
                    o.cancelled_at, o.cancellation_reason, u.name AS cancelled_by_name, o.grand_total
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             JOIN customers c ON o.customer_id = c.id
             LEFT JOIN users u ON o.cancelled_by = u.id
             WHERE o.production_status = 'cancelled' AND o.created_at >= :s AND o.created_at <= :e {$branchClause}
             ORDER BY o.cancelled_at DESC",
            $params
        );

        return [
            'headers' => ['No. Nota', 'Cabang', 'Pelanggan', 'Waktu Dibatalkan', 'Alasan Pembatalan', 'Otorisator', 'Nilai'],
            'rows' => $rows,
            'summary' => ['Total Pembatalan' => count($rows)],
        ];
    }

    private function reportProductivity(string $branchClause, array $params): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT u.name AS operator_name,
                    ps.name AS stage_name,
                    COUNT(psl.id) AS total_batches,
                    SUM(strftime('%s', IFNULL(psl.completed_at, datetime('now'))) - strftime('%s', psl.started_at)) / 60 AS total_minutes
             FROM production_stage_logs psl
             JOIN users u ON psl.user_id = u.id
             JOIN production_stages ps ON psl.stage_id = ps.id
             JOIN production_jobs pj ON psl.job_id = pj.id
             JOIN orders o ON pj.order_id = o.id
             WHERE psl.started_at >= :s AND psl.started_at <= :e {$branchClause}
             GROUP BY u.id, ps.id
             ORDER BY u.name ASC",
            $params
        );

        return [
            'headers' => ['Nama Staf Produksi', 'Tahapan Pekerjaan', 'Jumlah Batch Diproses', 'Total Durasi Kerja (Menit)'],
            'rows' => $rows,
            'summary' => ['Total Batch Kerja' => array_sum(array_column($rows, 'total_batches'))],
        ];
    }

    private function reportMachinePerformance(int $branchFilter): array
    {
        $where = "1=1";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND m.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT m.code, m.name, b.name AS branch_name, m.brand,
                    m.capacity_kg, m.total_cycles, m.operational_status, m.condition
             FROM machines m
             JOIN branches b ON m.branch_id = b.id
             WHERE {$where}
             ORDER BY m.total_cycles DESC",
            $params
        );

        return [
            'headers' => ['Kode Mesin', 'Nama Mesin', 'Cabang', 'Merk', 'Kapasitas (Kg)', 'Total Siklus Cuci', 'Status Operasi', 'Kondisi Fisik'],
            'rows' => $rows,
            'summary' => ['Total Armada Mesin' => count($rows)],
        ];
    }

    private function reportMachineUtilization(int $branchFilter): array
    {
        $where = "1=1";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND m.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT m.code, m.name, b.name AS branch_name,
                    COUNT(psl.id) AS total_jobs,
                    SUM(strftime('%s', IFNULL(psl.completed_at, datetime('now'))) - strftime('%s', psl.started_at)) / 60 AS active_minutes
             FROM machines m
             JOIN branches b ON m.branch_id = b.id
             LEFT JOIN production_stage_logs psl ON psl.machine_id = m.id
             WHERE {$where}
             GROUP BY m.id
             ORDER BY active_minutes DESC",
            $params
        );

        return [
            'headers' => ['Kode Mesin', 'Nama Mesin', 'Cabang', 'Total Siklus Pekerjaan', 'Total Jam/Menit Utilisasi (Menit)'],
            'rows' => $rows,
            'summary' => ['Total Mesin Terutilisasi' => count($rows)],
        ];
    }

    private function reportCourierTasks(int $branchFilter, string $startDate, string $endDate): array
    {
        $where = "dr.schedule_date >= :s AND dr.schedule_date <= :e";
        $params = [':s' => $startDate, ':e' => $endDate];
        if ($branchFilter > 0) {
            $where .= " AND dr.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT u.name AS courier_name,
                    b.name AS branch_name,
                    COUNT(dr.id) AS total_tasks,
                    SUM(CASE WHEN dr.status IN ('picked_up', 'delivered') THEN 1 ELSE 0 END) AS completed_tasks,
                    SUM(dr.fee) AS total_fees
             FROM delivery_requests dr
             JOIN branches b ON dr.branch_id = b.id
             LEFT JOIN users u ON dr.courier_user_id = u.id
             WHERE {$where}
             GROUP BY dr.courier_user_id
             ORDER BY completed_tasks DESC",
            $params
        );

        return [
            'headers' => ['Nama Kurir', 'Cabang', 'Total Tugas', 'Berhasil Diselesaikan', 'Total Ongkos Kirim'],
            'rows' => $rows,
            'summary' => ['Total Pengiriman Kurir' => array_sum(array_column($rows, 'completed_tasks'))],
        ];
    }

    private function reportCustomersNew(string $startDate, string $endDate): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT c.code, c.name, c.whatsapp, c.email, c.join_date, mt.name AS tier_name,
                    COUNT(o.id) AS order_count, SUM(o.grand_total) AS total_spent
             FROM customers c
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             LEFT JOIN orders o ON o.customer_id = c.id
             WHERE c.join_date >= :s AND c.join_date <= :e
             GROUP BY c.id
             ORDER BY c.join_date DESC",
            [':s' => $startDate, ':e' => $endDate]
        );

        return [
            'headers' => ['Kode Pelanggan', 'Nama', 'No. WhatsApp', 'Email', 'Tanggal Gabung', 'Tier Member', 'Jumlah Order', 'Total Belanja'],
            'rows' => $rows,
            'summary' => ['Pelanggan Baru Terdaftar' => count($rows)],
        ];
    }

    private function reportCustomersInactive(): array
    {
        $threshold = date('Y-m-d', strtotime('-30 days'));
        $rows = $this->db()->fetchAll(
            "SELECT c.code, c.name, c.whatsapp, MAX(o.created_at) AS last_order_at,
                    COUNT(o.id) AS lifetime_orders, SUM(o.grand_total) AS lifetime_spent
             FROM customers c
             LEFT JOIN orders o ON o.customer_id = c.id
             GROUP BY c.id
             HAVING last_order_at < :t OR last_order_at IS NULL
             ORDER BY last_order_at ASC",
            [':t' => $threshold]
        );

        return [
            'headers' => ['Kode', 'Nama Pelanggan', 'WhatsApp', 'Terakhir Order', 'Total Order Masa Lalu', 'Nilai Belanja Lalu'],
            'rows' => $rows,
            'summary' => ['Pelanggan Perlu Re-Aktivasi' => count($rows)],
        ];
    }

    private function reportVoucherUsage(string $startDate, string $endDate): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT v.code, v.name, v.type, v.value,
                    COUNT(vu.id) AS times_used,
                    SUM(vu.discount_amount) AS total_discount_given
             FROM vouchers v
             JOIN voucher_usages vu ON vu.voucher_id = v.id
             WHERE vu.used_at >= :s AND vu.used_at <= :e
             GROUP BY v.id
             ORDER BY times_used DESC",
            [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59']
        );

        return [
            'headers' => ['Kode Kupon', 'Nama Promo', 'Tipe', 'Nilai Voucher', 'Jumlah Digunakan', 'Total Diskon Diberikan'],
            'rows' => $rows,
            'summary' => ['Total Diskon Promo' => format_rupiah(array_sum(array_column($rows, 'total_discount_given')))],
        ];
    }

    private function reportLoyaltyPoints(): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT c.code, c.name, mt.name AS tier_name, c.points_balance,
                    (c.points_balance * 100) AS estimated_liability_rp
             FROM customers c
             LEFT JOIN membership_tiers mt ON c.current_tier_id = mt.id
             WHERE c.points_balance > 0
             ORDER BY c.points_balance DESC"
        );

        return [
            'headers' => ['Kode Pelanggan', 'Nama Member', 'Tingkat Tier', 'Poin Aktif', 'Estimasi Nilai Rupiah'],
            'rows' => $rows,
            'summary' => ['Total Poin Beredar' => number_format(array_sum(array_column($rows, 'points_balance')))],
        ];
    }

    private function reportCashReceipts(int $branchFilter, string $startDate, string $endDate): array
    {
        $where = "p.paid_at >= :s AND p.paid_at <= :e AND p.status = 'success'";
        $params = [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59'];
        if ($branchFilter > 0) {
            $where .= " AND p.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT p.payment_no, b.name AS branch_name, pm.name AS method_name,
                    p.payment_type, p.amount, p.paid_at, o.order_no
             FROM payments p
             JOIN branches b ON p.branch_id = b.id
             JOIN payment_methods pm ON p.payment_method_id = pm.id
             JOIN orders o ON p.order_id = o.id
             WHERE {$where}
             ORDER BY p.paid_at DESC",
            $params
        );

        return [
            'headers' => ['No. Bukti Bayar', 'Cabang', 'Metode Pembayaran', 'Tipe (DP/Lunas)', 'Jumlah Diterima', 'Waktu Pembayaran', 'No. Nota'],
            'rows' => $rows,
            'summary' => ['Total Kas Diterima' => format_rupiah(array_sum(array_column($rows, 'amount')))],
        ];
    }

    private function reportExpenses(int $branchFilter, string $startDate, string $endDate): array
    {
        $where = "e.expense_date >= :s AND e.expense_date <= :e";
        $params = [':s' => $startDate, ':e' => $endDate];
        if ($branchFilter > 0) {
            $where .= " AND e.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT e.expense_no, b.name AS branch_name, ec.name AS category_name,
                    ca.name AS cash_account_name, e.amount, e.paid_to, e.description, e.expense_date
             FROM expenses e
             JOIN branches b ON e.branch_id = b.id
             JOIN expense_categories ec ON e.category_id = ec.id
             JOIN cash_accounts ca ON e.cash_account_id = ca.id
             WHERE {$where}
             ORDER BY e.expense_date DESC",
            $params
        );

        return [
            'headers' => ['No. Pengeluaran', 'Cabang', 'Kategori Biaya', 'Sumber Kas/Bank', 'Nominal', 'Dibayar Kepada', 'Keterangan', 'Tanggal'],
            'rows' => $rows,
            'summary' => ['Total Pengeluaran Beban' => format_rupiah(array_sum(array_column($rows, 'amount')))],
        ];
    }

    private function reportReceivables(string $branchClause): array
    {
        $rows = $this->db()->fetchAll(
            "SELECT o.order_no, b.name AS branch_name, c.name AS customer_name, c.whatsapp,
                    o.grand_total, o.paid_amount, o.balance_due, o.created_at
             FROM orders o
             JOIN branches b ON o.branch_id = b.id
             JOIN customers c ON o.customer_id = c.id
             WHERE o.production_status != 'cancelled' AND o.balance_due > 0 {$branchClause}
             ORDER BY o.balance_due DESC"
        );

        return [
            'headers' => ['No. Nota', 'Cabang', 'Pelanggan', 'No. WA', 'Grand Total', 'Sudah Dibayar', 'Sisa Piutang', 'Tanggal Transaksi'],
            'rows' => $rows,
            'summary' => ['Total Piutang Belum Tertagih' => format_rupiah(array_sum(array_column($rows, 'balance_due')))],
        ];
    }

    private function reportPurchases(int $branchFilter, string $startDate, string $endDate): array
    {
        $where = "po.order_date >= :s AND po.order_date <= :e";
        $params = [':s' => $startDate, ':e' => $endDate];
        if ($branchFilter > 0) {
            $where .= " AND po.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT po.po_no, b.name AS branch_name, s.name AS supplier_name,
                    po.total_amount, po.status, po.order_date, po.received_date
             FROM purchase_orders po
             JOIN branches b ON po.branch_id = b.id
             JOIN suppliers s ON po.supplier_id = s.id
             WHERE {$where}
             ORDER BY po.order_date DESC",
            $params
        );

        return [
            'headers' => ['No. PO', 'Cabang', 'Supplier', 'Nilai PO', 'Status', 'Tanggal Order', 'Tanggal Diterima'],
            'rows' => $rows,
            'summary' => ['Total Nilai Pembelian' => format_rupiah(array_sum(array_column($rows, 'total_amount')))],
        ];
    }

    private function reportStockCurrent(int $branchFilter): array
    {
        $where = "1=1";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND bi.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT bi.branch_id, b.name AS branch_name, ii.code, ii.name, ic.name AS category_name,
                    bi.current_stock, ii.unit, bi.min_stock, ii.cost_price,
                    (bi.current_stock * ii.cost_price) AS stock_value
             FROM branch_inventory bi
             JOIN inventory_items ii ON bi.item_id = ii.id
             JOIN inventory_categories ic ON ii.category_id = ic.id
             JOIN branches b ON bi.branch_id = b.id
             WHERE {$where}
             ORDER BY b.id ASC, ii.name ASC",
            $params
        );

        return [
            'headers' => ['ID', 'Cabang', 'Kode SKU', 'Nama Barang', 'Kategori', 'Stok Saat Ini', 'Satuan', 'Batas Minimum', 'Harga Beli Satuan', 'Nilai Aset Stok'],
            'rows' => $rows,
            'summary' => ['Total Nilai Aset Stok' => format_rupiah(array_sum(array_column($rows, 'stock_value')))],
        ];
    }

    private function reportStockMovements(int $branchFilter, string $startDate, string $endDate): array
    {
        $where = "sm.created_at >= :s AND sm.created_at <= :e";
        $params = [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59'];
        if ($branchFilter > 0) {
            $where .= " AND sm.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT sm.created_at, b.name AS branch_name, ii.code, ii.name, sm.type,
                    sm.quantity, ii.unit, sm.stock_before, sm.stock_after, sm.notes
             FROM stock_movements sm
             JOIN inventory_items ii ON sm.item_id = ii.id
             JOIN branches b ON sm.branch_id = b.id
             WHERE {$where}
             ORDER BY sm.created_at DESC",
            $params
        );

        return [
            'headers' => ['Waktu', 'Cabang', 'Kode SKU', 'Nama Barang', 'Jenis Mutasi', 'Jumlah', 'Unit', 'Stok Awal', 'Stok Akhir', 'Catatan'],
            'rows' => $rows,
            'summary' => ['Total Mutasi' => count($rows)],
        ];
    }

    private function reportStockLow(int $branchFilter): array
    {
        $where = "bi.current_stock <= bi.min_stock";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND bi.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $rows = $this->db()->fetchAll(
            "SELECT b.name AS branch_name, ii.code, ii.name, ic.name AS category_name,
                    bi.current_stock, bi.min_stock, ii.unit
             FROM branch_inventory bi
             JOIN inventory_items ii ON bi.item_id = ii.id
             JOIN inventory_categories ic ON ii.category_id = ic.id
             JOIN branches b ON bi.branch_id = b.id
             WHERE {$where}
             ORDER BY (bi.min_stock - bi.current_stock) DESC",
            $params
        );

        return [
            'headers' => ['Cabang', 'Kode SKU', 'Nama Barang', 'Kategori', 'Sisa Stok', 'Batas Minimal', 'Satuan'],
            'rows' => $rows,
            'summary' => ['Item Perlu Restock' => count($rows)],
        ];
    }

    private function reportIncomeStatement(int $branchFilter, string $startDate, string $endDate): array
    {
        $branchWhere = "";
        $params = [':s' => $startDate, ':e' => $endDate];
        if ($branchFilter > 0) {
            $branchWhere = " AND branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        // Pendapatan Jasa Laundry
        $revenue = (int)$this->db()->fetchColumn(
            "SELECT SUM(grand_total) FROM orders WHERE production_status != 'cancelled' AND date(created_at) >= :s AND date(created_at) <= :e {$branchWhere}",
            $params
        );

        // Biaya Operasional
        $expenses = (int)$this->db()->fetchColumn(
            "SELECT SUM(amount) FROM expenses WHERE expense_date >= :s AND expense_date <= :e {$branchWhere}",
            $params
        );

        $netProfit = $revenue - $expenses;

        $rows = [
            ['komponen' => 'Pendapatan Jasa Laundry Bruto', 'nominal' => $revenue],
            ['komponen' => 'Total Beban Biaya Operasional', 'nominal' => -$expenses],
            ['komponen' => 'Estimasi Laba / (Rugi) Bersih Usaha', 'nominal' => $netProfit],
        ];

        return [
            'headers' => ['Komponen Finansial', 'Nilai Moneter (Rp)'],
            'rows' => $rows,
            'summary' => [
                'Laba Bersih Operasional' => format_rupiah($netProfit),
            ],
        ];
    }

    private function reportBranchComparison(string $startDate, string $endDate): array
    {
        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $rows = [];

        foreach ($branches as $b) {
            $sales = (int)$this->db()->fetchColumn(
                "SELECT SUM(grand_total) FROM orders WHERE branch_id = :b AND production_status != 'cancelled' AND date(created_at) >= :s AND date(created_at) <= :e",
                [':b' => $b['id'], ':s' => $startDate, ':e' => $endDate]
            );
            $orders = (int)$this->db()->fetchColumn(
                "SELECT COUNT(*) FROM orders WHERE branch_id = :b AND production_status != 'cancelled' AND date(created_at) >= :s AND date(created_at) <= :e",
                [':b' => $b['id'], ':s' => $startDate, ':e' => $endDate]
            );
            $expense = (int)$this->db()->fetchColumn(
                "SELECT SUM(amount) FROM expenses WHERE branch_id = :b AND expense_date >= :s AND expense_date <= :e",
                [':b' => $b['id'], ':s' => $startDate, ':e' => $endDate]
            );

            $rows[] = [
                'branch_name' => $b['name'],
                'total_orders' => $orders,
                'total_revenue' => $sales,
                'total_expenses' => $expense,
                'net_income' => $sales - $expense,
            ];
        }

        return [
            'headers' => ['Nama Cabang', 'Jumlah Transaksi', 'Total Pendapatan', 'Total Beban Biaya', 'Laba Bersih Cabang'],
            'rows' => $rows,
            'summary' => [
                'Total Omset Multi-Cabang' => format_rupiah(array_sum(array_column($rows, 'total_revenue'))),
            ],
        ];
    }

    private function reportExecutiveSummary(int $branchFilter, string $startDate, string $endDate): array
    {
        $branchClause = "";
        $params = [':s' => $startDate . ' 00:00:00', ':e' => $endDate . ' 23:59:59'];
        if ($branchFilter > 0) {
            $branchClause = " AND branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $totalSales = (int)$this->db()->fetchColumn("SELECT SUM(grand_total) FROM orders WHERE production_status != 'cancelled' AND created_at >= :s AND created_at <= :e {$branchClause}", $params);
        $totalOrders = (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM orders WHERE production_status != 'cancelled' AND created_at >= :s AND created_at <= :e {$branchClause}", $params);
        $totalWeight = (float)$this->db()->fetchColumn("SELECT SUM(total_weight) FROM orders WHERE production_status != 'cancelled' AND created_at >= :s AND created_at <= :e {$branchClause}", $params);
        $totalExpenses = (int)$this->db()->fetchColumn("SELECT SUM(amount) FROM expenses WHERE expense_date >= :s AND expense_date <= :e " . ($branchFilter ? "AND branch_id = " . (int)$branchFilter : ""), [':s' => $startDate, ':e' => $endDate]);
        $totalReceivables = (int)$this->db()->fetchColumn("SELECT SUM(balance_due) FROM orders WHERE production_status != 'cancelled' AND balance_due > 0 {$branchClause}", isset($params[':b']) ? [':b' => $params[':b']] : []);

        $rows = [
            ['indikator' => 'Total Omset Penjualan', 'nilai' => format_rupiah($totalSales)],
            ['indikator' => 'Total Beban Biaya Operasional', 'nilai' => format_rupiah($totalExpenses)],
            ['indikator' => 'Estimasi Laba Bersih Usaha', 'nilai' => format_rupiah($totalSales - $totalExpenses)],
            ['indikator' => 'Total Transaksi Nota Selesai / Berjalan', 'nilai' => number_format($totalOrders) . ' Transaksi'],
            ['indikator' => 'Volume Total Cucian Kiloan', 'nilai' => number_format($totalWeight, 1) . ' Kg'],
            ['indikator' => 'Total Sisa Piutang Belum Lunas', 'nilai' => format_rupiah($totalReceivables)],
        ];

        return [
            'headers' => ['Key Performance Indicator (KPI)', 'Capaian Eksekutif'],
            'rows' => $rows,
            'summary' => [
                'Status Bisnis' => ($totalSales >= $totalExpenses) ? 'PROFITABEL' : 'PERHATIAN / DEFISIT',
            ],
        ];
    }

    private function exportCsv(string $slug, string $title, array $data): void
    {
        $filename = "laundrypro_{$slug}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $out = fopen('php://output', 'w');
        fputcsv($out, [$title]);
        fputcsv($out, ['Tanggal Unduh:', date('Y-m-d H:i:s')]);
        fputcsv($out, []);

        if (!empty($data['headers'])) {
            fputcsv($out, $data['headers']);
        }

        foreach ($data['rows'] as $row) {
            fputcsv($out, array_values((array)$row));
        }

        if (!empty($data['summary'])) {
            fputcsv($out, []);
            fputcsv($out, ['--- RINGKASAN TOTAL ---']);
            foreach ($data['summary'] as $label => $val) {
                fputcsv($out, [$label, $val]);
            }
        }

        fclose($out);
        if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
            return;
        }
        exit;
    }
}
