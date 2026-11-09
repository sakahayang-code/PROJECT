<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Manajemen Pesanan Laundry</h4>
        <div class="text-muted small">Pantau semua pesanan masuk, status pengerjaan produksi, dan pelunasan pembayaran</div>
    </div>
    <a href="<?= url('/pos') ?>" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-cart-plus-fill"></i>
        <span>Buat Pesanan Baru (Kasir)</span>
    </a>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/orders') ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" name="q" value="<?= e($search) ?>" placeholder="No. Nota, Pelanggan, WA...">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">-- Status Cuci --</option>
                    <option value="received" <?= $selectedStatus === 'received' ? 'selected' : '' ?>>Diterima</option>
                    <option value="queued" <?= $selectedStatus === 'queued' ? 'selected' : '' ?>>Antrean</option>
                    <option value="in_process" <?= $selectedStatus === 'in_process' ? 'selected' : '' ?>>Sedang Dicuci</option>
                    <option value="qc" <?= $selectedStatus === 'qc' ? 'selected' : '' ?>>Quality Control</option>
                    <option value="packaging" <?= $selectedStatus === 'packaging' ? 'selected' : '' ?>>Pengemasan</option>
                    <option value="ready" <?= $selectedStatus === 'ready' ? 'selected' : '' ?>>Siap Diambil</option>
                    <option value="delivering" <?= $selectedStatus === 'delivering' ? 'selected' : '' ?>>Pengantaran</option>
                    <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Selesai Diserahkan</option>
                    <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="payment_status">
                    <option value="">-- Status Bayar --</option>
                    <option value="paid" <?= $selectedPayment === 'paid' ? 'selected' : '' ?>>Lunas</option>
                    <option value="partial" <?= $selectedPayment === 'partial' ? 'selected' : '' ?>>Sebagian (DP)</option>
                    <option value="unpaid" <?= $selectedPayment === 'unpaid' ? 'selected' : '' ?>>Belum Bayar</option>
                </select>
            </div>
            <?php if (\App\Core\Auth::hasGlobalAccess()): ?>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="branch_id">
                    <option value="0">-- Semua Cabang --</option>
                    <?php foreach ($branches as $br): ?>
                        <option value="<?= $br['id'] ?>" <?= (int)$selectedBranch === (int)$br['id'] ? 'selected' : '' ?>>
                            <?= e($br['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="<?= url('/orders') ?>" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>No. Nota</th>
                        <th>Pelanggan</th>
                        <th>Cabang</th>
                        <th>Layanan</th>
                        <th>Status Produksi</th>
                        <th>Pembayaran</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-end">Sisa Bayar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data pesanan yang cocok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/orders/' . $o['id']) ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= e($o['order_no']) ?>
                                </a>
                                <div class="text-muted" style="font-size:0.75rem;"><?= format_date($o['created_at']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($o['customer_name']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= e($o['customer_phone']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($o['branch_name']) ?></span></td>
                            <td>
                                <span class="badge bg-secondary text-uppercase" style="font-size:0.7rem;"><?= e($o['order_type']) ?></span>
                                <div class="small text-muted mt-1">
                                    <?= $o['total_weight'] > 0 ? $o['total_weight'] . ' kg' : '' ?>
                                    <?= $o['total_pieces'] > 0 ? ($o['total_weight'] > 0 ? ' &bull; ' : '') . $o['total_pieces'] . ' pcs' : '' ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $pBadge = 'bg-secondary';
                                    if ($o['production_status'] === 'received') $pBadge = 'bg-info';
                                    elseif ($o['production_status'] === 'in_process') $pBadge = 'bg-primary';
                                    elseif ($o['production_status'] === 'ready') $pBadge = 'bg-success';
                                    elseif ($o['production_status'] === 'cancelled') $pBadge = 'bg-danger';
                                    elseif ($o['production_status'] === 'completed') $pBadge = 'bg-dark';
                                ?>
                                <span class="badge <?= $pBadge ?> badge-status">
                                    <?= ucfirst($o['production_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $o['payment_status'] === 'paid' ? 'bg-success' : ($o['payment_status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                    <?= strtoupper($o['payment_status']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold"><?= format_rupiah($o['grand_total']) ?></td>
                            <td class="text-end fw-bold <?= $o['balance_due'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= $o['balance_due'] > 0 ? format_rupiah($o['balance_due']) : 'Lunas' ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/orders/' . $o['id']) ?>" class="btn btn-outline-primary" title="Detail Nota">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('/orders/' . $o['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Nota">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Android Card List View (Phones & Tablets) -->
        <div class="d-lg-none p-3">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                    Tidak ada data pesanan yang cocok.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $pBadge = 'bg-secondary';
                            if ($o['production_status'] === 'received') $pBadge = 'bg-info';
                            elseif ($o['production_status'] === 'in_process') $pBadge = 'bg-primary';
                            elseif ($o['production_status'] === 'ready') $pBadge = 'bg-success';
                            elseif ($o['production_status'] === 'cancelled') $pBadge = 'bg-danger';
                            elseif ($o['production_status'] === 'completed') $pBadge = 'bg-dark';
                        ?>
                        <div class="card border rounded-4 shadow-sm p-3 position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <a href="<?= url('/orders/' . $o['id']) ?>" class="fw-bold text-primary text-decoration-none fs-6">
                                        <?= e($o['order_no']) ?>
                                    </a>
                                    <div class="text-muted" style="font-size:0.75rem;"><?= format_date($o['created_at']) ?></div>
                                </div>
                                <span class="badge <?= $pBadge ?> rounded-pill px-2 py-1">
                                    <?= ucfirst($o['production_status']) ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <div class="fw-semibold text-dark small"><?= e($o['customer_name']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        <i class="bi bi-telephone text-muted me-1"></i><?= e($o['customer_phone']) ?> &bull; <i class="bi bi-shop me-1"></i><?= e($o['branch_name']) ?>
                                    </div>
                                </div>
                                <span class="badge <?= $o['payment_status'] === 'paid' ? 'bg-success' : ($o['payment_status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill px-2 py-1">
                                    <?= strtoupper($o['payment_status']) ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small" style="font-size: 0.75rem;">Tagihan: </span>
                                    <span class="fw-bold text-dark"><?= format_rupiah($o['grand_total']) ?></span>
                                    <?php if ($o['balance_due'] > 0): ?>
                                        <div class="text-danger small" style="font-size:0.72rem;">Sisa: <?= format_rupiah($o['balance_due']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= url('/orders/' . $o['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2" title="Cetak Nota">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="<?= url('/orders/' . $o['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                                        Detail <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
