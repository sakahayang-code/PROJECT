<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Data Karyawan & SDM</h4>
        <div class="text-muted small">Kelola data seluruh staf operasional, kasir, kurir, dan teknisi</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/employees/shifts') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-clock-history"></i>
            <span>Shift Kerja</span>
        </a>
        <a href="<?= url('/employees/attendance') ?>" class="btn btn-outline-info d-flex align-items-center gap-1">
            <i class="bi bi-calendar-check"></i>
            <span>Rekap Absensi</span>
        </a>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="bi bi-person-plus-fill"></i>
            <span>Tambah Karyawan</span>
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>NIK & Nama</th>
                        <th>Posisi / Jabatan</th>
                        <th>Cabang</th>
                        <th>Kontak</th>
                        <th>Gaji Pokok</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data karyawan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($emp['name']) ?></div>
                                <div class="text-muted small">NIK: <?= e($emp['nik'] ?: '-') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= e($emp['position']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($emp['branch_name']) ?></span>
                            </td>
                            <td>
                                <div class="small"><?= e($emp['phone'] ?: '-') ?></div>
                                <div class="text-muted small"><?= e($emp['email'] ?: '-') ?></div>
                            </td>
                            <td class="fw-semibold">
                                <?= format_rupiah($emp['base_salary']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $emp['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= ucfirst($emp['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEmpModal<?= $emp['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Edit Karyawan -->
                        <div class="modal fade" id="editEmpModal<?= $emp['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="<?= url('/employees/' . $emp['id']) ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Karyawan: <?= e($emp['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nama Lengkap</label>
                                                <input type="text" class="form-control" name="name" value="<?= e($emp['name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Cabang Penempatan</label>
                                                <select class="form-select" name="branch_id" required>
                                                    <?php foreach ($branches as $br): ?>
                                                        <option value="<?= $br['id'] ?>" <?= $br['id'] == $emp['branch_id'] ? 'selected' : '' ?>>
                                                            <?= e($br['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Jabatan / Posisi</label>
                                                <input type="text" class="form-control" name="position" value="<?= e($emp['position']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                                                <input type="text" class="form-control" name="phone" value="<?= e($emp['phone']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Gaji Pokok (Rp)</label>
                                                <input type="number" class="form-control" name="base_salary" value="<?= $emp['base_salary'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Status Karyawan</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?= $emp['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="inactive" <?= $emp['status'] === 'inactive' ? 'selected' : '' ?>>Non-Aktif</option>
                                                    <option value="leave" <?= $emp['status'] === 'leave' ? 'selected' : '' ?>>Cuti</option>
                                                </select>
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Karyawan -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/employees') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Karyawan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="name" placeholder="Nama karyawan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cabang Penempatan</label>
                        <select class="form-select" name="branch_id" required>
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jabatan / Posisi</label>
                        <input type="text" class="form-control" name="position" placeholder="Misal: Kasir, Petugas Cuci, Kurir" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp / Telepon</label>
                        <input type="text" class="form-control" name="phone" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="karyawan@laundrypro.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Gaji Pokok Bulanan (Rp)</label>
                        <input type="number" class="form-control" name="base_salary" placeholder="Contoh: 3000000" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Hubungkan dengan Akun Login Pengguna (Opsional)</label>
                        <select class="form-select" name="user_id">
                            <option value="">-- Tidak Terhubung ke Login Sistem --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (@<?= e($u['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Karyawan</button>
                </div>
            </form>
        </div>
    </div>
</div>
