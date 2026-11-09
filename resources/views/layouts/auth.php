<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
    <title><?= e($title ?? 'Login') ?> — <?= e($appConfig['name']) ?></title>
    
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
            <div class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle mb-3 shadow" style="width: 54px; height: 54px;">
                <i class="bi bi-water fs-2"></i>
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
        </div>
    </div>
</div>

<script src="<?= asset('js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/laundrypro.js') ?>"></script>

</body>
</html>
