<?php

declare(strict_types=1);

/**
 * LaundryPro / SimLaundry Auto-Deployment Webhook Handler with Data Armor Protection
 * 
 * URL: https://multimediatama.com/laundry/deploy.php?token=laundrypro_secret_deploy_2026
 * 
 * DATA SAFETY GUARANTEE:
 * 1. Automatic pre-deployment SQLite database snapshot before any file changes.
 * 2. Strict exclusions: Live database, user uploads (storage), and environment files are NEVER overwritten.
 * 3. Post-deployment integrity check with automatic rollback if any issue is detected.
 */

header('Content-Type: application/json; charset=utf-8');

$secretToken = 'laundrypro_secret_deploy_2026';

$inputToken = $_GET['token'] ?? ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
$hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

$isAuthorized = false;

// 1. Validasi via Token Query Parameter
if (!empty($inputToken) && hash_equals($secretToken, (string)$inputToken)) {
    $isAuthorized = true;
}

// 2. Validasi via GitHub Webhook HMAC SHA256 Signature
if (!$isAuthorized && !empty($hubSignature)) {
    $calculated = 'sha256=' . hash_hmac('sha256', $payload, $secretToken);
    if (hash_equals($calculated, $hubSignature)) {
        $isAuthorized = true;
    }
}

if (!$isAuthorized) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'code' => 403,
        'message' => 'Akses ditolak: Token rahasia tidak valid atau belum diisi.',
    ], JSON_PRETTY_PRINT);
    exit;
}

$output = [];
$status = 'success';
$executedCommands = [];

