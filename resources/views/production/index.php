<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Antrean Produksi & Quality Control</h4>
        <div class="text-muted small">Pantauan tahapan cuci, pengeringan, setrika uap, pemeriksaan kualitas, dan packing</div>
    </div>
    <?php if (\App\Core\Auth::hasGlobalAccess()): ?>
    <div>
        <form method="GET" action="<?= url('/production') ?>" class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" name="branch_id" onchange="this.form.submit()">
                <option value="0">-- Semua Cabang --</option>
                <?php foreach ($branches as $br): ?>
                    <option value="<?= $br['id'] ?>" <?= (int)$selectedBranch === (int)$br['id'] ? 'selected' : '' ?>>
                        [<?= e($br['code']) ?>] <?= e($br['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Production Kanban Board Horizontal Scrolling -->
<div class="d-flex flex-nowrap gap-3 overflow-auto pb-4" style="min-height: calc(100vh - 220px);">
    <?php foreach ($stages as $stage): ?>
        <?php
            $stageJobs = array_filter($jobs, fn($j) => ($j['current_stage_id'] ?? 0) == $stage['id']);
        ?>
        <div class="card shadow-sm border flex-shrink-0" style="width: 320px; background-color: var(--lp-body-bg);">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 border-bottom">
                <div class="fw-bold small text-uppercase">
                    <span class="badge bg-primary me-1"><?= $stage['sort_order'] ?></span>
                    <?= e($stage['name']) ?>
                </div>
                <span class="badge bg-secondary rounded-pill"><?= count($stageJobs) ?></span>
            </div>
            <div class="card-body p-2 d-flex flex-column gap-2 overflow-auto" style="max-height: calc(100vh - 280px);">
                <?php if (empty($stageJobs)): ?>
                    <div class="text-center py-5 text-muted small">Tidak ada antrean.</div>
                <?php else: ?>
                    <?php foreach ($stageJobs as $job): ?>
                        <?php
                            $isOverdue = strtotime($job['est_completion_at']) < time();
                            $prioColor = 'bg-secondary';
                            if ($job['order_type'] === 'express') $prioColor = 'bg-warning text-dark';
                            elseif ($job['order_type'] === 'priority') $prioColor = 'bg-danger';
                        ?>
                        <div class="card shadow-sm border <?= $isOverdue ? 'border-danger border-2' : '' ?> bg-white p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <a href="<?= url('/orders/' . $job['order_id']) ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= e($job['order_no']) ?>
                                </a>
                                <span class="badge <?= $prioColor ?>" style="font-size:0.65rem;"><?= strtoupper($job['order_type']) ?></span>
                            </div>

                            <div class="fw-semibold small mb-1"><?= e($job['customer_name']) ?></div>

                            <div class="d-flex justify-content-between text-muted small mb-2" style="font-size:0.75rem;">
                                <span>
                                    <?= $job['total_weight'] > 0 ? $job['total_weight'] . ' kg' : '' ?>
                                    <?= $job['total_pieces'] > 0 ? ($job['total_weight'] > 0 ? ' &bull; ' : '') . $job['total_pieces'] . ' pcs' : '' ?>
                                </span>
                                <span><?= e($job['branch_code']) ?></span>
                            </div>

                            <!-- Overdue Alert -->
                            <?php if ($isOverdue): ?>
                                <div class="badge bg-danger-subtle text-danger mb-2 small text-start py-1">
                                    <i class="bi bi-clock-fill me-1"></i>Terlambat! Est: <?= format_date($job['est_completion_at'], 'd M H:i') ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small mb-2" style="font-size:0.75rem;">
                                    <i class="bi bi-calendar-event me-1"></i>Est: <?= format_date($job['est_completion_at'], 'd M H:i') ?>
                                </div>
                            <?php endif; ?>

                            <!-- Assigned Operator & Machine -->
                            <div class="bg-light p-2 rounded small mb-3" style="font-size:0.75rem;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Operator:</span>
                                    <strong class="text-dark"><?= e($job['operator_name'] ?: 'Belum ada') ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Mesin:</span>
                                    <strong class="text-dark"><?= e($job['machine_name'] ?: 'Belum ada') ?></strong>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-1">
                                <?php if ($stage['slug'] === 'qc'): ?>
                                    <!-- QC Check Button -->
                                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="openQcModal(<?= $job['id'] ?>, '<?= e($job['order_no']) ?>')">
                                        <i class="bi bi-check2-all me-1"></i> Input QC & Skoring
                                    </button>
                                <?php elseif ($job['status'] === 'waiting'): ?>
                                    <!-- Start Stage -->
                                    <form action="<?= url('/production/start') ?>" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                                            <i class="bi bi-play-fill me-1"></i> Mulai Tahap Ini
                                        </button>
                                    </form>
                                <?php elseif ($job['status'] === 'in_progress'): ?>
                                    <!-- Complete Stage -->
                                    <form action="<?= url('/production/complete') ?>" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success w-100 fw-semibold">
                                            <i class="bi bi-check-lg me-1"></i> Selesai & Lanjut Tahap
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Reassign Operator / Machine -->
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1" data-bs-toggle="modal" data-bs-target="#assignModal<?= $job['id'] ?>">
                                    <i class="bi bi-person-gear me-1"></i> Ubah Petugas / Mesin
                                </button>
                            </div>
                        </div>

                        <!-- Modal Tugaskan Operator & Mesin -->
                        <div class="modal fade" id="assignModal<?= $job['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="<?= url('/production/assign') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Penugasan: <?= e($job['order_no']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Pilih Operator Bertanggung Jawab</label>
                                                <select class="form-select" name="operator_id">
                                                    <option value="">-- Belum Ditugaskan --</option>
                                                    <?php foreach ($operators as $op): ?>
                                                        <option value="<?= $op['id'] ?>" <?= $op['id'] == $job['assigned_user_id'] ? 'selected' : '' ?>>
                                                            <?= e($op['name']) ?> (<?= e($op['role_name']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Pilih Mesin Operasional (Cuci / Pengering)</label>
                                                <select class="form-select" name="machine_id">
                                                    <option value="">-- Tanpa Mesin (Penyetrikaan / QC) --</option>
                                                    <?php foreach ($machines as $mc): ?>
                                                        <option value="<?= $mc['id'] ?>" <?= $mc['id'] == $job['machine_id'] ? 'selected' : '' ?>>
                                                            <?= e($mc['name']) ?> (Kapasitas <?= $mc['capacity_kg'] ?> kg) - [<?= e($mc['operational_status']) ?>]
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan Penugasan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Quality Control (QC) Rating -->
<div class="modal fade" id="qcModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/production/qc') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="job_id" id="qcJobId">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="qcModalTitle">Pemeriksaan Quality Control (QC)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Periksa standar kebersihan, wangi, kerapian, dan kelengkapan pakaian sebelum masuk tahap pengemasan & siap diserahkan kepada pelanggan.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">1. Kebersihan Noda & Cuci (1 - 5 Bintang)</label>
                        <select class="form-select" name="cleanliness_score">
                            <option value="5">5 - Sangat Bersih & Bebas Noda</option>
                            <option value="4">4 - Bersih Standar</option>
                            <option value="3">3 - Ada Noda Samar Bandel</option>
                            <option value="2">2 - Kurang Bersih (Perlu Cuci Ulang)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">2. Aroma & Wangi Parfum (1 - 5 Bintang)</label>
                        <select class="form-select" name="fragrance_score">
                            <option value="5">5 - Sangat Wangi & Segar</option>
                            <option value="4">4 - Wangi Standar</option>
                            <option value="3">3 - Sedikit Kurang Harum</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">3. Kerapian Setrika & Uap (1 - 5 Bintang)</label>
                        <select class="form-select" name="neatness_score">
                            <option value="5">5 - Sangat Rapi & Licin Sempurna</option>
                            <option value="4">4 - Rapi Standar</option>
                            <option value="3">3 - Masih Ada Sedikit Kerutan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">4. Kelengkapan Jumlah Pakaian (1 - 5 Bintang)</label>
                        <select class="form-select" name="completeness_score">
                            <option value="5">5 - Lengkap 100% Sesuai Rincian</option>
                            <option value="3">3 - Perlu Dihitung Ulang</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Defek / Pemeriksaan</label>
                        <textarea class="form-control" name="defect_notes" rows="2" placeholder="Catatan kancing copot bawaan, noda membandel yang tidak hilang..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_passed" id="qcPassCheck" value="1" checked>
                        <label class="form-check-label fw-bold small text-success" for="qcPassCheck">Lolos Pemeriksaan QC (Siap Dikemas)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold">Simpan Hasil QC & Lanjut</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openQcModal(jobId, orderNo) {
    document.getElementById('qcJobId').value = jobId;
    document.getElementById('qcModalTitle').innerText = 'Quality Control: ' + orderNo;
    new bootstrap.Modal(document.getElementById('qcModal')).show();
}
</script>
