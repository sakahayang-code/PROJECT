<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-arrow-left-right text-primary me-2"></i>Transfer Stok Antar Cabang</h4>
            <p class="text-muted small mb-0">Permintaan dan pengiriman persediaan bahan kimia atau plastik antar outlet laundry.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Stok</a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateTransfer">
                <i class="bi bi-plus-circle me-1"></i> Kirim Transfer Baru
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-truck me-2 text-primary"></i>Daftar Pengiriman Transfer Stok</h6>
        <span class="badge bg-light text-dark border"><?= count($transfers) ?> data transfer</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Transfer</th>
                    <th>Dari Cabang</th>
                    <th>Ke Cabang Tujuan</th>
                    <th>Tanggal Kirim</th>
                    <th>Tanggal Diterima</th>
                    <th>Diminta Oleh</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat transfer stok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transfers as $tr): ?>
                        <tr>
                            <td><span class="badge bg-dark font-monospace"><?= e($tr['transfer_no']) ?></span></td>
                            <td><span class="badge bg-danger-subtle text-danger border"><i class="bi bi-box-arrow-up-right me-1"></i><?= e($tr['from_branch_name']) ?></span></td>
                            <td><span class="badge bg-success-subtle text-success border"><i class="bi bi-box-arrow-in-down me-1"></i><?= e($tr['to_branch_name']) ?></span></td>
                            <td class="small"><?= format_date($tr['sent_date'], 'd M Y') ?></td>
                            <td class="small"><?= $tr['received_date'] ? format_date($tr['received_date'], 'd M Y') : '<span class="text-muted">- Belum diterima -</span>' ?></td>
                            <td class="small"><?= e($tr['requester_name'] ?: 'Staf') ?></td>
                            <td>
                                <?php if ($tr['status'] === 'received'): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Diterima</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-hourglass-split me-1"></i>Dalam Pengiriman</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($tr['status'] === 'pending'): ?>
                                    <form method="POST" action="<?= url('/inventory/transfers/receive') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="transfer_id" value="<?= $tr['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Konfirmasi penerimaan barang di cabang tujuan?')">
                                            <i class="bi bi-check2-circle me-1"></i> Terima Barang
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-check2-all text-success me-1"></i>Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Transfer -->
<div class="modal fade" id="modalCreateTransfer" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/inventory/transfers/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Kirim Transfer Stok Antar Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Dari Cabang (Asal)</label>
                    <select name="from_branch_id" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Ke Cabang (Tujuan)</label>
                    <select name="to_branch_id" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Barang Yang Ditransfer</label>
                    <select name="item_id" class="form-select" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>"><?= e($it['code'] . ' - ' . $it['name'] . ' (' . $it['unit'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Jumlah Yang Dikirim</label>
                    <input type="number" step="0.1" name="quantity" class="form-control" placeholder="Contoh: 5" required min="0.1">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Catatan Pengiriman</label>
                    <input type="text" name="notes" class="form-control" placeholder="Contoh: Bantuan stok mendesak">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Kirim Permintaan Transfer</button>
            </div>
        </form>
    </div>
</div>
