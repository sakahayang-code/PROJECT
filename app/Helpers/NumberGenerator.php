<?php

namespace App\Helpers;

use App\Core\Database;

class NumberGenerator
{
    /**
     * Generate thread-safe sequential transaction number without using COUNT().
     * Example: LP-PST-20261109-0001
     */
    public static function generateOrderNumber(int $branchId, string $branchCode): string
    {
        return self::generateSequence($branchId, $branchCode, 'order', 'LP', 4);
    }

    public static function generatePaymentNumber(int $branchId, string $branchCode): string
    {
        return self::generateSequence($branchId, $branchCode, 'payment', 'PAY', 4);
    }

    public static function generateExpenseNumber(int $branchId, string $branchCode): string
    {
        return self::generateSequence($branchId, $branchCode, 'expense', 'EXP', 4);
    }

    public static function generatePoNumber(int $branchId, string $branchCode): string
    {
        return self::generateSequence($branchId, $branchCode, 'po', 'PO', 4);
    }

    public static function generateTransferNumber(int $branchId, string $branchCode): string
    {
        return self::generateSequence($branchId, $branchCode, 'transfer', 'TRF', 4);
    }

    public static function generateOpnameNumber(int $branchId, string $branchCode): string
    {
        return self::generateSequence($branchId, $branchCode, 'opname', 'OPN', 4);
    }

    private static function generateSequence(int $branchId, string $branchCode, string $type, string $prefix, int $padLength = 4): string
    {
        $db = Database::getInstance();
        $dateCode = date('Ymd');
        $now = date('Y-m-d H:i:s');

        // SQLite atomic upsert for sequence tracking
        $db->getConnection()->exec("
            INSERT INTO sequences (branch_id, type, prefix, current_val, date_code, updated_at)
            VALUES ({$branchId}, '{$type}', '{$prefix}', 1, '{$dateCode}', '{$now}')
            ON CONFLICT(branch_id, type, date_code) DO UPDATE SET
                current_val = current_val + 1,
                updated_at = '{$now}';
        ");

        $currentVal = (int)$db->fetchColumn(
            "SELECT current_val FROM sequences WHERE branch_id = :b AND type = :t AND date_code = :d",
            [':b' => $branchId, ':t' => $type, ':d' => $dateCode]
        );

        $seqNumber = str_pad((string)$currentVal, $padLength, '0', STR_PAD_LEFT);
        $cleanBranch = strtoupper($branchCode);

        return "{$prefix}-{$cleanBranch}-{$dateCode}-{$seqNumber}";
    }

    /**
     * Generate an unpredictable, cryptographically random tracking token for public QR verification.
     */
    public static function generateTrackingToken(): string
    {
        return bin2hex(random_bytes(16)); // 32 characters hex
    }
}
