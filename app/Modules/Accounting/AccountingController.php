<?php

namespace App\Modules\Accounting;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Helpers\NumberGenerator;

class AccountingController extends Controller
{
    public function cash(): void
    {
        Permission::abortUnless('accounting.access');

        $activeBranchId = Auth::getActiveBranchId();
        $where = "1=1";
        $params = [];
        if ($activeBranchId) {
            $where .= " AND ca.branch_id = :b";
            $params[':b'] = $activeBranchId;
        }

        $cashAccounts = $this->db()->fetchAll(
            "SELECT ca.*, b.name AS branch_name, a.code AS account_code, a.name AS coa_name
             FROM cash_accounts ca
             JOIN branches b ON ca.branch_id = b.id
             JOIN accounts a ON ca.account_id = a.id
             WHERE {$where}
             ORDER BY ca.branch_id ASC, ca.id ASC",
            $params
        );

        $transactions = $this->db()->fetchAll(
            "SELECT ct.*, ca.name AS cash_account_name, b.name AS branch_name, u.name AS user_name
             FROM cash_transactions ct
             JOIN cash_accounts ca ON ct.cash_account_id = ca.id
             JOIN branches b ON ct.branch_id = b.id
             LEFT JOIN users u ON ct.user_id = u.id
             WHERE 1=1 " . ($activeBranchId ? "AND ct.branch_id = " . (int)$activeBranchId : "") . "
             ORDER BY ct.id DESC
             LIMIT 50"
        );

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $this->view('accounting.cash', [
            'title' => 'Kas & Bank Operasional',
            'activeMenu' => 'accounting_cash',
            'cashAccounts' => $cashAccounts,
            'transactions' => $transactions,
            'branches' => $branches,
        ]);
    }

