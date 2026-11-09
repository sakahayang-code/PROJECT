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

<hr class="my-4">

<!-- Demo Quick Accounts Accordion -->
<div class="accordion" id="demoAccountsAccordion">
    <div class="accordion-item border-0">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2 px-3 bg-light rounded small fw-semibold text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#demoAccountsList">
                <i class="bi bi-key-fill text-warning me-2"></i> Akun Demo Cepat (Klik untuk Isi Otomatis)
            </button>
        </h2>
        <div id="demoAccountsList" class="accordion-collapse collapse" data-bs-parent="#demoAccountsAccordion">
            <div class="accordion-body p-2 small">
                <div class="d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('superadmin', 'Admin@123')">Super Admin</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('owner', 'Admin@123')">Owner / Pemilik</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('manager.pusat', 'Admin@123')">Manager</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('kasir.pusat', 'Admin@123')">Kasir (POS)</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('operator.pusat', 'Admin@123')">Produksi</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('kurir.pusat', 'Admin@123')">Kurir</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('gudang.pusat', 'Admin@123')">Gudang</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="fillLogin('akuntan', 'Admin@123')">Akuntan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-3 small">
    <a href="<?= url('/portal/login') ?>" class="text-decoration-none">
        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Portal Khusus Pelanggan
    </a>
</div>

<script>
function fillLogin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
}
</script>
