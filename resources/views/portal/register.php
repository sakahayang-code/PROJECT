<div class="row justify-content-center py-5">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="rounded bg-primary-subtle text-primary p-3 d-inline-flex mb-2">
                        <i class="bi bi-person-plus fs-2"></i>
                    </span>
                    <h4 class="fw-bold text-dark">Daftar Akun Pelanggan Baru</h4>
                    <p class="text-muted small">Dapatkan bonus <strong>20 Poin Loyalitas</strong> langsung setelah mendaftar!</p>
                </div>

                <form method="POST" action="<?= url('/portal/register') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Rina Novitasari" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nomor WhatsApp Aktif</label>
                        <input type="text" name="whatsapp" class="form-control" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email (Opsional)</label>
                        <input type="email" name="email" class="form-control" placeholder="rina@example.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Cabang Terdekat</label>
                        <select name="branch_id" class="form-select">
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Alamat Penjemputan / Rumah</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Password Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
                            <i class="bi bi-check-circle me-1"></i> Selesaikan Pendaftaran
                        </button>
                        <div class="text-center small text-muted">
                            Sudah memiliki akun? <a href="<?= url('/portal/login') ?>" class="text-primary fw-bold text-decoration-none">Masuk di sini</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