    public function expenses(): void
    {
        Permission::abortUnless('accounting.access');

        $activeBranchId = Auth::getActiveBranchId();
        $branchFilter = (int)($_GET['branch_id'] ?? ($activeBranchId ?: 0));
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $where = "e.expense_date BETWEEN :s AND :e";
        $params = [':s' => $startDate, ':e' => $endDate];

        if ($branchFilter > 0) {
            $where .= " AND e.branch_id = :b";
            $params[':b'] = $branchFilter;
        } elseif (!Auth::hasGlobalAccess()) {
            $allowed = Auth::getAllowedBranchIds();
            if (!empty($allowed)) {
                $where .= " AND e.branch_id IN (" . implode(',', $allowed) . ")";
            }
        }

        $expenses = $this->db()->fetchAll(
            "SELECT e.*, ec.name AS category_name, b.name AS branch_name, ca.name AS cash_account_name, u.name AS created_by_name
             FROM expenses e
             JOIN expense_categories ec ON e.category_id = ec.id
             JOIN branches b ON e.branch_id = b.id
             JOIN cash_accounts ca ON e.cash_account_id = ca.id
             LEFT JOIN users u ON e.created_by = u.id
             WHERE {$where}
             ORDER BY e.expense_date DESC, e.id DESC",
            $params
        );

        $categories = $this->db()->fetchAll("SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY name ASC");
        $cashAccounts = $this->db()->fetchAll("SELECT * FROM cash_accounts WHERE is_active = 1");
        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $totalAmount = array_sum(array_column($expenses, 'amount'));

        $this->view('accounting.expenses', [
            'title' => 'Pengeluaran Biaya Operasional',
            'activeMenu' => 'accounting_expenses',
            'expenses' => $expenses,
            'categories' => $categories,
            'cashAccounts' => $cashAccounts,
            'branches' => $branches,
            'selectedBranch' => $branchFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function storeExpense(): void
    {
        Permission::abortUnless('accounting.access');

        $branchId = (int)($_POST['branch_id'] ?? (Auth::getActiveBranchId() ?: 1));
        $categoryId = (int)$_POST['category_id'];
        $cashAccountId = (int)$_POST['cash_account_id'];
        $amount = (int)str_replace(['.', ','], '', $_POST['amount'] ?? '0');
        $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
        $paidTo = trim($_POST['paid_to'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($amount <= 0 || empty($categoryId) || empty($cashAccountId)) {
            $this->back('Harap lengkapi semua kolom wajib pengeluaran.', 'error');
        }

        $branch = $this->db()->fetch("SELECT * FROM branches WHERE id = :id", [':id' => $branchId]);
        $cashAcc = $this->db()->fetch("SELECT * FROM cash_accounts WHERE id = :id", [':id' => $cashAccountId]);
        $category = $this->db()->fetch("SELECT * FROM expense_categories WHERE id = :id", [':id' => $categoryId]);

        if (!$branch || !$cashAcc || !$category) {
            $this->back('Data cabang, kas, atau kategori tidak valid.', 'error');
        }

        if ($cashAcc['balance'] < $amount) {
            $this->back("Saldo kas/bank tidak mencukupi (Tersedia: " . format_rupiah($cashAcc['balance']) . ").", 'error');
        }

        $now = date('Y-m-d H:i:s');
        $this->db()->transaction(function() use ($branch, $branchId, $categoryId, $cashAcc, $cashAccountId, $category, $amount, $expenseDate, $paidTo, $description, $now) {
            $expenseNo = NumberGenerator::generateExpenseNumber($branchId, $branch['code']);

            $expenseId = $this->db()->insert('expenses', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'cash_account_id' => $cashAccountId,
                'expense_no' => $expenseNo,
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'paid_to' => $paidTo ?: null,
                'description' => $description ?: $category['name'],
                'status' => 'approved',
                'created_by' => Auth::id(),
                'created_at' => $now,
            ]);

            // Deduct cash account balance
            $newBalance = $cashAcc['balance'] - $amount;
            $this->db()->update('cash_accounts', ['balance' => $newBalance, 'updated_at' => $now], 'id = :id', [':id' => $cashAccountId]);

            // Log cash transaction
            $this->db()->insert('cash_transactions', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'cash_account_id' => $cashAccountId,
                'type' => 'out',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'category' => $category['name'],
                'reference_no' => $expenseNo,
                'description' => "Pengeluaran {$expenseNo} - {$description}",
                'user_id' => Auth::id(),
                'transaction_date' => $expenseDate,
                'created_at' => $now,
            ]);

            // Post Balanced Double-Entry Accounting Journal
            // Debit: Akun Beban Kategori (misal Beban Operasional / 5205)
            // Credit: Akun Kas/Bank (1102 / 1110)
            $expenseAccountId = $category['account_id'] ?: (int)$this->db()->fetchColumn("SELECT id FROM accounts WHERE code = '5205'");

            $jId = $this->db()->insert('journal_entries', [
                'company_id' => $branch['company_id'],
                'branch_id' => $branchId,
                'entry_no' => 'JV-' . $expenseNo,
                'entry_date' => $expenseDate,
                'reference_type' => 'expense',
                'reference_id' => $expenseId,
                'reference_no' => $expenseNo,
                'description' => "Beban Pengeluaran: {$description} (Dibayar ke: {$paidTo})",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'is_balanced' => 1,
                'status' => 'posted',
                'created_by' => Auth::id(),
                'created_at' => $now,
            ]);

            // Debit Expense Account
            $this->db()->insert('journal_entry_lines', [
                'journal_entry_id' => $jId,
                'account_id' => $expenseAccountId,
                'debit' => $amount,
                'credit' => 0,
                'memo' => $description,
                'created_at' => $now,
            ]);

            // Credit Cash Account
            $this->db()->insert('journal_entry_lines', [
                'journal_entry_id' => $jId,
                'account_id' => $cashAcc['account_id'],
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Kas keluar pembayaran beban {$expenseNo}",
                'created_at' => $now,
            ]);
        });

        $this->logActivity('create_expense', 'expenses', null, "Mencatat pengeluaran sebesar " . format_rupiah($amount) . " untuk {$description}");
        $this->redirect(url('/accounting/expenses'), 'Pengeluaran biaya berhasil disimpan dan jurnal telah diposting!');
    }

    public function journals(): void
    {
        Permission::abortUnless('accounting.access');

        $activeBranchId = Auth::getActiveBranchId();
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $refType = $_GET['ref_type'] ?? '';

        $where = "je.entry_date BETWEEN :s AND :e";
        $params = [':s' => $startDate, ':e' => $endDate];

        if ($activeBranchId) {
            $where .= " AND (je.branch_id = :b OR je.branch_id IS NULL)";
            $params[':b'] = $activeBranchId;
        }

        if (!empty($refType)) {
            $where .= " AND je.reference_type = :rt";
            $params[':rt'] = $refType;
        }

        $journals = $this->db()->fetchAll(
            "SELECT je.*, b.name AS branch_name, u.name AS user_name
             FROM journal_entries je
             LEFT JOIN branches b ON je.branch_id = b.id
             LEFT JOIN users u ON je.created_by = u.id
             WHERE {$where}
             ORDER BY je.entry_date DESC, je.id DESC",
            $params
        );

        // Fetch lines for each journal
        foreach ($journals as &$j) {
            $j['lines'] = $this->db()->fetchAll(
                "SELECT jel.*, a.code AS account_code, a.name AS account_name, a.type AS account_type
                 FROM journal_entry_lines jel
                 JOIN accounts a ON jel.account_id = a.id
                 WHERE jel.journal_entry_id = :jid
                 ORDER BY jel.debit DESC, jel.id ASC",
                [':jid' => $j['id']]
            );
        }
        unset($j);

        $branches = $this->db()->fetchAll("SELECT id, name FROM branches WHERE is_active = 1");

        $this->view('accounting.journals', [
            'title' => 'Jurnal Umum (Double-Entry)',
            'activeMenu' => 'accounting_journals',
            'journals' => $journals,
            'branches' => $branches,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedRefType' => $refType,
        ]);
    }

    public function ledger(): void
    {
        Permission::abortUnless('accounting.access');

        $accountId = (int)($_GET['account_id'] ?? 0);
        $accounts = $this->db()->fetchAll("SELECT * FROM accounts ORDER BY code ASC");

        if ($accountId === 0 && !empty($accounts)) {
            $accountId = (int)$accounts[0]['id'];
        }

        $selectedAccount = $this->db()->fetch("SELECT * FROM accounts WHERE id = :id", [':id' => $accountId]);

        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $lines = $this->db()->fetchAll(
            "SELECT jel.*, je.entry_no, je.entry_date, je.description AS entry_description, b.name AS branch_name
             FROM journal_entry_lines jel
             JOIN journal_entries je ON jel.journal_entry_id = je.id
             LEFT JOIN branches b ON je.branch_id = b.id
             WHERE jel.account_id = :aid AND je.entry_date BETWEEN :s AND :e AND je.status = 'posted'
             ORDER BY je.entry_date ASC, je.id ASC",
            [':aid' => $accountId, ':s' => $startDate, ':e' => $endDate]
        );

        $this->view('accounting.ledger', [
            'title' => 'Buku Besar (General Ledger)',
            'activeMenu' => 'accounting_journals',
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'lines' => $lines,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function reports(): void
    {
        Permission::abortUnless('accounting.access');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $activeBranchId = Auth::getActiveBranchId();

        $branchClause = $activeBranchId ? " AND (je.branch_id = {$activeBranchId} OR je.branch_id IS NULL)" : "";

        // 1. Income Statement (Laba Rugi)
        // Revenue (4xxx): Credit - Debit
        $sqlRev = "SELECT a.code, a.name, COALESCE(SUM(jel.credit - jel.debit), 0) AS total
                   FROM accounts a
                   LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                   LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date BETWEEN :s AND :e {$branchClause}
                   WHERE a.type = 'revenue'
                   GROUP BY a.id
                   ORDER BY a.code ASC";
        $revenueRows = $this->db()->fetchAll($sqlRev, [':s' => $startDate, ':e' => $endDate]);
        $totalRevenue = array_sum(array_column($revenueRows, 'total'));

        // Expenses (5xxx): Debit - Credit
        $sqlExp = "SELECT a.code, a.name, COALESCE(SUM(jel.debit - jel.credit), 0) AS total
                   FROM accounts a
                   LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                   LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date BETWEEN :s AND :e {$branchClause}
                   WHERE a.type = 'expense'
                   GROUP BY a.id
                   ORDER BY a.code ASC";
        $expenseRows = $this->db()->fetchAll($sqlExp, [':s' => $startDate, ':e' => $endDate]);
        $totalExpenses = array_sum(array_column($expenseRows, 'total'));

        $netIncome = $totalRevenue - $totalExpenses;

        // 2. Balance Sheet (Neraca)
        // Assets (1xxx): Debit - Credit
        $sqlAssets = "SELECT a.code, a.name, COALESCE(SUM(jel.debit - jel.credit), 0) AS balance
                      FROM accounts a
                      LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                      LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date <= :e {$branchClause}
                      WHERE a.type = 'asset'
                      GROUP BY a.id
                      ORDER BY a.code ASC";
        $assetRows = $this->db()->fetchAll($sqlAssets, [':e' => $endDate]);
        $totalAssets = array_sum(array_column($assetRows, 'balance'));

        // Liabilities (2xxx): Credit - Debit
        $sqlLiab = "SELECT a.code, a.name, COALESCE(SUM(jel.credit - jel.debit), 0) AS balance
                    FROM accounts a
                    LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                    LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date <= :e {$branchClause}
                    WHERE a.type = 'liability'
                    GROUP BY a.id
                    ORDER BY a.code ASC";
        $liabRows = $this->db()->fetchAll($sqlLiab, [':e' => $endDate]);
        $totalLiabilities = array_sum(array_column($liabRows, 'balance'));

        // Equity (3xxx): Credit - Debit
        $sqlEquity = "SELECT a.code, a.name, COALESCE(SUM(jel.credit - jel.debit), 0) AS balance
                      FROM accounts a
                      LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                      LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date <= :e {$branchClause}
                      WHERE a.type = 'equity'
                      GROUP BY a.id
                      ORDER BY a.code ASC";
        $equityRows = $this->db()->fetchAll($sqlEquity, [':e' => $endDate]);
        $totalEquity = array_sum(array_column($equityRows, 'balance')) + $netIncome;

        // 3. Trial Balance (Neraca Saldo)
        $sqlTrial = "SELECT a.code, a.name, a.normal_balance,
                            COALESCE(SUM(jel.debit), 0) AS sum_debit,
                            COALESCE(SUM(jel.credit), 0) AS sum_credit
                     FROM accounts a
                     LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                     LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date <= :e {$branchClause}
                     GROUP BY a.id
                     ORDER BY a.code ASC";
        $trialRows = $this->db()->fetchAll($sqlTrial, [':e' => $endDate]);

        $this->view('accounting.reports', [
            'title' => 'Laporan Keuangan & Akuntansi',
            'activeMenu' => 'accounting_journals',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'revenueRows' => $revenueRows,
            'totalRevenue' => $totalRevenue,
            'expenseRows' => $expenseRows,
            'totalExpenses' => $totalExpenses,
            'netIncome' => $netIncome,
            'assetRows' => $assetRows,
            'totalAssets' => $totalAssets,
            'liabRows' => $liabRows,
            'totalLiabilities' => $totalLiabilities,
            'equityRows' => $equityRows,
            'totalEquity' => $totalEquity,
            'trialRows' => $trialRows,
        ]);
    }
}
