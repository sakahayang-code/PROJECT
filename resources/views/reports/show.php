<div class="d-print-none row g-3 mb-4">
    <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('/reports') ?>" class="text-decoration-none">Pusat Laporan</a></li>
                    <li class="breadcrumb-item active"><?= e($reportMeta['title']) ?></li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark"><?= e($reportMeta['title']) ?></h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/' . $slug . '?export=csv&branch_id=' . $selectedBranch . '&start_date=' . $startDate . '&end_date=' . $endDate) ?>" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Unduh CSV
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Format Bersih
            </button>
            <a href="<?= url('/reports') ?>" class="btn btn-light btn-sm border">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="d-print-none card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/reports/' . $slug) ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Cabang</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $selectedBranch == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Terapkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards (If Any) -->
<?php if (!empty($data['summary'])): ?>
    <div class="row g-3 mb-4">
        <?php foreach ($data['summary'] as $label => $val): ?>
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="text-muted small fw-semibold"><?= e($label) ?></div>
                    <div class="stat-value text-primary fs-4"><?= is_numeric($val) ? number_format((float)$val) : e($val) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Data Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold m-0"><i class="bi bi-table me-2 text-primary"></i>Tabel Data: <?= e($reportMeta['title']) ?></h6>
        <span class="badge bg-light text-dark border"><?= count($data['rows'] ?? []) ?> baris data</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach ($data['headers'] as $header): ?>
                        <th><?= e($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['rows'])): ?>
                    <tr>
                        <td colspan="<?= count($data['headers']) ?>" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            Tidak ada data untuk filter dan rentang tanggal yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['rows'] as $row): ?>
                        <tr>
                            <?php foreach ((array)$row as $colKey => $cell): ?>
                                <td>
                                    <?php
                                    // Automatic formatting for currency or dates
                                    if (is_numeric($cell) && (str_contains((string)$colKey, 'total') || str_contains((string)$colKey, 'amount') || str_contains((string)$colKey, 'nominal') || str_contains((string)$colKey, 'revenue') || str_contains((string)$colKey, 'price') || str_contains((string)$colKey, 'cost') || str_contains((string)$colKey, 'fee') || str_contains((string)$colKey, 'due') || str_contains((string)$colKey, 'income') || str_contains((string)$colKey, 'paid'))) {
                                        echo format_rupiah((float)$cell);
                                    } elseif (is_numeric($cell) && str_contains((string)$colKey, 'weight')) {
                                        echo number_format((float)$cell, 1) . ' Kg';
                                    } else {
                                        echo e((string)$cell);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
