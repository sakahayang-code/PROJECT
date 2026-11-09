<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Total Master SKU</div>
            <div class="stat-value text-primary"><?= number_format($stats['total_sku']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-box-seam me-1"></i>Bahan & perlengkapan</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Peringatan Stok Menipis</div>
            <div class="stat-value text-danger"><?= number_format($stats['low_stock']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-exclamation-triangle text-danger me-1"></i>Di bawah batas minimum</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Total Titik Stok Cabang</div>
            <div class="stat-value text-info"><?= number_format($stats['total_items']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-shop me-1"></i>Posisi inventaris</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Pemasok Terdaftar</div>
            <div class="stat-value text-success"><?= number_format($stats['suppliers']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-truck text-success me-1"></i>Mitra supplier</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/inventory') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $selectedBranch == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Kategori Barang</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $selectedCategory == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama atau kode barang..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="<?= url('/inventory') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalCreateItem">
                    <i class="bi bi-plus-circle me-1"></i> SKU Baru
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-boxes me-2 text-primary"></i>Stok Inventaris Bahan & Perlengkapan</h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAdjustStock">
                <i class="bi bi-plus-slash-minus me-1"></i> Masuk / Keluar Manual
            </button>
            <a href="<?= url('/inventory/movements') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clock-history me-1"></i> Mutasi</a>
            <a href="<?= url('/inventory/opname') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clipboard-check me-1"></i> Opname</a>
            <a href="<?= url('/inventory/transfers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-right me-1"></i> Transfer</a>
            <a href="<?= url('/inventory/suppliers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-truck me-1"></i> Supplier</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode SKU</th>
                    <th>Nama Barang & Kategori</th>
                    <th>Cabang</th>
                    <th>Stok Saat Ini</th>
                    <th>Batas Minimum</th>
                    <th>Estimasi Biaya / Satuan</th>
                    <th>Status Stok</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stockItems)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada barang inventaris yang terdaftar.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stockItems as $item): ?>
                        <?php $isLow = (float)$item['current_stock'] <= (float)$item['min_stock']; ?>
                        <tr class="<?= $isLow ? 'table-warning-subtle' : '' ?>">
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($item['code']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($item['name']) ?></div>
                                <div class="small text-muted"><span class="badge bg-secondary-subtle text-secondary me-1"><?= e($item['category_name']) ?></span> Satuan: <?= e($item['unit']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border"><i class="bi bi-shop me-1"></i><?= e($item['branch_name']) ?></span>
                            </td>
                            <td>
                                <span class="fs-6 fw-bold <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                                    <?= number_format((float)$item['current_stock'], 1) ?> <?= e($item['unit']) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= number_format((float)$item['min_stock'], 1) ?> <?= e($item['unit']) ?>
                            </td>
                            <td class="small fw-semibold">
                                <?= format_rupiah($item['cost_price']) ?> / <?= e($item['unit']) ?>
                            </td>
                            <td>
                                <?php if ($isLow): ?>
                                    <span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle-fill me-1"></i>Menipis</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check2-circle me-1"></i>Aman</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="quickStockIn(<?= $item['branch_id'] ?>, <?= $item['item_id'] ?>, '<?= e($item['name']) ?>')">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Stok
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Item -->
<div class="modal fade" id="modalCreateItem" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/inventory/items') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Master SKU Inventaris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kode SKU (Opsional)</label>
                    <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="Otomatis jika kosong (misal: DET-01)">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Barang</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Deterjen Liquid Konsentrat" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Kategori</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Satuan (Unit)</label>
                    <input type="text" name="unit" class="form-control" placeholder="Liter / Kg / Pcs / Roll" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Batas Stok Minimum</label>
                    <input type="number" step="0.5" name="min_stock" class="form-control" value="5" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Harga Beli Rata-Rata (Rp)</label>
                    <input type="text" name="cost_price" class="form-control" placeholder="Contoh: 15.000">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Keterangan / Spesifikasi</label>
                    <input type="text" name="description" class="form-control" placeholder="Warna, aroma, atau vendor utama">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan SKU</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Quick Adjust Stock -->
<div class="modal fade" id="modalAdjustStock" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/inventory/stock/adjust') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-slash-minus me-2 text-primary"></i>Penyesuaian Stok Masuk / Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Cabang</label>
                    <select name="branch_id" id="adjustBranchId" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Barang Inventaris</label>
                    <select name="item_id" id="adjustItemId" class="form-select" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>"><?= e($it['code'] . ' - ' . $it['name'] . ' (' . $it['unit'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jenis Pergerakan</label>
                    <select name="type" class="form-select" required>
                        <option value="in">Barang Masuk (+ In)</option>
                        <option value="out">Barang Keluar / Pemakaian (- Out)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jumlah (Qty)</label>
                    <input type="number" step="0.1" name="quantity" class="form-control" placeholder="Misal: 10" required min="0.1">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Catatan / Alasan</label>
                    <input type="text" name="notes" class="form-control" placeholder="Contoh: Pemakaian harian / Pembelian eceran" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Perbarui Stok</button>
            </div>
        </form>
    </div>
</div>

<script>
function quickStockIn(branchId, itemId, itemName) {
    document.getElementById('adjustBranchId').value = branchId;
    document.getElementById('adjustItemId').value = itemId;
    new bootstrap.Modal(document.getElementById('modalAdjustStock')).show();
}
</script>
