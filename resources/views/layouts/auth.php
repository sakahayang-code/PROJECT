<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
    <title><?= e($title ?? 'Login') ?> — <?= e($appConfig['name']) ?></title>
    
    <link rel="icon" type="image/png" href="<?= asset('img/icon-192.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#13AFA8">

    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/laundrypro.css') ?>">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0B1E32 0%, #102A43 50%, #0B817C 100%);
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        [data-bs-theme="dark"] .auth-card {
            background: #152238;
        }
        .auth-header {
            background: linear-gradient(135deg, #13AFA8 0%, #0B817C 100%);
            padding: 2.25rem 2rem;
            text-align: center;
            color: #FFFFFF;
        }
        .auth-body {
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle mb-3 shadow p-2" style="width: 68px; height: 68px;">
                <img src="<?= asset('img/logo.png') ?>" alt="SimLaundry Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h4 class="fw-bold mb-1"><?= e($appConfig['name']) ?></h4>
            <div class="small opacity-75"><?= e($appConfig['tagline']) ?></div>
        </div>
        <div class="auth-body">
            <!-- Flash message alerts -->
            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show small" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= e($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>

            <div class="text-center mt-3 pt-3 border-top">
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 shadow-sm" onclick="SimLaundry.promptInstall()">
                    <i class="bi bi-android2 me-1"></i> Download App Android
                </button>
            </div>
        </div>
    </div>
</div>

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
                        Pasang di layar utama HP Android untuk akses cepat dan praktis.
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
                    <p class="text-muted small mt-2">Aplikasi PWA SimLaundry dapat dipasang langsung di HP Android Anda.</p>
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
                            <div class="text-muted small">Ikon SimLaundry otomatis hadir di beranda HP Anda, siap digunakan kapan saja.</div>
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
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= url('service-worker.js') ?>')
            .catch(err => console.log('SW error:', err));
    });
}
</script>

</body>
</html>
