<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bicycle text-primary me-2"></i>Daftar Tugas Pengantaran & Penjemputan</h4>
            <p class="text-muted small mb-0">Halaman operasional kurir untuk memantau rute dan serah terima pesanan.</p>
        </div>
        <form method="GET" action="<?= url('/delivery/tasks') ?>" class="d-flex gap-2">
            <input type="date" name="date" class="form-control form-control-sm" value="<?= e($selectedDate) ?>" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($tasks)): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0 text-center py-5">
                <div class="card-body">
                    <i class="bi bi-clipboard-check text-muted display-4 d-block mb-3"></i>
                    <h5 class="fw-bold text-muted">Tidak Ada Tugas Pengiriman</h5>
                    <p class="text-muted small">Semua tugas untuk tanggal ini telah selesai atau belum ditugaskan.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $t): ?>
            <?php
            $isDone = in_array($t['status'], ['picked_up', 'delivered']);
            $cardBorder = match($t['status']) {
                'on_the_way' => 'border-primary border-2',
                'picked_up', 'delivered' => 'border-success',
                'failed' => 'border-danger',
                default => 'border-0'
            };
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100 <?= $cardBorder ?>">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <?php if ($t['type'] === 'pickup'): ?>
                            <span class="badge bg-info-subtle text-info"><i class="bi bi-box-arrow-in-down me-1"></i>Jemput Pakaian</span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Antar Cucian</span>
                        <?php endif; ?>

                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-clock me-1"></i><?= e($t['schedule_time'] ?: 'Fleksibel') ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= e($t['customer_name']) ?></h6>
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($t['customer_phone']) ?></div>
                            </div>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $t['customer_phone']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-whatsapp"></i> Chat
                            </a>
                        </div>

                        <div class="p-2 bg-light rounded small mb-3">
                            <div class="fw-semibold text-secondary mb-1"><i class="bi bi-geo-alt me-1 text-danger"></i>Alamat:</div>
                            <div><?= e($t['address_text']) ?></div>
                            <?php if (!empty($t['notes'])): ?>
                                <div class="text-muted fst-italic mt-1"><i class="bi bi-info-circle me-1"></i>Catatan: <?= e($t['notes']) ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($t['order_no'])): ?>
                            <div class="d-flex justify-content-between small text-muted mb-2 border-top pt-2">
                                <span>Nota: <strong><?= e($t['order_no']) ?></strong></span>
                                <span>Tagihan: <strong class="text-primary"><?= format_rupiah($t['grand_total']) ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-muted">Status:</span>
                            <span class="badge bg-dark"><?= strtoupper($t['status']) ?></span>
                        </div>

                        <?php if (!$isDone): ?>
                            <div class="d-grid gap-2">
                                <?php if ($t['status'] === 'assigned'): ?>
                                    <form method="POST" action="<?= url('/delivery/status') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delivery_request_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="status" value="on_the_way">
                                        <input type="hidden" name="notes" value="Kurir mulai bergerak ke lokasi">
                                        <button type="submit" class="btn btn-primary w-100 btn-sm">
                                            <i class="bi bi-play-circle me-1"></i> Mulai Jalan (On The Way)
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn btn-success btn-sm" onclick="openProofModal(<?= $t['id'] ?>, '<?= $t['type'] ?>', '<?= e($t['customer_name']) ?>')">
                                    <i class="bi bi-camera me-1"></i> Selesai & Upload Bukti
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success py-2 mb-0 small text-center">
                                <i class="bi bi-check-circle-fill me-1"></i> Tugas telah diselesaikan
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Upload Proof (Shared) -->
<div class="modal fade" id="modalUploadProof" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/delivery/proof') ?>" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="delivery_request_id" id="proofRequestId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-camera me-2 text-success"></i>Upload Bukti Serah Terima</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Penerima / Penyerah Barang</label>
                    <input type="text" name="recipient_name" id="proofRecipientName" class="form-control" required placeholder="Contoh: Ibu Rina (Pelanggan Langsung)">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Foto Bukti Penerimaan (Opsional)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*" capture="environment">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Cucian diterima dalam kondisi baik..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Selesaikan Tugas</button>
            </div>
        </form>
    </div>
</div>

<script>
function openProofModal(id, type, custName) {
    document.getElementById('proofRequestId').value = id;
    document.getElementById('proofRecipientName').value = custName;
    new bootstrap.Modal(document.getElementById('modalUploadProof')).show();
}
</script>
