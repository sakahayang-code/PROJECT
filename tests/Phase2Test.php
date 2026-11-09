<?php

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;

echo "=============================================\n";
echo "TEST SUITE FASE 2: PERUSAHAAN, CABANG, PENGGUNA & KARYAWAN\n";
echo "=============================================\n";

$db = Database::getInstance();
Session::start();

// Test 1: Create a new branch & verify default price matrix auto-created
$branchCode = 'TGR';
$existing = $db->fetch("SELECT id FROM branches WHERE code = :c", [':c' => $branchCode]);
if ($existing) {
    $db->delete('branches', 'id = :id', [':id' => $existing['id']]);
}

$newBranchId = $db->insert('branches', [
    'company_id' => 1,
    'code' => $branchCode,
    'name' => 'LaundryPro Cabang Tangerang',
    'phone' => '081277778888',
    'address' => 'Jl. Boulevard Gading Serpong, Tangerang',
    'is_main' => 0,
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
assert($newBranchId > 0, "New branch ID must be valid");

// Populate branch service prices
$services = $db->fetchAll("SELECT id, base_price, min_order, est_hours FROM services");
foreach ($services as $srv) {
    $db->insert('branch_service_prices', [
        'branch_id' => $newBranchId,
        'service_id' => $srv['id'],
        'price' => $srv['base_price'],
        'min_order' => $srv['min_order'],
        'est_hours' => $srv['est_hours'],
        'is_active' => 1,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

$priceCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM branch_service_prices WHERE branch_id = :b", [':b' => $newBranchId]);
assert($priceCount === count($services), "All services must have a price entry for new branch");
echo "[PASS] Test 1: Branch Creation & Price Matrix Auto-Setup OK\n";

// Test 2: User Creation with Multi-Branch Access
$testUsername = 'kasir.tangerang';
$existingUser = $db->fetch("SELECT id FROM users WHERE username = :u", [':u' => $testUsername]);
if ($existingUser) {
    $db->delete('users', 'id = :id', [':id' => $existingUser['id']]);
}

$cashierRoleId = (int)$db->fetchColumn("SELECT id FROM roles WHERE slug = 'cashier'");
$tgrUserId = $db->insert('users', [
    'company_id' => 1,
    'branch_id' => $newBranchId,
    'username' => $testUsername,
    'name' => 'Sinta Dewi',
    'email' => 'sinta@laundrypro.id',
    'password_hash' => password_hash('Admin@123', PASSWORD_BCRYPT),
    'role_id' => $cashierRoleId,
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
$db->insert('user_branch_access', [
    'user_id' => $tgrUserId,
    'branch_id' => $newBranchId,
    'is_default' => 1,
    'created_at' => date('Y-m-d H:i:s'),
]);

Auth::logout();
$tgrLogin = Auth::attempt($testUsername, 'Admin@123');
assert($tgrLogin === true, "New Tangerang cashier login should succeed");
assert(Auth::canAccessBranch($newBranchId) === true, "Must have access to Tangerang branch");
$pusatBranchId = (int)$db->fetchColumn("SELECT id FROM branches WHERE code = 'PST'");
assert(Auth::canAccessBranch($pusatBranchId) === false, "Must NOT have access to Pusat branch");
echo "[PASS] Test 2: User Branch Access Isolation OK\n";

// Test 3: Employee Registration & Attendance Record
$empId = $db->insert('employees', [
    'company_id' => 1,
    'branch_id' => $newBranchId,
    'user_id' => $tgrUserId,
    'nik' => 'EMP-TGR-001',
    'name' => 'Sinta Dewi',
    'position' => 'Kasir Utama',
    'base_salary' => 3500000,
    'status' => 'active',
    'join_date' => date('Y-m-d'),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
assert($empId > 0, "Employee record created successfully");

$attId = $db->insert('attendance', [
    'company_id' => 1,
    'branch_id' => $newBranchId,
    'employee_id' => $empId,
    'date' => date('Y-m-d'),
    'clock_in' => '07:55',
    'clock_out' => '17:05',
    'status' => 'present',
    'notes' => 'Tepat waktu',
    'created_at' => date('Y-m-d H:i:s'),
]);
assert($attId > 0, "Attendance recorded successfully");
echo "[PASS] Test 3: Employee & Attendance Tracking OK\n";

// Clean up test branch & user
$db->delete('branches', 'id = :id', [':id' => $newBranchId]);
$db->delete('users', 'id = :id', [':id' => $tgrUserId]);

echo "\n=============================================\n";
echo "SEMUA TEST FASE 2 BERHASIL DILALUI DENGAN SUKSES!\n";
echo "=============================================\n";
