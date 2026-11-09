<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="<?= url('/customers') ?>" class="text-decoration-none small text-muted mb-1 d-inline-block">&laquo; Kembali ke Daftar Pelanggan</a>
        <h4 class="fw-bold mb-0">[<?= e($customer['code']) ?>] <?= e($customer['name']) ?></h4>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#topupDepositModal">
            <i class="bi bi-wallet2"></i>
            <span>Top Up Saldo Deposit</span>
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Customer Info Card -->
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header">Informasi Profil Pelanggan</div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold fs-3 mb-2" style="width: 70px; height: 70px;">
                        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-0"><?= e($customer['name']) ?></h5>
                    <span class="badge" style="background-color: <?= e($customer['badge_color'] ?: '#64748B') ?>;">
                        <?= e($customer['tier_name'] ?: 'Regular') ?> &bull; Diskon <?= $customer['discount_percent'] ?>%
                    </span>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Nomor WhatsApp:</span>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $customer['whatsapp']) ?>" target="_blank" class="fw-semibold text-success text-decoration-none">
                            <i class="bi bi-whatsapp me-1"></i><?= e($customer['whatsapp']) ?>
                        </a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Email:</span>
                        <span class="fw-semibold"><?= e($customer['email'] ?: '-') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Cabang Langganan:</span>
                        <span class="fw-semibold"><?= e($customer['favorite_branch_name'] ?: 'Pusat') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Akun Portal:</span>
                        <span class="badge bg-light text-dark border">@<?= e($customer['username'] ?: '-') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Tanggal Bergabung:</span>
                        <span><?= format_date($customer['join_date'], 'd M Y') ?></span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1">Alamat Utama:</span>
                        <div class="fw-semibold"><?= e($customer['address'] ?: 'Belum ada alamat') ?></div>
                    </li>
                </ul>

                <hr class="my-3">

                <!-- Financial & Points Balance Summary -->
                <div class="p-3 bg-light rounded text-center mb-2">
                    <div class="text-muted small">Saldo Deposit Saat Ini</div>
                    <h4 class="fw-bold text-primary mb-0"><?= format_rupiah($customer['deposit_balance']) ?></h4>
                </div>
                <div class="p-3 bg-light rounded text-center">
                    <div class="text-muted small">Total Poin Loyalitas</div>
                    <h5 class="fw-bold text-warning mb-0"><i class="bi bi-star-fill me-1"></i><?= number_format($customer['points_balance']) ?> Pts</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs: Riwayat Pesanan, Mutasi Deposit, Riwayat Poin -->
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header p-0 border-bottom">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-orders">
                            <i class="bi bi-receipt me-1"></i> Riwayat Pesanan (<?= count($orders) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-deposits">
                            <i class="bi bi-wallet2 me-1"></i> Mutasi Deposit (<?= count($deposits) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold small" data-bs-toggle="tab" data-bs-target="#tab-points">
                            <i class="bi bi-star-fill me-1"></i> Ledger Poin (<?= count($points) ?>)
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    <!-- Tab Pesanan -->
                    <div class="tab-pane fade show active" id="tab-orders">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Nota</th>
                                        <th>Cabang</th>
                                        <th>Tanggal</th>
                                        <th>Status Cuci</th>
                                        <th>Pembayaran</th>
                                        <th class="text-end">Total Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi laundry.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <a href="<?= url('/orders/' . $o['id']) ?>" class="text-decoration-none">
                                                    <?= e($o['order_no']) ?>
                                                </a>
                                            </td>
                                            <td><?= e($o['branch_name']) ?></td>
                                            <td><?= format_date($o['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= e($o['production_status']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $o['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= strtoupper($o['payment_status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold"><?= format_rupiah($o['grand_total']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Mutasi Deposit -->
                    <div class="tab-pane fade" id="tab-deposits">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tipe</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Nominal</th>
                                        <th class="text-end">Saldo Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($deposits)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat mutasi deposit.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($deposits as $dep): ?>
                                        <tr>
                                            <td><?= format_date($dep['created_at']) ?></td>
                                            <td>
                                                <span class="badge <?= $dep['type'] === 'credit' ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $dep['type'] === 'credit' ? 'Top Up (+)' : 'Pemakaian (-)' ?>
                                                </span>
                                            </td>
                                            <td><?= e($dep['description']) ?></td>
                                            <td class="text-end fw-bold <?= $dep['type'] === 'credit' ? 'text-success' : 'text-danger' ?>">
                                                <?= $dep['type'] === 'credit' ? '+' : '-' ?> <?= format_rupiah($dep['amount']) ?>
                                            </td>
                                            <td class="text-end fw-semibold"><?= format_rupiah($dep['balance_after']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Ledger Poin -->
                    <div class="tab-pane fade" id="tab-points">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tipe</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Poin</th>
                                        <th class="text-end">Saldo Poin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($points)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi poin.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($points as $p): ?>
                                        <tr>
                                            <td><?= format_date($p['created_at']) ?></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst($p['type']) ?></span></td>
                                            <td><?= e($p['description']) ?></td>
                                            <td class="text-end fw-bold text-warning"><?= $p['points'] > 0 ? "+{$p['points']}" : $p['points'] ?></td>
                                            <td class="text-end fw-semibold"><?= $p['balance_after'] ?> Pts</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Top Up Deposit -->
<div class="modal fade" id="topupDepositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('/customers/' . $customer['id'] . '/deposit') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Top Up Saldo Deposit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Top up deposit akan otomatis menjurnal kas masuk dan meningkatkan saldo deposit pelanggan untuk transaksi non-tunai di kasir.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pelanggan</label>
                        <input type="text" class="form-control" value="<?= e($customer['name']) ?> (<?= e($customer['code']) ?>)" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal Top Up (Rp)</label>
                        <input type="number" class="form-control" name="amount" placeholder="Contoh: 100000" min="10000" step="5000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                        <input type="text" class="form-control" name="notes" value="Top Up Deposit Tunai di Kasir">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Proses Top Up Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>
