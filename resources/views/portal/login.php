<div class="row justify-content-center py-5">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm p-2 mb-2" style="width: 72px; height: 72px;">
                        <img src="<?= asset('img/logo.png') ?>" alt="LaundryPro Logo" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                    <h4 class="fw-bold text-dark">Portal Pelanggan</h4>
                    <p class="text-muted small">Masuk untuk memantau cucian, bayar pesanan, dan gunakan poin diskon.</p>
                </div>

                <form method="POST" action="<?= url('/portal/login') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nomor WhatsApp atau Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                            <input type="text" name="identifier" class="form-control" placeholder="08xxxxxxxxxx / email@domain.com" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                    </button>
                    <div class="text-center small text-muted">
                        Belum punya akun? <a href="<?= url('/portal/register') ?>" class="text-primary fw-bold text-decoration-none">Daftar Akun Baru</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
