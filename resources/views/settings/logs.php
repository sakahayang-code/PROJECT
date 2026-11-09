<div class="row g-3 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-journal-text text-info me-2"></i>Log Aktivitas & Riwayat Audit Sistem</h4>
            <p class="text-muted small mb-0">Audit trail lengkap seluruh tindakan pengguna, transaksi keuangan, perubahan status, dan catatan keamanan login.</p>
        </div>
        <div>
            <a href="<?= url('/settings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali ke Pengaturan</a>
        </div>
    </div>
</div>

<!-- Filter Cabang -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/settings/logs') ?>" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="col-form-label small fw-bold"><i class="bi bi-funnel me-1"></i> Filter Cabang:</label>
            </div>
            <div class="col-auto">
                <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0" <?= ($selectedBranch === 0) ? 'selected' : '' ?>>Semua Cabang (Konsolidasi)</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($selectedBranch === (int)$b['id']) ? 'selected' : '' ?>>
                            <?= e($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <a href="<?= url('/settings/logs') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabs: Audit Trail vs Login History -->
<ul class="nav nav-pills mb-3" id="logTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active px-4" id="activity-tab" data-bs-toggle="pill" data-bs-target="#activity-content" type="button" role="tab">
            <i class="bi bi-activity me-1"></i> Aktivitas Sistem (<?= count($logs) ?> Terakhir)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-4" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-content" type="button" role="tab">
            <i class="bi bi-shield-lock me-1"></i> Riwayat Login & Akses (<?= count($loginLogs) ?> Terakhir)
        </button>
    </li>
</ul>

<div class="tab-content" id="logTabsContent">
    <!-- Tab 1: Activity Logs -->
    <div class="tab-pane fade show active" id="activity-content" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Cabang</th>
                                <th>Aksi</th>
                                <th>Entitas</th>
                                <th>Deskripsi Aktivitas</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Tidak ada catatan aktivitas yang cocok dengan filter.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                    $actionClass = 'bg-secondary';
                                    $act = strtolower($log['action'] ?? '');
                                    if (strpos($act, 'create') !== false || strpos($act, 'add') !== false || strpos($act, 'store') !== false) {
                                        $actionClass = 'bg-success';
                                    } elseif (strpos($act, 'update') !== false || strpos($act, 'edit') !== false) {
                                        $actionClass = 'bg-primary';
                                    } elseif (strpos($act, 'delete') !== false || strpos($act, 'cancel') !== false) {
                                        $actionClass = 'bg-danger';
                                    } elseif (strpos($act, 'login') !== false) {
                                        $actionClass = 'bg-info';
                                    }
                                    ?>
                                    <tr>
                                        <td class="text-nowrap text-muted">
                                            <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= e($log['user_name'] ?? 'Sistem') ?></span>
                                            <?php if (!empty($log['username'])): ?>
                                                <small class="text-muted d-block font-monospace">@<?= e($log['username']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($log['branch_name'] ?? 'Semua') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $actionClass ?> font-monospace"><?= e($log['action']) ?></span>
                                        </td>
                                        <td>
                                            <span class="font-monospace text-muted"><?= e($log['entity_type'] ?? '-') ?> <?= $log['entity_id'] ? '#' . e($log['entity_id']) : '' ?></span>
                                        </td>
                                        <td>
                                            <?= e($log['description'] ?? '-') ?>
                                        </td>
                                        <td class="font-monospace text-muted small">
                                            <?= e($log['ip_address'] ?? '127.0.0.1') ?>
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

    <!-- Tab 2: Login Logs -->
    <div class="tab-pane fade" id="login-content" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Waktu Login</th>
                                <th>Username / Email</th>
                                <th>Nama Pengguna</th>
                                <th>Status</th>
                                <th>Alasan Kegagalan</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($loginLogs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-shield-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada riwayat autentikasi login tercatat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($loginLogs as $ll): ?>
                                    <tr>
                                        <td class="text-nowrap text-muted">
                                            <?= date('d/m/Y H:i:s', strtotime($ll['created_at'])) ?>
                                        </td>
                                        <td class="fw-bold font-monospace">
                                            <?= e($ll['username_attempted'] ?? '-') ?>
                                        </td>
                                        <td>
                                            <?= e($ll['user_name'] ?? '<i class="text-muted">Tidak Dikenal</i>') ?>
                                        </td>
                                        <td>
                                            <?php if (($ll['status'] ?? '') === 'success'): ?>
                                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Berhasil</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i> Gagal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-danger small">
                                            <?= e($ll['failure_reason'] ?? '-') ?>
                                        </td>
                                        <td class="font-monospace text-muted small">
                                            <?= e($ll['ip_address'] ?? '127.0.0.1') ?>
                                        </td>
                                        <td class="text-truncate text-muted small" style="max-width: 200px;" title="<?= e($ll['user_agent'] ?? '') ?>">
                                            <?= e($ll['user_agent'] ?? '-') ?>
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
