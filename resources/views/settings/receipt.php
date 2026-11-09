<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-printer text-primary me-2"></i>Pengaturan Cetak Nota Thermal</h4>
            <p class="text-muted small mb-0">Kustomisasi ukuran kertas printer thermal kasir POS, header struk, footer syarat & ketentuan, dan tampilan QR code.</p>
        </div>
        <a href="<?= url('/settings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Pengaturan</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-sliders me-2 text-primary"></i>Format Struk Kasir</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('/settings/receipt') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lebar Kertas Printer Thermal</label>
                        <select name="paper_size" class="form-select" id="receiptPaperSize" onchange="updateReceiptPreview()">
                            <option value="58mm" <?= ($receipt['paper_size'] ?? '') === '58mm' ? 'selected' : '' ?>>58 mm (Printer Kasir Mini / Bluetooth Portabel)</option>
                            <option value="80mm" <?= ($receipt['paper_size'] ?? '') === '80mm' ? 'selected' : '' ?>>80 mm (Printer Kasir Desktop USB/LAN)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Header Teks (Nama Usaha & Slogan)</label>
                        <textarea name="header_text" id="receiptHeader" class="form-control font-monospace small" rows="3" oninput="updateReceiptPreview()"><?= e($receipt['header_text'] ?? "LAUNDRYPRO INDONESIA\nBersih, Wangi & Tepat Waktu") ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Footer Teks (Syarat & Ketentuan Pengambilan)</label>
                        <textarea name="footer_text" id="receiptFooter" class="form-control font-monospace small" rows="3" oninput="updateReceiptPreview()"><?= e($receipt['footer_text'] ?? "Barang yang tidak diambil dalam 30 hari di luar tanggung jawab kami.\nKomplain maksimal 1x24 jam sejak barang diserahkan disertai nota.") ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Elemen Tampilan Struk</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="show_logo" value="1" id="switchShowLogo" <?= !empty($receipt['show_logo']) ? 'checked' : '' ?> onchange="updateReceiptPreview()">
                            <label class="form-check-label small" for="switchShowLogo">Tampilkan Logo LaundryPro di Atas Struk</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="show_qr" value="1" id="switchShowQr" <?= !empty($receipt['show_qr']) ? 'checked' : '' ?> onchange="updateReceiptPreview()">
                            <label class="form-check-label small" for="switchShowQr">Tampilkan QR Code Pelacakan Status Cucian</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_notes" value="1" id="switchShowNotes" <?= !empty($receipt['show_notes']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="switchShowNotes">Tampilkan Catatan Khusus Pelanggan di Bawah Item</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Format Nota</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Thermal Preview -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-eye me-2 text-primary"></i>Pratinjau Struk (Live Preview)</h6>
            </div>
            <div class="card-body bg-light p-3 d-flex justify-content-center">
                <div id="previewReceiptCard" class="bg-white p-3 border shadow-sm font-monospace text-dark small text-center" style="width: 260px; font-size: 0.75rem; line-height: 1.2;">
                    <div id="previewLogo" class="fw-bold mb-1"><i class="bi bi-water text-primary"></i> LAUNDRYPRO</div>
                    <div id="previewHeaderText" style="white-space: pre-wrap;" class="fw-semibold mb-2">LAUNDRYPRO INDONESIA</div>
                    <div class="border-top border-bottom border-dark border-dashed py-1 mb-2 text-start">
                        <div>Nota : LP-PST-20261109-0001</div>
                        <div>Tgl  : <?= date('d/m/Y H:i') ?></div>
                        <div>Kasir: Kasir Pusat</div>
                    </div>
                    <div class="text-start mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Cuci Setrika Reguler (4 Kg)</span>
                            <span>32.000</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Bedcover Tebal (1 Pcs)</span>
                            <span>25.000</span>
                        </div>
                    </div>
                    <div class="border-top border-dark border-dashed py-1 text-start mb-2">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>TOTAL</span>
                            <span>Rp 57.000</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Bayar (QRIS)</span>
                            <span>Rp 57.000</span>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Status</span>
                            <span>LUNAS</span>
                        </div>
                    </div>
                    <div id="previewQrContainer" class="my-2">
                        <div class="bg-light border p-2 d-inline-block">
                            <i class="bi bi-qr-code fs-1 text-dark"></i>
                            <div style="font-size: 0.6rem;">Scan Lacak Pesanan</div>
                        </div>
                    </div>
                    <div id="previewFooterText" style="white-space: pre-wrap;" class="text-muted" style="font-size: 0.65rem;">
                        Terima kasih atas kunjungan Anda.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateReceiptPreview() {
    const paper = document.getElementById('receiptPaperSize').value;
    const card = document.getElementById('previewReceiptCard');
    card.style.width = (paper === '80mm') ? '340px' : '260px';

    document.getElementById('previewHeaderText').innerText = document.getElementById('receiptHeader').value;
    document.getElementById('previewFooterText').innerText = document.getElementById('receiptFooter').value;
    document.getElementById('previewLogo').style.display = document.getElementById('switchShowLogo').checked ? 'block' : 'none';
    document.getElementById('previewQrContainer').style.display = document.getElementById('switchShowQr').checked ? 'block' : 'none';
}
updateReceiptPreview();
</script>
