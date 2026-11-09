<div class="row g-4 py-4">
    <div class="col-12 col-md-4">
        <!-- Membership Card -->
        <div class="card border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #102A43 0%, #0B817C 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="badge bg-warning text-dark px-2 py-1 fw-bold">MEMBER CARD</span>
                    <i class="bi bi-water fs-3 text-white-50"></i>
                </div>
                <h5 class="fw-bold mb-1"><?= e($customer['name']) ?></h5>
                <div class="small font-monospace text-white-50 mb-3"><?= e($customer['code']) ?></div>

                <div class="d-flex justify-content-between small border-top border-secondary pt-3">
                    <div>
                        <div class="text-white-50">Level Member</div>
                        <div class="fw-bold fs-6"><?= e($customer['tier_name'] ?? 'Regular') ?></div>
                    </div>
                    <div class="text-end">
                        <div class="text-white-50">Total Poin</div>
                        <div class="fw-bold fs-6 text-warning"><?= number_format((int)$customer['points_balance']) ?> Pts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet / Deposit Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="text-muted small fw-semibold">Saldo Deposit Laundry</div>
                <h3 class="fw-bold text-success mb-2"><?= format_rupiah($customer['deposit_balance']) ?></h3>
                <p class="small text-muted mb-0">Untuk isi ulang deposit saldo, silakan hubungi kasir di outlet terdekat.</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8">
        <!-- Profile Update Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-person-gear me-2 text-primary"></i>Informasi Akun Pelanggan</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('/portal/profile') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nomor Pelanggan (Kode)</label>
                        <input type="text" class="form-control font-monospace bg-light" value="<?= e($customer['code']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nomor WhatsApp</label>
                        <input type="text" class="form-control font-monospace bg-light" value="<?= e($customer['whatsapp']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="<?= e($customer['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($customer['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Alamat Utama</label>
                        <textarea name="address" class="form-control" rows="2"><?= e($customer['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan Profil</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Points History Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Perolehan & Penukaran Poin</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Aktivitas</th>
                            <th>Poin</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pointsLedger)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat poin.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pointsLedger as $pl): ?>
                                <tr>
                                    <td class="small"><?= format_date($pl['created_at'], 'd M Y H:i') ?></td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($pl['description']) ?></div>
                                        <?php if (!empty($pl['reference_no'])): ?>
                                            <span class="badge bg-light text-secondary border small"><?= e($pl['reference_no']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?= (int)$pl['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= (int)$pl['points'] > 0 ? '+' : '' ?><?= number_format($pl['points']) ?>
                                        </span>
                                    </td>
                                    <td class="small fw-semibold"><?= number_format($pl['balance_after']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
