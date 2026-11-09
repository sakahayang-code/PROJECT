<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Shift Kerja Karyawan</h4>
        <div class="text-muted small">Atur jadwal jam kerja dan pergantian shift per cabang</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/employees') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Kembali ke Karyawan</span>
        </a>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addShiftModal">
            <i class="bi bi-plus-circle"></i>
            <span>Tambah Shift</span>
        </button>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($shifts)): ?>
        <div class="col-12"><div class="alert alert-info">Belum ada shift terdaftar.</div></div>
    <?php else: ?>
        <?php foreach ($shifts as $s): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border">
                <div class="card-body">
                    <span class="badge bg-light text-dark border mb-2"><?= e($s['branch_name']) ?></span>
                    <h5 class="fw-bold text-primary mb-2"><?= e($s['name']) ?></h5>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-clock"></i>
                        <span><?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?> WIB</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/employees/shifts') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Shift Kerja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cabang</label>
                        <select class="form-select" name="branch_id" required>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Shift</label>
                        <input type="text" class="form-control" name="name" placeholder="Misal: Shift Pagi / Shift Sore" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold">Jam Mulai</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold">Jam Selesai</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
