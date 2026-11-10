<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Core\Validator;

class SettingController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;
        $company = $this->db()->fetch("SELECT * FROM companies WHERE id = :id", [':id' => $companyId]);
        $appSettings = $this->db()->fetchAll("SELECT * FROM app_settings ORDER BY `key` ASC");

        $settingsMap = [];
        foreach ($appSettings as $s) {
            $settingsMap[$s['key']] = $s['value'];
        }

        $this->view('settings.index', [
            'title' => 'Pengaturan Perusahaan & Sistem',
            'activeMenu' => 'settings_general',
            'company' => $company,
            'settings' => $settingsMap,
        ]);
    }

    public function saveCompany(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;
        $name = trim($_POST['name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $taxRate = (float)($_POST['tax_rate_percent'] ?? 0);

        if (empty($name)) {
            $this->back('Nama perusahaan wajib diisi.', 'error');
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->update('companies', [
            'name' => $name,
            'tagline' => $tagline ?: null,
            'phone' => $phone ?: null,
            'email' => $email ?: null,
            'address' => $address ?: null,
            'updated_at' => $now,
        ], 'id = :id', [':id' => $companyId]);

        // Update tax rate in app_settings
        $exists = $this->db()->fetchColumn("SELECT id FROM app_settings WHERE `key` = 'tax_rate_percent'");
        if ($exists) {
            $this->db()->update('app_settings', ['value' => (string)$taxRate, 'updated_at' => $now], 'id = :id', [':id' => $exists]);
        } else {
            $this->db()->insert('app_settings', [
                'key' => 'tax_rate_percent',
                'value' => (string)$taxRate,
                'group_name' => 'pos',
                'description' => 'Persentase Pajak Transaksi POS',
                'updated_at' => $now,
            ]);
        }

        $this->logActivity('update_settings', 'companies', $companyId, "Memperbarui profil perusahaan {$name}");
        $this->redirect(url('/settings'), 'Profil perusahaan dan pengaturan sistem berhasil disimpan.', 'success');
    }

    public function receipt(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;
        $receipt = $this->db()->fetch("SELECT * FROM receipt_settings WHERE company_id = :c", [':c' => $companyId]);

        if (!$receipt) {
            $now = date('Y-m-d H:i:s');
            $this->db()->insert('receipt_settings', [
                'company_id' => $companyId,
                'paper_size' => '58mm',
                'header_text' => "SIMLAUNDRY INDONESIA\nBersih, Wangi & Tepat Waktu",
                'footer_text' => "Barang yang tidak diambil dalam 30 hari di luar tanggung jawab kami.",
                'show_logo' => 1,
                'show_qr' => 1,
                'show_barcode' => 0,
                'show_notes' => 1,
                'updated_at' => $now,
            ]);
            $receipt = $this->db()->fetch("SELECT * FROM receipt_settings WHERE company_id = :c", [':c' => $companyId]);
        }

        $this->view('settings.receipt', [
            'title' => 'Pengaturan Printer & Nota Thermal',
            'activeMenu' => 'settings_receipt',
            'receipt' => $receipt,
        ]);
    }

    public function saveReceipt(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;
        $paperSize = in_array($_POST['paper_size'], ['58mm', '80mm']) ? $_POST['paper_size'] : '58mm';
        $headerText = trim($_POST['header_text'] ?? '');
        $footerText = trim($_POST['footer_text'] ?? '');
        $showLogo = isset($_POST['show_logo']) ? 1 : 0;
        $showQr = isset($_POST['show_qr']) ? 1 : 0;
        $showNotes = isset($_POST['show_notes']) ? 1 : 0;

        $this->db()->update('receipt_settings', [
            'paper_size' => $paperSize,
            'header_text' => $headerText,
            'footer_text' => $footerText,
            'show_logo' => $showLogo,
            'show_qr' => $showQr,
            'show_notes' => $showNotes,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'company_id = :c', [':c' => $companyId]);

        $this->redirect(url('/settings/receipt'), 'Pengaturan cetak nota thermal berhasil disimpan.', 'success');
    }

    public function backup(): void
    {
        Permission::abortUnless('manage.backup');

        $backupService = new \App\Services\BackupService();
        // Check and trigger daily auto-backup if not created today
        $backupService->autoBackupDaily();

        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $files = glob($backupDir . '/*.{sqlite,tar.gz,zip}', GLOB_BRACE);
        rsort($files);

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        $this->view('settings.backup', [
            'title' => 'Backup & Restore Database SQLite',
            'activeMenu' => 'settings_backup',
            'backups' => $backups,
        ]);
    }

    public function createBackup(): void
    {
        Permission::abortUnless('manage.backup');

        try {
            $backupService = new \App\Services\BackupService();
            $targetPath = $backupService->createSnapshot('manual');
            $filename = basename($targetPath);

            $this->logActivity('create_backup', 'database', null, "Membuat snapshot cadangan database {$filename}");
            $this->redirect(url('/settings/backup'), "Backup database berhasil dibuat: {$filename}", 'success');
        } catch (\Throwable $e) {
            $this->back("Gagal membuat backup: " . $e->getMessage(), 'error', '/settings/backup');
        }
    }

    public function downloadFullArchive(): void
    {
        Permission::abortUnless('manage.backup');

        try {
            $backupService = new \App\Services\BackupService();
            $archivePath = $backupService->createFullArchive();
            $filename = basename($archivePath);

            $mime = str_ends_with($filename, '.zip') ? 'application/zip' : 'application/gzip';
            header("Content-Type: {$mime}");
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Length: ' . filesize($archivePath));
            readfile($archivePath);
            if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
                return;
            }
            exit;
        } catch (\Throwable $e) {
            $this->back("Gagal membuat arsip lengkap: " . $e->getMessage(), 'error');
        }
    }

    public function exportJson(): void
    {
        Permission::abortUnless('manage.backup');

        try {
            $backupService = new \App\Services\BackupService();
            $data = $backupService->exportJsonDump();
            $filename = 'simlaundry_data_export_' . date('Ymd_His') . '.json';
            $jsonStr = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            header('Content-Type: application/json; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header('Content-Length: ' . strlen($jsonStr));
            echo $jsonStr;
            if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
                return;
            }
            exit;
        } catch (\Throwable $e) {
            $this->back("Gagal mengekspor data JSON: " . $e->getMessage(), 'error');
        }
    }

    public function downloadBackup(string $filename): void
    {
        Permission::abortUnless('manage.backup');

        $cleanFile = basename($filename);
        $filePath = storage_path('backups/' . $cleanFile);

        if (!file_exists($filePath)) {
            $this->back('File backup tidak ditemukan.', 'error');
            return;
        }

        $mime = 'application/x-sqlite3';
        if (str_ends_with($cleanFile, '.zip')) $mime = 'application/zip';
        if (str_ends_with($cleanFile, '.tar.gz')) $mime = 'application/gzip';

        header("Content-Type: {$mime}");
        header("Content-Disposition: attachment; filename=\"{$cleanFile}\"");
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
            return;
        }
        exit;
    }

    public function restoreBackup(): void
    {
        Permission::abortUnless('manage.backup');

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $this->back('Harap pilih file database SQLite (.sqlite) yang valid.', 'error');
            return;
        }

        $tmpFile = $_FILES['backup_file']['tmp_name'];

        try {
            $backupService = new \App\Services\BackupService();
            $backupService->restoreSnapshot($tmpFile);

            $this->logActivity('restore_backup', 'database', null, "Melakukan pemulihan database dari file cadangan yang diunggah.");
            $this->redirect(url('/settings/backup'), 'Database berhasil dipulihkan (Restore Sukses). Snapshot cadangan sebelum restore telah disimpan dengan aman.', 'success');
        } catch (\Throwable $e) {
            $this->back("Gagal memulihkan database: " . $e->getMessage(), 'error', '/settings/backup');
        }
    }

    public function logs(): void
    {
        Permission::abortUnless('audit.view');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));

        $where = "1=1";
        $params = [];
        if ($branchFilter > 0) {
            $where .= " AND al.branch_id = :b";
            $params[':b'] = $branchFilter;
        }

        $logs = $this->db()->fetchAll(
            "SELECT al.*, u.name AS user_name, u.username, b.name AS branch_name
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             LEFT JOIN branches b ON al.branch_id = b.id
             WHERE {$where}
             ORDER BY al.id DESC
             LIMIT 100",
            $params
        );

        $loginLogs = $this->db()->fetchAll(
            "SELECT ll.*, u.name AS user_name
             FROM login_logs ll
             LEFT JOIN users u ON ll.user_id = u.id
             ORDER BY ll.id DESC
             LIMIT 50"
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $this->view('settings.logs', [
            'title' => 'Log Aktivitas & Riwayat Audit Sistem',
            'activeMenu' => 'settings_logs',
            'logs' => $logs,
            'loginLogs' => $loginLogs,
            'branches' => $branches,
            'selectedBranch' => $branchFilter,
        ]);
    }
}
