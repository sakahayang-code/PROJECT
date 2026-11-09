<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <a href="<?= url('/orders') ?>" class="text-decoration-none small text-muted mb-1 d-inline-block">&laquo; Kembali ke Daftar Pesanan</a>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Nota: <?= e($order['order_no']) ?></h4>
            <span class="badge bg-primary text-uppercase"><?= e($order['order_type']) ?></span>
            <span class="badge <?= $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?= strtoupper($order['payment_status']) ?>
            </span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/orders/' . $order['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-printer"></i>
            <span>Cetak Nota</span>
        </a>
        <a href="<?= url('/track/' . $order['qr_token']) ?>" target="_blank" class="btn btn-outline-info d-flex align-items-center gap-1">
            <i class="bi bi-qr-code-scan"></i>
            <span>Lacak Status (QR)</span>
        </a>
        <?php if ($order['production_status'] !== 'cancelled'): ?>
        <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
            <i class="bi bi-x-circle"></i>
            <span>Batalkan Pesanan</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($order['production_status'] === 'cancelled'): ?>
<div class="alert alert-danger shadow-sm mb-4">
    <div class="fw-bold fs-6"><i class="bi bi-exclamation-triangle-fill me-2"></i>Pesanan Ini Telah Dibatalkan</div>
    <div class="small mt-1">Alasan: <em><?= e($order['cancellation_reason'] ?: 'Tidak ada alasan dicatat') ?></em></div>
    <?php if ($cancellation): ?>
        <div class="small text-muted mt-1">
            Dibatalkan oleh: <?= e($cancellation['requested_by_name'] ?? 'Staff') ?> &bull; 
            Refund Pengembalian Dana: <strong><?= format_rupiah($cancellation['refund_amount']) ?></strong> &bull;
            Waktu: <?= format_date($cancellation['created_at']) ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Order Items & Pricing Breakdown -->
    <div class="col-lg-8">
        <!-- Order Items -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">Rincian Layanan Cucian</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Layanan</th>
                                <th class="text-center">Kuantitas / Berat</th>
                                <th class="text-end">Tarif Snapshot</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= e($item['service_name']) ?></div>
                                    <?php if (!empty($item['notes'])): ?>
                                        <div class="small text-muted"><?= e($item['notes']) ?></div>
                                    <?php endif; ?>

                                    <!-- Garment details if any -->
                                    <?php if (!empty($item['details'])): ?>
                                    <div class="mt-1 ps-2 border-start border-2 border-primary small text-muted">
                                        <?php foreach ($item['details'] as $det): ?>
                                            <div>
                                                &bull; <?= e($det['item_name']) ?> (<?= e($det['color'] ?: 'Warna biasa') ?>)
                                                <?php if (!empty($det['defect_notes'])): ?>
                                                    - <span class="text-danger fw-semibold">Defek: <?= e($det['defect_notes']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['unit_type'] === 'kg'): ?>
                                        <span class="fw-bold"><?= $item['weight'] ?></span> kg
                                    <?php else: ?>
                                        <span class="fw-bold"><?= $item['quantity'] ?></span> pcs
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-muted small">
                                    <?= format_rupiah($item['unit_price']) ?>
                                </td>
                                <td class="text-end fw-bold"><?= format_rupiah($item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light small">
                            <tr>
                                <td colspan="3" class="text-end">Subtotal:</td>
                                <td class="text-end fw-semibold"><?= format_rupiah($order['subtotal']) ?></td>
                            </tr>
                            <?php if ($order['addon_total'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Layanan Tambahan (Addon):</td>
                                <td class="text-end fw-semibold text-primary">+<?= format_rupiah($order['addon_total']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($order['discount_total'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end text-success">Diskon Membership:</td>
                                <td class="text-end fw-semibold text-success">-<?= format_rupiah($order['discount_total']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($order['voucher_discount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end text-success">Potongan Voucher (<?= e($order['voucher_code']) ?>):</td>
                                <td class="text-end fw-semibold text-success">-<?= format_rupiah($order['voucher_discount']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="fs-6">
                                <td colspan="3" class="text-end fw-bold">TOTAL TAGIHAN:</td>
                                <td class="text-end fw-bold text-primary"><?= format_rupiah($order['grand_total']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">Total Pembayaran:</td>
                                <td class="text-end fw-semibold text-success"><?= format_rupiah($order['paid_amount']) ?></td>
                            </tr>
                            <tr class="fw-bold <?= $order['balance_due'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <td colspan="3" class="text-end">Sisa Tagihan:</td>
                                <td class="text-end"><?= $order['balance_due'] > 0 ? format_rupiah($order['balance_due']) : 'LUNAS' ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments History -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">Riwayat Penerimaan Pembayaran</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>No. Pembayaran</th>
                                <th>Metode</th>
                                <th>Waktu Bayar</th>
                                <th>Penerima</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada pembayaran dicatat.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($p['payment_no']) ?></td>
                                    <td><span class="badge bg-secondary"><?= e($p['method_name']) ?></span></td>
                                    <td><?= format_date($p['paid_at']) ?></td>
                                    <td><?= e($p['receiver_name'] ?: 'Kasir') ?></td>
                                    <td class="text-end fw-bold text-success"><?= format_rupiah($p['amount']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Customer & Production Workflow Status -->
    <div class="col-lg-4">
        <!-- Customer & Branch Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">Data Pelanggan & Outlet</div>
            <div class="card-body small">
                <div class="mb-3">
                    <div class="text-muted">Nama Pelanggan:</div>
                    <div class="fw-bold fs-6"><?= e($order['customer_name']) ?></div>
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $order['customer_phone']) ?>" target="_blank" class="text-success text-decoration-none">
                        <i class="bi bi-whatsapp me-1"></i><?= e($order['customer_phone']) ?>
                    </a>
                </div>
                <div class="mb-3">
                    <div class="text-muted">Alamat Pelanggan:</div>
                    <div><?= e($order['customer_address'] ?: '-') ?></div>
                </div>
                <hr>
                <div class="mb-2">
                    <div class="text-muted">Cabang Operasional:</div>
                    <div class="fw-semibold"><?= e($order['branch_name']) ?></div>
                    <div class="text-muted" style="font-size:0.75rem;"><?= e($order['branch_address']) ?></div>
                </div>
                <div>
                    <div class="text-muted">Kasir Pembuat Nota:</div>
                    <div class="fw-semibold"><?= e($order['cashier_name']) ?></div>
                </div>
            </div>
        </div>

        <!-- Production Stage Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">Status Tahapan Pengerjaan</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tahap Saat Ini:</span>
                    <span class="badge bg-primary text-uppercase"><?= e($productionJob['stage_name'] ?? $order['production_status']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Estimasi Selesai:</span>
                    <span class="fw-semibold"><?= format_date($order['est_completion_at']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Operator Penanggung Jawab:</span>
                    <span><?= e($productionJob['operator_name'] ?? 'Belum ditugaskan') ?></span>
                </div>
            </div>
        </div>

        <!-- Status History Timeline -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">Riwayat Status (Timeline)</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php foreach ($statusHistory as $hist): ?>
                    <li class="list-group-item d-flex align-items-start gap-2 py-2">
                        <i class="bi bi-clock-history text-primary fs-6 mt-1"></i>
                        <div>
                            <div class="fw-semibold">
                                <?= ucfirst($hist['to_status']) ?>
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">
                                <?= e($hist['user_name'] ?? 'Sistem') ?> &bull; <?= format_date($hist['created_at']) ?>
                            </div>
                            <?php if (!empty($hist['notes'])): ?>
                                <div class="text-secondary fst-italic" style="font-size:0.75rem;"><?= e($hist['notes']) ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Batalkan Pesanan -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/orders/' . $order['id'] . '/cancel') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Konfirmasi Pembatalan Pesanan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Tindakan pembatalan pesanan akan dicatat dalam audit trail. Jika terdapat refund pengembalian uang kasir, jurnal pembalik akuntansi otomatis dibuat.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Pembatalan (Wajib)</label>
                        <textarea class="form-control" name="reason" rows="2" placeholder="Contoh: Pelanggan salah membawa jenis kain, pesanan dibatalkan atas permintaan pelanggan..." required></textarea>
                    </div>
                    <?php if ($order['paid_amount'] > 0): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal Pengembalian Dana / Refund (Rp)</label>
                        <input type="number" class="form-control" name="refund_amount" value="<?= $order['paid_amount'] ?>" max="<?= $order['paid_amount'] ?>">
                        <div class="form-text small">Maksimal sebesar nominal yang sudah dibayar: <?= format_rupiah($order['paid_amount']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold">Ya, Batalkan Pesanan Ini</button>
                </div>
            </form>
        </div>
    </div>
</div>
