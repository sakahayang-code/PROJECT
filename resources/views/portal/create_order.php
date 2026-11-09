<div class="row justify-content-center py-4">
    <div class="col-12 col-md-9 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Pesan Penjemputan Cucian (Pickup Request)</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">Kurir SimLaundry akan datang menjemput pakaian kotor ke lokasi Anda sesuai jadwal yang dipilih.</p>

                <form method="POST" action="<?= url('/portal/pickup/create') ?>" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Pilih Cabang Laundry</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['address']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!empty($addresses)): ?>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Gunakan Alamat Tersimpan</label>
                            <select class="form-select" onchange="document.getElementById('portalAddressInput').value = this.value">
                                <option value="">-- Pilih Alamat Tersimpan atau Ketik di Bawah --</option>
                                <?php foreach ($addresses as $addr): ?>
                                    <option value="<?= e($addr['address']) ?>"><?= e($addr['label'] . ' - ' . $addr['address']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Alamat Lengkap Penjemputan</label>
                        <textarea name="address_text" id="portalAddressInput" class="form-control" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, nomor kamar/apartemen..." required><?= e($customer['address'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tanggal Penjemputan</label>
                        <input type="date" name="schedule_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Pilihan Waktu</label>
                        <select name="schedule_time" class="form-select">
                            <option value="09:00 - 12:00">Pagi (09:00 - 12:00 WIB)</option>
                            <option value="13:00 - 16:00">Siang (13:00 - 16:00 WIB)</option>
                            <option value="16:00 - 19:00">Sore (16:00 - 19:00 WIB)</option>
                            <option value="Fleksibel">Fleksibel / Kapan Saja</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">Catatan Untuk Kurir & Kasir</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Estimasi 5 kg kiloan + 2 sprei bedcover. Titip di satpam bila tidak ada orang.">
                    </div>

                    <div class="col-12 mt-4 d-flex justify-content-between">
                        <a href="<?= url('/portal') ?>" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i> Kirim Permintaan Jemput
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
