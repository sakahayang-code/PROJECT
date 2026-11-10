<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use Exception;
use PDO;
use Phar;
use PharData;
use ZipArchive;

class BackupService
{
    private Database $db;
    private string $backupDir;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->backupDir = storage_path('backups');
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create an online SQLite snapshot using VACUUM INTO
     */
    public function createSnapshot(string $type = 'manual', ?string $prefix = null): string
    {
        $prefix = $prefix ?: ($type === 'auto' ? 'auto_snapshot' : 'manual_backup');
        $filename = "laundrypro_{$prefix}_" . date('Ymd_His') . '.sqlite';
        $targetPath = $this->backupDir . '/' . $filename;

        if (file_exists($targetPath)) {
            @unlink($targetPath);
        }

        // Atomic SQLite VACUUM INTO creates a consistent point-in-time snapshot
        $escaped = str_replace("'", "''", $targetPath);
        $this->db->getConnection()->exec("VACUUM INTO '{$escaped}';");

        if (!file_exists($targetPath)) {
            throw new Exception("Gagal membuat snapshot database ke: {$targetPath}");
        }

        $size = filesize($targetPath);
        $companyId = Auth::companyId() ?: 1;
        $userId = Auth::id() ?: null;
        $backupType = ($type === 'auto') ? 'auto' : 'manual';

        try {
            $this->db->insert('backup_logs', [
                'company_id' => $companyId,
                'file_name' => $filename,
                'file_size' => $size,
                'backup_type' => $backupType,
                'file_path' => $targetPath,
                'created_by' => $userId,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Log insert failure is non-fatal to the backup itself
        }

        return $targetPath;
    }

    /**
     * Create a full archive containing both the SQLite database snapshot and user uploads (storage)
     */
    public function createFullArchive(): string
    {
        // 1. Create fresh database snapshot first
        $snapshotPath = $this->createSnapshot('manual', 'full_archive_db');
        $snapshotFilename = basename($snapshotPath);

        $dateStr = date('Ymd_His');
        $uploadsDir = public_path('storage');

        // Check if ZipArchive is available
        if (class_exists(ZipArchive::class)) {
            $zipFilename = "simlaundry_full_backup_{$dateStr}.zip";
            $zipPath = $this->backupDir . '/' . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                // Add SQLite database snapshot
                $zip->addFile($snapshotPath, 'database/' . $snapshotFilename);

                // Add storage uploads
                if (is_dir($uploadsDir)) {
                    $this->addFolderToZip($uploadsDir, $zip, 'uploads');
                }

                // Add metadata info
                $meta = [
                    'app' => 'SimLaundry',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'php_version' => PHP_VERSION,
                    'database_file' => $snapshotFilename,
                ];
                $zip->addFromString('backup_info.json', json_encode($meta, JSON_PRETTY_PRINT));
                $zip->close();

                @unlink($snapshotPath);
                return $zipPath;
            }
        }

        // Fallback to PharData (.tar.gz) which is always available in PHP
        $tarFilename = "simlaundry_full_backup_{$dateStr}.tar";
        $tarPath = $this->backupDir . '/' . $tarFilename;
        $tarGzPath = $tarPath . '.gz';

        if (file_exists($tarPath)) @unlink($tarPath);
        if (file_exists($tarGzPath)) @unlink($tarGzPath);

        $phar = new PharData($tarPath);
        $phar->addFile($snapshotPath, 'database/' . $snapshotFilename);

        if (is_dir($uploadsDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                $subPath = substr($file->getPathname(), strlen($uploadsDir) + 1);
                if ($file->isFile()) {
                    $phar->addFile($file->getPathname(), 'uploads/' . $subPath);
                }
            }
        }

        $meta = [
            'app' => 'SimLaundry',
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'database_file' => $snapshotFilename,
        ];
        $phar->addFromString('backup_info.json', json_encode($meta, JSON_PRETTY_PRINT));

        $phar->compress(Phar::GZ);
        @unlink($tarPath);
        @unlink($snapshotPath);

        return $tarGzPath;
    }

    private function addFolderToZip(string $folder, ZipArchive $zip, string $localFolder): void
    {
        $dir = opendir($folder);
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $folder . '/' . $file;
            $localPath = $localFolder . '/' . $file;
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($localPath);
                $this->addFolderToZip($fullPath, $zip, $localPath);
            } elseif (is_file($fullPath)) {
                $zip->addFile($fullPath, $localPath);
            }
        }
        closedir($dir);
    }

    /**
     * Automatic daily backup. Checks if a backup exists for today, and if not, creates one and prunes old backups.
     */
    public function autoBackupDaily(): ?string
    {
        $today = date('Ymd');
        $existing = glob($this->backupDir . "/*_daily_{$today}_*.sqlite");

        if (!empty($existing)) {
            return null; // Backup for today already exists
        }

        try {
            $path = $this->createSnapshot('auto', "daily_{$today}");
            $this->pruneOldBackups(30);
            return $path;
        } catch (\Throwable $e) {
            error_log("Auto backup failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prune old backup files to preserve disk space while keeping the most recent $keepCount backups
     */
    public function pruneOldBackups(int $keepCount = 30): int
    {
        $files = glob($this->backupDir . '/*.{sqlite,tar.gz,zip}', GLOB_BRACE);
        if (count($files) <= $keepCount) {
            return 0;
        }

        // Sort descending by modified time (newest first)
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $pruned = 0;
        $toDelete = array_slice($files, $keepCount);
        foreach ($toDelete as $f) {
            if (@unlink($f)) {
                $pruned++;
            }
        }

        return $pruned;
    }

    /**
     * Export full master and transactional data to portable JSON format
     */
    public function exportJsonDump(): array
    {
        $tables = [
            'companies',
            'branches',
            'roles',
            'users',
            'service_categories',
            'services',
            'membership_tiers',
            'vouchers',
            'customers',
            'customer_addresses',
            'orders',
            'order_items',
            'payments',
            'delivery_requests',
            'app_settings',
            'receipt_settings',
        ];

        $dump = [
            'app' => 'SimLaundry',
            'version' => '2.0.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'tables' => [],
        ];

        foreach ($tables as $tbl) {
            try {
                $rows = $this->db->fetchAll("SELECT * FROM {$tbl}");
                $dump['tables'][$tbl] = [
                    'count' => count($rows),
                    'data' => $rows,
                ];
            } catch (\Throwable $e) {
                // Table might not exist or error
                $dump['tables'][$tbl] = ['count' => 0, 'data' => []];
            }
        }

        return $dump;
    }

    /**
     * Safe restore with schema integrity verification and automatic pre-restore safety copy
     */
    public function restoreSnapshot(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new Exception("File backup tidak ditemukan: {$filePath}");
        }

        // 1. Verify SQLite integrity
        $testPdo = new PDO("sqlite:{$filePath}");
        $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $quickCheck = $testPdo->query("PRAGMA quick_check;")->fetchColumn();
        if ($quickCheck !== 'ok') {
            throw new Exception("Integritas database rusak (quick_check: {$quickCheck})");
        }

        // 2. Verify required tables exist
        $tables = $testPdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $required = ['companies', 'branches', 'users', 'orders', 'customers'];
        foreach ($required as $req) {
            if (!in_array($req, $tables, true)) {
                throw new Exception("File backup tidak valid: Tabel {$req} tidak ditemukan.");
            }
        }

        // 3. Take safety copy of the current live database before replacing
        $liveDb = config('database.connections.sqlite.database');
        if (file_exists($liveDb)) {
            $safetyCopy = $this->backupDir . '/safety_before_restore_' . date('Ymd_His') . '.sqlite';
            $escaped = str_replace("'", "''", $safetyCopy);
            $this->db->getConnection()->exec("VACUUM INTO '{$escaped}';");
        }

        // 4. Overwrite live DB with verified backup
        if (!@copy($filePath, $liveDb)) {
            throw new Exception("Gagal menyalin file backup ke database aktif.");
        }

        return true;
    }
}
