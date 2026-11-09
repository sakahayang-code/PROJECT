<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Role & Hak Akses (RBAC)</h4>
        <div class="text-muted small">Konfigurasi hak akses dan izin granular per role pengguna</div>
    </div>
    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Pengguna</span>
    </a>
</div>

<div class="row g-4">
    <?php foreach ($roles as $role): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold mb-0 text-primary"><?= e($role['name']) ?></h5>
                    <span class="badge bg-secondary"><?= $role['user_count'] ?> pengguna</span>
                </div>
                <div class="text-muted small mb-3">
                    <?= e($role['description'] ?: 'Tidak ada deskripsi.') ?>
                </div>
                <div class="small fw-semibold text-muted">
                    Total Izin Aktif: <span class="badge bg-info-subtle text-info"><?= $role['permission_count'] ?> izin</span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openPermissionsModal(<?= $role['id'] ?>, '<?= e($role['name']) ?>')">
                    <i class="bi bi-shield-lock me-1"></i> Atur Hak Akses
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Atur Izin Role -->
<div class="modal fade" id="permissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="permForm" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="permModalTitle">Atur Hak Akses Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Centang izin yang diperbolehkan untuk role ini.
                    </div>

                    <?php foreach ($groupedPermissions as $groupName => $perms): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2 fw-bold small text-uppercase text-secondary">
                            <?= e($groupName) ?>
                        </div>
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <?php foreach ($perms as $p): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>">
                                        <label class="form-check-label small" for="perm_<?= $p['id'] ?>">
                                            <span class="fw-semibold"><?= e($p['name']) ?></span>
                                            <div class="text-muted" style="font-size:0.75rem;"><?= e($p['description']) ?></div>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Hak Akses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPermissionsModal(roleId, roleName) {
    document.getElementById('permModalTitle').innerText = 'Atur Hak Akses Role: ' + roleName;
    document.getElementById('permForm').action = '<?= url('/roles/') ?>' + roleId + '/permissions';

    // Reset all checkboxes
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);

    // Fetch assigned permissions
    LaundryPro.fetch('<?= url('/roles/') ?>' + roleId + '/permissions')
        .then(res => res.json())
        .then(data => {
            if (data.permissions) {
                data.permissions.forEach(pid => {
                    const cb = document.getElementById('perm_' + pid);
                    if (cb) cb.checked = true;
                });
            }
            new bootstrap.Modal(document.getElementById('permissionsModal')).show();
        });
}
</script>
