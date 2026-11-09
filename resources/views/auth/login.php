<form action="<?= url('/login') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="username" class="form-label small fw-semibold">Username / Email / Telepon</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username atau email" required autofocus>
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold">Kata Sandi</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata sandi" required>
        </div>
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
        </button>
    </div>
</form>

<div class="text-center mt-3 small">
    <a href="<?= url('/portal/login') ?>" class="text-decoration-none">
        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Portal Khusus Pelanggan
    </a>
</div>
