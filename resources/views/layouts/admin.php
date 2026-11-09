<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
    <title><?= e($title ?? 'Dashboard') ?> — <?= e($appConfig['name']) ?></title>
    
    <!-- PWA Manifest & Icons -->
    <link rel="icon" type="image/png" href="<?= asset('img/icon-192.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#13AFA8">

    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/laundrypro.css') ?>">
</head>
<body>

<div class="sidebar-overlay"></div>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo p-1 bg-white rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <img src="<?= asset('img/logo.png') ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div>
                <div class="brand-text"><?= e($appConfig['name']) ?></div>
                <span class="brand-badge"><?= e($currentUser['role_name'] ?? 'Staff') ?></span>
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-heading">Utama</div>
            <a href="<?= url('/dashboard') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

            <?php if (\App\Core\Auth::hasPermission('pos.access')): ?>
            <a href="<?= url('/pos') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'pos' ? 'active' : '' ?>">
                <i class="bi bi-cart-check-fill text-warning"></i>
                <span class="fw-bold">Kasir (POS)</span>
            </a>
            <?php endif; ?>

            <a href="<?= url('/orders') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'orders' ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Manajemen Pesanan</span>
            </a>

            <?php if (\App\Core\Auth::hasPermission('production.access')): ?>
            <div class="nav-heading">Operasional</div>
            <a href="<?= url('/production') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'production' ? 'active' : '' ?>">
                <i class="bi bi-diagram-3-fill"></i>
                <span>Antrean Produksi</span>
            </a>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('delivery.access')): ?>
            <a href="<?= url('/delivery') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'delivery' ? 'active' : '' ?>">
                <i class="bi bi-truck"></i>
                <span>Pickup & Delivery</span>
            </a>
            <?php endif; ?>

            <div class="nav-heading">Pelanggan & CRM</div>
            <a href="<?= url('/customers') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'customers' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Data Pelanggan</span>
            </a>
            <a href="<?= url('/membership') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'membership' ? 'active' : '' ?>">
                <i class="bi bi-star-fill"></i>
                <span>Poin & Voucher</span>
            </a>
            <a href="<?= url('/whatsapp') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'whatsapp' ? 'active' : '' ?>">
                <i class="bi bi-whatsapp"></i>
                <span>WhatsApp Gateway</span>
            </a>

            <?php if (\App\Core\Auth::hasPermission('inventory.access')): ?>
            <div class="nav-heading">Inventaris & Mesin</div>
            <a href="<?= url('/inventory') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'inventory' ? 'active' : '' ?>">
                <i class="bi bi-box-seam-fill"></i>
                <span>Stok Persediaan</span>
            </a>
            <a href="<?= url('/machines') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'machines' ? 'active' : '' ?>">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Mesin & Servis</span>
            </a>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('accounting.access') || \App\Core\Auth::hasRole(['super_admin', 'owner', 'accountant'])): ?>
            <div class="nav-heading">Keuangan</div>
            <a href="<?= url('/accounting/cash') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'accounting_cash' ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i>
                <span>Kas & Bank</span>
            </a>
            <a href="<?= url('/accounting/expenses') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'accounting_expenses' ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i>
                <span>Pengeluaran Biaya</span>
            </a>
            <a href="<?= url('/accounting/journals') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'accounting_journals' ? 'active' : '' ?>">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Jurnal & Akuntansi</span>
            </a>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasPermission('reports.access') || \App\Core\Auth::hasRole(['super_admin', 'owner', 'manager'])): ?>
            <div class="nav-heading">Laporan</div>
            <a href="<?= url('/reports') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Pusat Laporan (30)</span>
            </a>
            <?php endif; ?>

            <?php if (\App\Core\Auth::hasRole(['super_admin', 'owner', 'manager'])): ?>
            <div class="nav-heading">Pengaturan & Admin</div>
            <a href="<?= url('/services') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'services' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Layanan & Tarif</span>
            </a>
            <a href="<?= url('/branches') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'branches' ? 'active' : '' ?>">
                <i class="bi bi-shop"></i>
                <span>Manajemen Cabang</span>
            </a>
            <a href="<?= url('/employees') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'employees' ? 'active' : '' ?>">
                <i class="bi bi-person-badge"></i>
                <span>Karyawan & Shift</span>
            </a>
            <?php if (\App\Core\Auth::hasRole('super_admin')): ?>
            <a href="<?= url('/users') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Pengguna & Hak Akses</span>
            </a>
            <a href="<?= url('/settings') ?>" class="sidebar-link <?= ($activeMenu ?? '') === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i>
                <span>Konfigurasi & Backup</span>
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main Section -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn">
                    <i class="bi bi-list fs-5"></i>
                </button>
                
                <!-- Branch Switcher for Super Admin, Owner, or multi-branch staff -->
                <?php
                    $branches = \App\Core\Database::getInstance()->fetchAll("SELECT id, name, code FROM branches WHERE is_active = 1");
                    $activeBranchId = \App\Core\Auth::getActiveBranchId();
                    $activeBranchName = 'Semua Cabang (Konsolidasi)';
                    foreach ($branches as $b) {
                        if ((int)$b['id'] === (int)$activeBranchId) {
                            $activeBranchName = $b['name'];
                            break;
                        }
                    }
                ?>
                <?php if (\App\Core\Auth::hasGlobalAccess() || count($branches) > 1): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-geo-alt-fill text-primary"></i>
                        <span class="fw-semibold small d-none d-sm-inline"><?= e($activeBranchName) ?></span>
                    </button>
                    <ul class="dropdown-menu shadow-sm">
                        <?php if (\App\Core\Auth::hasGlobalAccess()): ?>
                        <li><a class="dropdown-item <?= empty($activeBranchId) ? 'active' : '' ?>" href="<?= url('/branches/switch?id=0') ?>">Semua Cabang (Konsolidasi)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <?php foreach ($branches as $br): ?>
                            <?php if (\App\Core\Auth::canAccessBranch((int)$br['id'])): ?>
                            <li>
                                <a class="dropdown-item <?= (int)$activeBranchId === (int)$br['id'] ? 'active' : '' ?>" href="<?= url('/branches/switch?id=' . $br['id']) ?>">
                                    [<?= e($br['code']) ?>] <?= e($br['name']) ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                <div class="small fw-semibold text-muted d-flex align-items-center gap-1">
                    <i class="bi bi-geo-alt-fill text-primary"></i> <?= e($activeBranchName) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Topbar Actions & User Profile -->
            <div class="d-flex align-items-center gap-2">
                <!-- Tombol Download / Pasang App Android -->
                <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 rounded-pill px-2 py-1 shadow-sm" onclick="SimLaundry.promptInstall()" title="Download & Pasang Aplikasi Android">
                    <i class="bi bi-android2 fs-6"></i>
                    <span class="d-none d-sm-inline small fw-semibold">App Android</span>
                </button>

                <!-- Theme toggle button -->
                <button type="button" class="btn btn-sm btn-light border rounded-circle p-2" id="themeToggleBtn" title="Ganti Tema">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>

                <!-- User profile dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.8rem;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="small fw-semibold d-none d-md-inline"><?= e($currentUser['name'] ?? 'Pengguna') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header"><?= e($currentUser['role_name'] ?? 'Role') ?></h6></li>
                        <li><a class="dropdown-item" href="<?= url('/profile') ?>"><i class="bi bi-person me-2"></i> Profil Akun</a></li>
                        <li><a class="dropdown-item" href="<?= url('/portal') ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i> Portal Pelanggan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= url('/logout') ?>" method="POST" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="app-content">
            <!-- Flash message alerts -->
            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= e($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flashWarning)): ?>
                <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> <?= e($flashWarning) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<!-- Navigasi Bawah Android (Mobile & Tablet) -->
