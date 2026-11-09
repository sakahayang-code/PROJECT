<div class="row g-3 mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1"><i class="bi bi-sliders text-primary me-2"></i>Pengaturan Perusahaan & Sistem</h4>
        <p class="text-muted small mb-0">Kelola identitas perusahaan, tarif pajak transaksi kasir, dan preferensi aplikasi.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-building me-2 text-primary"></i>Profil Perusahaan Induk</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('/settings/company') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Kode Perusahaan</label>
                        <input type="text" class="form-control bg-light font-monospace" value="<?= e($company['code'] ?? 'LP') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Mata Uang Sistem</label>
                        <input type="text" class="form-control bg-light font-monospace" value="<?= e($company['currency'] ?? 'IDR (Rp)') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nama Bisnis / Brand</label>
                        <input type="text" name="name" class="form-control" value="<?= e($company['name'] ?? 'SimLaundry') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tagline Bisnis</label>
                        <input type="text" name="tagline" class="form-control" value="<?= e($company['tagline'] ?? 'Smart Laundry Business Management System') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nomor Telepon Kantor / Hotline</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($company['phone'] ?? '021-5550123') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Bisnis</label>
                        <input type="email" name="email" class="form-control" value="<?= e($company['email'] ?? 'info@simlaundry.id') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Alamat Kantor Pusat</label>
                        <textarea name="address" class="form-control" rows="2"><?= e($company['address'] ?? 'Jl. Sudirman No. 100, Jakarta') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tarif Pajak Transaksi Kasir POS (%)</label>
                        <input type="number" step="0.5" name="tax_rate_percent" class="form-control" value="<?= (float)($settings['tax_rate_percent'] ?? 0) ?>" min="0" max="100">
                        <div class="form-text small">Isi 0 jika harga layanan kasir sudah termasuk pajak atau tidak mengenakan PPN.</div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Profil Perusahaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <!-- Quick Links -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-grid me-2 text-primary"></i>Menu Pengaturan Lanjutan</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="<?= url('/settings/receipt') ?>" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Printer & Cetak Nota</div>
                            <div class="small text-muted">Format 58mm / 80mm, QR code, logo</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="<?= url('/settings/backup') ?>" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><i class="bi bi-database me-2 text-success"></i>Backup & Restore Database</div>
                            <div class="small text-muted">Snapshot SQLite, unduh, & pemulihan</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="<?= url('/settings/logs') ?>" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><i class="bi bi-journal-text me-2 text-info"></i>Log Aktivitas & Audit Trail</div>
                            <div class="small text-muted">Riwayat aksi pengguna & keamanan login</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    <a href="<?= url('/whatsapp') ?>" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold"><i class="bi bi-whatsapp me-2 text-success"></i>Integrasi WhatsApp Gateway</div>
                            <div class="small text-muted">Provider, API key, & antrean pesan</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
