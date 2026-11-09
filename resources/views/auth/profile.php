<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Profil Pengguna</h4>
        <div class="text-muted small">Kelola informasi akun dan kata sandi Anda</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Informasi Pribadi</div>
            <div class="card-body">
                <form action="<?= url('/profile/update') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username</label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                        <div class="form-text small">Username tidak dapat diubah.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role / Hak Akses</label>
                        <input type="text" class="form-control" value="<?= e($user['role_name']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Email</label>
                        <input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp / Telepon</label>
                        <input type="text" class="form-control" name="phone" value="<?= e($user['phone']) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2 me-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Ganti Kata Sandi</div>
            <div class="card-body">
                <form action="<?= url('/profile/password') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata Sandi Saat Ini</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata Sandi Baru</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" class="form-control" name="new_password_confirmation" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-warning px-4 text-white">
                        <i class="bi bi-shield-lock me-1"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
