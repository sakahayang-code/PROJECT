<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Rekap Absensi Karyawan</h4>
        <div class="text-muted small">Catatan kehadiran, jam masuk, jam pulang, dan status presensi</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/employees') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Kembali ke Karyawan</span>
        </a>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#recordAttendanceModal">
            <i class="bi bi-check2-circle"></i>
            <span>Catat Kehadiran</span>
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/employees/attendance') ?>" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text small"><i class="bi bi-calendar"></i></span>
                    <input type="date" class="form-control" name="date" value="<?= e($selectedDate) ?>">
                </div>
            </div>
            <?php if (\App\Core\Auth::hasGlobalAccess()): ?>
            <div class="col-md-4">
                <select class="form-select" name="branch_id">
                    <option value="0">-- Semua Cabang --</option>
                    <?php foreach ($branches as $br): ?>
                        <option value="<?= $br['id'] ?>" <?= (int)$selectedBranch === (int)$br['id'] ? 'selected' : '' ?>>
                            <?= e($br['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Posisi</th>
                        <th>Cabang</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada rekaman absensi pada tanggal ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $rec): ?>
                        <tr>
                            <td class="fw-bold"><?= e($rec['employee_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($rec['position']) ?></span></td>
                            <td><?= e($rec['branch_name']) ?></td>
                            <td class="fw-semibold text-primary"><?= e($rec['clock_in'] ?: '-') ?></td>
                            <td class="fw-semibold text-muted"><?= e($rec['clock_out'] ?: '-') ?></td>
                            <td>
                                <?php
                                    $badge = 'bg-secondary';
                                    if ($rec['status'] === 'present') $badge = 'bg-success';
                                    elseif ($rec['status'] === 'late') $badge = 'bg-warning';
                                    elseif ($rec['status'] === 'absent') $badge = 'bg-danger';
                                    elseif ($rec['status'] === 'leave') $badge = 'bg-info';
                                ?>
                                <span class="badge <?= $badge ?>">
                                    <?= ucfirst($rec['status']) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= e($rec['notes'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Catat Presensi -->
<div class="modal fade" id="recordAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/employees/attendance') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Catat Presensi Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Karyawan</label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">-- Pilih Karyawan --</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= e($e['name']) ?> (<?= e($e['position']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal</label>
                        <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jam Masuk</label>
                            <input type="time" class="form-control" name="clock_in" value="<?= date('H:i') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jam Keluar</label>
                            <input type="time" class="form-control" name="clock_out">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Kehadiran</label>
                        <select class="form-select" name="status">
                            <option value="present">Hadir (Tepat Waktu)</option>
                            <option value="late">Hadir (Terlambat)</option>
                            <option value="leave">Izin / Sakit / Cuti</option>
                            <option value="absent">Alpha / Tidak Hadir</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Catatan opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Presensi</button>
                </div>
            </form>
        </div>
    </div>
</div>
