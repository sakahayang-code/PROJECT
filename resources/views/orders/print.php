<div class="text-center mb-2">
    <h5 class="fw-bold mb-0 text-uppercase"><?= e($order['company_name'] ?: 'LAUNDRYPRO') ?></h5>
    <div style="font-size: 0.8em;"><?= e($order['branch_name']) ?></div>
    <div style="font-size: 0.75em;"><?= e($order['branch_address']) ?></div>
    <div style="font-size: 0.75em;">Telp/WA: <?= e($order['branch_phone']) ?></div>
</div>

<div style="border-top: 1px dashed #000; margin: 6px 0;"></div>

<div style="font-size: 0.8em;">
    <div class="d-flex justify-content-between">
        <span>No. Nota:</span>
        <span class="fw-bold"><?= e($order['order_no']) ?></span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Tanggal:</span>
        <span><?= format_date($order['created_at'], 'd/m/Y H:i') ?></span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Kasir:</span>
        <span><?= e($order['cashier_name']) ?></span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Pelanggan:</span>
        <span class="fw-bold"><?= e($order['customer_name']) ?></span>
    </div>
    <div class="d-flex justify-content-between">
        <span>WhatsApp:</span>
        <span><?= e($order['customer_phone']) ?></span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Layanan:</span>
        <span class="badge bg-dark text-white text-uppercase" style="font-size: 0.7em;"><?= e($order['order_type']) ?></span>
    </div>
</div>

<div style="border-top: 1px dashed #000; margin: 6px 0;"></div>

<!-- Items Table -->
<table class="w-100" style="font-size: 0.8em;">
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td colspan="2" class="fw-bold pt-1"><?= e($item['service_name']) ?></td>
        </tr>
        <tr>
            <td class="text-muted">
                <?php if ($item['unit_type'] === 'kg'): ?>
                    <?= $item['weight'] ?> kg &times; <?= format_rupiah($item['unit_price']) ?>
                <?php else: ?>
                    <?= $item['quantity'] ?> pcs &times; <?= format_rupiah($item['unit_price']) ?>
                <?php endif; ?>
            </td>
            <td class="text-end fw-semibold"><?= format_rupiah($item['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="border-top: 1px dashed #000; margin: 6px 0;"></div>

<!-- Totals -->
<div style="font-size: 0.8em;">
    <div class="d-flex justify-content-between">
        <span>Subtotal:</span>
        <span><?= format_rupiah($order['subtotal']) ?></span>
    </div>

    <?php if ($order['addon_total'] > 0): ?>
    <div class="d-flex justify-content-between">
        <span>Layanan Tambahan:</span>
        <span><?= format_rupiah($order['addon_total']) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($order['discount_total'] > 0): ?>
    <div class="d-flex justify-content-between text-success">
        <span>Diskon Member:</span>
        <span>-<?= format_rupiah($order['discount_total']) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($order['voucher_discount'] > 0): ?>
    <div class="d-flex justify-content-between text-success">
        <span>Voucher (<?= e($order['voucher_code']) ?>):</span>
        <span>-<?= format_rupiah($order['voucher_discount']) ?></span>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between fw-bold pt-1" style="font-size: 1.1em; border-top: 1px solid #000;">
        <span>TOTAL:</span>
        <span><?= format_rupiah($order['grand_total']) ?></span>
    </div>

    <div class="d-flex justify-content-between pt-1">
        <span>Sudah Dibayar:</span>
        <span class="fw-semibold"><?= format_rupiah($order['paid_amount']) ?></span>
    </div>

    <div class="d-flex justify-content-between fw-bold <?= $order['balance_due'] > 0 ? 'text-danger' : 'text-success' ?>">
        <span>Sisa Tagihan:</span>
        <span><?= $order['balance_due'] > 0 ? format_rupiah($order['balance_due']) : 'LUNAS' ?></span>
    </div>
</div>

<div style="border-top: 1px dashed #000; margin: 6px 0;"></div>

<div class="text-center" style="font-size: 0.75em;">
    <div class="fw-bold">Estimasi Selesai:</div>
    <div class="mb-2"><?= format_date($order['est_completion_at'], 'd M Y H:i') ?> WIB</div>

    <!-- QR Code Container -->
    <div id="qrcode" class="d-flex justify-content-center my-2"></div>
    <div style="font-size: 0.7em;" class="text-muted">Scan QR untuk lacak status cucian Anda</div>
</div>

<div style="border-top: 1px dashed #000; margin: 6px 0;"></div>

<div class="text-center text-muted" style="font-size: 0.65em; line-height: 1.2;">
    <?= nl2br(e($setting['footer_text'] ?? "Komplain maksimal 1x24 jam sejak barang diserahkan disertai nota.\nBarang tidak diambil dalam 30 hari di luar tanggung jawab kami.")) ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= $trackingUrl ?>",
        width: 90,
        height: 90,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
    });
});
</script>
