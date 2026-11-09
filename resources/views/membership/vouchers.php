<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-ticket-perforated text-primary me-2"></i>Voucher Diskon & Promo</h4>
            <p class="text-muted small mb-0">Kelola kupon potongan harga dan voucher loyalitas pelanggan untuk kasir dan online order.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateVoucher">
            <i class="bi bi-plus-circle me-1"></i> Buat Voucher Baru
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-tags me-2 text-primary"></i>Daftar Voucher Aktif & Riwayat</h6>
        <span class="badge bg-light text-dark border"><?= count($vouchers) ?> voucher</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode Voucher</th>
                    <th>Nama Promo</th>
                    <th>Nilai Potongan</th>
                    <th>Syarat Min. Belanja</th>
                    <th>Kuota & Penggunaan</th>
                    <th>Periode Berlaku</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada voucher yang dibuat.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vouchers as $v): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace fs-6 px-2 py-1"><?= e($v['code']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($v['name']) ?></div>
                                <div class="small text-muted"><?= $v['type'] === 'percent' ? 'Diskon Persentase' : 'Potongan Nominal Tetap' ?></div>
                            </td>
                            <td>
                                <?php if ($v['type'] === 'percent'): ?>
                                    <span class="fw-bold text-success fs-6"><?= (int)$v['value'] ?>%</span>
                                    <?php if ((int)$v['max_discount'] > 0): ?>
                                        <div class="small text-muted">Maks. <?= format_rupiah($v['max_discount']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="fw-bold text-success fs-6"><?= format_rupiah($v['value']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int)$v['min_spend'] > 0 ? format_rupiah($v['min_spend']) : '<span class="text-muted">Tanpa minimum</span>' ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= (int)$v['used_count'] ?> / <?= (int)$v['quota'] ?></div>
                                <div class="progress mt-1" style="height: 4px; width: 100px;">
                                    <?php $pct = $v['quota'] > 0 ? min(100, round(($v['used_count'] / $v['quota']) * 100)) : 0; ?>
                                    <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <div class="small"><?= format_date($v['start_date'], 'd M Y') ?> - <?= format_date($v['end_date'], 'd M Y') ?></div>
                                <?php if (strtotime($v['end_date']) < time()): ?>
                                    <span class="badge bg-danger-subtle text-danger small">Kedaluwarsa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($v['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="<?= url('/vouchers/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="voucher_id" value="<?= $v['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $v['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <?= $v['is_active'] ? '<i class="bi bi-pause me-1"></i> Nonaktifkan' : '<i class="bi bi-play me-1"></i> Aktifkan' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Voucher -->
<div class="modal fade" id="modalCreateVoucher" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/vouchers/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Buat Voucher Diskon Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kode Voucher (Kupon)</label>
                    <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="Contoh: PROMOLEBARAN" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Voucher / Promo</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Promo Spesial Hari Raya" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Tipe Diskon</label>
                    <select name="type" class="form-select" required>
                        <option value="fixed">Potongan Tetap (Nominal Rp)</option>
                        <option value="percent">Persentase (%)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Nilai Diskon</label>
                    <input type="text" name="value" class="form-control" placeholder="Misal: 15000 atau 10" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Maks. Potongan (Jika %)</label>
                    <input type="text" name="max_discount" class="form-control" placeholder="0 jika tanpa batas">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Minimal Belanja (Rp)</label>
                    <input type="text" name="min_spend" class="form-control" placeholder="0 jika tanpa minimum">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Total Kuota Penggunaan</label>
                    <input type="number" name="quota" class="form-control" value="100" min="1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Masa Berlaku Dari</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Masa Berlaku Sampai</label>
                    <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Terbitkan Voucher</button>
            </div>
        </form>
    </div>
</div>
