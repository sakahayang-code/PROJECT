<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Matriks Harga Layanan Per Cabang</h4>
        <div class="text-muted small">Atur penyesuaian tarif khusus, batas minimal order, dan estimasi waktu per cabang</div>
    </div>
    <a href="<?= url('/services') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Master Layanan</span>
    </a>
</div>

<!-- Branch Selector -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/services/pricing') ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Pilih Cabang untuk Penyesuaian Tarif:</label>
                <select class="form-select" name="branch_id" onchange="this.form.submit()">
                    <?php foreach ($branches as $br): ?>
                        <option value="<?= $br['id'] ?>" <?= (int)$selectedBranchId === (int)$br['id'] ? 'selected' : '' ?>>
                            [<?= e($br['code']) ?>] <?= e($br['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Price Matrix Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Layanan</th>
                        <th>Kategori</th>
                        <th>Tarif Standar Pusat</th>
                        <th>Tarif Cabang Ini</th>
                        <th>Min. Order</th>
                        <th>Estimasi Selesai</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prices as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= e($p['service_name']) ?></div>
                            <span class="badge bg-secondary font-monospace" style="font-size:0.7rem;"><?= e($p['service_code']) ?></span>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e($p['category_name']) ?></span></td>
                        <td class="text-muted small">
                            <?= format_rupiah($p['base_price']) ?> / <?= e($p['unit_type']) ?>
                        </td>
                        <td class="fw-bold <?= $p['price'] != $p['base_price'] ? 'text-primary' : 'text-success' ?>">
                            <?= format_rupiah($p['price']) ?> / <?= e($p['unit_type']) ?>
                            <?php if ($p['price'] != $p['base_price']): ?>
                                <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:0.65rem;">Khusus</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['min_order'] ?> <?= e($p['unit_type']) ?></td>
                        <td><?= $p['est_hours'] ?> Jam</td>
                        <td>
                            <span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPriceModal<?= $p['service_id'] ?>">
                                <i class="bi bi-pencil me-1"></i> Edit Tarif
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Branch Price Modal -->
                    <div class="modal fade" id="editPriceModal<?= $p['service_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= url('/services/pricing/update') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="branch_id" value="<?= $selectedBranchId ?>">
                                    <input type="hidden" name="service_id" value="<?= $p['service_id'] ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Ubah Tarif Cabang: <?= e($p['service_name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-light border small mb-3">
                                            Tarif Standar Pusat: <strong><?= format_rupiah($p['base_price']) ?> / <?= e($p['unit_type']) ?></strong>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tarif Khusus Cabang Ini (Rp)</label>
                                            <input type="number" class="form-control" name="price" value="<?= $p['price'] ?>" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Min. Order (<?= e($p['unit_type']) ?>)</label>
                                                <input type="number" step="0.5" class="form-control" name="min_order" value="<?= $p['min_order'] ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Estimasi (Jam)</label>
                                                <input type="number" class="form-control" name="est_hours" value="<?= $p['est_hours'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="bpActive<?= $p['service_id'] ?>" value="1" <?= $p['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="bpActive<?= $p['service_id'] ?>">Layanan Aktif di Cabang Ini</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan Tarif Cabang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
