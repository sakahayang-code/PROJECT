<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $sqliteConfig = $config['connections']['sqlite'];
        $dbPath = $sqliteConfig['database'];

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $dbPath, null, null, $sqliteConfig['options']);
            
            // Apply essential SQLite pragmas for production stability & integrity
            if (!empty($sqliteConfig['foreign_keys'])) {
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            }
            if (!empty($sqliteConfig['journal_mode'])) {
                $this->pdo->exec("PRAGMA journal_mode = {$sqliteConfig['journal_mode']};");
            }
            if (!empty($sqliteConfig['busy_timeout'])) {
                $this->pdo->exec("PRAGMA busy_timeout = {$sqliteConfig['busy_timeout']};");
            }
        } catch (PDOException $e) {
            throw new Exception("Database connection error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function pdo(): PDO
    {
        return self::getInstance()->getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = $this->query($sql, $params);
        $val = $stmt->fetchColumn($column);
        return $val !== false ? $val : null;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $fields = implode(', ', array_map(fn($col) => "`$col`", $columns));
        $placeholders = implode(', ', array_map(fn($col) => ":$col", $columns));

        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
        
        $params = [];
        foreach ($data as $key => $value) {
            $params[":$key"] = $value;
        }

        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $fields = implode(', ', array_map(fn($col) => "`$col` = :set_$col", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$fields} WHERE {$where}";

        $params = [];
        foreach ($data as $key => $value) {
            $params[":set_$key"] = $value;
        }
        foreach ($whereParams as $key => $value) {
            $params[$key] = $value;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return $this->pdo->beginTransaction();
        }
        return false;
    }

    public function commit(): bool
    {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->commit();
        }
        return false;
    }

    public function rollBack(): bool
    {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return false;
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Safe SQLite live backup using VACUUM INTO
     */
    public function backup(string $destinationPath): bool
    {
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($destinationPath)) {
            unlink($destinationPath);
        }

        // SQLite 3.27+ VACUUM INTO safely creates a consistent point-in-time snapshot
        $escaped = str_replace("'", "''", $destinationPath);
        $this->pdo->exec("VACUUM INTO '{$escaped}'");
        return file_exists($destinationPath);
    }
}
