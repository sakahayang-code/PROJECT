<!-- Mobile Native Android Header & Quick Action Launcher (Mobile Only) -->
<div class="d-lg-none mb-3">
    <div class="card border-0 bg-primary text-white p-3 shadow-sm mb-3" style="background: linear-gradient(135deg, #102A43 0%, #0B817C 100%) !important;">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-white text-primary p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; font-weight: 700; font-size: 1.1rem;">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div class="small text-white-50" style="font-size: 0.72rem;">Selamat datang,</div>
                    <div class="fw-bold mb-0 text-white" style="font-size: 1rem;"><?= e($currentUser['name'] ?? 'Staff') ?></div>
                </div>
            </div>
            <span class="badge bg-white text-primary px-2 py-1 shadow-sm" style="font-size: 0.7rem;">
                <i class="bi bi-geo-alt-fill me-1"></i><?= e($activeBranchName ?? 'Pusat') ?>
            </span>
        </div>
    </div>

    <!-- Quick Actions Native Grid -->
    <div class="card border-0 shadow-sm p-3 mb-3">
        <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Aksi Cepat</div>
        <div class="row g-2 text-center">
            <div class="col-3">
                <a href="<?= url('/pos') ?>" class="text-decoration-none text-dark d-flex flex-column align-items-center">
                    <div class="rounded-3 bg-warning text-dark p-2 mb-1 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-cart-check-fill fs-4"></i>
                    </div>
                    <span class="small fw-semibold" style="font-size: 0.7rem;">Kasir POS</span>
                </a>
            </div>
            <div class="col-3">
                <a href="<?= url('/orders') ?>" class="text-decoration-none text-dark d-flex flex-column align-items-center">
                    <div class="rounded-3 bg-info text-white p-2 mb-1 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                    <span class="small fw-semibold" style="font-size: 0.7rem;">Pesanan</span>
                </a>
            </div>
            <div class="col-3">
                <a href="<?= url('/production') ?>" class="text-decoration-none text-dark d-flex flex-column align-items-center">
                    <div class="rounded-3 bg-primary text-white p-2 mb-1 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-diagram-3-fill fs-4"></i>
                    </div>
                    <span class="small fw-semibold" style="font-size: 0.7rem;">Produksi</span>
                </a>
            </div>
            <div class="col-3">
                <a href="<?= url('/delivery') ?>" class="text-decoration-none text-dark d-flex flex-column align-items-center">
                    <div class="rounded-3 bg-success text-white p-2 mb-1 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                    <span class="small fw-semibold" style="font-size: 0.7rem;">Kurir</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Header (Desktop Only) -->
<div class="d-none d-lg-flex align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">
            <?= $isOwnerOrSuper ? 'Ringkasan Konsolidasi SimLaundry' : 'Dashboard Operasional Cabang' ?>
        </h4>
        <div class="text-muted small">
            Pantauan kinerja penjualan, antrean produksi, dan keuangan terkini secara realtime.
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('/pos') ?>" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-cart-plus-fill"></i>
            <span>Buka Kasir POS</span>
        </a>
        <a href="<?= url('/orders') ?>" class="btn btn-light border d-flex align-items-center gap-2">
            <i class="bi bi-receipt"></i>
            <span>Daftar Pesanan</span>
        </a>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pendapatan Hari Ini</span>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-cash"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= format_rupiah($revenueToday) ?></h4>
            <div class="small text-muted">Uang masuk tervalidasi</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pendapatan Bulan Ini</span>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= format_rupiah($revenueMonth) ?></h4>
            <div class="small text-muted">Akumulasi bulan berjalan</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Estimasi Laba Bersih</span>
                <div class="stat-icon <?= $estProfit >= 0 ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?>">
                    <i class="bi bi-piggy-bank-fill"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= format_rupiah($estProfit) ?></h4>
            <div class="small text-muted">Pendapatan dikurangi biaya</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Piutang Pelanggan</span>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= format_rupiah($totalReceivable) ?></h4>
            <div class="small text-muted">Belum lunas / DP</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pesanan Aktif</span>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= number_format($activeOrders) ?></h4>
            <div class="small text-muted">Dalam proses pengerjaan</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Siap Diambil</span>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= number_format($readyOrders) ?></h4>
            <div class="small text-muted">Selesai cuci & packing</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pesanan Terlambat</span>
                <div class="stat-icon <?= $lateOrders > 0 ? 'bg-danger-subtle text-danger' : 'bg-light text-muted' ?>">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1 <?= $lateOrders > 0 ? 'text-danger' : '' ?>"><?= number_format($lateOrders) ?></h4>
            <div class="small text-muted">Melewati estimasi selesai</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pengeluaran Bulan Ini</span>
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-credit-card-2-front"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= format_rupiah($totalExpenses) ?></h4>
            <div class="small text-muted">Biaya operasional tervalidasi</div>
        </div>
    </div>
