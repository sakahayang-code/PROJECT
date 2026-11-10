<div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); width: 42px; height: 42px;">
                <i class="bi bi-people-gear fs-5"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Pusat Kelola Akun</h4>
                <div class="text-muted small">Kelola seluruh akun staf operasional, akun portal pelanggan, dan hak akses sistem</div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('/roles') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-shield-check"></i>
            <span>Matriks Hak Akses</span>
        </a>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i>
            <span>Tambah Akun Staf</span>
        </button>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Akun Staf</span>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">Internal</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_staff'] ?></h3>
                <span class="text-success small fw-semibold" style="font-size: 0.78rem;">
                    <i class="bi bi-check-circle-fill"></i> <?= $stats['active_staff'] ?> Aktif
                </span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Akun Pelanggan</span>
                <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1 small">Portal PWA</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_customer_accounts'] ?></h3>
                <span class="text-success small fw-semibold" style="font-size: 0.78rem;">
                    <i class="bi bi-person-check-fill"></i> <?= $stats['active_customer_accounts'] ?> Aktif
                </span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Role Sistem</span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 small">RBAC</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h3 class="fw-bold mb-0 text-dark"><?= $stats['total_roles'] ?></h3>
                <span class="text-muted small" style="font-size: 0.78rem;">8 Hak Akses</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Status Keamanan</span>
                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small">Aman</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <h6 class="fw-bold mb-0 text-dark">Bcrypt (Cost 10)</h6>
            </div>
            <span class="text-muted small" style="font-size: 0.72rem;">CSRF + Proteksi Replay Aktif</span>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white border-bottom p-2 px-3">
        <ul class="nav nav-pills card-header-pills gap-1" id="accountTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-pane" type="button" role="tab">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Akun Staf & Manajemen (<?= count($users) ?>)</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer-pane" type="button" role="tab">
                    <i class="bi bi-phone-fill"></i>
                    <span>Akun Portal Pelanggan (<?= count($customerAccounts) ?>)</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-pane" type="button" role="tab">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Hak Akses Role (<?= count($roles) ?>)</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold d-flex align-items-center gap-2 rounded-pill px-3 py-2" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs-pane" type="button" role="tab">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Login</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="accountTabsContent">
        <!-- ================================================================= -->
        <!-- TAB 1: AKUN STAF & MANAJEMEN                                      -->
        <!-- ================================================================= -->
        <div class="tab-pane fade show active p-0" id="staff-pane" role="tabpanel">
            <div class="p-3 border-bottom bg-light-subtle d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-2">
                <div class="input-group" style="max-width: 380px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="staffSearchInput" class="form-control border-start-0" placeholder="Cari nama, username, atau email...">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small">
                        <i class="bi bi-key-fill me-1"></i> Sandi Default: <code>Admin@123</code>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="staffTable">
                    <thead class="table-light small">
                        <tr>
                            <th>Pengguna</th>
                            <th>Kontak</th>
                            <th>Role & Izin</th>
                            <th>Penugasan Cabang</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" 
                                         style="width: 40px; height: 40px; font-size: 1rem; background: <?= match($u['role_slug']) {
                                             'super_admin' => 'linear-gradient(135deg, #1E293B, #0F172A)',
                                             'owner' => 'linear-gradient(135deg, #F59E0B, #D97706)',
                                             'manager' => 'linear-gradient(135deg, #3B82F6, #1D4ED8)',
                                             'cashier' => 'linear-gradient(135deg, #10B981, #059669)',
                                             'production' => 'linear-gradient(135deg, #6366F1, #4F46E5)',
                                             'courier' => 'linear-gradient(135deg, #06B6D4, #0891B2)',
                                             'warehouse' => 'linear-gradient(135deg, #8B5CF6, #7C3AED)',
                                             'accountant' => 'linear-gradient(135deg, #EC4899, #DB2777)',
                                             default => '#64748B'
                                         } ?>;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($u['name']) ?></div>
                                        <div class="text-muted small">
                                            <span class="badge bg-light text-dark border">@<?= e($u['username']) ?></span>
                                            <?php if ($u['id'] == \App\Core\Auth::id()): ?>
                                                <span class="badge bg-primary-subtle text-primary ms-1">Anda</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= e($u['email'] ?: '-') ?></div>
                                <div class="text-muted small"><?= e($u['phone'] ?: '-') ?></div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 small <?= match($u['role_slug']) {
                                    'super_admin' => 'bg-dark text-white',
                                    'owner' => 'bg-warning text-dark',
                                    'manager' => 'bg-primary text-white',
                                    'cashier' => 'bg-success text-white',
                                    'production' => 'bg-info text-white',
                                    'courier' => 'bg-secondary text-white',
                                    'warehouse' => 'bg-indigo text-white',
                                    'accountant' => 'bg-danger text-white',
                                    default => 'bg-secondary'
                                } ?>">
                                    <?= e($u['role_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['branch_name']): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-shop me-1 text-primary"></i> <?= e($u['branch_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-globe me-1"></i> Semua Cabang</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 <?= $u['is_active'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?>">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= format_date($u['last_login_at']) ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" title="Salin Info Login" onclick="copyLoginCredentials('<?= e($u['username']) ?>', 'Admin@123', '<?= e($u['name']) ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" title="Reset Kata Sandi" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $u['id'] ?>">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" title="Edit Akun" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($u['id'] != \App\Core\Auth::id() && !($u['role_slug'] === 'super_admin' && $u['username'] === 'superadmin')): ?>
                                        <form action="<?= url('/users/' . $u['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin <?= $u['is_active'] ? 'menonaktifkan' : 'mengaktifkan kembali' ?> akun <?= e($u['name']) ?>?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $u['is_active'] ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>">
                                                <i class="bi <?= $u['is_active'] ? 'bi-person-x' : 'bi-person-check' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit User -->
                        <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/users/' . $u['id']) ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Akun Staf: <?= e($u['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Username Login</label>
                                                <input type="text" class="form-control bg-light" value="<?= e($u['username']) ?>" disabled>
                                                <div class="form-text small">Username unik akun staf tidak dapat diubah.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                                <input type="text" class="form-control" name="name" value="<?= e($u['name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Role / Hak Akses</label>
                                                <select class="form-select" name="role_id" required>
                                                    <?php foreach ($roles as $r): ?>
                                                        <option value="<?= $r['id'] ?>" <?= $r['id'] == $u['role_id'] ? 'selected' : '' ?>>
                                                            <?= e($r['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Cabang Penugasan Utama</label>
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
                                                <label class="form-label small fw-semibold">Alamat Email</label>
                                                <input type="email" class="form-control" name="email" value="<?= e($u['email']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nomor WhatsApp / Telepon</label>
                                                <input type="text" class="form-control" name="phone" value="<?= e($u['phone']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Ganti Kata Sandi (Opsional)</label>
                                                <input type="password" class="form-control" name="password" placeholder="Kosongkan jika kata sandi tidak diubah">
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="userActiveCheck<?= $u['id'] ?>" value="1" <?= $u['is_active'] ? 'checked' : '' ?>>
                                                <label class="form-check-label small fw-semibold" for="userActiveCheck<?= $u['id'] ?>">Akun Berstatus Aktif</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Reset Password Staf -->
                        <div class="modal fade" id="resetPassModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/users/' . $u['id'] . '/reset-password') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                    <i class="bi bi-key-fill"></i>
                                                </div>
                                                <h5 class="modal-title fw-bold">Reset Kata Sandi</h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small">Atur ulang kata sandi login untuk pengguna <strong><?= e($u['name']) ?></strong> (@<?= e($u['username']) ?>).</p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Kata Sandi Baru</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="new_password" id="inputResetPass<?= $u['id'] ?>" value="Admin@123" required minlength="6">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('inputResetPass<?= $u['id'] ?>').value = 'Admin@' + Math.floor(100 + Math.random() * 900)">
                                                        <i class="bi bi-shuffle"></i> Acak
                                                    </button>
                                                </div>
                                                <div class="form-text small">Minimal 6 karakter kombinasi huruf dan angka.</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning fw-semibold px-4 text-white">Konfirmasi Reset Sandi</button>
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

        <!-- ================================================================= -->
        <!-- TAB 2: AKUN PORTAL PELANGGAN                                      -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-0" id="customer-pane" role="tabpanel">
            <div class="p-3 border-bottom bg-light-subtle d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-2">
                <div class="input-group" style="max-width: 380px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="customerSearchInput" class="form-control border-start-0" placeholder="Cari nama, WhatsApp, atau username pelanggan...">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill small">
                        <i class="bi bi-box-arrow-up-right me-1"></i> URL Portal: <code>/portal/login</code>
                    </span>
                    <?php if (!empty($customersWithoutAccount)): ?>
                    <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCustAccModal">
                        <i class="bi bi-plus-circle"></i>
                        <span>Buat Akun Pelanggan</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="customerTable">
                    <thead class="table-light small">
                        <tr>
                            <th>Pelanggan</th>
                            <th>Login ID (WhatsApp & Username)</th>
                            <th>Level Membership</th>
                            <th>Deposit & Poin</th>
                            <th>Status Akun</th>
                            <th>Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerAccounts as $ca): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" 
                                         style="width: 38px; height: 38px; background: linear-gradient(135deg, #13AFA8, #0B817C);">
                                        <?= strtoupper(substr($ca['customer_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($ca['customer_name']) ?></div>
                                        <div class="text-muted small"><?= e($ca['customer_code']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><i class="bi bi-whatsapp text-success me-1"></i> <?= e($ca['customer_phone']) ?></div>
                                <div class="text-muted small">Username: <code><?= e($ca['username']) ?></code></div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 small" style="background-color: <?= $ca['badge_color'] ?: '#64748B' ?>; color: #fff;">
                                    <i class="bi bi-star-fill me-1"></i> <?= e($ca['tier_name'] ?: 'Regular') ?>
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">Saldo: <?= format_rupiah($ca['deposit_balance']) ?></div>
                                <div class="small text-muted"><?= number_format((int)$ca['points_balance']) ?> Poin</div>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 <?= $ca['is_active'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?>">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> <?= $ca['is_active'] ? 'Aktif' : 'Diblokir' ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= format_date($ca['created_at']) ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" title="Salin Kredensial Portal" onclick="copyLoginCredentials('<?= e($ca['customer_phone']) ?>', 'Admin@123', '<?= e($ca['customer_name']) ?>', '<?= url('/portal/login') ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" title="Reset Sandi Portal" data-bs-toggle="modal" data-bs-target="#resetCustPassModal<?= $ca['id'] ?>">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <form action="<?= url('/accounts/customers/' . $ca['id'] . '/toggle') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin <?= $ca['is_active'] ? 'memblokir' : 'mengaktifkan kembali' ?> akun portal <?= e($ca['customer_name']) ?>?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn <?= $ca['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $ca['is_active'] ? 'Blokir Akun' : 'Buka Blokir' ?>">
                                            <i class="bi <?= $ca['is_active'] ? 'bi-lock' : 'bi-unlock' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Reset Sandi Pelanggan -->
                        <div class="modal fade" id="resetCustPassModal<?= $ca['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/accounts/customers/' . $ca['id'] . '/reset-password') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                    <i class="bi bi-shield-lock-fill"></i>
                                                </div>
                                                <h5 class="modal-title fw-bold">Reset Kata Sandi Portal</h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small">Atur ulang kata sandi login portal pelanggan untuk <strong><?= e($ca['customer_name']) ?></strong> (No. WhatsApp: <?= e($ca['customer_phone']) ?>).</p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Kata Sandi Baru</label>
                                                <input type="text" class="form-control" name="new_password" value="Pelanggan@123" required minlength="6">
                                                <div class="form-text small">Default: <code>Pelanggan@123</code>. Anda dapat menggantinya sesuai permintaan pelanggan.</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning fw-semibold px-4 text-white">Reset Kata Sandi</button>
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

        <!-- ================================================================= -->
        <!-- TAB 3: HAK AKSES ROLE (RBAC MATRIX)                               -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-3" id="roles-pane" role="tabpanel">
            <div class="row g-3">
                <?php foreach ($roles as $r): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border shadow-sm rounded-4 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-light text-dark border">Role ID #<?= $r['id'] ?></span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill small"><?= $r['total_perms'] ?> Izin</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1"><?= e($r['name']) ?></h5>
                        <p class="text-muted small mb-3 flex-grow-1" style="min-height: 40px;"><?= e($r['description'] ?: 'Hak akses operasional sistem') ?></p>
                        <a href="<?= url('/roles/' . $r['id'] . '/permissions') ?>" class="btn btn-sm btn-outline-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-shield-lock"></i>
                            <span>Atur Izin Role</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 4: RIWAYAT LOGIN & KEAMANAN                                   -->
        <!-- ================================================================= -->
        <div class="tab-pane fade p-0" id="logs-pane" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu & Tanggal</th>
                            <th>Nama / Username Login</th>
                            <th>IP Address</th>
                            <th>User Agent (Perangkat / Browser)</th>
                            <th>Status Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($loginLogs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat catatan login.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($loginLogs as $log): ?>
                            <tr>
                                <td class="text-muted"><?= format_date($log['created_at']) ?></td>
                                <td class="fw-bold text-dark"><?= e($log['user_name'] ?: $log['username']) ?></td>
                                <td><code><?= e($log['ip_address']) ?></code></td>
                                <td class="text-muted text-truncate" style="max-width: 250px;" title="<?= e($log['user_agent']) ?>">
                                    <?= e($log['user_agent'] ?: 'Web Client') ?>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Berhasil
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1" title="<?= e($log['failure_reason']) ?>">
                                            <i class="bi bi-x-circle-fill me-1"></i> Gagal (<?= e($log['failure_reason']) ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun Staf Baru -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/users') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle p-2 text-white d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #4F46E5, #7C3AED); width: 36px; height: 36px;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h5 class="modal-title fw-bold">Tambah Akun Staf Baru</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username Login (Huruf kecil, tanpa spasi)</label>
                        <input type="text" class="form-control" name="username" placeholder="misal: kasir.dago" required pattern="[a-zA-Z0-9._-]+" title="Hanya huruf, angka, titik, strip">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="name" placeholder="Nama lengkap karyawan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role / Hak Akses</label>
                        <select class="form-select" name="role_id" required>
                            <option value="">-- Pilih Role Jabatan --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cabang Penugasan</label>
                        <select class="form-select" name="branch_id">
                            <option value="">-- Akses Semua Cabang (Pusat / Global) --</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata Sandi Awal</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="password" id="newStaffPassword" value="Admin@123" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('newStaffPassword').value = 'Admin@' + Math.floor(100 + Math.random() * 900)">
                                <i class="bi bi-shuffle"></i> Acak
                            </button>
                        </div>
                        <div class="form-text small">Default: <code>Admin@123</code>. Staf dapat mengubahnya setelah login.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Email (Opsional)</label>
                        <input type="email" class="form-control" name="email" placeholder="nama@simlaundry.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp / Telepon</label>
                        <input type="text" class="form-control" name="phone" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Akun Staf</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Buat Akun Portal Pelanggan Baru -->
<?php if (!empty($customersWithoutAccount)): ?>
<div class="modal fade" id="createCustAccModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/accounts/customers/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Buat Akun Portal Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Pelanggan</label>
                        <select class="form-select" name="customer_id" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($customersWithoutAccount as $cwo): ?>
                                <option value="<?= $cwo['id'] ?>"><?= e($cwo['name']) ?> (<?= e($cwo['whatsapp']) ?> - <?= e($cwo['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata Sandi Awal</label>
                        <input type="text" class="form-control" name="password" value="Pelanggan@123" required minlength="6">
                        <div class="form-text small">Default: <code>Pelanggan@123</code></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Buat Akun Portal</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Filter Pencarian Staf
document.getElementById('staffSearchInput')?.addEventListener('input', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#staffTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
});

// Filter Pencarian Pelanggan
document.getElementById('customerSearchInput')?.addEventListener('input', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#customerTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
});

// Salin Kredensial Login ke Clipboard
function copyLoginCredentials(identifier, password, name, customUrl = '') {
    const loginUrl = customUrl || (window.location.origin + '<?= url("/login") ?>');
    const text = `Halo Kak ${name},\nBerikut akun login SimLaundry Anda:\n- Login ID / Username: ${identifier}\n- Kata Sandi: ${password}\n- Link Login: ${loginUrl}\nMohon jaga kerahasiaan kata sandi Anda.`;
    navigator.clipboard.writeText(text).then(() => {
        if (window.SimLaundry && SimLaundry.toast) {
            SimLaundry.toast(`Kredensial login ${name} berhasil disalin ke clipboard!`, 'success');
        } else {
            alert(`Kredensial login ${name} berhasil disalin ke clipboard!`);
        }
    });
}
</script>
