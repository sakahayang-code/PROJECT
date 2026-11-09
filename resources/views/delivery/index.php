<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Total Tugas Hari Ini</div>
                    <div class="stat-value text-primary"><?= (int)$counts['total'] ?></div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-truck fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Menunggu Penugasan</div>
                    <div class="stat-value text-warning"><?= (int)$counts['pending'] ?></div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-hourglass-split fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Dalam Perjalanan</div>
                    <div class="stat-value text-info"><?= (int)$counts['on_the_way'] ?></div>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-geo-alt-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-semibold">Selesai Dijemput / Diantar</div>
                    <div class="stat-value text-success"><?= (int)$counts['completed'] ?></div>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/delivery') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $selectedBranch == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tipe</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua Tipe</option>
                    <option value="pickup" <?= $selectedType === 'pickup' ? 'selected' : '' ?>>Penjemputan (Pickup)</option>
                    <option value="delivery" <?= $selectedType === 'delivery' ? 'selected' : '' ?>>Pengantaran (Delivery)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Menunggu Kurir</option>
                    <option value="assigned" <?= $selectedStatus === 'assigned' ? 'selected' : '' ?>>Ditugaskan</option>
                    <option value="on_the_way" <?= $selectedStatus === 'on_the_way' ? 'selected' : '' ?>>Dalam Perjalanan</option>
                    <option value="picked_up" <?= $selectedStatus === 'picked_up' ? 'selected' : '' ?>>Sudah Dijemput</option>
                    <option value="delivered" <?= $selectedStatus === 'delivered' ? 'selected' : '' ?>>Sudah Diantar</option>
                    <option value="failed" <?= $selectedStatus === 'failed' ? 'selected' : '' ?>>Gagal</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($selectedDate) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                <a href="<?= url('/delivery') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalCreateDelivery">
                    <i class="bi bi-plus-circle me-1"></i> Baru
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Daftar Tugas Pickup & Pengantaran</h6>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($requests) ?> data ditemukan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tipe & Jadwal</th>
                    <th>Pelanggan</th>
                    <th>Alamat & Lokasi</th>
                    <th>Nota Pesanan</th>
                    <th>Kurir</th>
                    <th>Biaya Kirim</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            Tidak ada jadwal penjemputan/pengantaran untuk filter yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <?php if ($r['type'] === 'pickup'): ?>
                                    <span class="badge bg-info-subtle text-info mb-1"><i class="bi bi-box-arrow-in-down me-1"></i>Penjemputan</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary mb-1"><i class="bi bi-box-arrow-up-right me-1"></i>Pengantaran</span>
                                <?php endif; ?>
                                <div class="small fw-semibold"><?= format_date($r['schedule_date'], 'd M Y') ?></div>
                                <div class="text-muted small"><i class="bi bi-clock me-1"></i><?= e($r['schedule_time'] ?: 'Fleksibel') ?></div>
                            </td>
                            <td>
                                <div class="fw-bold"><?= e($r['customer_name']) ?></div>
                                <div class="small text-muted"><i class="bi bi-whatsapp me-1"></i><?= e($r['customer_phone']) ?></div>
                            </td>
                            <td style="max-width: 250px;">
                                <div class="small text-break"><?= e($r['address_text']) ?></div>
                                <?php if (!empty($r['notes'])): ?>
                                    <div class="small text-muted fst-italic mt-1"><i class="bi bi-chat-left-dots me-1"></i><?= e($r['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['order_no'])): ?>
                                    <span class="badge bg-light text-dark border"><?= e($r['order_no']) ?></span>
                                    <div class="small fw-semibold mt-1"><?= format_rupiah($r['grand_total']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">- Belum ada nota -</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['courier_name'])): ?>
                                    <div class="fw-semibold text-primary"><i class="bi bi-person-badge me-1"></i><?= e($r['courier_name']) ?></div>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning">Belum Ada Kurir</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= (int)$r['fee'] > 0 ? format_rupiah($r['fee']) : '<span class="text-success">Gratis</span>' ?></div>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($r['status']) {
                                    'pending' => 'bg-warning-subtle text-warning',
                                    'assigned' => 'bg-info-subtle text-info',
                                    'on_the_way' => 'bg-primary-subtle text-primary',
                                    'picked_up', 'delivered' => 'bg-success-subtle text-success',
                                    'failed', 'cancelled' => 'bg-danger-subtle text-danger',
                                    default => 'bg-secondary'
                                };
                                $statusLabel = match($r['status']) {
                                    'pending' => 'Menunggu Kurir',
                                    'assigned' => 'Ditugaskan',
                                    'on_the_way' => 'Dalam Perjalanan',
                                    'picked_up' => 'Selesai Dijemput',
                                    'delivered' => 'Selesai Diantar',
                                    'failed' => 'Gagal',
                                    'cancelled' => 'Dibatalkan',
                                    default => $r['status']
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        Aksi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="openAssignModal(<?= $r['id'] ?>, '<?= e($r['customer_name']) ?>', <?= (int)$r['courier_user_id'] ?>)">
                                                <i class="bi bi-person-plus me-2 text-primary"></i>Tugaskan Kurir
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="openStatusModal(<?= $r['id'] ?>, '<?= $r['status'] ?>')">
                                                <i class="bi bi-arrow-repeat me-2 text-info"></i>Ubah Status
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)" onclick="openProofModal(<?= $r['id'] ?>, '<?= $r['type'] ?>', '<?= e($r['customer_name']) ?>')">
                                                <i class="bi bi-camera me-2 text-success"></i>Upload Bukti Terima
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create Delivery -->
<div class="modal fade" id="modalCreateDelivery" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/delivery/create') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Buat Tugas Pickup / Delivery Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Cabang</label>
                    <select name="branch_id" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tipe Layanan</label>
                    <select name="type" class="form-select" required>
                        <option value="pickup">Jemput Pakaian Kotor (Pickup)</option>
                        <option value="delivery">Antar Cucian Bersih (Delivery)</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold">Pelanggan</label>
                    <select name="customer_id" id="deliveryCustomerId" class="form-select" required onchange="fillCustomerAddress(this)">
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" data-address="<?= e($c['address']) ?>"><?= e($c['code'] . ' - ' . $c['name'] . ' (' . $c['phone'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold">Alamat Lengkap</label>
                    <textarea name="address_text" id="deliveryAddressText" class="form-control" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, patokan lokasi..." required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tanggal Jadwal</label>
                    <input type="date" name="schedule_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Waktu Penjemputan/Pengantaran</label>
                    <input type="text" name="schedule_time" class="form-control" placeholder="Contoh: 10:00 - 12:00">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Tugaskan Kurir (Opsional)</label>
                    <select name="courier_user_id" class="form-select">
                        <option value="">-- Tugaskan Nanti --</option>
                        <?php foreach ($couriers as $cr): ?>
                            <option value="<?= $cr['id'] ?>"><?= e($cr['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Biaya Pengiriman (Rp)</label>
                    <input type="text" name="fee" class="form-control" value="0" placeholder="0 jika gratis">
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold">Catatan Khusus</label>
                    <input type="text" name="notes" class="form-control" placeholder="Contoh: Titip di pos satpam bila tidak ada orang di rumah">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Tugas</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Assign Courier -->
<div class="modal fade" id="modalAssignCourier" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/delivery/assign') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="delivery_request_id" id="assignRequestId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Tugaskan Kurir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Tugaskan kurir untuk pengiriman pelanggan: <strong id="assignCustomerName" class="text-dark"></strong></p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih Kurir</label>
                    <select name="courier_user_id" id="assignCourierId" class="form-select" required>
                        <option value="">-- Pilih Kurir Aktif --</option>
                        <?php foreach ($couriers as $cr): ?>
                            <option value="<?= $cr['id'] ?>"><?= e($cr['name']) ?> (<?= e($cr['phone']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Simpan Penugasan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Update Status -->
<div class="modal fade" id="modalUpdateStatus" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/delivery/status') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="delivery_request_id" id="statusRequestId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-repeat me-2 text-info"></i>Ubah Status Pengiriman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Status Baru</label>
                    <select name="status" id="selectNewStatus" class="form-select" required>
                        <option value="assigned">Ditugaskan (Assigned)</option>
                        <option value="on_the_way">Dalam Perjalanan (On The Way)</option>
                        <option value="picked_up">Selesai Dijemput (Picked Up)</option>
                        <option value="delivered">Selesai Diantar (Delivered)</option>
                        <option value="failed">Pengiriman Gagal</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan Perubahan Status</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Alasan atau catatan perjalanan..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Perbarui Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Upload Proof -->
<div class="modal fade" id="modalUploadProof" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/delivery/proof') ?>" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="delivery_request_id" id="proofRequestId">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-camera me-2 text-success"></i>Upload Bukti Serah Terima</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Penerima / Penyerah Barang</label>
                    <input type="text" name="recipient_name" id="proofRecipientName" class="form-control" required placeholder="Contoh: Ibu Rina (Pemilik / ART / Satpam)">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Foto Bukti Penerimaan (Opsional)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*" capture="environment">
                    <div class="form-text small">Dapat menggunakan kamera smartphone saat di lokasi serah terima.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan Serah Terima</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Kondisi barang saat diserahkan..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Simpan & Selesaikan</button>
            </div>
        </form>
    </div>
</div>

<script>
function fillCustomerAddress(select) {
    const opt = select.options[select.selectedIndex];
    const addr = opt.getAttribute('data-address');
    if (addr) {
        document.getElementById('deliveryAddressText').value = addr;
    }
}

function openAssignModal(id, custName, courierId) {
    document.getElementById('assignRequestId').value = id;
    document.getElementById('assignCustomerName').innerText = custName;
    if (courierId) {
        document.getElementById('assignCourierId').value = courierId;
    }
    new bootstrap.Modal(document.getElementById('modalAssignCourier')).show();
}

function openStatusModal(id, currentStatus) {
    document.getElementById('statusRequestId').value = id;
    document.getElementById('selectNewStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('modalUpdateStatus')).show();
}

function openProofModal(id, type, custName) {
    document.getElementById('proofRequestId').value = id;
    document.getElementById('proofRecipientName').value = custName;
    new bootstrap.Modal(document.getElementById('modalUploadProof')).show();
}
</script>
