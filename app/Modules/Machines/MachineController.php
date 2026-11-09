<?php

declare(strict_types=1);

namespace App\Modules\Machines;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Core\Validator;

class MachineController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('machines.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $typeFilter = (int)($_GET['type_id'] ?? 0);
        $statusFilter = trim($_GET['status'] ?? '');

        $where = "1=1";
        $params = [];

        if ($branchFilter > 0) {
            $where .= " AND m.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND m.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        if ($typeFilter > 0) {
            $where .= " AND m.type_id = :t";
            $params[':t'] = $typeFilter;
        }

        if (!empty($statusFilter)) {
            $where .= " AND m.operational_status = :st";
            $params[':st'] = $statusFilter;
        }

        $machines = $this->db()->fetchAll(
            "SELECT m.*, b.name AS branch_name, mt.name AS type_name
             FROM machines m
             JOIN branches b ON m.branch_id = b.id
             JOIN machine_types mt ON m.type_id = mt.id
             WHERE {$where}
             ORDER BY b.id ASC, m.operational_status DESC, m.name ASC",
            $params
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $types = $this->db()->fetchAll("SELECT id, name FROM machine_types ORDER BY name ASC");

        $stats = [
            'total' => count($machines),
            'idle' => count(array_filter($machines, fn($m) => $m['operational_status'] === 'idle')),
            'in_use' => count(array_filter($machines, fn($m) => $m['operational_status'] === 'in_use')),
            'maintenance' => count(array_filter($machines, fn($m) => $m['operational_status'] === 'maintenance' || $m['condition'] !== 'good')),
        ];

        $this->view('machines.index', [
            'title' => 'Manajemen Mesin & Peralatan',
            'activeMenu' => 'machines_list',
            'machines' => $machines,
            'branches' => $branches,
            'types' => $types,
            'stats' => $stats,
            'selectedBranch' => $branchFilter,
            'selectedType' => $typeFilter,
            'selectedStatus' => $statusFilter,
        ]);
    }

    public function storeMachine(): void
    {
        Permission::abortUnless('machines.edit');

        $v = Validator::make($_POST, [
            'name' => 'required',
            'branch_id' => 'required',
            'type_id' => 'required',
            'capacity_kg' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (empty($code)) {
            $code = 'MCH-' . strtoupper(substr(uniqid(), -6));
        }

        $branchId = (int)$_POST['branch_id'];
        $typeId = (int)$_POST['type_id'];
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $capacity = (float)$_POST['capacity_kg'];
        $serialNumber = trim($_POST['serial_number'] ?? '');
        $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
        $companyId = Auth::companyId() ?: 1;
        $now = date('Y-m-d H:i:s');

        $this->db()->insert('machines', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'type_id' => $typeId,
            'code' => $code,
            'name' => $name,
            'brand' => $brand ?: null,
            'model' => $model ?: null,
            'serial_number' => $serialNumber ?: null,
            'capacity_kg' => $capacity,
            'purchase_date' => $purchaseDate,
            'condition' => 'good',
            'operational_status' => 'idle',
            'total_cycles' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->back("Mesin {$name} ({$code}) berhasil ditambahkan.", 'success');
    }

    public function maintenance(): void
    {
        Permission::abortUnless('machines.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));

        $where = "1=1";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND mm.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $maintenances = $this->db()->fetchAll(
            "SELECT mm.*, m.name AS machine_name, m.code AS machine_code, b.name AS branch_name
             FROM machine_maintenance mm
             JOIN machines m ON mm.machine_id = m.id
             JOIN branches b ON mm.branch_id = b.id
             WHERE {$where}
             ORDER BY mm.scheduled_date DESC, mm.id DESC",
            $params
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");
        $machines = $this->db()->fetchAll("SELECT id, code, name, branch_id FROM machines ORDER BY name ASC");

        $this->view('machines.maintenance', [
            'title' => 'Pemeliharaan & Servis Mesin',
            'activeMenu' => 'machines_maintenance',
            'maintenances' => $maintenances,
            'branches' => $branches,
            'machines' => $machines,
            'selectedBranch' => $branchFilter,
        ]);
    }

    public function storeMaintenance(): void
    {
        Permission::abortUnless('machines.edit');

        $v = Validator::make($_POST, [
            'machine_id' => 'required',
            'title' => 'required',
            'scheduled_date' => 'required',
        ]);

        if ($v->fails()) {
            $this->back($v->firstError(), 'error');
        }

        $machineId = (int)$_POST['machine_id'];
        $machine = $this->db()->fetch("SELECT * FROM machines WHERE id = :id", [':id' => $machineId]);
        if (!$machine) {
            $this->back('Mesin tidak ditemukan.', 'error');
        }

        $title = trim($_POST['title']);
        $type = in_array($_POST['maintenance_type'], ['routine', 'repair']) ? $_POST['maintenance_type'] : 'routine';
        $scheduledDate = $_POST['scheduled_date'];
        $cost = (int)str_replace(['.', ','], '', $_POST['cost'] ?? '0');
        $technician = trim($_POST['technician_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $now = date('Y-m-d H:i:s');

        $this->db()->transaction(function() use ($machine, $machineId, $title, $type, $scheduledDate, $cost, $technician, $notes, $now) {
            $this->db()->insert('machine_maintenance', [
                'machine_id' => $machineId,
                'branch_id' => $machine['branch_id'],
                'title' => $title,
                'maintenance_type' => $type,
                'scheduled_date' => $scheduledDate,
                'cost' => $cost,
                'technician_name' => $technician ?: null,
                'status' => 'scheduled',
                'notes' => $notes ?: null,
                'created_at' => $now,
            ]);

            // If it's a repair, mark machine status
            if ($type === 'repair') {
                $this->db()->update('machines', [
                    'operational_status' => 'maintenance',
                    'condition' => 'needs_repair',
                    'updated_at' => $now,
                ], 'id = :id', [':id' => $machineId]);
            }
        });

        $this->back("Jadwal pemeliharaan {$title} berhasil dibuat.", 'success');
    }

    public function completeMaintenance(): void
    {
        Permission::abortUnless('machines.edit');

        $maintId = (int)($_POST['maintenance_id'] ?? 0);
        $cost = (int)str_replace(['.', ','], '', $_POST['cost'] ?? '0');
        $actionTaken = trim($_POST['action_taken'] ?? 'Servis selesai');
        $partsReplaced = trim($_POST['parts_replaced'] ?? '');

        $maint = $this->db()->fetch("SELECT * FROM machine_maintenance WHERE id = :id", [':id' => $maintId]);
        if (!$maint) {
            $this->back('Data pemeliharaan tidak ditemukan.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->transaction(function() use ($maint, $maintId, $cost, $actionTaken, $partsReplaced, $now) {
            $this->db()->update('machine_maintenance', [
                'status' => 'completed',
                'completed_date' => date('Y-m-d'),
                'cost' => $cost,
                'notes' => $actionTaken,
            ], 'id = :id', [':id' => $maintId]);

            $this->db()->insert('machine_maintenance_logs', [
                'maintenance_id' => $maintId,
                'machine_id' => $maint['machine_id'],
                'action_taken' => $actionTaken,
                'parts_replaced' => $partsReplaced ?: null,
                'notes' => 'Diselesaikan pada ' . date('d M Y H:i'),
                'created_at' => $now,
            ]);

            // Restore machine to good and idle
            $this->db()->update('machines', [
                'operational_status' => 'idle',
                'condition' => 'good',
                'updated_at' => $now,
            ], 'id = :id', [':id' => $maint['machine_id']]);
        });

        $this->back('Pemeliharaan mesin selesai dan kondisi mesin kembali siap pakai (Ready).', 'success');
    }

    public function reportFault(): void
    {
        $machineId = (int)($_POST['machine_id'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $severity = in_array($_POST['severity'], ['low', 'medium', 'high', 'critical']) ? $_POST['severity'] : 'medium';

        if ($machineId <= 0 || empty($desc)) {
            $this->back('Pilih mesin dan jelaskan kendala kerusakan.', 'error');
        }

        $machine = $this->db()->fetch("SELECT * FROM machines WHERE id = :id", [':id' => $machineId]);
        if (!$machine) {
            $this->back('Mesin tidak ditemukan.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->transaction(function() use ($machineId, $desc, $severity, $now) {
            $this->db()->insert('machine_faults', [
                'machine_id' => $machineId,
                'reported_by' => Auth::id(),
                'fault_date' => date('Y-m-d'),
                'description' => $desc,
                'severity' => $severity,
                'status' => 'open',
                'created_at' => $now,
            ]);

            $this->db()->update('machines', [
                'condition' => ($severity === 'critical') ? 'damaged' : 'needs_repair',
                'operational_status' => ($severity === 'critical') ? 'offline' : 'maintenance',
                'updated_at' => $now,
            ], 'id = :id', [':id' => $machineId]);
        });

        $this->back("Laporan kerusakan mesin telah dicatat.", 'success');
    }
}
