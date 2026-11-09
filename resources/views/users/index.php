<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Manajemen Pengguna & Staf</h4>
        <div class="text-muted small">Kelola akun staf, hak akses role, dan pembatasan cabang</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/roles') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-shield-check"></i>
            <span>Kelola Role & Izin</span>
        </a>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Nama & Username</th>
                        <th>Kontak</th>
                        <th>Role / Jabatan</th>
                        <th>Cabang Utama</th>
                        <th>Status</th>
                        <th>Login Terakhir</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= e($u['name']) ?></div>
                                    <div class="text-muted small">@<?= e($u['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small"><?= e($u['email'] ?: '-') ?></div>
                            <div class="text-muted small"><?= e($u['phone'] ?: '-') ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= e($u['role_name']) ?></span>
                        </td>
                        <td>
                            <?php if ($u['branch_name']): ?>
                                <span class="badge bg-light text-dark border"><?= e($u['branch_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Semua Cabang (Global)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $u['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= format_date($u['last_login_at']) ?>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Edit User Modal -->
                    <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= url('/users/' . $u['id']) ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Pengguna: <?= e($u['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Username</label>
                                            <input type="text" class="form-control" value="<?= e($u['username']) ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                                            <input type="text" class="form-control" name="name" value="<?= e($u['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Role Pengguna</label>
                                            <select class="form-select" name="role_id" required>
                                                <?php foreach ($roles as $r): ?>
                                                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $u['role_id'] ? 'selected' : '' ?>>
                                                        <?= e($r['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Cabang Utama</label>
                                            <select class="form-select" name="branch_id">
                                                <option value="">-- Akses Semua Cabang (Global) --</option>
                                                <?php foreach ($branches as $br): ?>
                                                    <option value="<?= $br['id'] ?>" <?= $br['id'] == $u['branch_id'] ? 'selected' : '' ?>>
                                                        <?= e($br['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?= e($u['email']) ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                                            <input type="text" class="form-control" name="phone" value="<?= e($u['phone']) ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Ganti Kata Sandi (Kosongkan bila tidak diubah)</label>
                                            <input type="password" class="form-control" name="password" placeholder="Minimal 6 karakter">
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="userActive<?= $u['id'] ?>" value="1" <?= $u['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="userActive<?= $u['id'] ?>">Akun Aktif</label>
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

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/users') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username (Unik, huruf kecil tanpa spasi)</label>
                        <input type="text" class="form-control" name="username" placeholder="misal: kasir.dago" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="name" placeholder="Nama staf" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Pengguna</label>
                        <select class="form-select" name="role_id" required>
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cabang Penugasan</label>
                        <select class="form-select" name="branch_id">
                            <option value="">-- Akses Semua Cabang (Global) --</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata Sandi Awal</label>
                        <input type="password" class="form-control" name="password" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@simlaundry.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                        <input type="text" class="form-control" name="phone" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>
