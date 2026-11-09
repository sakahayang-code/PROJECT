<div class="row g-4 mb-4">
    <!-- Welcome Profile Banner -->
    <div class="col-12">
        <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #0B817C 0%, #13AFA8 100%);">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-8">
                        <span class="badge bg-white text-primary mb-2 px-3 py-1 fw-bold">
                            <i class="bi bi-award-fill me-1"></i> Member <?= e($customer['tier_name'] ?? 'Regular') ?>
                        </span>
                        <h3 class="fw-bold mb-1">Halo, <?= e($customer['name']) ?>!</h3>
                        <p class="mb-0 text-white-50">Selamat datang di SimLaundry Customer Portal. Pantau cucian dan pesan penjemputan dari rumah dengan mudah.</p>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <a href="<?= url('/portal/create-order') ?>" class="btn btn-light text-primary fw-bold px-4 py-2 shadow-sm">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Pesan Penjemputan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Poin Loyalitas Saya</div>
                    <div class="stat-value text-primary"><?= number_format((int)$customer['points_balance']) ?> Pts</div>
                    <div class="small text-muted mt-1"><i class="bi bi-gift text-primary me-1"></i>Tukarkan diskon saat POS</div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-star-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Saldo Deposit / Dompet</div>
                    <div class="stat-value text-success"><?= format_rupiah($customer['deposit_balance']) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-wallet2 text-success me-1"></i>Bayar cepat tanpa uang pas</div>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-wallet-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Pesanan Sedang Aktif</div>
                    <div class="stat-value text-warning"><?= count($activeOrders) ?> Pesanan</div>
                    <div class="small text-muted mt-1"><i class="bi bi-hourglass-split text-warning me-1"></i>Dalam proses laundry</div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-water fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Orders Section -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Status Cucian Aktif Anda</h6>
                <a href="<?= url('/portal/my-orders') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua Riwayat</a>
            </div>
            <div class="card-body">
                <?php if (empty($activeOrders)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle display-4 d-block text-success mb-2"></i>
                        <h5 class="fw-bold text-dark">Tidak Ada Cucian Sedang Diproses</h5>
                        <p class="small text-muted mb-3">Semua cucian Anda telah selesai atau Anda belum melakukan pesanan baru.</p>
                        <a href="<?= url('/portal/create-order') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Minta Jemput Pakaian Sekarang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($activeOrders as $ao): ?>
                            <?php
                            $stepIndex = match($ao['production_status']) {
                                'received', 'queued' => 1,
                                'in_process' => 2,
                                'qc' => 3,
                                'packaging' => 4,
                                'ready' => 5,
                                'delivering' => 6,
                                default => 1
                            };
                            $pct = round(($stepIndex / 6) * 100);
                            ?>
                            <div class="col-12">
                                <div class="card border p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="badge bg-dark font-monospace"><?= e($ao['order_no']) ?></span>
                                            <span class="small text-muted ms-2"><i class="bi bi-shop me-1"></i><?= e($ao['branch_name']) ?></span>
                                        </div>
                                        <a href="<?= url('/track/' . $ao['tracking_token']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-qr-code me-1"></i> Lacak QR
                                        </a>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                        <span>Status Produksi: <strong class="text-primary"><?= strtoupper($ao['production_status']) ?></strong></span>
                                        <span>Estimasi Selesai: <strong><?= format_date($ao['est_completion_at']) ?></strong></span>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: <?= $pct ?>%"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                        <div>
                                            <span class="small text-muted">Total Tagihan:</span>
                                            <span class="fw-bold text-dark ms-1"><?= format_rupiah($ao['grand_total']) ?></span>
                                            <?php if ($ao['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success-subtle text-success ms-2">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning ms-2">Sisa: <?= format_rupiah($ao['balance_due']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($ao['payment_status'] !== 'paid'): ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="openPayModal(<?= $ao['id'] ?>, '<?= e($ao['order_no']) ?>', <?= (int)$ao['balance_due'] ?>)">
                                                <i class="bi bi-qr-code-scan me-1"></i> Bayar QRIS
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vouchers Sidebar -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-ticket-perforated me-2 text-danger"></i>Voucher Promo Tersedia</h6>
            </div>
            <div class="card-body">
                <?php if (empty($vouchers)): ?>
                    <p class="text-muted small text-center my-3">Belum ada promo baru saat ini.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($vouchers as $v): ?>
                            <div class="border rounded p-2 bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-dark font-monospace"><?= e($v['code']) ?></span>
                                    <div class="small fw-bold text-dark mt-1"><?= e($v['name']) ?></div>
                                    <div class="small text-muted">Hingga <?= format_date($v['end_date'], 'd M Y') ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-danger"><?= $v['type'] === 'percent' ? $v['value'] . '%' : format_rupiah($v['value']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Branches Info -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-shop me-2 text-primary"></i>Outlet & Cabang Laundry</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($branches as $br): ?>
                        <li class="list-group-item px-3 py-2">
                            <div class="fw-bold small"><?= e($br['name']) ?></div>
                            <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= e($br['address']) ?></div>
                            <div class="small text-success mt-1"><i class="bi bi-whatsapp me-1"></i><?= e($br['phone']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal QRIS Payment -->
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

                <p class="small text-muted mb-0">Buka aplikasi mobile banking atau e-wallet Anda (BCA, Mandiri, BRI, GoPay, OVO, Dana) dan scan kode QR di atas.</p>
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
let qrcodeObj = null;

function openPayModal(orderId, orderNo, amount) {
    document.getElementById('qrisOrderNo').innerText = orderNo;
    document.getElementById('qrisAmountText').innerText = 'Rp ' + amount.toLocaleString('id-ID');

    const qrDiv = document.getElementById('qrcodeDisplay');
    qrDiv.innerHTML = '<div class="spinner-border text-primary my-4" role="status"></div>';
    new bootstrap.Modal(document.getElementById('modalPayQris')).show();

    // Call payQris API
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
            qrDiv.innerHTML = '<div class="alert alert-danger py-2 small">Gagal memuat QRIS. Silakan hubungi kasir.</div>';
        }
    })
    .catch(() => {
        qrDiv.innerHTML = '<div class="alert alert-danger py-2 small">Gagal menghubungi gateway pembayaran.</div>';
    });
}
</script>
