<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Jurnal Umum (Double-Entry Bookkeeping)</h4>
        <div class="text-muted small">Semua pencatatan transaksi finansial dengan verifikasi keseimbangan debit dan kredit mutlak</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/accounting/ledger') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-book"></i>
            <span>Buku Besar</span>
        </a>
        <a href="<?= url('/accounting/reports') ?>" class="btn btn-primary d-flex align-items-center gap-1">
            <i class="bi bi-bar-chart-fill"></i>
            <span>Laba Rugi & Neraca</span>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/accounting/journals') ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" name="start_date" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" name="end_date" value="<?= e($endDate) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="ref_type">
                    <option value="">-- Semua Tipe Transaksi --</option>
                    <option value="payment" <?= $selectedRefType === 'payment' ? 'selected' : '' ?>>Penerimaan Pembayaran</option>
                    <option value="expense" <?= $selectedRefType === 'expense' ? 'selected' : '' ?>>Beban Pengeluaran</option>
                    <option value="deposit" <?= $selectedRefType === 'deposit' ? 'selected' : '' ?>>Top Up Deposit</option>
                    <option value="refund" <?= $selectedRefType === 'refund' ? 'selected' : '' ?>>Pengembalian Dana / Pembalik</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter Jurnal</button>
            </div>
        </form>
    </div>
</div>

<!-- Journal Entries List -->
<div class="d-flex flex-column gap-3">
    <?php if (empty($journals)): ?>
        <div class="card shadow-sm p-5 text-center text-muted">Belum ada jurnal transaksi yang tercatat pada rentang tanggal ini.</div>
    <?php else: ?>
        <?php foreach ($journals as $j): ?>
        <div class="card shadow-sm border">
            <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary font-monospace"><?= e($j['entry_no']) ?></span>
                    <span class="text-muted small">&bull; <?= format_date($j['entry_date'], 'd M Y') ?></span>
                    <?php if ($j['branch_name']): ?>
                        <span class="badge bg-light text-dark border"><?= e($j['branch_name']) ?></span>
                    <?php endif; ?>
                    <span class="badge bg-secondary" style="font-size:0.65rem;"><?= strtoupper($j['reference_type']) ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-check-circle-fill me-1"></i> Seimbang (Rp <?= number_format($j['total_debit'], 0, ',', '.') ?>)
                    </span>
                    <span class="badge <?= $j['status'] === 'posted' ? 'bg-dark' : 'bg-warning' ?>">
                        <?= strtoupper($j['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 bg-light small fw-semibold text-secondary border-bottom">
                    <?= e($j['description']) ?>
                    <?php if (!empty($j['reference_no'])): ?>
                        <span class="text-muted fw-normal"> &bull; Ref: <?= e($j['reference_no']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50%;">Kode & Nama Akun Perkiraan (COA)</th>
                                <th class="text-end" style="width: 25%;">Debit (Rp)</th>
                                <th class="text-end" style="width: 25%;">Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($j['lines'] as $line): ?>
                            <tr>
                                <td class="<?= $line['credit'] > 0 ? 'ps-4 text-muted' : 'fw-semibold text-dark' ?>">
                                    <?= e($line['account_code']) ?> &mdash; <?= e($line['account_name']) ?>
                                    <?php if (!empty($line['memo'])): ?>
                                        <span class="small text-muted font-italic">(<?= e($line['memo']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold <?= $line['debit'] > 0 ? 'text-primary' : 'text-muted' ?>">
                                    <?= $line['debit'] > 0 ? format_rupiah($line['debit']) : '-' ?>
                                </td>
                                <td class="text-end fw-semibold <?= $line['credit'] > 0 ? 'text-success' : 'text-muted' ?>">
                                    <?= $line['credit'] > 0 ? format_rupiah($line['credit']) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
