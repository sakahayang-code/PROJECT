<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-wrench-adjustable text-primary me-2"></i>Jadwal & Riwayat Servis Mesin</h4>
            <p class="text-muted small mb-0">Catat pemeliharaan rutin berkala, penggantian spare part, dan log servis perbaikan teknisi.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/machines') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Armada Mesin</a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateMaintenance">
                <i class="bi bi-calendar-plus me-1"></i> Jadwalkan Servis
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-tools me-2 text-primary"></i>Riwayat Pemeliharaan & Perbaikan</h6>
        <span class="badge bg-light text-dark border"><?= count($maintenances) ?> riwayat</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mesin</th>
                    <th>Cabang</th>
                    <th>Judul Servis</th>
                    <th>Tipe</th>
                    <th>Tanggal Jadwal</th>
                    <th>Selesai Pada</th>
                    <th>Teknisi</th>
                    <th>Biaya Servis</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($maintenances)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">Belum ada riwayat pemeliharaan mesin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($maintenances as $m): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($m['machine_name']) ?></div>
                                <span class="badge bg-light text-dark border font-monospace"><?= e($m['machine_code']) ?></span>
                            </td>
                            <td><span class="badge bg-light text-secondary border"><?= e($m['branch_name']) ?></span></td>
                            <td>
                                <div class="fw-semibold"><?= e($m['title']) ?></div>
                                <?php if (!empty($m['notes'])): ?>
                                    <div class="small text-muted"><?= e($m['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $m['maintenance_type'] === 'repair' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info' ?>">
                                    <?= $m['maintenance_type'] === 'repair' ? 'Perbaikan Kerusakan' : 'Servis Rutin' ?>
                                </span>
                            </td>
                            <td class="small"><?= format_date($m['scheduled_date'], 'd M Y') ?></td>
                            <td class="small"><?= $m['completed_date'] ? format_date($m['completed_date'], 'd M Y') : '<span class="text-muted">-</span>' ?></td>
                            <td class="small"><?= e($m['technician_name'] ?: 'Internal Staf') ?></td>
                            <td class="fw-semibold text-danger"><?= format_rupiah($m['cost']) ?></td>
                            <td>
                                <?php
                                $badge = match($m['status']) {
                                    'completed' => 'bg-success-subtle text-success',
                                    'scheduled' => 'bg-warning-subtle text-warning',
                                    'in_progress' => 'bg-primary-subtle text-primary',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= strtoupper(str_replace('_', ' ', $m['status'])) ?></span>
                            </td>
                            <td class="text-end">
                                <?php if ($m['status'] !== 'completed'): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="openCompleteMaintenanceModal(<?= $m['id'] ?>, '<?= e($m['title']) ?>', <?= (int)$m['cost'] ?>)">
                                        <i class="bi bi-check2-circle me-1"></i> Selesaikan
                                    </button>
                                <?php else: ?>
                                    <span class="text-success small"><i class="bi bi-check2-all me-1"></i>Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Maintenance -->
<div class="modal fade" id="modalCreateMaintenance" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/machines/maintenance/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i>Jadwalkan Servis / Pemeliharaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Pilih Mesin</label>
                    <select name="machine_id" class="form-select" required>
                        <option value="">-- Pilih Mesin --</option>
                        <?php foreach ($machines as $mc): ?>
                            <option value="<?= $mc['id'] ?>"><?= e($mc['code'] . ' - ' . $mc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jenis Pemeliharaan</label>
                    <select name="maintenance_type" class="form-select" required>
                        <option value="routine">Pemeliharaan Rutin Berkala</option>
                        <option value="repair">Perbaikan Kerusakan (Repair)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Judul / Pekerjaan Servis</label>
                    <input type="text" name="title" class="form-control" placeholder="Contoh: Pembersihan filter drum & pelumasan bearing" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tanggal Terjadwal</label>
                    <input type="date" name="scheduled_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Estimasi Biaya Servis (Rp)</label>
                    <input type="text" name="cost" class="form-control" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Nama Teknisi / Vendor Servis</label>
                    <input type="text" name="technician_name" class="form-control" placeholder="Contoh: Pak Joko (Teknisi Mesin Cuci)">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Catatan Tambahan</label>
                    <input type="text" name="notes" class="form-control" placeholder="Instruksi khusus untuk teknisi">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Complete Maintenance -->
<div class="modal fade" id="modalCompleteMaintenance" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/machines/maintenance/complete') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="maintenance_id" id="completeMaintId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Konfirmasi Servis Selesai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Pekerjaan</label>
                    <input type="text" id="completeMaintTitle" class="form-control" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Total Biaya Riil Servis (Rp)</label>
                    <input type="text" name="cost" id="completeMaintCost" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Tindakan Yang Dilakukan</label>
                    <textarea name="action_taken" class="form-control" rows="2" placeholder="Ganti v-belt, bersihkan saluran pembuangan air..." required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Spare Part Yang Diganti (Opsional)</label>
                    <input type="text" name="parts_replaced" class="form-control" placeholder="Misal: V-Belt A-38, Solenoid valve">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Konfirmasi Selesai & Aktifkan Mesin</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCompleteMaintenanceModal(id, title, cost) {
    document.getElementById('completeMaintId').value = id;
    document.getElementById('completeMaintTitle').value = title;
    document.getElementById('completeMaintCost').value = cost;
    new bootstrap.Modal(document.getElementById('modalCompleteMaintenance')).show();
}
</script>