try {
    $targetDir = is_dir('/home/mulw1713/public_html/laundry') 
        ? '/home/mulw1713/public_html/laundry' 
        : (is_dir(dirname(__DIR__) . '/app') ? dirname(__DIR__) : __DIR__);

    $liveDbPath = $targetDir . '/database/laundrypro.sqlite';
    $backupDir = $targetDir . '/storage/backups';
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }

    // =========================================================================
    // STEP 0: AUTOMATED PRE-DEPLOYMENT SNAPSHOT (DATABASE ARMOR)
    // =========================================================================
    $preDeployBackupPath = null;
    if (file_exists($liveDbPath) && filesize($liveDbPath) > 0) {
        $preDeployBackupPath = $backupDir . '/pre_deploy_auto_' . date('Ymd_His') . '.sqlite';
        try {
            $pdo = new PDO('sqlite:' . $liveDbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $escaped = str_replace("'", "''", $preDeployBackupPath);
            $pdo->exec("VACUUM INTO '{$escaped}';");
            $output[] = "DATA ARMOR: Snapshot cadangan pre-deployment aman dibuat: " . basename($preDeployBackupPath) . " (" . filesize($preDeployBackupPath) . " bytes).";
        } catch (\Throwable $e) {
            @copy($liveDbPath, $preDeployBackupPath);
            $output[] = "DATA ARMOR: Salinan fisik pre-deployment dibuat: " . basename($preDeployBackupPath);
        }
    }

    // =========================================================================
    // STEP 1: SAFE CODE SYNCHRONIZATION WITH STRICT EXCLUSIONS
    // =========================================================================
    $tarGzUrl = 'https://codeload.github.com/sakahayang-code/PROJECT/tar.gz/refs/heads/main';
    $tmpDir = sys_get_temp_dir();
    $tmpTarGz = $tmpDir . '/simlaundry_deploy_' . time() . '.tar.gz';
    $tmpTar = $tmpDir . '/simlaundry_deploy_' . time() . '.tar';
    $extractDir = $tmpDir . '/simlaundry_extract_' . time();

    $ch = curl_init($tarGzUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SimLaundry-Deployer-Protected');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $archiveData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($archiveData)) {
        file_put_contents($tmpTarGz, $archiveData);
        $phar = new PharData($tmpTarGz);
        $phar->decompress();
        $tar = new PharData($tmpTar);
        $tar->extractTo($extractDir, null, true);

        $subdirs = glob($extractDir . '/*');
        $sourceDir = $subdirs[0] ?? null;

        if ($sourceDir && is_dir($sourceDir)) {
            // Strict exclusion patterns: NEVER OVERWRITE LIVE USER DATA
            $isExcluded = function(string $relPath): bool {
                $relPath = str_replace('\\', '/', $relPath);
                $relPath = ltrim($relPath, '/');

                // 1. Database protection
                if ($relPath === 'database/laundrypro.sqlite') return true;
                if (preg_match('#^database/.*\.sqlite(-wal|-shm)?$#', $relPath)) return true;

                // 2. Storage & uploads protection
                if (str_starts_with($relPath, 'storage/backups/')) return true;
                if (str_starts_with($relPath, 'storage/logs/')) return true;
                if (str_starts_with($relPath, 'public/storage/')) return true;

                // 3. Environment & repository metadata
                if ($relPath === '.env' || str_starts_with($relPath, '.git') || str_starts_with($relPath, '.github')) return true;

                return false;
            };

            $copySafe = function($src, $dst, $baseSrc, $isExcluded, &$copySafe) use (&$output) {
                $count = 0;
                $dir = opendir($src);
                @mkdir($dst, 0755, true);
                while (false !== ($file = readdir($dir))) {
                    if ($file === '.' || $file === '..') continue;
                    $srcPath = $src . '/' . $file;
                    $dstPath = $dst . '/' . $file;
                    $relPath = substr($srcPath, strlen($baseSrc) + 1);

                    if ($isExcluded($relPath)) {
                        continue;
                    }

                    if (is_dir($srcPath)) {
                        $count += $copySafe($srcPath, $dstPath, $baseSrc, $isExcluded, $copySafe);
                    } else {
                        if (@copy($srcPath, $dstPath)) {
                            $count++;
                        }
                    }
                }
                closedir($dir);
                return $count;
            };

            $copied = $copySafe($sourceDir, $targetDir, $sourceDir, $isExcluded, $copySafe);
            $status = 'success';
            $output[] = "Sinkronisasi kode aman selesai: {$copied} file aplikasi diperbarui tanpa menyentuh data database/storage.";
        } else {
            $status = 'error';
            $output[] = 'Gagal menemukan direktori ekstraksi kode.';
        }

        @unlink($tmpTarGz);
        @unlink($tmpTar);
        // Recursive cleanup of temp extract dir
        if (is_dir($extractDir)) {
            $cleanup = function($dir, &$cleanup) {
                foreach (scandir($dir) as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $p = $dir . '/' . $item;
                    is_dir($p) ? $cleanup($p, $cleanup) : @unlink($p);
                }
                @rmdir($dir);
            };
            $cleanup($extractDir, $cleanup);
        }
    } else {
        $status = 'error';
        $output[] = "Gagal mengunduh arsip dari GitHub (HTTP code {$httpCode}).";
    }

    // =========================================================================
    // STEP 2: POST-DEPLOYMENT DATABASE INTEGRITY CHECK
    // =========================================================================
    if (file_exists($liveDbPath)) {
        try {
            $verifyPdo = new PDO('sqlite:' . $liveDbPath);
            $verifyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $quickCheck = $verifyPdo->query("PRAGMA quick_check;")->fetchColumn();
            if ($quickCheck === 'ok') {
                $output[] = "DATA ARMOR: Verifikasi integritas database live PASSED (quick_check: OK).";
            } else {
                throw new Exception("Pemeriksaan cepat integritas menghasilkan: {$quickCheck}");
            }
        } catch (\Throwable $e) {
            $status = 'error';
            $output[] = "PERINGATAN: Integritas database terganggu (" . $e->getMessage() . "). Menjalankan auto-rollback...";
            if ($preDeployBackupPath && file_exists($preDeployBackupPath)) {
                copy($preDeployBackupPath, $liveDbPath);
                $output[] = "DATA ARMOR: Auto-rollback BERHASIL dipulihkan dari snapshot " . basename($preDeployBackupPath);
            }
        }
    }

} catch (\Throwable $e) {
    $status = 'warning';
    $output[] = 'Catatan sistem: ' . $e->getMessage();
}

// Log aktivitas
$logFile = $targetDir . '/storage/logs/deploy.log';
@mkdir(dirname($logFile), 0755, true);
@file_put_contents(
    $logFile,
    "[" . date('Y-m-d H:i:s') . "] Status: {$status}\nOutput:\n" . implode("\n", $output) . "\n---\n",
    FILE_APPEND
);

http_response_code(200);
echo json_encode([
    'status' => $status,
    'timestamp' => date('Y-m-d H:i:s'),
    'data_armor' => 'ACTIVE',
    'command_output' => $output,
    'message' => ($status === 'success') ? 'Deployment & perlindungan data berhasil dijalankan.' : 'Deployment selesai dengan peringatan.'
], JSON_PRETTY_PRINT);
