<?php

namespace App\Modules\Production;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Services\WhatsAppAdapter;

class ProductionController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('production.access');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));

        $where = "o.production_status NOT IN ('cancelled')";
        $params = [];

        if ($branchFilter > 0) {
            $where .= " AND o.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND o.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        $jobs = $this->db()->fetchAll(
            "SELECT pj.*, ps.name AS stage_name, ps.slug AS stage_slug, ps.sort_order,
                    o.order_no, o.order_type, o.est_completion_at, o.total_weight, o.total_pieces,
                    o.production_status, o.created_at AS order_created_at,
                    c.name AS customer_name, c.whatsapp AS customer_phone,
                    b.name AS branch_name, b.code AS branch_code,
                    u.name AS operator_name, m.name AS machine_name, m.code AS machine_code
             FROM production_jobs pj
             JOIN orders o ON pj.order_id = o.id
             JOIN customers c ON o.customer_id = c.id
             JOIN branches b ON o.branch_id = b.id
             LEFT JOIN production_stages ps ON pj.current_stage_id = ps.id
             LEFT JOIN users u ON pj.assigned_user_id = u.id
             LEFT JOIN machines m ON pj.machine_id = m.id
             WHERE {$where}
             ORDER BY o.est_completion_at ASC, pj.id ASC",
            $params
        );

        $stages = $this->db()->fetchAll("SELECT * FROM production_stages ORDER BY sort_order ASC");
        $branches = $this->db()->fetchAll("SELECT id, name, code FROM branches WHERE is_active = 1");

        // Operators & machines available in the active branch
        $opBranchClause = $branchFilter > 0 ? "WHERE u.branch_id = {$branchFilter} OR u.branch_id IS NULL" : "";
        $operators = $this->db()->fetchAll("SELECT u.id, u.name, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id {$opBranchClause}");

        $mcBranchClause = $branchFilter > 0 ? "WHERE m.branch_id = {$branchFilter} AND m.condition = 'good'" : "WHERE m.condition = 'good'";
        $machines = $this->db()->fetchAll("SELECT m.*, mt.name as type_name FROM machines m JOIN machine_types mt ON m.type_id = mt.id {$mcBranchClause}");

        $this->view('production.index', [
            'title' => 'Antrean Produksi & Quality Control',
            'activeMenu' => 'production',
            'jobs' => $jobs,
            'stages' => $stages,
            'branches' => $branches,
            'operators' => $operators,
            'machines' => $machines,
            'selectedBranch' => $branchFilter,
        ]);
    }

    public function assign(): void
    {
        Permission::abortUnless('production.access');

        $jobId = (int)$_POST['job_id'];
        $operatorId = !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : null;
        $machineId = !empty($_POST['machine_id']) ? (int)$_POST['machine_id'] : null;

        $this->db()->update('production_jobs', [
            'assigned_user_id' => $operatorId,
            'machine_id' => $machineId,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $jobId]);

        $this->back('Penugasan operator dan mesin berhasil disimpan.', 'success');
    }

    public function startStage(): void
    {
        Permission::abortUnless('production.access');

        $jobId = (int)$_POST['job_id'];
        $job = $this->db()->fetch("SELECT * FROM production_jobs WHERE id = :id", [':id' => $jobId]);
        if (!$job) {
            $this->back('Pekerjaan produksi tidak ditemukan.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $operatorId = !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : Auth::id();
        $machineId = !empty($_POST['machine_id']) ? (int)$_POST['machine_id'] : $job['machine_id'];

        $this->db()->transaction(function() use ($jobId, $job, $operatorId, $machineId, $now) {
            $this->db()->update('production_jobs', [
                'status' => 'in_progress',
                'assigned_user_id' => $operatorId,
                'machine_id' => $machineId,
                'started_at' => $now,
                'updated_at' => $now,
            ], 'id = :id', [':id' => $jobId]);

            // Record stage log
            $this->db()->insert('production_stage_logs', [
                'job_id' => $jobId,
                'stage_id' => $job['current_stage_id'],
                'user_id' => $operatorId,
                'machine_id' => $machineId,
                'status' => 'in_progress',
                'started_at' => $now,
                'created_at' => $now,
            ]);

            // Update order status to in_process
            $this->db()->update('orders', [
                'production_status' => 'in_process',
                'updated_at' => $now,
            ], 'id = :id', [':id' => $job['order_id']]);

            // If machine used, record machine usage and update machine operational status
            if ($machineId) {
                $this->db()->insert('production_machine_usage', [
                    'job_id' => $jobId,
                    'machine_id' => $machineId,
                    'user_id' => $operatorId,
                    'started_at' => $now,
                    'created_at' => $now,
                ]);
                $this->db()->update('machines', ['operational_status' => 'in_use'], 'id = :id', [':id' => $machineId]);
            }
        });

        $this->logActivity('start_stage', 'production_jobs', $jobId, "Memulai pengerjaan produksi job #{$jobId}");
        $this->back('Tahap pengerjaan berhasil dimulai.', 'success');
    }

    public function completeStage(): void
    {
        Permission::abortUnless('production.access');

        $jobId = (int)$_POST['job_id'];
        $notes = trim($_POST['notes'] ?? '');

        $job = $this->db()->fetch(
            "SELECT pj.*, ps.sort_order, ps.slug AS current_stage_slug, o.order_no, o.company_id, o.branch_id,
                    c.name AS customer_name, c.whatsapp AS customer_phone, b.name AS branch_name
             FROM production_jobs pj
             JOIN production_stages ps ON pj.current_stage_id = ps.id
             JOIN orders o ON pj.order_id = o.id
             JOIN customers c ON o.customer_id = c.id
             JOIN branches b ON o.branch_id = b.id
             WHERE pj.id = :id",
            [':id' => $jobId]
        );

        if (!$job) {
            $this->back('Pekerjaan produksi tidak ditemukan.', 'error');
        }

        // Find next stage
        $nextStage = $this->db()->fetch(
            "SELECT * FROM production_stages WHERE sort_order > :sort ORDER BY sort_order ASC LIMIT 1",
            [':sort' => $job['sort_order']]
        );

        $now = date('Y-m-d H:i:s');
        $this->db()->transaction(function() use ($jobId, $job, $nextStage, $notes, $now) {
            // Close active stage log
            $activeLog = $this->db()->fetch(
                "SELECT id FROM production_stage_logs WHERE job_id = :jid AND stage_id = :sid AND completed_at IS NULL ORDER BY id DESC LIMIT 1",
                [':jid' => $jobId, ':sid' => $job['current_stage_id']]
            );

            if ($activeLog) {
                $this->db()->update('production_stage_logs', [
                    'status' => 'completed',
                    'completed_at' => $now,
                    'notes' => $notes ?: null,
                ], 'id = :id', [':id' => $activeLog['id']]);
            }

            // Close machine usage if active
            if ($job['machine_id']) {
                $this->db()->getConnection()->exec("
                    UPDATE production_machine_usage 
                    SET finished_at = '{$now}', duration_minutes = CAST((strftime('%s', '{$now}') - strftime('%s', started_at)) / 60 AS INTEGER)
                    WHERE job_id = {$jobId} AND machine_id = {$job['machine_id']} AND finished_at IS NULL;
                ");
                $this->db()->update('machines', [
                    'operational_status' => 'idle',
                    'total_cycles' => (int)$this->db()->fetchColumn("SELECT total_cycles FROM machines WHERE id = :id", [':id' => $job['machine_id']]) + 1
                ], 'id = :id', [':id' => $job['machine_id']]);
            }

            if ($nextStage) {
                // Advance to next stage
                $nextStatus = ($nextStage['slug'] === 'ready') ? 'completed' : 'waiting';
                $orderStatus = 'in_process';
                if ($nextStage['slug'] === 'qc') $orderStatus = 'qc';
                elseif ($nextStage['slug'] === 'packaging') $orderStatus = 'packaging';
                elseif ($nextStage['slug'] === 'ready') $orderStatus = 'ready';

                $this->db()->update('production_jobs', [
                    'current_stage_id' => $nextStage['id'],
                    'status' => $nextStatus,
                    'machine_id' => null, // reset machine for next phase
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $jobId]);

                $this->db()->update('orders', [
                    'production_status' => $orderStatus,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $job['order_id']]);

                $this->db()->insert('order_status_history', [
                    'order_id' => $job['order_id'],
                    'from_status' => $job['current_stage_slug'],
                    'to_status' => $orderStatus,
                    'notes' => "Lanjut ke tahap: {$nextStage['name']}",
                    'changed_by_user_id' => Auth::id(),
                    'created_at' => $now,
                ]);

                // If ready, enqueue WhatsApp notification to customer
                if ($nextStage['slug'] === 'ready') {
                    $wa = new WhatsAppAdapter();
                    $orderData = $this->db()->fetch("SELECT * FROM orders WHERE id = :id", [':id' => $job['order_id']]);
                    $wa->enqueue(
                        $job['company_id'],
                        $job['branch_id'],
                        $job['customer_phone'],
                        $job['customer_name'],
                        'order_ready',
                        [
                            'customer_name' => $job['customer_name'],
                            'order_no' => $job['order_no'],
                            'branch_name' => $job['branch_name'],
                            'balance_due' => format_rupiah($orderData['balance_due']),
                        ],
                        'orders',
                        $job['order_id']
                    );
                }
            } else {
                // Completed completely
                $this->db()->update('production_jobs', [
                    'status' => 'completed',
                    'completed_at' => $now,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $jobId]);

                $this->db()->update('orders', [
                    'production_status' => 'ready',
                    'actual_completion_at' => $now,
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $job['order_id']]);
            }
        });

        $this->logActivity('complete_stage', 'production_jobs', $jobId, "Menyelesaikan tahap pengerjaan untuk pesanan {$job['order_no']}");
        $this->back('Tahap produksi berhasil diselesaikan!', 'success');
    }

    public function qcCheck(): void
    {
        Permission::abortUnless('production.access');

        $jobId = (int)$_POST['job_id'];
        $cleanliness = (int)($_POST['cleanliness_score'] ?? 5);
        $fragrance = (int)($_POST['fragrance_score'] ?? 5);
        $neatness = (int)($_POST['neatness_score'] ?? 5);
        $completeness = (int)($_POST['completeness_score'] ?? 5);
        $isPassed = isset($_POST['is_passed']) ? 1 : 0;
        $defectNotes = trim($_POST['defect_notes'] ?? '');

        $job = $this->db()->fetch("SELECT * FROM production_jobs WHERE id = :id", [':id' => $jobId]);
        if (!$job) {
            $this->back('Pekerjaan produksi tidak ditemukan.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $qcId = $this->db()->insert('quality_checks', [
            'order_id' => $job['order_id'],
            'job_id' => $jobId,
            'checked_by_user_id' => Auth::id(),
            'cleanliness_score' => $cleanliness,
            'fragrance_score' => $fragrance,
            'neatness_score' => $neatness,
            'completeness_score' => $completeness,
            'is_passed' => $isPassed,
            'defect_notes' => $defectNotes ?: null,
            'checked_at' => $now,
        ]);

        if ($isPassed) {
            // Auto complete QC stage and move to packaging
            $this->completeStage();
        } else {
            $this->db()->update('production_jobs', [
                'status' => 'hold',
                'notes' => "QC Gagal: {$defectNotes}",
                'updated_at' => $now,
            ], 'id = :id', [':id' => $jobId]);
            $this->back('Pemeriksaan QC dicatat sebagai Gagal. Cucian ditahan untuk perbaikan.', 'warning');
        }
    }
}
