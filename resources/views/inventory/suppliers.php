<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-truck text-primary me-2"></i>Daftar Pemasok (Suppliers)</h4>
            <p class="text-muted small mb-0">Kelola database vendor bahan deterjen, parfum, plastik kemasan, dan perlengkapan laundry.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Stok</a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSupplier">
                <i class="bi bi-plus-circle me-1"></i> Tambah Supplier Baru
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-building me-2 text-primary"></i>Mitra Pemasok Aktif</h6>
        <span class="badge bg-light text-dark border"><?= count($suppliers) ?> supplier</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode Supplier</th>
                    <th>Nama Perusahaan / Toko</th>
                    <th>Kontak Person</th>
                    <th>No. Telepon / WA</th>
                    <th>Email</th>
                    <th>Alamat Gudang</th>
                    <th>Total PO</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data pemasok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><span class="badge bg-dark font-monospace"><?= e($s['code']) ?></span></td>
                            <td class="fw-bold text-dark"><?= e($s['name']) ?></td>
                            <td><?= e($s['contact_person'] ?: '-') ?></td>
                            <td>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $s['phone']) ?>" target="_blank" class="text-decoration-none text-success">
                                    <i class="bi bi-whatsapp me-1"></i><?= e($s['phone'] ?: '-') ?>
                                </a>
                            </td>
                            <td class="small text-muted"><?= e($s['email'] ?: '-') ?></td>
                            <td class="small text-muted" style="max-width: 250px;"><?= e($s['address'] ?: '-') ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= (int)$s['total_po'] ?> PO</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Supplier -->
<div class="modal fade" id="modalCreateSupplier" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/inventory/suppliers/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Pemasok Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kode Supplier</label>
                    <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="Contoh: SUPP-03" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kontak Person</label>
                    <input type="text" name="contact_person" class="form-control" placeholder="Nama sales / PIC">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Nama Perusahaan / Toko</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: PT Harum Wangi Kimia" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="sales@perusahaan.com">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Alamat Lengkap</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Alamat kantor / gudang supplier"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Supplier</button>
            </div>
        </form>
    </div>
</div>
