<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-receipt text-primary me-2"></i>Riwayat & Status Pesanan Saya</h4>
            <p class="text-muted small mb-0">Lacak perkembangan cucian, bayar via QRIS, dan berikan ulasan kepuasan layanan.</p>
        </div>
        <a href="<?= url('/portal/create-order') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Pesan Penjemputan
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Nota</th>
                    <th>Cabang</th>
                    <th>Tanggal Pesan</th>
                    <th>Berat / Pcs</th>
                    <th>Total Tagihan</th>
                    <th>Status Pembayaran</th>
                    <th>Status Cucian</th>
                    <th>Ulasan / Rating</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada riwayat transaksi cucian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark font-monospace"><?= e($o['order_no']) ?></span>
                            </td>
                            <td><span class="badge bg-light text-secondary border"><?= e($o['branch_name']) ?></span></td>
                            <td class="small"><?= format_date($o['created_at'], 'd M Y') ?></td>
                            <td class="small">
                                <?= (float)$o['total_weight'] > 0 ? (float)$o['total_weight'] . ' Kg' : '' ?>
                                <?= (int)$o['total_pieces'] > 0 ? (int)$o['total_pieces'] . ' Pcs' : '' ?>
                            </td>
                            <td class="fw-bold"><?= format_rupiah($o['grand_total']) ?></td>
                            <td>
                                <?php if ($o['payment_status'] === 'paid'): ?>
                                    <span class="badge bg-success-subtle text-success">Lunas</span>
                                <?php elseif ($o['payment_status'] === 'partial'): ?>
                                    <span class="badge bg-info-subtle text-info">DP (Sisa <?= format_rupiah($o['balance_due']) ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning">Belum Bayar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge = match($o['production_status']) {
                                    'completed' => 'bg-success text-white',
                                    'ready' => 'bg-info text-white',
                                    'delivering' => 'bg-primary text-white',
                                    'in_process', 'qc', 'packaging' => 'bg-warning text-dark',
                                    'cancelled' => 'bg-danger text-white',
                                    default => 'bg-secondary text-white'
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= strtoupper($o['production_status']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($o['rating'])): ?>
                                    <div class="text-warning small">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= (int)$o['rating'] ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if (!empty($o['review_comment'])): ?>
                                        <div class="small text-muted fst-italic text-truncate" style="max-width: 150px;"><?= e($o['review_comment']) ?></div>
                                    <?php endif; ?>
                                <?php elseif ($o['production_status'] === 'completed'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2" onclick="openReviewModal(<?= $o['id'] ?>, '<?= e($o['order_no']) ?>')">
                                        <i class="bi bi-star me-1"></i> Beri Rating
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/track/' . $o['tracking_token']) ?>" target="_blank" class="btn btn-outline-secondary">
                                        <i class="bi bi-qr-code"></i> Lacak
                                    </a>
                                    <?php if ($o['payment_status'] !== 'paid'): ?>
                                        <button type="button" class="btn btn-success" onclick="openPayModal(<?= $o['id'] ?>, '<?= e($o['order_no']) ?>', <?= (int)$o['balance_due'] ?>)">
                                            Bayar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Review -->
<div class="modal fade" id="modalReview" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/portal/review') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" id="reviewOrderId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-star-fill text-warning me-2"></i>Beri Rating & Ulasan Kepuasan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Nomor Nota Pesanan</label>
                    <input type="text" id="reviewOrderNo" class="form-control" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Rating Kepuasan</label>
                    <select name="rating" class="form-select" required>
                        <option value="5">⭐⭐⭐⭐⭐ Sangat Puas (5 Bintang)</option>
                        <option value="4">⭐⭐⭐⭐ Puas (4 Bintang)</option>
                        <option value="3">⭐⭐⭐ Cukup (3 Bintang)</option>
                        <option value="2">⭐⭐ Kurang Puas (2 Bintang)</option>
                        <option value="1">⭐ Kecewa (1 Bintang)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Komentar / Ulasan</label>
                    <textarea name="comment" class="form-control" rows="3" placeholder="Bagaimana hasil cucian, keharuman, ketepatan waktu kurir kami?"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Kirim Ulasan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal QRIS Payment (Shared) -->
<div class="modal fade" id="modalPayQris" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Pembayaran QRIS Realtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <h6 class="text-muted small mb-1">Nomor Nota: <span id="qrisOrderNo" class="fw-bold text-dark"></span></h6>
                <h4 class="fw-bold text-primary mb-3" id="qrisAmountText">Rp 0</h4>
                <div id="qrisContainer" class="p-3 bg-white border d-inline-block rounded shadow-sm mb-3">
                    <div id="qrcodeDisplay"></div>
                </div>
                <p class="small text-muted mb-0">Scan kode QR di atas menggunakan BCA Mobile, Livin, GoPay, OVO, atau aplikasi QRIS lainnya.</p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Cek Status Pembayaran</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
function openReviewModal(orderId, orderNo) {
    document.getElementById('reviewOrderId').value = orderId;
    document.getElementById('reviewOrderNo').value = orderNo;
    new bootstrap.Modal(document.getElementById('modalReview')).show();
}

function openPayModal(orderId, orderNo, amount) {
    document.getElementById('qrisOrderNo').innerText = orderNo;
    document.getElementById('qrisAmountText').innerText = 'Rp ' + amount.toLocaleString('id-ID');

    const qrDiv = document.getElementById('qrcodeDisplay');
    qrDiv.innerHTML = '<div class="spinner-border text-primary my-4" role="status"></div>';
    new bootstrap.Modal(document.getElementById('modalPayQris')).show();

    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('_csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url('/portal/pay-qris') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        qrDiv.innerHTML = '';
        if (data.success && data.qr_string) {
            new QRCode(qrDiv, {
                text: data.qr_string,
                width: 200,
                height: 200,
                colorDark: "#102A43",
                colorLight: "#FFFFFF",
                correctLevel: QRCode.CorrectLevel.M
            });
        } else {
            qrDiv.innerHTML = '<div class="alert alert-danger py-2 small">Gagal memuat QRIS.</div>';
        }
    })
    .catch(() => {
        qrDiv.innerHTML = '<div class="alert alert-danger py-2 small">Gagal menghubungi server gateway.</div>';
    });
}
</script>
