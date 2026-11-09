<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clipboard-check text-primary me-2"></i>Stok Opname (Penyesuaian Fisik)</h4>
            <p class="text-muted small mb-0">Lakukan inventarisasi fisik secara periodik untuk mencatat selisih antara pencatatan sistem dan kondisi nyata di gudang.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Stok</a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateOpname">
                <i class="bi bi-plus-circle me-1"></i> Catat Opname Baru
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-journal-text me-2 text-primary"></i>Riwayat Sesi Stok Opname</h6>
        <span class="badge bg-light text-dark border"><?= count($opnames) ?> sesi tercatat</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Opname</th>
                    <th>Cabang</th>
                    <th>Tanggal Pelaksanaan</th>
                    <th>Jumlah Item</th>
                    <th>Total Selisih Fisik</th>
                    <th>Petugas Pelaksana</th>
                    <th>Catatan & Alasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($opnames)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada sesi stok opname yang dilakukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($opnames as $op): ?>
                        <tr>
                            <td><span class="badge bg-dark font-monospace"><?= e($op['opname_no']) ?></span></td>
                            <td><span class="badge bg-light text-secondary border"><?= e($op['branch_name']) ?></span></td>
                            <td><?= format_date($op['opname_date'], 'd M Y') ?></td>
                            <td class="fw-semibold"><?= (int)$op['total_items'] ?> item</td>
                            <td>
                                <?php $diff = (float)$op['total_discrepancy']; ?>
                                <span class="badge <?= $diff > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' ?>">
                                    <?= $diff > 0 ? '+' . number_format($diff, 1) : 'Sesuai (0)' ?>
                                </span>
                            </td>
                            <td><?= e($op['conducted_by_name'] ?: 'Petugas') ?></td>
                            <td class="small text-muted"><?= e($op['notes'] ?: '-') ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-all me-1"></i>Selesai</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Opname -->
<div class="modal fade" id="modalCreateOpname" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/inventory/opname/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Catat Hasil Hitung Fisik Opname</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Cabang</label>
                    <select name="branch_id" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Barang Yang Dihitung</label>
                    <select name="item_id" class="form-select" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>"><?= e($it['code'] . ' - ' . $it['name'] . ' (' . $it['unit'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Jumlah Hitung Fisik Nyata (Real)</label>
                    <input type="number" step="0.1" name="physical_stock" class="form-control" placeholder="Hasil hitung langsung di rak gudang" required min="0">
                    <div class="form-text small">Sistem akan otomatis menghitung selisih dengan stok tercatat dan menyesuaikan stok akhir.</div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Alasan / Keterangan Selisih</label>
                    <input type="text" name="reason" class="form-control" placeholder="Contoh: Tumpah saat penuangan / Barang rusak / Selisih kemasan" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Penyesuaian Opname</button>
            </div>
        </form>
    </div>
</div>
