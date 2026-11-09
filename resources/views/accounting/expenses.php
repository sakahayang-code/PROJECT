<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Pengeluaran & Beban Operasional</h4>
        <div class="text-muted small">Pencatatan biaya listrik, air, deterjen, gaji, servis mesin, dan beban harian cabang</div>
    </div>
    <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
        <i class="bi bi-dash-circle-fill"></i>
        <span>Catat Biaya Pengeluaran</span>
    </button>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/accounting/expenses') ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" name="start_date" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" name="end_date" value="<?= e($endDate) ?>">
            </div>
            <?php if (\App\Core\Auth::hasGlobalAccess()): ?>
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="branch_id">
                    <option value="0">-- Semua Cabang --</option>
                    <?php foreach ($branches as $br): ?>
                        <option value="<?= $br['id'] ?>" <?= (int)$selectedBranch === (int)$br['id'] ? 'selected' : '' ?>>
                            <?= e($br['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Expenses Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>Daftar Pengeluaran Beban Terverifikasi</span>
        <span class="fw-bold text-danger">Total Periode: <?= format_rupiah($totalAmount) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>No. Bukti / Tanggal</th>
                        <th>Kategori Beban</th>
                        <th>Cabang</th>
                        <th>Dibayar Kepada / Catatan</th>
                        <th>Sumber Kas / Bank</th>
                        <th class="text-end">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pengeluaran pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $exp): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($exp['expense_no']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= format_date($exp['expense_date'], 'd M Y') ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?= e($exp['category_name']) ?></span></td>
                            <td><?= e($exp['branch_name']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($exp['paid_to'] ?: '-') ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= e($exp['description']) ?></div>
                            </td>
                            <td><?= e($exp['cash_account_name']) ?></td>
                            <td class="text-end fw-bold text-danger"><?= format_rupiah($exp['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengeluaran -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/accounting/expenses') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Catat Beban Pengeluaran Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Setiap pengeluaran akan otomatis memotong saldo akun kas/bank terpilih dan memposting entri jurnal akuntansi seimbang.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cabang Pengeluaran</label>
                        <select class="form-select" name="branch_id" required>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Biaya / Beban</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sumber Dana (Kasir / Bank)</label>
                        <select class="form-select" name="cash_account_id" required>
                            <option value="">-- Pilih Akun Kas/Bank --</option>
                            <?php foreach ($cashAccounts as $ca): ?>
                                <option value="<?= $ca['id'] ?>"><?= e($ca['name']) ?> (Saldo: <?= format_rupiah($ca['balance']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nominal Pengeluaran (Rp)</label>
                            <input type="number" class="form-control" name="amount" placeholder="Contoh: 150000" min="1000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Pengeluaran</label>
                            <input type="date" class="form-control" name="expense_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dibayar Kepada (Vendor / Penerima)</label>
                        <input type="text" class="form-control" name="paid_to" placeholder="Misal: PLN, Warung Gas, Toko Plastik">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan Pengeluaran</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Keperluan rinci pengeluaran"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Posting Jurnal</button>
                </div>
            </form>
        </div>
    </div>
</div>
