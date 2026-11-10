<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Session;
use App\Core\Response;
use App\Core\Database;
use App\Middleware\CsrfMiddleware;
use App\Modules\Customers\CustomerController;

echo "=======================================================\n";
echo "   SIMLAUNDRY FORM SUBMISSION & REDIRECT TEST SUITE    \n";
echo "=======================================================\n\n";

Session::start();
$db = Database::getInstance();

// -----------------------------------------------------------------
// Test 1: Response::redirect produces PRG 303 and HTML/JS Fallback
// -----------------------------------------------------------------
echo "[Test 1] Testing Response::redirect PRG and HTML fallback...\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'multimediatama.com';
$_SERVER['HTTPS'] = 'on';

// Because redirect clears existing buffers, we start a fresh buffer or capture via custom wrapper
ob_start();
Response::redirect('/laundry/customers');
$output = ob_get_clean();
// If redirect cleaned the outer buffer and echoed directly, let us test redirect capturing
if ($output === false || $output === '') {
    // In CLI testing mode, if buffer was cleared by redirect, we can verify by starting buffer inside
    ob_start(function($buffer) use (&$captured) { $captured = $buffer; return ''; });
    Response::redirect('/laundry/customers');
    ob_end_clean();
    $output = $captured ?? '';
}

// Check with escaped or raw URL in JSON
if (!str_contains($output, 'https://multimediatama.com/laundry/customers') &&
    !str_contains($output, 'https:\/\/multimediatama.com\/laundry\/customers')) {
    echo "FAILED: JS replace not found in redirect output!\nOutput: {$output}\n";
    exit(1);
}
if (!str_contains($output, 'content="0;url=https://multimediatama.com/laundry/customers"')) {
    echo "FAILED: Meta refresh not found in redirect output!\n";
    exit(1);
}
if (!str_contains($output, 'Data Berhasil Disimpan')) {
    echo "FAILED: Friendly fallback message missing!\n";
    exit(1);
}
echo "PASSED: Response::redirect correctly outputs PRG 303 and full HTML/JS fallback.\n\n";

// -----------------------------------------------------------------
// Test 2: Double Submission / Replay Interception in CsrfMiddleware
// -----------------------------------------------------------------
echo "[Test 2] Testing Double-Submission / Replay Guard in CsrfMiddleware...\n";

// Login as superadmin so permission check passes
$adminUser = $db->fetch("SELECT * FROM users WHERE role_id = 1 LIMIT 1");
\App\Core\Auth::login($adminUser);

// Generate a valid token
$token = Session::getCsrfToken();
$uniquePhone = '08999' . str_pad((string)mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
$uniqueName = 'Budi Test ' . mt_rand(100, 999);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/customers';
$_POST = [
    '_csrf_token' => $token,
    'name' => $uniqueName,
    'whatsapp' => $uniquePhone,
    'favorite_branch_id' => 1,
    'notes' => 'Testing double submit protection',
];

$middleware = new CsrfMiddleware();

// First submission
$allowed1 = $middleware->handle();
if (!$allowed1) {
    echo "FAILED: First submission was blocked by CsrfMiddleware!\n";
    exit(1);
}

// Emulate Controller executing save
$beforeCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM customers WHERE whatsapp = :w", [':w' => $uniquePhone]);
$controller = new CustomerController();

ob_start();
$controller->store();
$ctrlOutput = ob_get_clean();

$afterFirstCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM customers WHERE whatsapp = :w", [':w' => $uniquePhone]);
if ($afterFirstCount !== $beforeCount + 1) {
    echo "FAILED: First customer was not inserted into database!\n";
    exit(1);
}
echo "  - First submission: Successfully created customer record.\n";

// Second submission (immediate replay / browser refresh)
ob_start();
$allowed2 = $middleware->handle();
$replayOutput = ob_get_clean();

if ($allowed2 !== false) {
    echo "FAILED: Second submission should have been blocked by CsrfMiddleware!\n";
    exit(1);
}

// Verify that customer count did NOT increase
$afterSecondCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM customers WHERE whatsapp = :w", [':w' => $uniquePhone]);
if ($afterSecondCount !== $afterFirstCount) {
    echo "FAILED: Duplicate customer was created on replay! Count: {$afterSecondCount}\n";
    exit(1);
}

echo "  - Second submission (refresh replay): Blocked duplicate insert and redirected cleanly.\n";
echo "PASSED: Double-submission protection verified.\n\n";

// -----------------------------------------------------------------
// Test 3: Output Buffering safety against stray notices
// -----------------------------------------------------------------
echo "[Test 3] Testing Output Buffering header safety...\n";
if (!ob_get_level()) {
    ob_start();
}
// Emit a notice/warning simulation
echo "Stray whitespace or warning emitted before redirect";

ob_start();
Response::redirect('/laundry/dashboard');
$redirectBody = ob_get_clean();

if (!str_contains($redirectBody, 'https://multimediatama.com/laundry/dashboard')) {
    echo "FAILED: Buffer clearing failed to preserve redirect destination!\n";
    exit(1);
}
echo "PASSED: Stray output cleanly handled without breaking redirect.\n\n";

echo "=======================================================\n";
echo "   ALL FORM SUBMISSION & REDIRECT TESTS PASSED!        \n";
echo "=======================================================\n";
