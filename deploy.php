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
        $status = 'exec_disabled';
        $output[] = 'Fungsi exec() & shell_exec() dinonaktifkan oleh provider hosting Anda.';
        $output[] = 'Gunakan metode GitHub Actions FTP Deploy untuk auto-update otomatis 100%.';
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
