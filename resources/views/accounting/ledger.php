<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Buku Besar (General Ledger)</h4>
        <div class="text-muted small">Riwayat mutasi debit, kredit, dan saldo berjalan per akun perkiraan (COA)</div>
    </div>
    <a href="<?= url('/accounting/journals') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Jurnal</span>
    </a>
</div>

<!-- Account Selector Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/accounting/ledger') ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Pilih Akun Buku Besar:</label>
                <select class="form-select" name="account_id" onchange="this.form.submit()">
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= $acc['id'] ?>" <?= $acc['id'] == ($selectedAccount['id'] ?? 0) ? 'selected' : '' ?>>
                            [<?= e($acc['code']) ?>] <?= e($acc['name']) ?> (<?= ucfirst($acc['type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Dari Tanggal:</label>
                <input type="date" class="form-control" name="start_date" value="<?= e($startDate) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Sampai Tanggal:</label>
                <input type="date" class="form-control" name="end_date" value="<?= e($endDate) ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<?php if ($selectedAccount): ?>
<!-- Ledger Header Info -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="badge bg-primary fs-6 font-monospace mb-1"><?= e($selectedAccount['code']) ?></span>
            <h5 class="fw-bold mb-0"><?= e($selectedAccount['name']) ?></h5>
            <div class="text-muted small">Tipe: <?= ucfirst($selectedAccount['type']) ?> &bull; Saldo Normal: <?= strtoupper($selectedAccount['normal_balance']) ?></div>
        </div>
    </div>
</div>

<!-- Ledger Table with Running Balance -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Jurnal</th>
                        <th>Keterangan / Memo</th>
                        <th>Cabang</th>
                        <th class="text-end">Debit (Rp)</th>
                        <th class="text-end">Kredit (Rp)</th>
                        <th class="text-end">Saldo Berjalan (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lines)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada mutasi jurnal pada akun ini.</td></tr>
                    <?php else: ?>
                        <?php
                            $runningBalance = 0;
                            $normal = $selectedAccount['normal_balance']; // 'debit' or 'credit'
                        ?>
                        <?php foreach ($lines as $line): ?>
                            <?php
                                if ($normal === 'debit') {
                                    $runningBalance += ($line['debit'] - $line['credit']);
                                } else {
                                    $runningBalance += ($line['credit'] - $line['debit']);
                                }
                            ?>
                            <tr>
                                <td><?= format_date($line['entry_date'], 'd M Y') ?></td>
                                <td class="fw-bold font-monospace"><?= e($line['entry_no']) ?></td>
                                <td>
                                    <div><?= e($line['entry_description']) ?></div>
                                    <?php if (!empty($line['memo'])): ?>
                                        <span class="text-muted" style="font-size:0.75rem;">(<?= e($line['memo']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($line['branch_name'] ?: 'Pusat') ?></td>
                                <td class="text-end fw-semibold <?= $line['debit'] > 0 ? 'text-primary' : 'text-muted' ?>">
                                    <?= $line['debit'] > 0 ? format_rupiah($line['debit']) : '-' ?>
                                </td>
                                <td class="text-end fw-semibold <?= $line['credit'] > 0 ? 'text-success' : 'text-muted' ?>">
                                    <?= $line['credit'] > 0 ? format_rupiah($line['credit']) : '-' ?>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    <?= format_rupiah($runningBalance) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