</div>

<!-- Charts & Performance Row -->
<div class="row g-4 mb-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Tren Pendapatan 7 Hari Terakhir</span>
                <span class="badge bg-primary-subtle text-primary">Harian</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Services -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Layanan Terlaris</div>
            <div class="card-body p-0">
                <?php if (empty($topServices)): ?>
                    <div class="text-center py-4 text-muted small">Belum ada data penjualan tercatat.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Layanan</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topServices as $srv): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($srv['service_name']) ?></div>
                                        <div class="text-muted"><?= $srv['order_count'] ?> kali transaksi</div>
                                    </td>
                                    <td class="text-end fw-bold"><?= format_rupiah($srv['total_sales']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Multi-Branch Breakdown (Owner/Super Admin) OR Low Stock Alerts & Recent Activities -->
<div class="row g-4">
    <?php if ($isOwnerOrSuper && !empty($branchComparison)): ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Perbandingan Pendapatan Antar Cabang (Bulan Ini)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Cabang</th>
                                <th class="text-center">Pesanan</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branchComparison as $bc): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary me-1"><?= e($bc['code']) ?></span>
                                    <span class="fw-semibold"><?= e($bc['name']) ?></span>
                                </td>
                                <td class="text-center"><?= number_format($bc['total_orders']) ?></td>
                                <td class="text-end fw-bold text-success"><?= format_rupiah($bc['total_revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Low Stock Items Warning -->
    <div class="col-lg-<?= ($isOwnerOrSuper && !empty($branchComparison)) ? '6' : '6' ?>">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Peringatan Stok Menipis</span>
                <a href="<?= url('/inventory') ?>" class="small text-decoration-none">Kelola Stok &raquo;</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockItems)): ?>
                    <div class="text-center py-4 text-success small">
                        <i class="bi bi-check-circle fs-4 d-block mb-1"></i>
                        Semua persediaan bahan dalam batas aman.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Cabang</th>
                                    <th class="text-end">Sisa Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $stk): ?>
                                <tr>
                                    <td class="fw-semibold text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <?= e($stk['item_name']) ?>
                                    </td>
                                    <td><?= e($stk['branch_name']) ?></td>
                                    <td class="text-end fw-bold"><?= $stk['current_stock'] ?> <?= e($stk['unit']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Aktivitas Terkini (Audit Trail)</div>
            <div class="card-body p-0">
                <?php if (empty($recentActivities)): ?>
                    <div class="text-center py-4 text-muted small">Belum ada aktivitas tercatat.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($recentActivities as $act): ?>
                        <li class="list-group-item d-flex align-items-start gap-2 py-2">
                            <i class="bi bi-dot fs-4 text-primary"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold"><?= e($act['description']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    <?= e($act['user_name'] ?? 'Sistem') ?> &bull; <?= format_date($act['created_at']) ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: " . json_encode($chartLabels) . ",
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: " . json_encode($chartRevenues) . ",
                    borderColor: '#13AFA8',
                    backgroundColor: 'rgba(19, 175, 168, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#13AFA8',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
";
?>
