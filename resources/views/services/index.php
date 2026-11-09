<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Katalog Layanan & Tarif</h4>
        <div class="text-muted small">Kelola master layanan kiloan, satuan, tarif dasar, dan biaya tambahan (Add-on)</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/services/pricing') ?>" class="btn btn-outline-primary d-flex align-items-center gap-1">
            <i class="bi bi-grid-3x3"></i>
            <span>Matriks Harga Cabang</span>
        </a>
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addAddonModal">
            <i class="bi bi-plus"></i>
            <span>Tambah Add-On</span>
        </button>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="bi bi-plus-circle"></i>
            <span>Tambah Layanan Baru</span>
        </button>
    </div>
</div>

<!-- Services List Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Daftar Layanan Laundry Utama</span>
        <span class="badge bg-primary"><?= count($services) ?> layanan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Kode & Nama Layanan</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Tarif Standar</th>
                        <th>Min. Order</th>
                        <th>Estimasi Pengerjaan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= e($s['name']) ?></div>
                            <span class="badge bg-secondary font-monospace" style="font-size:0.7rem;"><?= e($s['code']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($s['category_name']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info text-uppercase"><?= e($s['unit_type']) ?></span>
                        </td>
                        <td class="fw-bold text-success">
                            <?= format_rupiah($s['base_price']) ?> / <?= e($s['unit_type']) ?>
                        </td>
                        <td><?= $s['min_order'] ?> <?= e($s['unit_type']) ?></td>
                        <td><?= $s['est_hours'] ?> Jam</td>
                        <td>
                            <span class="badge <?= $s['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editServiceModal<?= $s['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Service Modal -->
                    <div class="modal fade" id="editServiceModal<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= url('/services/' . $s['id']) ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Layanan: <?= e($s['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Kode Layanan</label>
                                            <input type="text" class="form-control" value="<?= e($s['code']) ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nama Layanan</label>
                                            <input type="text" class="form-control" name="name" value="<?= e($s['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Kategori</label>
                                            <select class="form-select" name="category_id" required>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $s['category_id'] ? 'selected' : '' ?>>
                                                        <?= e($cat['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Satuan Hitung</label>
                                                <select class="form-select" name="unit_type">
                                                    <option value="kg" <?= $s['unit_type'] === 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                                                    <option value="piece" <?= $s['unit_type'] === 'piece' ? 'selected' : '' ?>>Satuan (piece)</option>
                                                    <option value="meter" <?= $s['unit_type'] === 'meter' ? 'selected' : '' ?>>Meter Persegi (meter)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tarif Dasar (Rp)</label>
                                                <input type="number" class="form-control" name="base_price" value="<?= $s['base_price'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Minimal Order</label>
                                                <input type="number" step="0.5" class="form-control" name="min_order" value="<?= $s['min_order'] ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Estimasi Selesai (Jam)</label>
                                                <input type="number" class="form-control" name="est_hours" value="<?= $s['est_hours'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="srvActive<?= $s['id'] ?>" value="1" <?= $s['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="srvActive<?= $s['id'] ?>">Status Layanan Aktif</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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

<!-- Service Addons Table -->
<div class="card shadow-sm">
    <div class="card-header">Layanan Tambahan (Add-on) Kasir</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nama Tambahan</th>
                        <th>Tarif</th>
                        <th>Satuan</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($addons as $ad): ?>
                    <tr>
                        <td class="fw-bold"><?= e($ad['name']) ?></td>
                        <td class="fw-semibold text-success"><?= format_rupiah($ad['price']) ?></td>
                        <td><span class="badge bg-secondary"><?= e($ad['unit_type']) ?></span></td>
                        <td class="text-muted"><?= e($ad['description'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Layanan -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/services') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Layanan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Layanan (Singkat, Unik)</label>
                        <input type="text" class="form-control text-uppercase" name="code" placeholder="Misal: CKS-EXP" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Layanan</label>
                        <input type="text" class="form-control" name="name" placeholder="Misal: Cuci Kering Setrika Express 12 Jam" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Satuan Hitung</label>
                            <select class="form-select" name="unit_type">
                                <option value="kg">Kilogram (kg)</option>
                                <option value="piece">Satuan (piece)</option>
                                <option value="meter">Meter (meter)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tarif Dasar (Rp)</label>
                            <input type="number" class="form-control" name="base_price" placeholder="10000" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Minimal Order</label>
                            <input type="number" step="0.5" class="form-control" name="min_order" value="1.0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Estimasi Selesai (Jam)</label>
                            <input type="number" class="form-control" name="est_hours" value="24">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Layanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Addon -->
<div class="modal fade" id="addAddonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/services/addons') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Layanan Tambahan (Add-on)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Tambahan</label>
                        <input type="text" class="form-control" name="name" placeholder="Misal: Pewangi Ekstra Vanila" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tarif (Rp)</label>
                        <input type="number" class="form-control" name="price" placeholder="Contoh: 3000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Satuan</label>
                        <input type="text" class="form-control" name="unit_type" value="item" placeholder="item / order / kg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan</label>
                        <input type="text" class="form-control" name="description" placeholder="Aroma tahan lama...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Add-on</button>
                </div>
            </form>
        </div>
    </div>
</div>
