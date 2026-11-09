<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
    <title><?= e($title ?? 'Portal Pelanggan') ?> — SimLaundry</title>

    <link rel="icon" type="image/png" href="<?= asset('img/icon-192.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#13AFA8">

    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/laundrypro.css') ?>">
    <style>
        .portal-header {
            background: linear-gradient(135deg, #102A43 0%, #0B817C 100%);
            color: #FFFFFF;
            padding: 1.25rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .portal-footer {
            background: #102A43;
            color: #BAC7D5;
            padding: 2rem 0;
            margin-top: auto;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<header class="portal-header">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="<?= url('/portal') ?>" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <span class="rounded bg-white p-1 d-inline-flex shadow-sm" style="width: 32px; height: 32px;">
                <img src="<?= asset('img/logo.png') ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </span>
            <span class="fw-bold fs-5 tracking-tight">SimLaundry <span class="badge bg-light text-primary small" style="font-size:0.65rem;">Customer Portal</span></span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-2 py-1 d-flex align-items-center gap-1 shadow-sm" onclick="SimLaundry.promptInstall()" title="Download & Pasang Aplikasi Android">
                <i class="bi bi-android2 text-warning fs-6"></i>
                <span class="d-none d-sm-inline small fw-semibold">App Android</span>
            </button>
            <?php if (\App\Core\Auth::checkCustomer()): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span><?= e(\App\Core\Auth::customer()['name'] ?? 'Akun Saya') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="<?= url('/portal/my-orders') ?>">Pesanan Saya</a></li>
                        <li><a class="dropdown-item" href="<?= url('/portal/profile') ?>">Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= url('/portal/logout') ?>" method="POST" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= url('/portal/login') ?>" class="btn btn-sm btn-light fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container my-4 flex-grow-1">
    <?= $content ?? '' ?>
</main>

<footer class="portal-footer small text-center">
    <div class="container">
        <div class="mb-2 fw-semibold text-white">SimLaundry Indonesia &bull; Smart Laundry Business System</div>
        <div>Layanan cuci higienis, bersih, wangi, dan tepat waktu untuk kenyamanan Anda.</div>
    </div>
</footer>

<!-- Navigasi Bawah Android Pelanggan (Mobile) -->
<nav class="android-bottom-nav d-lg-none" aria-label="Navigasi Bawah Android">
    <a href="<?= url('/portal') ?>" class="android-nav-item <?= !str_contains($_SERVER['REQUEST_URI'] ?? '', 'my-orders') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'create-order') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'profile') ? 'active' : '' ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Beranda</span>
    </a>

    <a href="<?= url('/portal/my-orders') ?>" class="android-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'my-orders') ? 'active' : '' ?>">
        <i class="bi bi-receipt"></i>
        <span>Pesanan</span>
    </a>

    <a href="<?= url('/portal/create-order') ?>" class="android-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'create-order') ? 'active' : '' ?>">
        <div class="center-action-btn">
            <i class="bi bi-plus-lg"></i>
        </div>
        <span>Pesan Jemput</span>
    </a>

    <button type="button" class="android-nav-item" onclick="SimLaundry.promptInstall()" title="Download Aplikasi Android">
        <i class="bi bi-android2 text-success"></i>
        <span>Download</span>
    </button>

    <?php if (\App\Core\Auth::checkCustomer()): ?>
    <a href="<?= url('/portal/profile') ?>" class="android-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'profile') ? 'active' : '' ?>">
        <i class="bi bi-person-circle"></i>
        <span>Akun</span>
    </a>
    <?php else: ?>
    <a href="<?= url('/portal/login') ?>" class="android-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'login') ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Masuk</span>
    </a>
    <?php endif; ?>
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
                        Pesan jemput pakaian, lacak cucian realtime, dan dapatkan poin diskon lebih cepat langsung dari HP Anda.
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
                    <p class="text-muted small mt-2">Pasang aplikasi SimLaundry langsung di layar utama HP Anda dengan cepat tanpa ribet.</p>
                </div>
                <div class="list-group list-group-flush border rounded-3 mb-3">
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
                        <div>
                            <div class="fw-bold small">Buka Menu Browser Chrome</div>
                            <div class="text-muted small">Tekan tombol ikon titik tiga (<strong>&vellip;</strong>) di pojok kanan atas browser Anda.</div>
                        </div>
                    </div>
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
                        <div>
                            <div class="fw-bold small">Pilih "Install Aplikasi" atau "Tambahkan ke Layar Utama"</div>
                            <div class="text-muted small">Pilih menu <em>"Install app"</em> atau <em>"Add to Home screen"</em> pada opsi yang muncul.</div>
                        </div>
                    </div>
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge bg-primary rounded-circle p-2 fs-6" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
                        <div>
                            <div class="fw-bold small">Konfirmasi Pasang & Selesai</div>
                            <div class="text-muted small">Ikon SimLaundry otomatis hadir di layar HP Anda, siap digunakan kapan saja.</div>
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

<script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/laundrypro.js') ?>"></script>

</body>
</html>
