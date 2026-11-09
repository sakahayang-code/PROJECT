<?php

namespace App\Modules\Employees;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;

class EmployeeController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('view');

        $activeBranchId = Auth::getActiveBranchId();
        $where = "1=1";
        $params = [];
        if ($activeBranchId) {
            $where .= " AND e.branch_id = :b";
            $params[':b'] = $activeBranchId;
        }

        $employees = $this->db()->fetchAll(
            "SELECT e.*, b.name AS branch_name, u.username
             FROM employees e
             JOIN branches b ON e.branch_id = b.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE {$where}
             ORDER BY e.id DESC",
            $params
        );

        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1");
        $users = $this->db()->fetchAll("SELECT id, username, name FROM users WHERE is_active = 1");

        $this->view('employees.index', [
            'title' => 'Data Karyawan & SDM',
            'activeMenu' => 'employees',
            'employees' => $employees,
            'branches' => $branches,
            'users' => $users,
        ]);
    }

    public function store(): void
    {
        Permission::abortUnless('create');

        $name = trim($_POST['name'] ?? '');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $position = trim($_POST['position'] ?? 'Staf Operasional');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nik = trim($_POST['nik'] ?? '');
        $baseSalary = (int)str_replace(['.', ','], '', $_POST['base_salary'] ?? '0');
        $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

        if (empty($name) || empty($branchId)) {
            $this->back('Nama karyawan dan cabang wajib diisi.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $empId = $this->db()->insert('employees', [
            'company_id' => $this->companyId(),
            'branch_id' => $branchId,
            'user_id' => $userId,
            'nik' => $nik,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'position' => $position,
            'base_salary' => $baseSalary,
            'status' => 'active',
            'join_date' => date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->logActivity('create', 'employees', $empId, "Menambahkan karyawan baru: {$name} ({$position})");
        $this->redirect(url('/employees'), 'Karyawan baru berhasil ditambahkan!');
    }

    public function update(int|string $id): void
    {
        Permission::abortUnless('edit');

        $id = (int)$id;
        $name = trim($_POST['name'] ?? '');
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $position = trim($_POST['position'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $baseSalary = (int)str_replace(['.', ','], '', $_POST['base_salary'] ?? '0');

        $this->db()->update('employees', [
            'name' => $name,
            'branch_id' => $branchId,
            'position' => $position,
            'phone' => $phone,
            'base_salary' => $baseSalary,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);

        $this->logActivity('update', 'employees', $id, "Memperbarui data karyawan ID {$id}: {$name}");
        $this->redirect(url('/employees'), 'Data karyawan berhasil diperbarui!');
    }

    public function shifts(): void
    {
        $shifts = $this->db()->fetchAll(
            "SELECT s.*, b.name as branch_name 
             FROM employee_shifts s 
             JOIN branches b ON s.branch_id = b.id 
             ORDER BY s.id ASC"
        );
        $branches = $this->db()->fetchAll("SELECT * FROM branches WHERE is_active = 1");

        $this->view('employees.shifts', [
            'title' => 'Shift Kerja Karyawan',
            'activeMenu' => 'employees',
            'shifts' => $shifts,
            'branches' => $branches,
        ]);
    }

    public function storeShift(): void
    {
        Permission::abortUnless('create');

        $branchId = (int)$_POST['branch_id'];
        $name = trim($_POST['name']);
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];

        $this->db()->insert('employee_shifts', [
            'branch_id' => $branchId,
            'name' => $name,
            'start_time' => $start,
            'end_time' => $end,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->redirect(url('/employees/shifts'), 'Jadwal shift berhasil ditambahkan!');
    }

    public function attendance(): void
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        $branchId = Auth::getActiveBranchId() ?? (int)($_GET['branch_id'] ?? 0);

        $where = "a.date = :d";
        $params = [':d' => $date];
        if ($branchId) {
            $where .= " AND a.branch_id = :b";
            $params[':b'] = $branchId;
        }

        $records = $this->db()->fetchAll(
            "SELECT a.*, e.name as employee_name, e.position, b.name as branch_name
             FROM attendance a
             JOIN employees e ON a.employee_id = e.id
             JOIN branches b ON a.branch_id = b.id
             WHERE {$where}
             ORDER BY a.created_at DESC",
            $params
        );

        $employees = $this->db()->fetchAll(
            "SELECT id, name, position, branch_id FROM employees WHERE status = 'active'"
        );
        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $this->view('employees.attendance', [
            'title' => 'Rekap Absensi Karyawan',
            'activeMenu' => 'employees',
            'records' => $records,
            'selectedDate' => $date,
            'selectedBranch' => $branchId,
            'employees' => $employees,
            'branches' => $branches,
        ]);
    }

    public function storeAttendance(): void
    {
        Permission::abortUnless('create');

        $employeeId = (int)$_POST['employee_id'];
        $status = $_POST['status'] ?? 'present';
        $clockIn = $_POST['clock_in'] ?? date('H:i');
        $clockOut = !empty($_POST['clock_out']) ? $_POST['clock_out'] : null;
        $date = $_POST['date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');

        $emp = $this->db()->fetch("SELECT company_id, branch_id FROM employees WHERE id = :id", [':id' => $employeeId]);
        if (!$emp) {
            $this->back('Karyawan tidak ditemukan.', 'error');
        }

        // Check if attendance already recorded today
        $existing = $this->db()->fetch(
            "SELECT id FROM attendance WHERE employee_id = :e AND date = :d",
            [':e' => $employeeId, ':d' => $date]
        );

        if ($existing) {
            $this->db()->update('attendance', [
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => $status,
                'notes' => $notes,
            ], 'id = :id', [':id' => $existing['id']]);
        } else {
            $this->db()->insert('attendance', [
                'company_id' => $emp['company_id'],
                'branch_id' => $emp['branch_id'],
                'employee_id' => $employeeId,
                'date' => $date,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => $status,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->redirect(url('/employees/attendance?date=' . $date), 'Data absensi berhasil dicatat!');
    }
}
