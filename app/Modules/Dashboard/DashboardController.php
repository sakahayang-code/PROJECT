<?php

namespace App\Modules\Dashboard;

use App\Core\Controller;
use App\Core\Auth;

class DashboardController extends Controller
{
    public function index(): void
    {
        $activeBranchId = Auth::getActiveBranchId();
        $isOwnerOrSuper = Auth::hasGlobalAccess() && empty($activeBranchId);

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');

        // Build branch condition
        $branchClause = "";
        $branchParams = [];
        if (!empty($activeBranchId)) {
            $branchClause = " AND branch_id = :branch_id";
            $branchParams = [':branch_id' => $activeBranchId];
        }

        // 1. Metrics: Revenue Today
        $sqlToday = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success' AND date(paid_at) = :today" . $branchClause;
        $revenueToday = (int)$this->db()->fetchColumn($sqlToday, array_merge([':today' => $today], $branchParams));

        // 2. Revenue This Month
        $sqlMonth = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success' AND paid_at BETWEEN :start AND :end" . $branchClause;
        $revenueMonth = (int)$this->db()->fetchColumn($sqlMonth, array_merge([':start' => $monthStart, ':end' => $monthEnd], $branchParams));

        // 3. Total Orders Count
        $sqlOrders = "SELECT COUNT(*) FROM orders WHERE 1=1" . $branchClause;
        $totalOrders = (int)$this->db()->fetchColumn($sqlOrders, $branchParams);

        // 4. Active Orders (not completed, not cancelled)
        $sqlActive = "SELECT COUNT(*) FROM orders WHERE production_status NOT IN ('completed', 'cancelled')" . $branchClause;
        $activeOrders = (int)$this->db()->fetchColumn($sqlActive, $branchParams);

        // 5. Ready Orders
        $sqlReady = "SELECT COUNT(*) FROM orders WHERE production_status = 'ready'" . $branchClause;
        $readyOrders = (int)$this->db()->fetchColumn($sqlReady, $branchParams);

        // 6. Overdue / Late Orders (est_completion_at < NOW and not completed)
        $now = date('Y-m-d H:i:s');
        $sqlLate = "SELECT COUNT(*) FROM orders WHERE production_status NOT IN ('completed', 'cancelled') AND est_completion_at < :now" . $branchClause;
        $lateOrders = (int)$this->db()->fetchColumn($sqlLate, array_merge([':now' => $now], $branchParams));

        // 7. Total Receivables (Piutang)
        $sqlReceivable = "SELECT COALESCE(SUM(balance_due), 0) FROM orders WHERE payment_status IN ('unpaid', 'partial') AND production_status != 'cancelled'" . $branchClause;
        $totalReceivable = (int)$this->db()->fetchColumn($sqlReceivable, $branchParams);

        // 8. Total Expenses This Month
        $sqlExpenses = "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE status = 'approved' AND expense_date BETWEEN :start AND :end" . $branchClause;
        $totalExpenses = (int)$this->db()->fetchColumn($sqlExpenses, array_merge([':start' => date('Y-m-01'), ':end' => date('Y-m-t')], $branchParams));

        // Estimated Profit
        $estProfit = $revenueMonth - $totalExpenses;

        // 9. Revenue Last 7 Days (Chart Data)
        $chartLabels = [];
        $chartRevenues = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $chartLabels[] = date('d M', strtotime($d));
            $sqlD = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success' AND date(paid_at) = :d" . $branchClause;
            $chartRevenues[] = (int)$this->db()->fetchColumn($sqlD, array_merge([':d' => $d], $branchParams));
        }

        // 10. Top Selling Services
        $sqlTopServices = "SELECT oi.service_name, COUNT(oi.id) as order_count, SUM(oi.subtotal) as total_sales
                           FROM order_items oi
                           JOIN orders o ON oi.order_id = o.id
                           WHERE o.production_status != 'cancelled' " . $branchClause . "
                           GROUP BY oi.service_name
                           ORDER BY total_sales DESC
                           LIMIT 5";
        $topServices = $this->db()->fetchAll($sqlTopServices, $branchParams);

        // 11. Low Stock Alerts
        $invClause = !empty($activeBranchId) ? " WHERE bi.branch_id = " . (int)$activeBranchId : "";
        $sqlLowStock = "SELECT bi.current_stock, bi.min_stock, ii.name as item_name, ii.unit, b.name as branch_name
                        FROM branch_inventory bi
                        JOIN inventory_items ii ON bi.item_id = ii.id
                        JOIN branches b ON bi.branch_id = b.id
                        {$invClause}
                        WHERE bi.current_stock <= bi.min_stock
                        ORDER BY bi.current_stock ASC
                        LIMIT 5";
        $lowStockItems = $this->db()->fetchAll($sqlLowStock);

        // 12. Recent Activities
        $actClause = !empty($activeBranchId) ? " WHERE branch_id = " . (int)$activeBranchId : "";
        $sqlActivities = "SELECT a.*, u.name as user_name 
                          FROM activity_logs a 
                          LEFT JOIN users u ON a.user_id = u.id 
                          {$actClause} 
                          ORDER BY a.created_at DESC 
                          LIMIT 7";
        $recentActivities = $this->db()->fetchAll($sqlActivities);

        // 13. Multi-Branch Comparison (For Owner / Super Admin)
        $branchComparison = [];
        if ($isOwnerOrSuper) {
            $sqlBranchComp = "SELECT b.id, b.name, b.code,
                                     COALESCE(SUM(p.amount), 0) AS total_revenue,
                                     COUNT(DISTINCT o.id) AS total_orders
                              FROM branches b
                              LEFT JOIN payments p ON b.id = p.branch_id AND p.status = 'success' AND p.paid_at BETWEEN :start AND :end
                              LEFT JOIN orders o ON b.id = o.branch_id AND o.created_at BETWEEN :start AND :end
                              WHERE b.is_active = 1
                              GROUP BY b.id
                              ORDER BY total_revenue DESC";
            $branchComparison = $this->db()->fetchAll($sqlBranchComp, [':start' => $monthStart, ':end' => $monthEnd]);
        }

        $this->view('dashboard.index', [
            'title' => 'Dashboard Bisnis',
            'activeMenu' => 'dashboard',
            'isOwnerOrSuper' => $isOwnerOrSuper,
            'activeBranchId' => $activeBranchId,
            'revenueToday' => $revenueToday,
            'revenueMonth' => $revenueMonth,
            'totalOrders' => $totalOrders,
            'activeOrders' => $activeOrders,
            'readyOrders' => $readyOrders,
            'lateOrders' => $lateOrders,
            'totalReceivable' => $totalReceivable,
            'totalExpenses' => $totalExpenses,
            'estProfit' => $estProfit,
            'chartLabels' => $chartLabels,
            'chartRevenues' => $chartRevenues,
            'topServices' => $topServices,
            'lowStockItems' => $lowStockItems,
            'recentActivities' => $recentActivities,
            'branchComparison' => $branchComparison,
        ]);
    }
}
