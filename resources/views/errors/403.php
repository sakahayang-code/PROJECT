<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/laundrypro.css') ?>">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="text-center p-4">
        <h1 class="display-1 fw-bold text-danger">403</h1>
        <h4 class="fw-semibold mb-3">Akses Ditolak</h4>
        <p class="text-muted mb-4">Anda tidak memiliki izin atau hak akses untuk membuka halaman atau fitur ini.</p>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary px-4">Kembali ke Dashboard</a>
    </div>
</body>
</html>
