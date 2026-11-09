<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
    <title><?= e($title ?? 'Portal Pelanggan') ?> — LaundryPro</title>

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
            <span class="fw-bold fs-5 tracking-tight">LaundryPro <span class="badge bg-light text-primary small" style="font-size:0.65rem;">Customer Portal</span></span>
        </a>
        <div class="d-flex align-items-center gap-2">
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
        <div class="mb-2 fw-semibold text-white">LaundryPro Indonesia &bull; Smart Laundry Business System</div>
        <div>Layanan cuci higienis, bersih, wangi, dan tepat waktu untuk kenyamanan Anda.</div>
    </div>
</footer>

<script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/laundrypro.js') ?>"></script>

</body>
</html>
