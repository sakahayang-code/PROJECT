<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Total Mesin Operasional</div>
            <div class="stat-value text-primary"><?= number_format($stats['total']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-cpu me-1"></i>Armada mesin aktif</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Mesin Standby (Idle)</div>
            <div class="stat-value text-success"><?= number_format($stats['idle']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-check-circle text-success me-1"></i>Siap digunakan</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Sedang Bekerja (In Use)</div>
            <div class="stat-value text-info"><?= number_format($stats['in_use']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-arrow-repeat text-info me-1"></i>Siklus produksi berjalan</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Perbaikan / Rusak</div>
            <div class="stat-value text-danger"><?= number_format($stats['maintenance']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-wrench text-danger me-1"></i>Perlu penanganan teknisi</div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/machines') ?>" class="row g-2 align-items-end">
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
                <label class="form-label small fw-bold">Tipe Mesin</label>
                <select name="type_id" class="form-select form-select-sm">
                    <option value="">Semua Tipe</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selectedType == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Status Operasional</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="idle" <?= $selectedStatus === 'idle' ? 'selected' : '' ?>>Siap Pakai (Idle)</option>
                    <option value="in_use" <?= $selectedStatus === 'in_use' ? 'selected' : '' ?>>Sedang Digunakan</option>
                    <option value="maintenance" <?= $selectedStatus === 'maintenance' ? 'selected' : '' ?>>Perawatan (Maintenance)</option>
                    <option value="offline" <?= $selectedStatus === 'offline' ? 'selected' : '' ?>>Mati / Offline</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="<?= url('/machines') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalCreateMachine">
                    <i class="bi bi-plus-circle me-1"></i> Mesin Baru
                </button>
                <a href="<?= url('/machines/maintenance') ?>" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-wrench me-1"></i> Jadwal Servis
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($machines)): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0 text-center py-5">
                <div class="card-body">
                    <i class="bi bi-cpu text-muted display-4 d-block mb-2"></i>
                    <h5 class="fw-bold text-muted">Tidak Ada Mesin Ditemukan</h5>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($machines as $m): ?>
            <?php
            $statusBadge = match($m['operational_status']) {
                'idle' => 'bg-success text-white',
                'in_use' => 'bg-primary text-white',
                'maintenance' => 'bg-warning text-dark',
                'offline' => 'bg-danger text-white',
                default => 'bg-secondary'
            };
            $conditionBadge = match($m['condition']) {
                'good' => 'text-success',
                'needs_repair' => 'text-warning',
                'damaged' => 'text-danger',
                default => 'text-muted'
            };
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-dark font-monospace"><?= e($m['code']) ?></span>
                        <span class="badge <?= $statusBadge ?>"><?= strtoupper(str_replace('_', ' ', $m['operational_status'])) ?></span>
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold text-dark mb-1"><?= e($m['name']) ?></h5>
                        <div class="small text-muted mb-3"><i class="bi bi-shop me-1"></i><?= e($m['branch_name']) ?> &bull; <?= e($m['type_name']) ?></div>

                        <div class="bg-light p-2 rounded small mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Merk & Model:</span>
                                <strong><?= e($m['brand'] ?: '-') ?> <?= e($m['model'] ?: '') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Kapasitas:</span>
                                <strong><?= (float)$m['capacity_kg'] ?> Kg</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total Siklus Cuci:</span>
                                <strong class="text-primary"><?= number_format((int)$m['total_cycles']) ?> siklus</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Kondisi Fisik:</span>
                                <strong class="<?= $conditionBadge ?>"><i class="bi bi-circle-fill me-1 small"></i><?= strtoupper(str_replace('_', ' ', $m['condition'])) ?></strong>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger flex-grow-1" onclick="openFaultModal(<?= $m['id'] ?>, '<?= e($m['name']) ?>')">
                                <i class="bi bi-exclamation-triangle me-1"></i> Lapor Rusak
                            </button>
                            <a href="<?= url('/machines/maintenance') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-wrench"></i> Servis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Create Machine -->
<div class="modal fade" id="modalCreateMachine" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/machines/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Mesin Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Cabang Penempatan</label>
                    <select name="branch_id" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tipe Mesin</label>
                    <select name="type_id" class="form-select" required>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Kode Mesin (Opsional)</label>
                    <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="Misal: WM-01 / DRY-02">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Mesin</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Mesin Cuci Front Loading 1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Merk Mesin</label>
                    <input type="text" name="brand" class="form-control" placeholder="LG / Speed Queen / Maytag">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Model / Tipe</label>
                    <input type="text" name="model" class="form-control" placeholder="Giant Pro / F1000">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Kapasitas (Kg)</label>
                    <input type="number" step="0.5" name="capacity_kg" class="form-control" value="10" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nomor Seri (Serial Number)</label>
                    <input type="text" name="serial_number" class="form-control" placeholder="S/N dari pabrikan">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tanggal Pembelian</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Mesin</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Report Fault -->
<div class="modal fade" id="modalReportFault" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/machines/fault') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="machine_id" id="faultMachineId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Laporkan Kerusakan Mesin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Mesin Yang Bermasalah</label>
                    <input type="text" id="faultMachineName" class="form-control" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Tingkat Keparahan (Severity)</label>
                    <select name="severity" class="form-select" required>
                        <option value="medium">Sedang (Bisa jalan namun ada kendala getar/bising)</option>
                        <option value="high">Tinggi (Sering mati mendadak / kebocoran air)</option>
                        <option value="critical">Kritis (Mati total / korsleting - Hentikan Operasional)</option>
                        <option value="low">Rendah (Pintu seret / tombol agak keras)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Keterangan Kerusakan / Gejala</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan kendala yang dialami secara rinci..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i> Kirim Laporan Kerusakan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFaultModal(id, name) {
    document.getElementById('faultMachineId').value = id;
    document.getElementById('faultMachineName').value = name;
    new bootstrap.Modal(document.getElementById('modalReportFault')).show();
}
</script>
