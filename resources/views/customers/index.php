<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Data Pelanggan (CRM)</h4>
        <div class="text-muted small">Kelola basis data pelanggan, saldo deposit, tier membership, dan riwayat pesanan</div>
    </div>
    <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
        <i class="bi bi-person-plus-fill"></i>
        <span>Daftar Pelanggan Baru</span>
    </button>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/customers') ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Cari nama, WhatsApp, atau kode pelanggan...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="tier_id">
                    <option value="0">-- Semua Level Member --</option>
                    <?php foreach ($tiers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (int)$selectedTier === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= e($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">-- Status --</option>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Non-Aktif</option>
                    <option value="blocked" <?= $selectedStatus === 'blocked' ? 'selected' : '' ?>>Diblokir</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
                <?php if (!empty($search) || !empty($selectedStatus) || $selectedTier > 0): ?>
                    <a href="<?= url('/customers') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Customer List Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Pelanggan</th>
                        <th>WhatsApp & Alamat</th>
                        <th>Level Member</th>
                        <th class="text-end">Saldo Deposit</th>
                        <th class="text-center">Poin</th>
                        <th class="text-center">Total Transaksi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ditemukan pelanggan yang cocok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($c['name']) ?></div>
                                <span class="badge bg-secondary" style="font-size:0.7rem;"><?= e($c['code']) ?></span>
                            </td>
                            <td>
                                <div>
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $c['whatsapp']) ?>" target="_blank" class="text-success text-decoration-none fw-semibold">
                                        <i class="bi bi-whatsapp me-1"></i><?= e($c['whatsapp']) ?>
                                    </a>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 200px;"><?= e($c['address'] ?: '-') ?></div>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?= e($c['badge_color'] ?: '#64748B') ?>;">
                                    <?= e($c['tier_name'] ?: 'Regular') ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                <?= format_rupiah($c['deposit_balance']) ?>
                            </td>
                            <td class="text-center fw-semibold text-warning">
                                <i class="bi bi-star-fill me-1"></i><?= number_format($c['points_balance']) ?>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold"><?= number_format($c['total_orders']) ?></span> nota
                                <div class="text-muted" style="font-size:0.75rem;"><?= format_rupiah($c['total_spent']) ?></div>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('/customers/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary" title="Detail Profil">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pelanggan -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/customers') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Daftar Pelanggan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="name" placeholder="Nama pelanggan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp (Aktif)</label>
                        <input type="text" class="form-control" name="whatsapp" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Email (Opsional)</label>
                        <input type="email" class="form-control" name="email" placeholder="email@contoh.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cabang Favorit / Langganan</label>
                        <select class="form-select" name="favorite_branch_id">
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= e($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Lahir (Untuk Promo Ulang Tahun)</label>
                        <input type="date" class="form-control" name="birth_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Lengkap (Untuk Antar / Jemput)</label>
                        <textarea class="form-control" name="address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, patokan"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Khusus (Alergi deterjen/pewangi, dll)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Catatan preferensi cucian"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="allow_marketing" id="allowMarketingCheck" value="1" checked>
                        <label class="form-check-label small" for="allowMarketingCheck">
                            Pelanggan bersedia menerima notifikasi status cucian dan info promo via WhatsApp
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pelanggan</button>
                </div>
            </form>
        </div>
    </div>
</div>
