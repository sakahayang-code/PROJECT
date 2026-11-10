<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-database text-success me-2"></i>Backup & Restore Database SQLite</h4>
            <p class="text-muted small mb-0">Kelola snapshot cadangan data, unduh file database, dan lakukan pemulihan sistem secara aman.</p>
        </div>
        <div>
            <a href="<?= url('/settings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Pengaturan</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Status & Buat Backup -->
    <div class="col-12 col-lg-5">
        <!-- Status Database Aktif -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-hdd-network me-2 text-primary"></i>Informasi Database Aktif</h6>
            </div>
            <div class="card-body">
                <?php
                $dbPath = base_path('database/laundrypro.sqlite');
                $dbSize = file_exists($dbPath) ? round(filesize($dbPath) / 1024, 2) . ' KB' : '0 KB';
                ?>
                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <td class="text-muted small w-40">Tipe Database:</td>
                        <td class="small fw-bold">SQLite 3 (PDO)</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Journal Mode:</td>
                        <td class="small"><span class="badge bg-success-subtle text-success">WAL (Write-Ahead Logging)</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Foreign Keys:</td>
                        <td class="small"><span class="badge bg-primary-subtle text-primary">ON (Strict Referential Integrity)</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Ukuran File:</td>
                        <td class="small fw-bold font-monospace"><?= $dbSize ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Lokasi File:</td>
                        <td class="small text-break font-monospace text-muted"><?= e(basename($dbPath)) ?></td>
                    </tr>
                </table>

                <div class="d-grid gap-2 mb-3">
                    <form method="POST" action="<?= url('/settings/backup/create') ?>" onsubmit="return confirm('Buat snapshot cadangan database saat ini?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success w-100 py-2">
                            <i class="bi bi-camera-fill me-2"></i> Buat Snapshot Backup Sekarang
                        </button>
                    </form>
                    
                    <a href="<?= url('/settings/backup/archive') ?>" class="btn btn-primary py-2 text-start d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-file-earmark-zip-fill me-2"></i> Unduh Full Backup (.ZIP / .TAR.GZ)</span>
                        <span class="badge bg-white text-primary">DB + Uploads</span>
                    </a>

                    <a href="<?= url('/settings/backup/export-json') ?>" class="btn btn-outline-info py-2 text-start d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-filetype-json me-2"></i> Ekspor Data Master (JSON Portable)</span>
                        <span class="badge bg-info-subtle text-info">Semua Tabel</span>
                    </a>
                </div>

                <div class="alert alert-success-subtle border-success-subtle p-2.5 mb-0 small">
                    <div class="fw-bold text-success mb-1"><i class="bi bi-shield-lock-fill me-1"></i> Data Armor Protection Aktif</div>
                    <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.8rem;">
                        <li><strong>Kebal Update:</strong> Database & folder uploads tidak akan pernah tertimpa saat update aplikasi.</li>
                        <li><strong>Auto-Snapshot:</strong> Snapshot cadangan otomatis dibuat sebelum deployment dan sebelum migrasi.</li>
                        <li><strong>Akses Terkunci:</strong> File database SQLite dilindungi oleh <code>.htaccess</code> dari akses publik.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Form Restore Database -->
        <div class="card shadow-sm border-0 mb-4 border-start border-danger border-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title fw-bold text-danger m-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Restore Database SQLite</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning small p-2 mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i> <strong>Perhatian:</strong> Memulihkan database akan menimpa data yang sedang berjalan. Snapshot cadangan otomatis akan dibuat sebelum file baru diterapkan.
                </div>

                <form method="POST" action="<?= url('/settings/backup/restore') ?>" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin ingin memulihkan database dari file ini? Seluruh data aktif akan diperbarui sesuai file yang diunggah.')">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih File SQLite Cadangan (.sqlite)</label>
                        <input type="file" name="backup_file" class="form-control form-control-sm" accept=".sqlite,.db" required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmRestore" required>
                        <label class="form-check-label small" for="confirmRestore">
                            Saya memahami risiko dan ingin melanjutkan pemulihan data.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 py-2">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Pulihkan Database (Restore)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar File Backup Tersimpan -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title fw-bold m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Daftar Snapshot Backup Tersimpan</h6>
                <span class="badge bg-secondary"><?= count($backups) ?> File</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Waktu Dibuat</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($backups)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada file backup tersimpan. Klik tombol <strong>Buat Snapshot Backup Sekarang</strong>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backups as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold font-monospace text-primary"><?= e($b['filename']) ?></div>
                                        </td>
                                        <td class="font-monospace text-muted">
                                            <?= round($b['size'] / 1024, 2) ?> KB
                                        </td>
                                        <td class="text-muted">
                                            <?= date('d M Y, H:i', strtotime($b['created_at'])) ?> WIB
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('/settings/backup/download/' . urlencode($b['filename'])) ?>" class="btn btn-sm btn-outline-primary" title="Unduh File SQLite">
                                                <i class="bi bi-download me-1"></i> Unduh
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
