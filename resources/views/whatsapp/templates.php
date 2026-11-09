<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-file-text text-primary me-2"></i>Template Pesan Notifikasi WhatsApp</h4>
            <p class="text-muted small mb-0">Sesuaikan teks pesan otomatis yang dikirimkan ke pelanggan pada setiap status transaksi dan produksi.</p>
        </div>
        <a href="<?= url('/whatsapp') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Antrean</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="row g-3">
            <?php foreach ($templates as $tmpl): ?>
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= e($tmpl['title']) ?></h6>
                                <span class="badge bg-secondary-subtle text-secondary small font-monospace">Event: <?= e($tmpl['trigger_event']) ?></span>
                            </div>
                            <span class="badge <?= $tmpl['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                <?= $tmpl['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-light rounded font-monospace small mb-3 text-break" style="white-space: pre-wrap;"><?= e($tmpl['message_template']) ?></div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditTemplateModal(<?= json_encode($tmpl) ?>)'>
                                <i class="bi bi-pencil me-1"></i> Edit Template Pesan
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cheatsheet Variables -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-info-circle me-2 text-info"></i>Variabel Tag Tersedia</h6>
            </div>
            <div class="card-body small">
                <p class="text-muted mb-3">Gunakan tag dalam kurung kurawal ganda untuk menyisipkan data pelanggan atau pesanan secara otomatis:</p>
                <ul class="list-group list-group-flush mb-0">
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{customer_name}</code>
                        <span class="text-muted">Nama Pelanggan</span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{order_no}</code>
                        <span class="text-muted">Nomor Nota Pesanan</span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{grand_total}</code>
                        <span class="text-muted">Total Tagihan (Rp)</span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{balance_due}</code>
                        <span class="text-muted">Sisa Belum Bayar (Rp)</span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{branch_name}</code>
                        <span class="text-muted">Nama Cabang Laundry</span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{est_completion}</code>
                        <span class="text-muted">Estimasi Selesai</span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <code>{tracking_link}</code>
                        <span class="text-muted">Link Pelacakan QR</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Template -->
<div class="modal fade" id="modalEditTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/whatsapp/templates/update') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="template_id" id="editTemplateId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Template Pesan WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Judul Template</label>
                    <input type="text" id="editTemplateTitle" class="form-control" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Isi Pesan Template</label>
                    <textarea name="message_template" id="editTemplateBody" class="form-control font-monospace" rows="8" required></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editTemplateActive">
                        <label class="form-check-label small fw-bold" for="editTemplateActive">Aktifkan Pengiriman Pesan Ini</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Template</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditTemplateModal(t) {
    document.getElementById('editTemplateId').value = t.id;
    document.getElementById('editTemplateTitle').value = t.title + ' (' + t.trigger_event + ')';
    document.getElementById('editTemplateBody').value = t.message_template;
    document.getElementById('editTemplateActive').checked = (t.is_active == 1);
    new bootstrap.Modal(document.getElementById('modalEditTemplate')).show();
}
</script>
