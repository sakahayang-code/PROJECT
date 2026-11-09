<?php

declare(strict_types=1);

/**
 * LaundryPro Auto-Deployment Webhook Handler
 * 
 * Endpoint ini dipanggil otomatis oleh GitHub Webhook setiap kali ada git push.
 * URL: https://domainanda.com/PROJECT/deploy.php?token=laundrypro_secret_deploy_2026
 */

// Ganti token ini sesuai keinginan Anda jika diperlukan
$secretToken = 'laundrypro_secret_deploy_2026';

// Ambil token dari query string ?token=... atau HTTP Header
$inputToken = $_GET['token'] ?? ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
$hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

$payload = file_get_contents('php://input');

$isAuthorized = false;

// 1. Validasi via Token Query Parameter
if (!empty($inputToken) && hash_equals($secretToken, $inputToken)) {
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
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak: Token rahasia tidak valid.'
    ]);
    exit;
}

$repoDir = dirname(__DIR__); // Root folder repositori
$output = [];
$returnVar = 0;

// Daftar perintah pembaruan dan deploy
$commands = [
    "cd " . escapeshellarg($repoDir),
    "git fetch origin main 2>&1",
    "git reset --hard origin/main 2>&1",
    "chmod -R 775 storage database 2>&1",
];

// Jika ada uapi cPanel untuk trigger .cpanel.yml
$uapiCmd = "/usr/local/cpanel/bin/uapi VersionControlDeployment create repository_root=" . escapeshellarg($repoDir) . " 2>&1";

$fullCmd = implode(" && ", $commands);

if (function_exists('exec')) {
    exec($fullCmd, $output, $returnVar);
    // Coba trigger uapi jika tersedia
    if (file_exists('/usr/local/cpanel/bin/uapi')) {
        @exec($uapiCmd, $output);
    }
} elseif (function_exists('shell_exec')) {
    $res = shell_exec($fullCmd);
    if ($res) {
        $output[] = trim($res);
    }
    if (file_exists('/usr/local/cpanel/bin/uapi')) {
        @shell_exec($uapiCmd);
    }
} elseif (function_exists('system')) {
    ob_start();
    system($fullCmd, $returnVar);
    $output[] = ob_get_clean();
} else {
    $output[] = "Perhatian: Fungsi exec/shell_exec dinonaktifkan oleh konfigurasi PHP hosting.";
}

// Catat log ke storage/logs/deploy.log
$logDir = $repoDir . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logEntry = "[" . date('Y-m-d H:i:s') . "] Auto-Deploy triggered. Exit Code: {$returnVar}\n" . implode("\n", $output) . "\n---\n";
@file_put_contents($logDir . '/deploy.log', $logEntry, FILE_APPEND);

header('Content-Type: application/json');
echo json_encode([
    'status' => ($returnVar === 0) ? 'success' : 'completed_with_notes',
    'timestamp' => date('Y-m-d H:i:s'),
    'command_output' => $output
], JSON_PRETTY_PRINT);
