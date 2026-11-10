<?php

declare(strict_types=1);

/**
 * SimLaundry Automated Scheduled Backup CLI & Web Cron Runner
 * 
 * Usage via CLI (cPanel Cron):
 *   php /home/mulw1713/public_html/laundry/cron/backup.php
 * 
 * Usage via Webhook:
 *   https://multimediatama.com/laundry/cron/backup.php?token=laundrypro_secret_deploy_2026
 */

require_once dirname(__DIR__) . '/app/autoload.php';

$secretToken = 'laundrypro_secret_deploy_2026';

// If called via Web, verify secret token
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    $inputToken = $_GET['token'] ?? ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
    if (!hash_equals($secretToken, (string)$inputToken)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: Token tidak valid.']);
        exit;
    }
}

try {
    $service = new \App\Services\BackupService();
    
    // 1. Create timestamped snapshot
    $snapshotPath = $service->createSnapshot('auto', 'cron_snapshot');
    
    // 2. Prune backups older than 30 days
    $pruned = $service->pruneOldBackups(30);

    $response = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'backup_file' => basename($snapshotPath),
        'file_size' => filesize($snapshotPath),
        'pruned_old_backups' => $pruned,
        'message' => 'Automated backup completed successfully.',
    ];

    if (php_sapi_name() === 'cli') {
        echo "[SimLaundry Cron Backup] SUCCESS: " . basename($snapshotPath) . " (" . filesize($snapshotPath) . " bytes). Pruned: {$pruned} old files.\n";
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
} catch (\Throwable $e) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "[SimLaundry Cron Backup] ERROR: " . $e->getMessage() . "\n");
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
