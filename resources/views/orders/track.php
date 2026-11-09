<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white p-4 text-center">
                <span class="badge bg-white text-primary mb-2 text-uppercase fw-bold"><?= e($order['order_type']) ?></span>
                <h3 class="fw-bold mb-1">Nota: <?= e($order['order_no']) ?></h3>
                <div class="small opacity-75">Outlet: <?= e($order['branch_name']) ?></div>
            </div>
            <div class="card-body p-4">
                <!-- Status Timeline Stepper -->
                <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size:0.75rem;">Tahapan Pengerjaan Cucian</h6>
                <div class="position-relative mb-4">
                    <div class="row text-center g-2">
                        <?php
                            $currentSlug = $currentJob['status'] ?? $order['production_status'];
                            $foundCurrent = false;
                        ?>
                        <?php foreach ($stages as $idx => $stg): ?>
                            <?php
                                $isCurrent = ($currentJob['current_stage_id'] ?? 0) == $stg['id'];
                                if ($isCurrent) $foundCurrent = true;
                                $isDone = !$foundCurrent || $order['production_status'] === 'completed';
                                if ($order['production_status'] === 'cancelled') {
                                    $isDone = false;
                                    $isCurrent = false;
                                }
                            ?>
                            <div class="col">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1 text-white shadow-sm" style="width: 36px; height: 36px; background-color: <?= $isCurrent ? '#13AFA8' : ($isDone ? '#16A34A' : '#CBD5E1') ?>;">
                                    <?php if ($isDone && !$isCurrent): ?>
                                        <i class="bi bi-check fs-5"></i>
                                    <?php else: ?>
                                        <?= $idx + 1 ?>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-semibold" style="font-size:0.7rem;"><?= e($stg['name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Order Details -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted small">Pelanggan:</div>
                        <div class="fw-bold"><?= e($order['customer_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Tanggal Masuk:</div>
                        <div class="fw-semibold"><?= format_date($order['created_at']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Estimasi Selesai:</div>
                        <div class="fw-bold text-primary"><?= format_date($order['est_completion_at']) ?> WIB</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Status Pembayaran:</div>
                        <span class="badge <?= $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= strtoupper($order['payment_status']) ?>
                        </span>
                    </div>
                </div>

                <!-- Items list -->
                <h6 class="fw-bold mb-2 text-secondary text-uppercase" style="font-size:0.75rem;">Item Cucian</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Layanan</th>
                                <th class="text-end">Jumlah / Berat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($item['service_name']) ?></td>
                                <td class="text-end">
                                    <?= $item['unit_type'] === 'kg' ? $item['weight'] . ' kg' : $item['quantity'] . ' pcs' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light rounded text-center small text-muted">
                    Butuh bantuan atau pertanyaan mengenai cucian Anda? Hubungi customer service kami di 
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $order['branch_phone']) ?>" target="_blank" class="fw-bold text-success text-decoration-none">
                        <i class="bi bi-whatsapp me-1"></i>WhatsApp Outlet <?= e($order['branch_name']) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
