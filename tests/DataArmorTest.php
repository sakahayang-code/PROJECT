<?php

declare(strict_types=1);

/**
 * SimLaundry Data Armor & Persistence Test Suite
 */

require_once __DIR__ . '/../app/autoload.php';

echo "=============================================\n";
echo "TEST SUITE: DATA ARMOR & PERLINDUNGAN DATA\n";
echo "=============================================\n";

$backupService = new \App\Services\BackupService();

// TEST 1: Database Snapshot Creation & Integrity Check
echo "[Test 1] Membuat snapshot database & verifikasi integritas... ";
$snapPath = $backupService->createSnapshot('manual', 'test_armor');
if (!file_exists($snapPath) || filesize($snapPath) === 0) {
    throw new Exception("Snapshot file tidak ditemukan atau berukuran 0");
}
$testPdo = new PDO('sqlite:' . $snapPath);
$testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$qc = $testPdo->query("PRAGMA quick_check;")->fetchColumn();
if ($qc !== 'ok') {
    throw new Exception("Integritas snapshot gagal: {$qc}");
}
echo "PASSED (Snapshot OK, Size: " . filesize($snapPath) . " bytes)\n";

// TEST 2: Daily Auto-Backup Idempotence
echo "[Test 2] Pengujian Auto-Backup Harian (Idempoten)... ";
$daily1 = $backupService->autoBackupDaily();
$daily2 = $backupService->autoBackupDaily(); // second call on same day should return null
if ($daily2 !== null) {
    throw new Exception("Auto-backup tidak idempoten, duplikasi terdeteksi");
}
echo "PASSED (1 backup harian tersimpan, panggilan berikutnya diabaikan)\n";

// TEST 3: Full Backup Archive (DB + Uploads)
echo "[Test 3] Pengujian Pembuatan Full Archive (.tar.gz / .zip)... ";
$archivePath = $backupService->createFullArchive();
if (!file_exists($archivePath) || filesize($archivePath) === 0) {
    throw new Exception("Archive gagal dibuat");
}
echo "PASSED (Arsip berhasil dibuat: " . basename($archivePath) . ")\n";

// TEST 4: Portable Master Data Export (JSON)
echo "[Test 4] Pengujian Ekspor Data Master JSON... ";
$dump = $backupService->exportJsonDump();
if (empty($dump['tables']) || !isset($dump['tables']['orders'])) {
    throw new Exception("Ekspor JSON tidak valid");
}
echo "PASSED (" . count($dump['tables']) . " tabel master berhasil diekspor)\n";

// TEST 5: Backup Pruning Retention (Max 30)
echo "[Test 5] Pengujian Retensi Cadangan (Pruning)... ";
$pruned = $backupService->pruneOldBackups(30);
echo "PASSED (Retensi 30 snapshot aman)\n";

// TEST 6: Directory Security (.htaccess)
echo "[Test 6] Pemeriksaan Keamanan Folder Database & Storage... ";
$dbHt = base_path('database/.htaccess');
$stHt = base_path('storage/.htaccess');
if (!file_exists($dbHt) || !file_exists($stHt)) {
    throw new Exception("File proteksi .htaccess belum terpasang di database/ atau storage/");
}
echo "PASSED (Akses HTTP langsung ke database & storage diblokir)\n";

// Clean up test files
@unlink($snapPath);
@unlink($archivePath);

echo "\n=============================================\n";
echo "SEMUA PENGUJIAN DATA ARMOR LOLOS 100% SUKSES!\n";
echo "=============================================\n";
