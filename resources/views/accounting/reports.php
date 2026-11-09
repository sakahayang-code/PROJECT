<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Laporan Keuangan & Akuntansi</h4>
        <div class="text-muted small">Laporan Laba Rugi Komprehensif, Neraca Keuangan, dan Neraca Saldo dari jurnal valid</div>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-printer"></i>
            <span>Cetak Laporan</span>
        </button>
    </div>
</div>

<!-- Date Filter Bar -->
<div class="card shadow-sm mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('/accounting/reports') ?>" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Dari Tanggal:</label>
                <input type="date" class="form-control form-control-sm" name="start_date" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Sampai Tanggal:</label>
                <input type="date" class="form-control form-control-sm" name="end_date" value="<?= e($endDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label d-none d-md-block mb-1">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-primary w-100">Tampilkan Laporan</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 no-print" role="tablist">
    <li class="nav-item">
        <button class="nav-link active px-4 fw-semibold" data-bs-toggle="pill" data-bs-target="#tab-income">
            <i class="bi bi-graph-up me-1"></i> Laba Rugi
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link px-4 fw-semibold" data-bs-toggle="pill" data-bs-target="#tab-balance">
            <i class="bi bi-bank me-1"></i> Neraca Keuangan
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link px-4 fw-semibold" data-bs-toggle="pill" data-bs-target="#tab-trial">
            <i class="bi bi-calculator me-1"></i> Neraca Saldo
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: LABA RUGI -->
    <div class="tab-pane fade show active" id="tab-income">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 text-center border-bottom">
                <h5 class="fw-bold mb-0">LAPORAN LABA RUGI (INCOME STATEMENT)</h5>
                <div class="text-muted small">Periode: <?= format_date($startDate, 'd M Y') ?> s/d <?= format_date($endDate, 'd M Y') ?></div>
            </div>
            <div class="card-body p-4">
                <!-- Pendapatan -->
                <h6 class="fw-bold text-primary mb-3">PENDAPATAN USAHA (REVENUE)</h6>
                <table class="table table-sm align-middle mb-4">
                    <tbody>
                        <?php foreach ($revenueRows as $r): ?>
                        <tr>
                            <td><?= e($r['code']) ?> &mdash; <?= e($r['name']) ?></td>
                            <td class="text-end fw-semibold"><?= format_rupiah($r['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td>TOTAL PENDAPATAN</td>
                            <td class="text-end text-success"><?= format_rupiah($totalRevenue) ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Beban -->
                <h6 class="fw-bold text-danger mb-3">BEBAN OPERASIONAL (EXPENSES)</h6>
                <table class="table table-sm align-middle mb-4">
                    <tbody>
                        <?php foreach ($expenseRows as $e): ?>
                        <tr>
                            <td><?= e($e['code']) ?> &mdash; <?= e($e['name']) ?></td>
                            <td class="text-end fw-semibold"><?= format_rupiah($e['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td>TOTAL BEBAN OPERASIONAL</td>
                            <td class="text-end text-danger"><?= format_rupiah($totalExpenses) ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Net Income -->
                <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">LABA / (RUGI) BERSIH:</h5>
                    <h4 class="fw-bold mb-0 <?= $netIncome >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= format_rupiah($netIncome) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: NERACA KEUANGAN -->
    <div class="tab-pane fade" id="tab-balance">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 text-center border-bottom">
                <h5 class="fw-bold mb-0">LAPORAN NERACA (BALANCE SHEET)</h5>
                <div class="text-muted small">Per Tanggal: <?= format_date($endDate, 'd M Y') ?></div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Aktiva / Aset -->
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-primary mb-3">ASET / AKTIVA (ASSETS)</h6>
                        <table class="table table-sm mb-3">
                            <tbody>
                                <?php foreach ($assetRows as $a): ?>
                                <tr>
                                    <td><?= e($a['code']) ?> &mdash; <?= e($a['name']) ?></td>
                                    <td class="text-end fw-semibold"><?= format_rupiah($a['balance']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>TOTAL ASET</td>
                                    <td class="text-end text-primary"><?= format_rupiah($totalAssets) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Pasiva / Kewajiban & Ekuitas -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-danger mb-3">KEWAJIBAN (LIABILITIES)</h6>
                        <table class="table table-sm mb-4">
                            <tbody>
                                <?php foreach ($liabRows as $l): ?>
                                <tr>
                                    <td><?= e($l['code']) ?> &mdash; <?= e($l['name']) ?></td>
                                    <td class="text-end fw-semibold"><?= format_rupiah($l['balance']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>TOTAL KEWAJIBAN</td>
                                    <td class="text-end text-danger"><?= format_rupiah($totalLiabilities) ?></td>
                                </tr>
                            </tfoot>
                        </table>

                        <h6 class="fw-bold text-info mb-3">EKUITAS (EQUITY)</h6>
                        <table class="table table-sm mb-3">
                            <tbody>
                                <?php foreach ($equityRows as $eq): ?>
                                <tr>
                                    <td><?= e($eq['code']) ?> &mdash; <?= e($eq['name']) ?></td>
                                    <td class="text-end fw-semibold"><?= format_rupiah($eq['balance']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td>Laba Berjalan Periode Ini</td>
                                    <td class="text-end fw-semibold text-success"><?= format_rupiah($netIncome) ?></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>TOTAL EKUITAS</td>
                                    <td class="text-end text-info"><?= format_rupiah($totalEquity) ?></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="p-2 bg-light rounded d-flex justify-content-between align-items-center fw-bold">
                            <span>TOTAL KEWAJIBAN & EKUITAS:</span>
                            <span class="text-primary"><?= format_rupiah($totalLiabilities + $totalEquity) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: NERACA SALDO -->
    <div class="tab-pane fade" id="tab-trial">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 text-center border-bottom">
                <h5 class="fw-bold mb-0">NERACA SALDO (TRIAL BALANCE)</h5>
                <div class="text-muted small">Pemeriksaan Keseimbangan Saldo Semua Akun</div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Kode & Nama Akun Perkiraan</th>
                                <th class="text-center">Saldo Normal</th>
                                <th class="text-end">Total Debit (Rp)</th>
                                <th class="text-end">Total Kredit (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $sumD = 0;
                                $sumC = 0;
                            ?>
                            <?php foreach ($trialRows as $tr): ?>
                                <?php
                                    $sumD += $tr['sum_debit'];
                                    $sumC += $tr['sum_credit'];
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($tr['code']) ?> &mdash; <?= e($tr['name']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($tr['normal_balance']) ?></span></td>
                                    <td class="text-end fw-semibold"><?= $tr['sum_debit'] > 0 ? format_rupiah($tr['sum_debit']) : '-' ?></td>
                                    <td class="text-end fw-semibold"><?= $tr['sum_credit'] > 0 ? format_rupiah($tr['sum_credit']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr>
                                <td colspan="2" class="text-end">TOTAL NERACA SALDO:</td>
                                <td class="text-end text-primary"><?= format_rupiah($sumD) ?></td>
                                <td class="text-end text-success"><?= format_rupiah($sumC) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
