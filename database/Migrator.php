<?php

namespace Database;

use App\Core\Database;

class Migrator
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->initMigrationsTable();
    }

    private function initMigrationsTable(): void
    {
        $this->db->getConnection()->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                applied_at TEXT NOT NULL
            );
        ");
    }

    public function run(): array
    {
        $applied = [];
        $appliedMigrations = $this->db->fetchAll("SELECT migration FROM migrations");
        $appliedList = array_column($appliedMigrations, 'migration');

        $currentBatch = (int)$this->db->fetchColumn("SELECT MAX(batch) FROM migrations") + 1;

        $dir = __DIR__ . '/migrations';
        $files = glob($dir . '/*.php');
        $pending = [];
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (!in_array($filename, $appliedList, true)) {
                $pending[] = $file;
            }
        }

        if (empty($pending)) {
            return $applied;
        }

        // Automatic safety snapshot before altering schema/running migrations
        try {
            $backupPath = storage_path('backups/pre_migration_' . date('Ymd_His') . '.sqlite');
            $this->db->backup($backupPath);
        } catch (\Throwable $e) {
            // Non-fatal, proceed with caution
        }

        foreach ($pending as $file) {
            $filename = basename($file, '.php');

            $migrationClass = require $file;
            if (is_object($migrationClass) && method_exists($migrationClass, 'up')) {
                $this->db->transaction(function() use ($migrationClass) {
                    $migrationClass->up();
                });
            }

            $this->db->insert('migrations', [
                'migration' => $filename,
                'batch' => $currentBatch,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            $applied[] = $filename;
        }

        return $applied;
    }

    public function rollback(): array
    {
        $rolledBack = [];
        $maxBatch = (int)$this->db->fetchColumn("SELECT MAX(batch) FROM migrations");
        if ($maxBatch <= 0) {
            return $rolledBack;
        }

        $records = $this->db->fetchAll(
            "SELECT migration FROM migrations WHERE batch = :batch ORDER BY id DESC",
            [':batch' => $maxBatch]
        );

        if (empty($records)) {
            return $rolledBack;
        }

        // Automatic safety snapshot before rolling back migrations
        try {
            $backupPath = storage_path('backups/pre_rollback_' . date('Ymd_His') . '.sqlite');
            $this->db->backup($backupPath);
        } catch (\Throwable $e) {
            // Non-fatal, proceed with caution
        }

        $dir = __DIR__ . '/migrations';
        foreach ($records as $row) {
            $filename = $row['migration'];
            $file = $dir . '/' . $filename . '.php';
            if (file_exists($file)) {
                $migrationClass = require $file;
                if (is_object($migrationClass) && method_exists($migrationClass, 'down')) {
                    $this->db->transaction(function() use ($migrationClass) {
                        $migrationClass->down();
                    });
                }
            }
            $this->db->delete('migrations', 'migration = :m', [':m' => $filename]);
            $rolledBack[] = $filename;
        }

        return $rolledBack;
    }
}
