<?php

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;

echo "=============================================\n";
echo "TEST SUITE FASE 1: FONDASI & SISTEM CORE\n";
echo "=============================================\n";

$db = Database::getInstance();

// Test 1: Database connection & Pragmas
$journalMode = $db->fetchColumn("PRAGMA journal_mode");
$foreignKeys = (int)$db->fetchColumn("PRAGMA foreign_keys");
assert($journalMode === 'wal', "Journal mode must be WAL (got {$journalMode})");
assert($foreignKeys === 1, "Foreign keys must be enabled (got {$foreignKeys})");
echo "[PASS] Test 1: SQLite Connection, WAL mode, & Foreign Keys OK\n";

// Test 2: Authentication Attempt (Valid Super Admin)
Session::start();
$loginSuccess = Auth::attempt('superadmin', 'Admin@123');
assert($loginSuccess === true, "Super Admin login should succeed");
assert(Auth::check() === true, "Auth::check should return true");
$user = Auth::user();
assert($user['username'] === 'superadmin', "Logged in username should be superadmin");
assert($user['role_slug'] === 'super_admin', "Role should be super_admin");
assert(Auth::isSuperAdmin() === true, "isSuperAdmin() should be true");
assert(Auth::hasGlobalAccess() === true, "hasGlobalAccess() should be true");
echo "[PASS] Test 2: Super Admin Login & RBAC OK\n";

// Test 3: Authentication Attempt (Invalid Password)
Auth::logout();
assert(Auth::check() === false, "Auth::check should return false after logout");
$invalidLogin = Auth::attempt('superadmin', 'WrongPass!');
assert($invalidLogin === false, "Invalid password login should fail");
$loginLog = $db->fetch("SELECT * FROM login_logs WHERE username = 'superadmin' ORDER BY id DESC LIMIT 1");
assert($loginLog['status'] === 'failed', "Login log status should be failed");
echo "[PASS] Test 3: Failed Login Rejection & Audit Log OK\n";

// Test 4: Branch Scoping for Branch Cashier
$cashierLogin = Auth::attempt('kasir.pusat', 'Admin@123');
assert($cashierLogin === true, "Cashier login should succeed");
$cashierUser = Auth::user();
assert(Auth::isSuperAdmin() === false, "Cashier should not be superadmin");
assert(Auth::hasRole('cashier') === true, "Role should be cashier");
$pusatBranchId = (int)$db->fetchColumn("SELECT id FROM branches WHERE code = 'PST'");
$baratBranchId = (int)$db->fetchColumn("SELECT id FROM branches WHERE code = 'BRT'");
assert(Auth::canAccessBranch($pusatBranchId) === true, "Cashier should access branch Pusat");
assert(Auth::canAccessBranch($baratBranchId) === false, "Cashier should NOT access branch Barat");
echo "[PASS] Test 4: Server-Side Branch Scoping Enforcement OK\n";

// Test 5: CSRF Token Handling
$csrfToken = Session::getCsrfToken();
assert(!empty($csrfToken), "CSRF token must not be empty");
assert(Session::validateCsrfToken($csrfToken) === true, "Valid CSRF token should pass");
assert(Session::validateCsrfToken('invalid_token') === false, "Invalid CSRF token must fail");
echo "[PASS] Test 5: CSRF Token Generation & Validation OK\n";

// Test 6: Validator Engine
$vPass = Validator::make(['email' => 'test@laundrypro.id', 'amount' => 50000], [
    'email' => 'required|email',
    'amount' => 'required|numeric|min:1000'
]);
assert($vPass->passes() === true, "Validator should pass valid data");

$vFail = Validator::make(['email' => 'invalid-email', 'amount' => 500], [
    'email' => 'required|email',
    'amount' => 'required|numeric|min:1000'
]);
assert($vFail->fails() === true, "Validator should fail invalid data");
echo "[PASS] Test 6: Validator Engine Rules OK\n";

// Test 7: VACUUM INTO Backup
$backupPath = dirname(__DIR__) . '/storage/backups/test_vacuum.sqlite';
if (file_exists($backupPath)) unlink($backupPath);
$backupResult = $db->backup($backupPath);
assert($backupResult === true, "Database backup via VACUUM INTO should succeed");
assert(file_exists($backupPath), "Backup file must exist on disk");
unlink($backupPath);
echo "[PASS] Test 7: SQLite Safe VACUUM INTO Backup OK\n";

echo "\n=============================================\n";
echo "SEMUA TEST FASE 1 BERHASIL DILALUI DENGAN SUKSES!\n";
echo "=============================================\n";
