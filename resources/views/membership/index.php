<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Total Poin Diberikan</div>
            <div class="stat-value text-primary"><?= number_format($totalPointsIssued) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-gift text-primary me-1"></i>Poin dari transaksi</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Total Poin Ditukarkan</div>
            <div class="stat-value text-success"><?= number_format($totalPointsRedeemed) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-tag text-success me-1"></i>Diskon & reward</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Poin Beredar (Aktif)</div>
            <div class="stat-value text-warning"><?= number_format(max(0, $totalPointsIssued - $totalPointsRedeemed)) ?></div>
            <div class="text-muted small mt-1"><i class="bi bi-wallet2 text-warning me-1"></i>Liabilitas poin</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold">Level Membership</div>
            <div class="stat-value text-info"><?= count($tiers) ?> Level</div>
            <div class="text-muted small mt-1"><i class="bi bi-award text-info me-1"></i>Tier otomatis</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-award me-2 text-warning"></i>Tingkatan Level Membership</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAdjustPoints">
                        <i class="bi bi-plus-slash-minus me-1"></i> Sesuaikan Poin Pelanggan
                    </button>
                    <a href="<?= url('/vouchers') ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-ticket-perforated me-1"></i> Kelola Voucher Promo
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($tiers as $t): ?>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card border h-100 shadow-none" style="border-top: 4px solid <?= e($t['badge_color'] ?: '#13AFA8') ?> !important;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-dark mb-0"><?= e($t['name']) ?></h5>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= (int)$t['member_count'] ?> member</span>
                                    </div>
                                    <p class="text-muted small mb-3"><?= e($t['description'] ?: 'Tingkatan loyalitas pelanggan') ?></p>

                                    <ul class="list-unstyled small mb-3">
                                        <li class="mb-1"><i class="bi bi-cash-coin me-2 text-muted"></i>Min. Belanja: <strong><?= format_rupiah($t['min_spend']) ?></strong></li>
                                        <li class="mb-1"><i class="bi bi-star me-2 text-muted"></i>Min. Poin: <strong><?= number_format($t['min_points']) ?> Pts</strong></li>
                                        <li class="mb-1"><i class="bi bi-percent me-2 text-success"></i>Diskon Member: <strong><?= (float)$t['discount_percent'] ?>%</strong></li>
                                        <li class="mb-1"><i class="bi bi-lightning-charge me-2 text-warning"></i>Poin Multiplier: <strong><?= (float)$t['points_multiplier'] ?>x</strong></li>
                                    </ul>

                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick='openEditTierModal(<?= json_encode($t) ?>)'>
                                        <i class="bi bi-pencil me-1"></i> Edit Syarat & Diskon
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Buku Mutasi Poin Loyalitas (50 Terakhir)</h6>
        <span class="badge bg-light text-dark border"><?= count($transactions) ?> mutasi</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu</th>
                    <th>Pelanggan</th>
                    <th>Tipe Mutasi</th>
                    <th>Poin</th>
                    <th>Saldo Akhir</th>
                    <th>Keterangan / Referensi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat mutasi poin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td class="small"><?= format_date($tx['created_at'], 'd M Y H:i') ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($tx['customer_name']) ?></div>
                                <div class="small text-muted"><?= e($tx['customer_code']) ?> &bull; <?= e($tx['customer_phone']) ?></div>
                            </td>
                            <td>
                                <?php
                                $typeBadge = match($tx['type']) {
                                    'earn' => 'bg-success-subtle text-success',
                                    'redeem' => 'bg-danger-subtle text-danger',
                                    'adjust' => 'bg-info-subtle text-info',
                                    'expire' => 'bg-secondary-subtle text-secondary',
                                    default => 'bg-light text-dark'
                                };
                                $typeLabel = match($tx['type']) {
                                    'earn' => 'Didapatkan (Earn)',
                                    'redeem' => 'Ditukarkan (Redeem)',
                                    'adjust' => 'Penyesuaian (Adjust)',
                                    'expire' => 'Kedaluwarsa',
                                    default => $tx['type']
                                };
                                ?>
                                <span class="badge <?= $typeBadge ?>"><?= $typeLabel ?></span>
                            </td>
                            <td>
                                <span class="fw-bold <?= (int)$tx['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= (int)$tx['points'] > 0 ? '+' : '' ?><?= number_format($tx['points']) ?> Pts
                                </span>
                            </td>
                            <td class="fw-semibold"><?= number_format($tx['balance_after']) ?> Pts</td>
                            <td class="small text-muted">
                                <?= e($tx['description']) ?>
                                <?php if (!empty($tx['reference_no'])): ?>
                                    <span class="badge bg-light text-secondary border ms-1"><?= e($tx['reference_no']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Tier -->
<div class="modal fade" id="modalEditTier" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/membership/tiers/update') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="tier_id" id="editTierId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Level Membership</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Nama Level</label>
                    <input type="text" id="editTierName" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Minimal Total Belanja (Rp)</label>
                    <input type="text" name="min_spend" id="editTierMinSpend" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Minimal Poin Terkumpul</label>
                    <input type="number" name="min_points" id="editTierMinPoints" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Diskon Transaksi (%)</label>
                    <input type="number" step="0.5" name="discount_percent" id="editTierDiscount" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Pengali Poin (Multiplier)</label>
                    <input type="number" step="0.1" name="points_multiplier" id="editTierMultiplier" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Deskripsi & Syarat</label>
                    <textarea name="description" id="editTierDesc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Adjust Points -->
<div class="modal fade" id="modalAdjustPoints" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/membership/points/adjust') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-slash-minus me-2 text-primary"></i>Sesuaikan Poin Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">ID Pelanggan</label>
                    <input type="number" name="customer_id" class="form-control" placeholder="Contoh: 1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jenis Penyesuaian</label>
                    <select name="type" class="form-select" required>
                        <option value="earn">Tambah Poin (+ Earn)</option>
                        <option value="redeem">Kurangi Poin (- Redeem)</option>
                        <option value="adjust">Penyesuaian Manual (Adjust)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Jumlah Poin</label>
                    <input type="number" name="points" class="form-control" placeholder="Contoh: 50" required min="1">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Alasan / Catatan</label>
                    <input type="text" name="description" class="form-control" placeholder="Contoh: Bonus ulang tahun pelanggan / Kompensasi" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Terapkan Perubahan Poin</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditTierModal(t) {
    document.getElementById('editTierId').value = t.id;
    document.getElementById('editTierName').value = t.name;
    document.getElementById('editTierMinSpend').value = t.min_spend;
    document.getElementById('editTierMinPoints').value = t.min_points;
    document.getElementById('editTierDiscount').value = t.discount_percent;
    document.getElementById('editTierMultiplier').value = t.points_multiplier;
    document.getElementById('editTierDesc').value = t.description || '';
    new bootstrap.Modal(document.getElementById('modalEditTier')).show();
}
</script>
