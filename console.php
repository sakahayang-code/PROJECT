<?php

require_once __DIR__ . '/app/autoload.php';

use Database\Migrator;
use Database\Seeders\DatabaseSeeder;
use App\Core\Database;

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        echo "Menjalankan migrasi database SQLite...\n";
        $migrator = new Migrator();
        $applied = $migrator->run();
        if (empty($applied)) {
            echo "Database sudah up-to-date. Tidak ada migrasi baru.\n";
        } else {
            foreach ($applied as $m) {
                echo "  [OK] Migrasi selesai: {$m}\n";
            }
        }
        break;

    case 'migrate:rollback':
        echo "Melakukan rollback migrasi batch terakhir...\n";
        $migrator = new Migrator();
        $rolledBack = $migrator->rollback();
        if (empty($rolledBack)) {
            echo "Tidak ada migrasi yang di-rollback.\n";
        } else {
            foreach ($rolledBack as $m) {
                echo "  [OK] Rollback selesai: {$m}\n";
            }
        }
        break;

    case 'db:seed':
        echo "Menjalankan database seeder...\n";
        $seeder = new DatabaseSeeder();
        $seeder->run();
        echo "[OK] Seeding database berhasil selesai!\n";
        break;

    case 'migrate:fresh':
        echo "Menghapus database lama dan menjalankan migrasi ulang...\n";
        $dbPath = __DIR__ . '/database/laundrypro.sqlite';
        if (file_exists($dbPath)) {
            unlink($dbPath);
            echo "Database lama dihapus: {$dbPath}\n";
        }
        $migrator = new Migrator();
        $migrator->run();
        echo "Migrasi baru selesai. Menjalankan seeder...\n";
        $seeder = new DatabaseSeeder();
        $seeder->run();
        echo "[OK] Fresh migration dan seeding berhasil selesai!\n";
        break;

    case 'backup':
        echo "Membuat backup SQLite database...\n";
        $db = Database::getInstance();
        $backupDir = __DIR__ . '/storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $filename = 'backup_laundrypro_' . date('Ymd_His') . '.sqlite';
        $dest = $backupDir . '/' . $filename;
        if ($db->backup($dest)) {
            $size = filesize($dest);
            $db->insert('backup_logs', [
                'company_id' => 1,
                'file_name' => $filename,
                'file_size' => $size,
                'backup_type' => 'manual',
                'file_path' => $dest,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            echo "[OK] Backup berhasil dibuat: {$filename} (" . number_format($size / 1024, 2) . " KB)\n";
        } else {
            echo "[FAIL] Gagal membuat backup database!\n";
        }
        break;

    case 'queue:work':
        echo "Menjalankan worker antrean pesan WhatsApp...\n";
        $worker = new \App\Services\WhatsAppAdapter();
        $processed = $worker->processQueue(10);
        echo "[OK] Diproses {$processed} pesan dalam antrean.\n";
        break;

    default:
        echo "SimLaundry CLI Console\n";
        echo "Perintah yang tersedia:\n";
        echo "  php console.php migrate          - Jalankan migrasi database\n";
        echo "  php console.php migrate:rollback - Rollback batch migrasi terakhir\n";
        echo "  php console.php migrate:fresh    - Reset database dan seeder ulang\n";
        echo "  php console.php db:seed          - Jalankan seeder data default\n";
        echo "  php console.php backup           - Buat live snapshot backup SQLite\n";
        echo "  php console.php queue:work       - Proses antrean pesan WhatsApp\n";
        break;
}
