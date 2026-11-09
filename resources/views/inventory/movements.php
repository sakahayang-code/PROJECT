<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Mutasi & Pergerakan Stok</h4>
            <p class="text-muted small mb-0">Audit pergerakan barang masuk, barang keluar, pemakaian produksi, transfer, dan penyesuaian opname.</p>
        </div>
        <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Stok</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/inventory/movements') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $selectedBranch == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Jenis Mutasi</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua Mutasi</option>
                    <option value="in" <?= $selectedType === 'in' ? 'selected' : '' ?>>Barang Masuk (In)</option>
                    <option value="out" <?= $selectedType === 'out' ? 'selected' : '' ?>>Barang Keluar (Out)</option>
                    <option value="transfer_in" <?= $selectedType === 'transfer_in' ? 'selected' : '' ?>>Transfer Masuk</option>
                    <option value="transfer_out" <?= $selectedType === 'transfer_out' ? 'selected' : '' ?>>Transfer Keluar</option>
                    <option value="opname" <?= $selectedType === 'opname' ? 'selected' : '' ?>>Penyesuaian Opname</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="<?= url('/inventory/movements') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Log Mutasi Stok</h6>
        <span class="badge bg-light text-dark border"><?= count($movements) ?> catatan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu Mutasi</th>
                    <th>Cabang</th>
                    <th>Barang</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Stok Sebelum</th>
                    <th>Stok Sesudah</th>
                    <th>Petugas</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Belum ada catatan mutasi stok untuk periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td class="small"><?= format_date($m['created_at'], 'd M Y H:i') ?></td>
                            <td><span class="badge bg-light text-secondary border"><?= e($m['branch_name']) ?></span></td>
                            <td>
                                <div class="fw-bold"><?= e($m['item_name']) ?></div>
                                <div class="small text-muted font-monospace"><?= e($m['item_code']) ?></div>
                            </td>
                            <td>
                                <?php
                                $badge = match($m['type']) {
                                    'in', 'transfer_in' => 'bg-success-subtle text-success',
                                    'out', 'transfer_out' => 'bg-danger-subtle text-danger',
                                    'opname' => 'bg-info-subtle text-info',
                                    default => 'bg-secondary-subtle text-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= strtoupper(str_replace('_', ' ', $m['type'])) ?></span>
                            </td>
                            <td>
                                <span class="fw-bold <?= in_array($m['type'], ['in', 'transfer_in']) ? 'text-success' : 'text-danger' ?>">
                                    <?= in_array($m['type'], ['in', 'transfer_in']) ? '+' : '-' ?><?= number_format((float)$m['quantity'], 1) ?> <?= e($m['unit']) ?>
                                </span>
                            </td>
                            <td class="small"><?= number_format((float)$m['stock_before'], 1) ?> <?= e($m['unit']) ?></td>
                            <td class="small fw-bold"><?= number_format((float)$m['stock_after'], 1) ?> <?= e($m['unit']) ?></td>
                            <td class="small text-muted"><?= e($m['user_name'] ?: 'Sistem') ?></td>
                            <td class="small text-muted" style="max-width: 250px;"><?= e($m['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
