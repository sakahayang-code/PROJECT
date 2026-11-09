<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Manajemen Cabang / Outlet</h4>
        <div class="text-muted small">Kelola seluruh cabang operasional laundry dan parameter outlet</div>
    </div>
    <?php if (\App\Core\Auth::hasRole('super_admin')): ?>
    <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addBranchModal">
        <i class="bi bi-plus-circle"></i>
        <span>Tambah Cabang Baru</span>
    </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <?php foreach ($branches as $b): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border <?= $b['is_main'] ? 'border-primary' : '' ?>">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <span class="badge bg-secondary mb-1"><?= e($b['code']) ?></span>
                        <h5 class="fw-bold mb-0"><?= e($b['name']) ?></h5>
                    </div>
                    <?php if ($b['is_main']): ?>
                        <span class="badge bg-primary text-white">Cabang Utama</span>
                    <?php else: ?>
                        <span class="badge <?= $b['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $b['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="text-muted small mb-3">
                    <div class="mb-1"><i class="bi bi-geo-alt me-2 text-danger"></i><?= e($b['address'] ?: 'Alamat belum diatur') ?></div>
                    <div class="mb-1"><i class="bi bi-telephone me-2 text-primary"></i><?= e($b['phone'] ?: '-') ?></div>
                    <div><i class="bi bi-envelope me-2 text-info"></i><?= e($b['email'] ?: '-') ?></div>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between text-muted small py-1">
                    <span>Staf Terdaftar:</span>
                    <span class="fw-bold"><?= $b['user_count'] ?> orang</span>
                </div>
                <div class="d-flex justify-content-between text-muted small py-1">
                    <span>Total Pesanan:</span>
                    <span class="fw-bold text-primary"><?= number_format($b['order_count']) ?> nota</span>
                </div>
            </div>

            <?php if (\App\Core\Auth::hasRole('super_admin')): ?>
            <div class="card-footer bg-transparent border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editBranchModal<?= $b['id'] ?>">
                    <i class="bi bi-pencil me-1"></i> Edit Cabang
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editBranchModal<?= $b['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= url('/branches/' . $b['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Cabang: <?= e($b['name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kode Cabang</label>
                            <input type="text" class="form-control" value="<?= e($b['code']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Cabang</label>
                            <input type="text" class="form-control" name="name" value="<?= e($b['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor Telepon / WA</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($b['phone']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Cabang</label>
                            <input type="email" class="form-control" name="email" value="<?= e($b['email']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alamat Lengkap</label>
                            <textarea class="form-control" name="address" rows="2"><?= e($b['address']) ?></textarea>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="activeSwitch<?= $b['id'] ?>" value="1" <?= $b['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="activeSwitch<?= $b['id'] ?>">Status Cabang Aktif</label>
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
</div>

<!-- Modal Tambah Cabang -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/branches') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Cabang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Cabang (Unik, misal: BDG, SBY)</label>
                        <input type="text" class="form-control text-uppercase" name="code" placeholder="Maksimal 5 karakter" required maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Cabang / Outlet</label>
                        <input type="text" class="form-control" name="name" placeholder="Misal: SimLaundry Dago Bandung" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                        <input type="text" class="form-control" name="phone" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Cabang</label>
                        <input type="email" class="form-control" name="email" placeholder="cabang@simlaundry.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Lengkap</label>
                        <textarea class="form-control" name="address" rows="2" placeholder="Alamat fisik outlet laundry"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Cabang</button>
                </div>
            </form>
        </div>
    </div>
</div>
