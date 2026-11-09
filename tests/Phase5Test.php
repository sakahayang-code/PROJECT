<?php

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Session;
use App\Helpers\NumberGenerator;

echo "=============================================\n";
echo "TEST SUITE FASE 5: PRODUKSI & QUALITY CONTROL\n";
echo "=============================================\n";

$db = Database::getInstance();
Session::start();

// Setup test order & job
$orderNo = NumberGenerator::generateOrderNumber(1, 'PST');
$trackingToken = NumberGenerator::generateTrackingToken();
$now = date('Y-m-d H:i:s');

$orderId = $db->insert('orders', [
    'company_id' => 1,
    'branch_id' => 1,
    'customer_id' => 1,
    'user_id' => 1,
    'order_no' => $orderNo,
    'tracking_token' => $trackingToken,
    'order_type' => 'regular',
    'est_completion_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
    'subtotal' => 50000,
    'grand_total' => 50000,
    'production_status' => 'received',
    'created_at' => $now,
    'updated_at' => $now,
]);

$stage1 = $db->fetch("SELECT * FROM production_stages WHERE slug = 'waiting'");
$stage2 = $db->fetch("SELECT * FROM production_stages WHERE slug = 'washing'");

$jobId = $db->insert('production_jobs', [
    'order_id' => $orderId,
    'current_stage_id' => $stage1['id'],
    'status' => 'waiting',
    'created_at' => $now,
    'updated_at' => $now,
]);

// Test 1: Assign Operator & Machine to Job
$machine = $db->fetch("SELECT * FROM machines WHERE branch_id = 1 LIMIT 1");
$operator = $db->fetch("SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = 'production') LIMIT 1");

$db->update('production_jobs', [
    'assigned_user_id' => $operator['id'],
    'machine_id' => $machine['id'],
], 'id = :id', [':id' => $jobId]);

$jobAfterAssign = $db->fetch("SELECT * FROM production_jobs WHERE id = :id", [':id' => $jobId]);
assert((int)$jobAfterAssign['assigned_user_id'] === (int)$operator['id'], "Operator should be assigned");
assert((int)$jobAfterAssign['machine_id'] === (int)$machine['id'], "Machine should be assigned");
echo "[PASS] Test 1: Operator & Machine Assignment OK\n";

// Test 2: Start Stage (Waiting -> Washing) & Machine Operational Status Change
$initialCycles = (int)$machine['total_cycles'];

$db->transaction(function() use ($db, $jobId, $stage2, $operator, $machine, $orderId, $now) {
    $db->update('production_jobs', [
        'current_stage_id' => $stage2['id'],
        'status' => 'in_progress',
        'started_at' => $now,
    ], 'id = :id', [':id' => $jobId]);

    $db->update('orders', ['production_status' => 'in_process'], 'id = :id', [':id' => $orderId]);

    // Record stage log
    $db->insert('production_stage_logs', [
        'job_id' => $jobId,
        'stage_id' => $stage2['id'],
        'user_id' => $operator['id'],
        'machine_id' => $machine['id'],
        'status' => 'in_progress',
        'started_at' => $now,
        'created_at' => $now,
    ]);

    // Machine usage & machine in_use
    $db->insert('production_machine_usage', [
        'job_id' => $jobId,
        'machine_id' => $machine['id'],
        'user_id' => $operator['id'],
        'started_at' => $now,
        'created_at' => $now,
    ]);
    $db->update('machines', ['operational_status' => 'in_use'], 'id = :id', [':id' => $machine['id']]);
});

$mcCheck = $db->fetch("SELECT operational_status FROM machines WHERE id = :id", [':id' => $machine['id']]);
assert($mcCheck['operational_status'] === 'in_use', "Machine operational status must be in_use");
$orderCheck = $db->fetch("SELECT production_status FROM orders WHERE id = :id", [':id' => $orderId]);
assert($orderCheck['production_status'] === 'in_process', "Order production_status must be in_process");
echo "[PASS] Test 2: Stage Start & Machine Status Tracking (in_use) OK\n";

// Test 3: Complete Washing Stage & Free Machine
$completedAt = date('Y-m-d H:i:s', strtotime('+45 minutes'));
$db->transaction(function() use ($db, $jobId, $machine, $completedAt, $initialCycles) {
    // Close stage log
    $db->update('production_stage_logs', [
        'status' => 'completed',
        'completed_at' => $completedAt,
    ], 'job_id = :jid AND completed_at IS NULL', [':jid' => $jobId]);

    // Close machine usage
    $db->update('production_machine_usage', [
        'finished_at' => $completedAt,
        'duration_minutes' => 45,
    ], 'job_id = :jid AND finished_at IS NULL', [':jid' => $jobId]);

    $db->update('machines', [
        'operational_status' => 'idle',
        'total_cycles' => $initialCycles + 1,
    ], 'id = :id', [':id' => $machine['id']]);
});

$mcCheckIdle = $db->fetch("SELECT operational_status, total_cycles FROM machines WHERE id = :id", [':id' => $machine['id']]);
assert($mcCheckIdle['operational_status'] === 'idle', "Machine operational status must return to idle");
assert((int)$mcCheckIdle['total_cycles'] === $initialCycles + 1, "Machine cycle count must increment by 1");
echo "[PASS] Test 3: Stage Completion, Machine Free (idle) & Cycle Increment OK\n";

// Test 4: Quality Control (QC) Inspection Check
$qcStage = $db->fetch("SELECT * FROM production_stages WHERE slug = 'qc'");
$readyStage = $db->fetch("SELECT * FROM production_stages WHERE slug = 'ready'");

$db->update('production_jobs', ['current_stage_id' => $qcStage['id']], 'id = :id', [':id' => $jobId]);

$qcId = $db->insert('quality_checks', [
    'order_id' => $orderId,
    'job_id' => $jobId,
    'checked_by_user_id' => $operator['id'],
    'cleanliness_score' => 5,
    'fragrance_score' => 5,
    'neatness_score' => 5,
    'completeness_score' => 5,
    'is_passed' => 1,
    'checked_at' => date('Y-m-d H:i:s'),
]);
assert($qcId > 0, "QC inspection record must be saved");

// Advance to Ready stage
$db->update('production_jobs', ['current_stage_id' => $readyStage['id'], 'status' => 'completed'], 'id = :id', [':id' => $jobId]);
$db->update('orders', ['production_status' => 'ready'], 'id = :id', [':id' => $orderId]);

$orderFinal = $db->fetch("SELECT production_status FROM orders WHERE id = :id", [':id' => $orderId]);
assert($orderFinal['production_status'] === 'ready', "Final status must be ready for pickup");
echo "[PASS] Test 4: Quality Control Inspection & Final Ready Status OK\n";

echo "\n=============================================\n";
echo "SEMUA TEST FASE 5 BERHASIL DILALUI DENGAN SUKSES!\n";
echo "=============================================\n";