<nav class="android-bottom-nav d-lg-none" aria-label="Navigasi Bawah Android">
    <a href="<?= url('/dashboard') ?>" class="android-nav-item <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>

    <?php if (\App\Core\Auth::hasPermission('pos.access')): ?>
    <a href="<?= url('/pos') ?>" class="android-nav-item <?= ($activeMenu ?? '') === 'pos' ? 'active' : '' ?>">
        <div class="center-action-btn">
            <i class="bi bi-cart-check-fill"></i>
        </div>
        <span>Kasir POS</span>
    </a>
    <?php endif; ?>

    <a href="<?= url('/orders') ?>" class="android-nav-item <?= ($activeMenu ?? '') === 'orders' ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i>
        <span>Pesanan</span>
    </a>

    <?php if (\App\Core\Auth::hasPermission('production.access')): ?>
    <a href="<?= url('/production') ?>" class="android-nav-item <?= ($activeMenu ?? '') === 'production' ? 'active' : '' ?>">
        <i class="bi bi-diagram-3-fill"></i>
        <span>Produksi</span>
    </a>
    <?php elseif (\App\Core\Auth::hasPermission('delivery.access')): ?>
    <a href="<?= url('/delivery') ?>" class="android-nav-item <?= ($activeMenu ?? '') === 'delivery' ? 'active' : '' ?>">
        <i class="bi bi-truck"></i>
        <span>Kurir</span>
    </a>
    <?php else: ?>
    <a href="<?= url('/customers') ?>" class="android-nav-item <?= ($activeMenu ?? '') === 'customers' ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i>
        <span>Pelanggan</span>
    </a>
    <?php endif; ?>

    <button type="button" class="android-nav-item" id="mobileBottomMenuBtn" onclick="document.getElementById('sidebarToggleBtn')?.click()">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>Menu</span>
    </button>
