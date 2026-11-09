<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Kas & Rekening Bank Operasional</h4>
        <div class="text-muted small">Kelola saldo kas laci kasir outlet, rekening bank BCA/Mandiri, dan mutasi saldo</div>
    </div>
</div>

<!-- Cash Accounts Summary Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($cashAccounts as $ca): ?>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 shadow-sm border">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-light text-dark border"><?= e($ca['branch_name']) ?></span>
                    <span class="badge <?= $ca['account_type'] === 'cash' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?>">
                        <?= strtoupper($ca['account_type']) ?>
                    </span>
                </div>
                <h6 class="fw-bold text-secondary mb-1"><?= e($ca['name']) ?></h6>
                <div class="text-muted small mb-2">COA: <?= e($ca['account_code']) ?> &bull; <?= e($ca['coa_name']) ?></div>
                <h4 class="fw-bold text-primary mb-0"><?= format_rupiah($ca['balance']) ?></h4>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Cash Transactions Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>Riwayat Mutasi Arus Kas Terkini</span>
        <span class="badge bg-secondary"><?= count($transactions) ?> mutasi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal & Waktu</th>
                        <th>Akun Kas / Bank</th>
                        <th>Cabang</th>
                        <th>Kategori</th>
                        <th>No. Ref / Keterangan</th>
                        <th class="text-end">Masuk / Keluar</th>
                        <th class="text-end">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada mutasi kas tercatat.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= format_date($t['created_at']) ?></td>
                            <td class="fw-semibold"><?= e($t['cash_account_name']) ?></td>
                            <td><?= e($t['branch_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($t['category'] ?: 'Umum') ?></span></td>
                            <td>
                                <div class="fw-bold"><?= e($t['reference_no'] ?: '-') ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= e($t['description']) ?></div>
                            </td>
                            <td class="text-end fw-bold <?= $t['type'] === 'in' ? 'text-success' : 'text-danger' ?>">
                                <?= $t['type'] === 'in' ? '+' : '-' ?> <?= format_rupiah($t['amount']) ?>
                            </td>
                            <td class="text-end fw-semibold"><?= format_rupiah($t['balance_after']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
