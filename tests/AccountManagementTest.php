<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\Database;
use App\Modules\Users\UserController;

echo "=======================================================\n";
echo "   SIMLAUNDRY ACCOUNT MANAGEMENT TEST SUITE            \n";
echo "=======================================================\n\n";

Session::start();
$db = Database::getInstance();

// Log in as Super Admin
$superAdmin = $db->fetch("SELECT * FROM users WHERE role_id = 1 LIMIT 1");
Auth::login($superAdmin);

$controller = new UserController();

// -----------------------------------------------------------------
// Test 1: Load Account Center View
// -----------------------------------------------------------------
echo "[Test 1] Testing Account Center Index...\n";
ob_start();
$controller->index();
$html = ob_get_clean();

if (!str_contains($html, 'Pusat Kelola Akun') || !str_contains($html, 'Akun Staf & Manajemen')) {
    echo "FAILED: Account Center view missing required headers!\n";
    exit(1);
}
echo "PASSED: Account Center view rendered successfully.\n\n";

// -----------------------------------------------------------------
// Test 2: Create New Staff Account
// -----------------------------------------------------------------
echo "[Test 2] Testing Staff Account Creation...\n";
$rand = mt_rand(1000, 9999);
$testUsername = 'kasir.test' . $rand;
$testEmail = "kasir.test{$rand}@simlaundry.id";

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'username' => $testUsername,
    'name' => 'Kasir Percobaan ' . $rand,
    'email' => $testEmail,
    'phone' => '0812' . $rand . '8899',
    'password' => 'StaffPass@' . $rand,
    'role_id' => 4, // Cashier
    'branch_id' => 1,
];

ob_start();
$controller->store();
ob_get_clean();

$createdUser = $db->fetch("SELECT * FROM users WHERE username = :u", [':u' => $testUsername]);
if (!$createdUser) {
    echo "FAILED: New staff user was not created in database!\n";
    exit(1);
}
if (!password_verify('StaffPass@' . $rand, $createdUser['password_hash'])) {
    echo "FAILED: Password hash verification failed for new staff user!\n";
    exit(1);
}
echo "PASSED: Staff user created with ID {$createdUser['id']} and valid bcrypt password.\n\n";

// -----------------------------------------------------------------
// Test 3: Reset Staff Password
// -----------------------------------------------------------------
echo "[Test 3] Testing Reset Staff Password...\n";
$newPassword = 'ResetAdminPass@999';
$_POST = ['new_password' => $newPassword];

ob_start();
$controller->resetPassword($createdUser['id']);
ob_get_clean();

$updatedUser = $db->fetch("SELECT * FROM users WHERE id = :id", [':id' => $createdUser['id']]);
if (!password_verify($newPassword, $updatedUser['password_hash'])) {
    echo "FAILED: Password reset did not update the password hash!\n";
    exit(1);
}
echo "PASSED: Staff password reset successfully verified.\n\n";

// -----------------------------------------------------------------
// Test 4: Customer Portal Account Management
// -----------------------------------------------------------------
echo "[Test 4] Testing Customer Portal Account Creation & Password Reset...\n";
// Pick or create a customer
$custPhone = '0877' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
$customerId = $db->insert('customers', [
    'company_id' => 1,
    'favorite_branch_id' => 1,
    'code' => 'CUST-T' . $rand,
    'name' => 'Pelanggan Uji ' . $rand,
    'whatsapp' => $custPhone,
    'join_date' => date('Y-m-d'),
    'status' => 'active',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

$_POST = [
    'customer_id' => $customerId,
    'username' => 'portal.cust' . $rand,
    'password' => 'CustPass@123',
];

ob_start();
$controller->createCustomerAccount();
ob_get_clean();

$custAcc = $db->fetch("SELECT * FROM customer_accounts WHERE customer_id = :cid", [':cid' => $customerId]);
if (!$custAcc) {
    echo "FAILED: Customer portal account was not created!\n";
    exit(1);
}
if (!password_verify('CustPass@123', $custAcc['password_hash'])) {
    echo "FAILED: Customer portal account password hash mismatch!\n";
    exit(1);
}

// Reset Customer Portal Password
$_POST = ['new_password' => 'NewCustPass@456'];
ob_start();
$controller->resetCustomerPassword($custAcc['id']);
ob_get_clean();

$updatedCustAcc = $db->fetch("SELECT * FROM customer_accounts WHERE id = :id", [':id' => $custAcc['id']]);
if (!password_verify('NewCustPass@456', $updatedCustAcc['password_hash'])) {
    echo "FAILED: Reset customer password failed!\n";
    exit(1);
}

// Toggle Customer Portal Account Status
ob_start();
$controller->toggleCustomerAccount($custAcc['id']);
ob_get_clean();

$toggledAcc = $db->fetch("SELECT is_active FROM customer_accounts WHERE id = :id", [':id' => $custAcc['id']]);
if ((int)$toggledAcc['is_active'] !== 0) {
    echo "FAILED: Customer account toggle to inactive failed!\n";
    exit(1);
}
echo "PASSED: Customer portal account created, password reset, and status toggled.\n\n";

// -----------------------------------------------------------------
// Test 5: Self-Deactivation & SuperAdmin Protection
// -----------------------------------------------------------------
echo "[Test 5] Testing Self-Deactivation and Superadmin Safeguards...\n";
// Attempt to delete own account
ob_start();
$controller->delete($superAdmin['id']);
ob_get_clean();

$checkSuper = $db->fetch("SELECT is_active FROM users WHERE id = :id", [':id' => $superAdmin['id']]);
if ((int)$checkSuper['is_active'] !== 1) {
    echo "FAILED: Self-deactivation safeguard failed!\n";
    exit(1);
}
echo "PASSED: Safeguards prevented self-deactivation and superadmin deletion.\n\n";

echo "=======================================================\n";
echo "   ALL ACCOUNT MANAGEMENT TESTS PASSED (100%)          \n";
echo "=======================================================\n";