</nav>

<!-- Notifikasi Mengambang Download / Install PWA Android -->
<div id="pwaInstallBanner" class="pwa-install-banner d-none" role="alert" aria-live="polite">
    <div class="card shadow-lg border-0 overflow-hidden">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 shadow-sm bg-white p-1 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <img src="<?= asset('img/icon-192.png') ?>" alt="SimLaundry" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h6 class="fw-bold mb-0 text-truncate" style="font-size: 0.95rem;">Install SimLaundry</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Android App</span>
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 0.78rem; line-height: 1.25;">
                        Akses lebih cepat, ringan, dan langsung dibuka dari layar utama HP Android Anda.
                    </p>
                </div>
                <button type="button" class="btn-close btn-sm align-self-start" onclick="SimLaundry.dismissInstallBanner()" aria-label="Tutup"></button>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3 pt-2 border-top">
                <button type="button" class="btn btn-sm btn-light border text-muted px-3" onclick="SimLaundry.dismissInstallBanner()">
                    Nanti Saja
                </button>
                <button type="button" class="btn btn-sm btn-primary px-3 d-flex align-items-center gap-1 shadow-sm" onclick="SimLaundry.promptInstall()">
                    <i class="bi bi-android2 fs-6"></i>
                    <span class="fw-bold">Download / Install</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Panduan Install PWA Android -->
<div class="modal fade" id="pwaGuideModal" tabindex="-1" aria-labelledby="pwaGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-android2 fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="pwaGuideModalLabel">Install SimLaundry di Android</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="text-center mb-3">
                    <img src="<?= asset('img/logo.png') ?>" alt="SimLaundry Logo" style="height: 60px;">
                    <p class="text-muted small mt-2">Aplikasi PWA SimLaundry dapat dipasang langsung di HP Android tanpa memakan kuota/memori besar.</p>
                </div>
                <div class="list-group list-group-flush border rounded-3 mb-3">
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
                        <div>
                            <div class="fw-bold small">Buka Menu Browser Chrome</div>
                            <div class="text-muted small">Tekan tombol ikon titik tiga (<strong>&vellip;</strong>) di pojok kanan atas atau bawah layar browser Anda.</div>
                        </div>
                    </div>
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
                        <div>
                            <div class="fw-bold small">Pilih "Install Aplikasi" atau "Tambahkan ke Layar Utama"</div>
                            <div class="text-muted small">Pilih menu <em>"Install app"</em> atau <em>"Add to Home screen"</em> pada daftar menu.</div>
                        </div>
                    </div>
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
                        <div>
                            <div class="fw-bold small">Konfirmasi Pasang & Selesai</div>
                            <div class="text-muted small">Ikon SimLaundry otomatis hadir di beranda HP Anda, siap digunakan kapan saja seperti aplikasi Android resmi.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-primary w-100 fw-bold" data-bs-dismiss="modal">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/chart.min.js') ?>"></script>
<script src="<?= asset('js/laundrypro.js') ?>"></script>

<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= url('service-worker.js') ?>')
            .then(reg => console.log('SimLaundry PWA ServiceWorker registered'))
            .catch(err => console.log('SimLaundry PWA ServiceWorker failed:', err));
    });
}
</script>

<?= $extraScripts ?? '' ?>

</body>
</html>
