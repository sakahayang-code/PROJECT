<div class="row g-3 mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Pusat Laporan & Analisis Bisnis</h4>
        <p class="text-muted small mb-0">Tersedia 30 laporan operasional, finansial, inventaris, dan performa multi-cabang lengkap dengan filter tanggal dan ekspor CSV.</p>
    </div>
</div>

<?php
$categories = [
    'sales' => ['title' => 'Laporan Penjualan & Kasir (POS)', 'icon' => 'bi-receipt-cutoff', 'color' => 'primary'],
    'operations' => ['title' => 'Laporan Operasional, Mesin & Produksi', 'icon' => 'bi-gear-wide-connected', 'color' => 'info'],
    'crm' => ['title' => 'Laporan CRM, Pelanggan & Loyalitas', 'icon' => 'bi-people-fill', 'color' => 'warning'],
    'finance' => ['title' => 'Laporan Keuangan, Kas & Pembelian', 'icon' => 'bi-cash-coin', 'color' => 'success'],
    'inventory' => ['title' => 'Laporan Stok & Inventaris Gudang', 'icon' => 'bi-boxes', 'color' => 'secondary'],
    'executive' => ['title' => 'Laba Rugi & Komparasi Multi-Cabang', 'icon' => 'bi-pie-chart-fill', 'color' => 'dark'],
];
?>

<?php foreach ($categories as $catKey => $catInfo): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="card-title fw-bold m-0 text-<?= $catInfo['color'] ?>">
                <i class="bi <?= $catInfo['icon'] ?> me-2"></i><?= $catInfo['title'] ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php
                $catReports = array_filter($reports, fn($r) => $r['category'] === $catKey);
                $index = 1;
                ?>
                <?php foreach ($catReports as $slug => $r): ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <a href="<?= url('/reports/' . $slug) ?>" class="card h-100 border text-decoration-none hover-shadow transition p-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-light text-dark border me-2 font-monospace">#<?= $slug ?></span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= e($r['title']) ?></h6>
                            <div class="small text-muted mt-auto pt-2">
                                <span class="text-primary fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i> Buka Laporan</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.transition {
    transition: all 0.2s ease-in-out;
}
</style>
