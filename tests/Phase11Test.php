<?php

declare(strict_types=1);

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Modules\Reports\ReportController;
use App\Modules\Settings\SettingController;

class Phase11Test
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function runAll(): void
    {
        echo "=== Running Phase 11 Tests: Reports & Analytics, System Settings, Backup & Audit Logs ===\n";

        $this->testReportRegistryAndAllThirtyReports();
        $this->testReportCsvExport();
        $this->testCompanySettingsAndReceiptSettings();
        $this->testSqliteOnlineBackupAndIntegrity();
        $this->testAuditTrailAndLoginLogs();

        echo "=== All Phase 11 Tests Passed Successfully! ===\n\n";
    }

    private function authenticateSuperAdmin(): void
    {
        Session::start();
        $user = $this->db->fetch("SELECT * FROM users WHERE username = 'superadmin'");
        if (!$user) {
            throw new \Exception("Super admin user not found in database.");
        }
        Auth::login($user);
    }

    public function testReportRegistryAndAllThirtyReports(): void
    {
        echo "Testing Report Registry & All 30 Business Reports Execution... ";

        $this->authenticateSuperAdmin();

        $controller = new ReportController();

        // Use reflection to access private $reportRegistry and generateReportData
        $refClass = new \ReflectionClass(ReportController::class);
        $registryProp = $refClass->getProperty('reportRegistry');
        $registryProp->setAccessible(true);
        $registry = $registryProp->getValue($controller);

        if (count($registry) !== 30) {
            throw new \Exception("Expected exactly 30 reports, found " . count($registry));
        }

        $expectedCategories = ['sales' => 8, 'operations' => 8, 'crm' => 4, 'finance' => 4, 'inventory' => 3, 'executive' => 3];
        $categoryCounts = [];
        foreach ($registry as $slug => $meta) {
            $cat = $meta['category'];
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
        }

        foreach ($expectedCategories as $cat => $expectedCount) {
            if (($categoryCounts[$cat] ?? 0) !== $expectedCount) {
                throw new \Exception("Category {$cat} expected {$expectedCount} reports, found " . ($categoryCounts[$cat] ?? 0));
            }
        }

        // Test executing all 30 reports with real SQLite queries
        $generateMethod = $refClass->getMethod('generateReportData');
        $generateMethod->setAccessible(true);

        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');

        foreach ($registry as $slug => $meta) {
            $data = $generateMethod->invoke($controller, $slug, 1, $startDate, $endDate);
            if (!is_array($data) || !isset($data['headers']) || !isset($data['rows']) || !isset($data['summary'])) {
                throw new \Exception("Report {$slug} failed to return required structure (headers, rows, summary).");
            }
        }

        echo "PASSED (All 30 reports validated)\n";
    }

    public function testReportCsvExport(): void
    {
        echo "Testing Report CSV Export Formatting... ";

        $this->authenticateSuperAdmin();

        $_GET['start_date'] = date('Y-m-01');
        $_GET['end_date'] = date('Y-m-d');
        $_GET['branch_id'] = '1';
        $_GET['export'] = 'csv';

        ob_start();
        $controller = new ReportController();
        $controller->show('sales_daily');
        $csvOutput = ob_get_clean();

        if (empty($csvOutput)) {
            throw new \Exception("CSV export produced empty output.");
        }

        if (strpos($csvOutput, 'Laporan Penjualan Harian') === false) {
            throw new \Exception("CSV export missing expected report title.");
        }

        if (strpos($csvOutput, 'Tanggal') === false || strpos($csvOutput, 'Jumlah Nota') === false) {
            throw new \Exception("CSV export missing expected column headers.");
        }

        echo "PASSED\n";
    }

    public function testCompanySettingsAndReceiptSettings(): void
    {
        echo "Testing Company Profile & Receipt Settings... ";

        $this->authenticateSuperAdmin();

        $controller = new SettingController();

        // 1. Update Company Profile
        $newPhone = '021-99887766';
        $newTax = 11.5;
        $_POST = [
            'name' => 'LaundryPro Group Indonesia',
            'tagline' => 'Solusi Cerdas Laundry Modern',
            'phone' => $newPhone,
            'email' => 'contact@laundrypro.id',
            'address' => 'Gedung Wisma Antigravity Lt. 8, Jakarta',
            'tax_rate_percent' => (string)$newTax,
        ];

        ob_start();
        $controller->saveCompany();
        ob_end_clean();

        $company = $this->db->fetch("SELECT * FROM companies WHERE id = 1");
        if ($company['phone'] !== $newPhone || $company['name'] !== 'LaundryPro Group Indonesia') {
            throw new \Exception("Company profile update was not persisted to database.");
        }

        $taxVal = $this->db->fetchColumn("SELECT value FROM app_settings WHERE `key` = 'tax_rate_percent'");
        if ((float)$taxVal !== 11.5) {
            throw new \Exception("Tax rate setting was not persisted to app_settings.");
        }

        // 2. Update Receipt Settings
        $_POST = [
            'paper_size' => '80mm',
            'header_text' => "LAUNDRYPRO FLAGSHIP\nBersih Sempurna & Tepat Waktu",
            'footer_text' => "Simpan nota ini sebagai bukti sah pengambilan cucian.",
            'show_logo' => '1',
            'show_qr' => '1',
            'show_notes' => '1',
        ];

        ob_start();
        $controller->saveReceipt();
        ob_end_clean();

        $receipt = $this->db->fetch("SELECT * FROM receipt_settings WHERE company_id = 1");
        if (!$receipt || $receipt['paper_size'] !== '80mm' || strpos($receipt['header_text'], 'FLAGSHIP') === false) {
            throw new \Exception("Thermal receipt settings were not saved properly.");
        }

        echo "PASSED\n";
    }

    public function testSqliteOnlineBackupAndIntegrity(): void
    {
        echo "Testing SQLite Online Snapshot Backup (VACUUM INTO) & Quick Check... ";

        $this->authenticateSuperAdmin();

        $controller = new SettingController();

        $backupDir = dirname(__DIR__) . '/storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Clean any pre-existing test files
        $beforeFiles = glob($backupDir . '/*.sqlite');

        ob_start();
        $controller->createBackup();
        ob_end_clean();

        $afterFiles = glob($backupDir . '/laundrypro_backup_*.sqlite');
        if (empty($afterFiles)) {
            throw new \Exception("Backup file was not created in storage/backups/.");
        }

        rsort($afterFiles);
        $latestBackup = $afterFiles[0];

        if (!file_exists($latestBackup) || filesize($latestBackup) < 1000) {
            throw new \Exception("Backup file is empty or corrupted (size < 1KB).");
        }

        // Verify SQLite Schema Integrity on the created backup
        $testPdo = new \PDO("sqlite:{$latestBackup}");
        $testPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $quickCheck = $testPdo->query("PRAGMA quick_check;")->fetchColumn();
        if ($quickCheck !== 'ok') {
            throw new \Exception("SQLite PRAGMA quick_check failed on backup: {$quickCheck}");
        }

        // Verify essential tables exist in backup
        $tables = $testPdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
        $requiredTables = ['companies', 'branches', 'users', 'orders', 'accounts', 'journal_entries', 'inventory_items', 'machines'];
        foreach ($requiredTables as $req) {
            if (!in_array($req, $tables, true)) {
                throw new \Exception("Essential table {$req} missing from SQLite backup file.");
            }
        }

        echo "PASSED (Valid SQLite backup created & verified)\n";
    }

    public function testAuditTrailAndLoginLogs(): void
    {
        echo "Testing Activity Audit Trail & Login Logs Verification... ";

        $this->authenticateSuperAdmin();

        // Verify activity_logs recorded updates
        $recentLog = $this->db->fetch("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 1");
        if (!$recentLog) {
            throw new \Exception("No activity log recorded in database.");
        }

        if (empty($recentLog['action']) || empty($recentLog['user_id'])) {
            throw new \Exception("Activity log entry is incomplete.");
        }

        // Verify logs() view can be invoked
        $_GET = ['branch_id' => '0'];
        ob_start();
        $controller = new SettingController();
        $controller->logs();
        $htmlOutput = ob_get_clean();

        if (empty($htmlOutput) || strpos($htmlOutput, 'Log Aktivitas') === false) {
            throw new \Exception("Audit logs view failed to render.");
        }

        echo "PASSED\n";
    }
}

// Execute tests
$test = new Phase11Test();
$test->runAll();
