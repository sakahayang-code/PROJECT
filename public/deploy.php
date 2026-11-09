<?php

declare(strict_types=1);

/**
 * LaundryPro Auto-Deployment Webhook Handler
 * 
 * Endpoint ini dipanggil otomatis oleh GitHub Webhook setiap kali ada git push.
 * URL: https://multimediatama.com/laundry/deploy.php?token=laundrypro_secret_deploy_2026
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
        'help' => 'Pastikan URL webhook di GitHub memiliki parameter ?token=laundrypro_secret_deploy_2026 atau field Secret diisi laundrypro_secret_deploy_2026'
    ], JSON_PRETTY_PRINT);
    exit;
}

$output = [];
$status = 'success';
$executedCommands = [];

try {
    // Cari lokasi direktori proyek yang memiliki .git
    $possibleDirs = [
        __DIR__,
        dirname(__DIR__),
        '/home/mulw1713/repositories/PROJECT',
        '/home/mulw1713/PROJECT',
        '/home/mulw1713/public_html/PROJECT',
        '/home/mulw1713/public_html/laundry',
    ];

    $gitDir = null;
    foreach ($possibleDirs as $dir) {
        if (is_dir($dir . '/.git')) {
            $gitDir = $dir;
            break;
        }
    }

    // Cek ketersediaan fungsi eksekusi di server hosting
    $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    $canExec = function_exists('exec') && !in_array('exec', $disabledFunctions, true);
    $canShellExec = function_exists('shell_exec') && !in_array('shell_exec', $disabledFunctions, true);

    if ($canExec || $canShellExec) {
        $cmds = [];
        if ($gitDir) {
            $cmds[] = "cd " . escapeshellarg($gitDir) . " && git fetch origin main 2>&1 && git reset --hard origin/main 2>&1";
        }

        // Salin ke folder web jika repo berada di repositories/
        $webDir = '/home/mulw1713/public_html/laundry';
        if ($gitDir && $gitDir !== $webDir && is_dir($webDir)) {
            $cmds[] = "/bin/cp -R " . escapeshellarg($gitDir) . "/* " . escapeshellarg($webDir) . "/ 2>&1";
            $cmds[] = "/bin/cp " . escapeshellarg($gitDir) . "/.htaccess " . escapeshellarg($webDir) . "/ 2>&1";
        }

        // Set permission folder storage & database
        if (is_dir($webDir . '/storage')) {
            $cmds[] = "chmod -R 775 " . escapeshellarg($webDir . '/storage') . " " . escapeshellarg($webDir . '/database') . " 2>&1";
        }

        foreach ($cmds as $c) {
            $executedCommands[] = $c;
            $res = [];
            if ($canExec) {
                @exec($c, $res);
                $output[] = implode("\n", $res);
            } elseif ($canShellExec) {
                $r = @shell_exec($c);
                if ($r) $output[] = trim($r);
            }
        }

        // Jalankan cPanel UAPI jika ada
        if (file_exists('/usr/local/cpanel/bin/uapi') && $gitDir) {
            $uapiCmd = "/usr/local/cpanel/bin/uapi VersionControlDeployment create repository_root=" . escapeshellarg($gitDir) . " 2>&1";
            $executedCommands[] = $uapiCmd;
            if ($canExec) {
                $uRes = [];
                @exec($uapiCmd, $uRes);
                $output[] = implode("\n", $uRes);
            }
        }
    } else {
        // Pure PHP Auto-Update Fallback (No exec, no git binary required)
        $output[] = 'Mode fallback Pure-PHP diaktifkan (exec/shell_exec dinonaktifkan hosting).';
        
        $tarGzUrl = 'https://codeload.github.com/sakahayang-code/PROJECT/tar.gz/refs/heads/main';
        $tmpDir = sys_get_temp_dir();
        $tmpTarGz = $tmpDir . '/simlaundry_' . time() . '.tar.gz';
        $tmpTar = $tmpDir . '/simlaundry_' . time() . '.tar';
        $extractDir = $tmpDir . '/simlaundry_extract_' . time();
        
        $ch = curl_init($tarGzUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SimLaundry-Deployer');
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
                $targetDir = is_dir('/home/mulw1713/public_html/laundry') ? '/home/mulw1713/public_html/laundry' : (is_dir(dirname(__DIR__) . '/app') ? dirname(__DIR__) : __DIR__);
                
                // Exclude sensitive files from being overwritten
                $excludes = ['database/laundrypro.sqlite', '.env', 'storage/logs', 'storage/backups', '.git', '.github'];
                
                $copyFunc = function($src, $dst, $excludes, &$copyFunc) use (&$output) {
                    $count = 0;
                    $dir = opendir($src);
                    @mkdir($dst, 0755, true);
                    while (false !== ($file = readdir($dir))) {
                        if ($file === '.' || $file === '..') continue;
                        $srcPath = $src . '/' . $file;
                        $dstPath = $dst . '/' . $file;
                        
                        $skip = false;
                        foreach ($excludes as $ex) {
                            if (str_ends_with($dstPath, $ex) || str_contains($dstPath, '/' . $ex . '/')) {
                                $skip = true;
                                break;
                            }
                        }
                        if ($skip) continue;
                        
                        if (is_dir($srcPath)) {
                            $count += $copyFunc($srcPath, $dstPath, $excludes, $copyFunc);
                        } else {
                            if (@copy($srcPath, $dstPath)) {
                                $count++;
                            }
                        }
                    }
                    closedir($dir);
                    return $count;
                };
                
                $copied = $copyFunc($sourceDir, $targetDir, $excludes, $copyFunc);
                $status = 'success';
                $output[] = "Sinkronisasi berhasil: {$copied} file diperbarui ke {$targetDir}.";
            } else {
                $status = 'error';
                $output[] = 'Gagal menemukan direktori ekstraksi.';
            }
            
            @unlink($tmpTarGz);
            @unlink($tmpTar);
        } else {
            $status = 'error';
            $output[] = "Gagal mengunduh arsip dari GitHub (HTTP code {$httpCode}).";
        }
    }
} catch (\Throwable $e) {
    $status = 'warning';
    $output[] = 'Catatan sistem: ' . $e->getMessage();
}

// Log aktivitas
$logFile = dirname(__DIR__) . '/storage/logs/deploy.log';
@file_put_contents(
    $logFile,
    "[" . date('Y-m-d H:i:s') . "] Status: {$status}\nOutput:\n" . implode("\n", $output) . "\n---\n",
    FILE_APPEND
);

http_response_code(200);
echo json_encode([
    'status' => $status,
    'timestamp' => date('Y-m-d H:i:s'),
    'git_directory' => $gitDir ?? 'not_found',
    'executed_commands' => $executedCommands,
    'command_output' => $output,
    'message' => ($status === 'success') ? 'Auto-deployment berhasil dijalankan.' : 'Webhook diterima dengan catatan.'
], JSON_PRETTY_PRINT);
