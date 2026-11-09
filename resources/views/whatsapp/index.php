<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Total Pesan Antrean</div>
            <div class="stat-value text-primary"><?= number_format($stats['total']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-chat-dots me-1"></i>Sepanjang waktu</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Menunggu Dikirim</div>
            <div class="stat-value text-warning"><?= number_format($stats['pending']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-hourglass-split me-1"></i>Antrean pending</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Berhasil Terkirim</div>
            <div class="stat-value text-success"><?= number_format($stats['sent']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-check-all text-success me-1"></i>Tersampaikan</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Gagal Dikirim</div>
            <div class="stat-value text-danger"><?= number_format($stats['failed']) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-exclamation-triangle text-danger me-1"></i>Koneksi / batas API</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- WhatsApp Gateway Settings -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-gear me-2 text-primary"></i>Pengaturan Gateway WhatsApp</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/whatsapp/settings') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Penyedia Gateway (Provider)</label>
                        <select name="provider" class="form-select" id="waProviderSelect">
                            <option value="mock" <?= ($settings['provider'] ?? '') === 'mock' ? 'selected' : '' ?>>Simulasi / Mock Development (Tanpa kuota pulsa)</option>
                            <option value="fonnte" <?= ($settings['provider'] ?? '') === 'fonnte' ? 'selected' : '' ?>>Fonnte (Rekomendasi Indonesia)</option>
                            <option value="wablas" <?= ($settings['provider'] ?? '') === 'wablas' ? 'selected' : '' ?>>Wablas</option>
                            <option value="official" <?= ($settings['provider'] ?? '') === 'official' ? 'selected' : '' ?>>WhatsApp Cloud API Official (Meta)</option>
                        </select>
                        <div class="form-text small">Mode "Simulasi / Mock" aman digunakan untuk pengujian tanpa memotong kuota paket SMS/WA gateway.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">URL Endpoint API</label>
                        <input type="text" name="api_url" class="form-control font-monospace small" value="<?= e($settings['api_url'] ?? 'https://api.fonnte.com/send') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">API Token / Secret Key</label>
                        <input type="password" name="api_key" class="form-control font-monospace small" value="<?= e($settings['api_key'] ?? '') ?>" placeholder="Masukkan API Key dari dashboard provider">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nomor Pengirim (Sender)</label>
                        <input type="text" name="sender_number" class="form-control" value="<?= e($settings['sender_number'] ?? '081234567890') ?>">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="waActiveSwitch" <?= !empty($settings['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-bold" for="waActiveSwitch">Aktifkan Pengiriman Notifikasi Otomatis</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Konfigurasi</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Manual Send / Blast Message -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-send me-2 text-success"></i>Kirim Pesan WhatsApp Manual / Promo</h6>
                <a href="<?= url('/whatsapp/templates') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-text me-1"></i> Template Pesan</a>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/whatsapp/send') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Pelanggan (Opsional)</label>
                        <select class="form-select form-select-sm" onchange="fillWaCustomer(this)">
                            <option value="">-- Ketik manual atau pilih pelanggan --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= e($c['whatsapp'] ?: $c['phone']) ?>" data-name="<?= e($c['name']) ?>"><?= e($c['name']) ?> (<?= e($c['whatsapp'] ?: $c['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor WhatsApp Tujuan</label>
                            <input type="text" name="phone_number" id="waRecipientPhone" class="form-control" placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Penerima</label>
                            <input type="text" name="recipient_name" id="waRecipientName" class="form-control" placeholder="Nama Pelanggan">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Isi Pesan WhatsApp</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Tulis isi pesan pengumuman, promosi, atau info operasional di sini..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-whatsapp me-1"></i> Kirim Pesan Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Queue Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-clock me-2 text-primary"></i>Daftar Antrean & Riwayat Pesan WhatsApp</h6>
        <div class="d-flex gap-2">
            <form method="POST" action="<?= url('/whatsapp/process') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-play-circle me-1"></i> Proses Antrean Sekarang (<?= $stats['pending'] ?>)
                </button>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu Dibuat</th>
                    <th>Penerima</th>
                    <th>Nomor WhatsApp</th>
                    <th>Pemicu (Event)</th>
                    <th>Isi Pesan</th>
                    <th>Status</th>
                    <th>Terkirim Pada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($queue)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada antrean pesan WhatsApp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($queue as $q): ?>
                        <tr>
                            <td class="small"><?= format_date($q['created_at'], 'd M Y H:i') ?></td>
                            <td class="fw-semibold"><?= e($q['recipient_name'] ?: 'Pelanggan') ?></td>
                            <td><span class="font-monospace small"><?= e($q['phone_number']) ?></span></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary"><?= e($q['trigger_event']) ?></span>
                            </td>
                            <td style="max-width: 300px;">
                                <div class="small text-truncate" title="<?= e($q['message']) ?>"><?= e($q['message']) ?></div>
                            </td>
                            <td>
                                <?php
                                $badge = match($q['status']) {
                                    'sent' => 'bg-success-subtle text-success',
                                    'pending' => 'bg-warning-subtle text-warning',
                                    'failed' => 'bg-danger-subtle text-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= strtoupper($q['status']) ?></span>
                                <?php if (!empty($q['last_error'])): ?>
                                    <div class="small text-danger mt-1" title="<?= e($q['last_error']) ?>"><i class="bi bi-info-circle me-1"></i>Error</div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= format_date($q['sent_at'], 'd M Y H:i') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function fillWaCustomer(select) {
    const opt = select.options[select.selectedIndex];
    if (opt.value) {
        document.getElementById('waRecipientPhone').value = opt.value;
        document.getElementById('waRecipientName').value = opt.getAttribute('data-name') || '';
    }
}
</script>
