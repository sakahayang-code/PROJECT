<div class="row g-3">
    <!-- Left: Services Selection -->
    <div class="col-lg-7 col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white"><i class="bi bi-shop me-1"></i><?= e($branch['name']) ?></span>
                </div>

                <!-- Service Category Filters -->
                <div class="d-flex flex-wrap gap-1" id="categoryFilterContainer">
                    <button type="button" class="btn btn-sm btn-outline-primary active py-1 px-3 cat-btn" onclick="filterCategory('all', this)">Semua</button>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 cat-btn" onclick="filterCategory(<?= $cat['id'] ?>, this)">
                            <?= e($cat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Service Items Grid -->
            <div class="card-body p-3 pos-services" style="max-height: calc(100vh - 180px); overflow-y: auto;">
                <div class="row g-2" id="serviceGrid">
                    <?php foreach ($services as $srv): ?>
                    <div class="col-6 col-md-4 col-xl-3 service-card-wrapper" data-category="<?= $srv['category_id'] ?>">
                        <div class="card h-100 service-tile p-2" onclick="addToCart(<?= htmlspecialchars(json_encode($srv), ENT_QUOTES, 'UTF-8') ?>)">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="badge bg-secondary font-monospace" style="font-size:0.65rem;"><?= e($srv['code']) ?></span>
                                <span class="badge bg-info-subtle text-info text-uppercase" style="font-size:0.65rem;"><?= e($srv['unit_type']) ?></span>
                            </div>
                            <div class="fw-semibold small text-truncate-2 mb-1" style="min-height: 38px; line-height: 1.2;">
                                <?= e($srv['name']) ?>
                            </div>
                            <div class="mt-auto pt-1 d-flex justify-content-between align-items-center border-top">
                                <span class="fw-bold text-primary small"><?= format_rupiah($srv['price']) ?></span>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;">+ Tambah</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Customer & Cart -->
    <div class="col-lg-5 col-xl-4">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <!-- Customer Picker -->
            <div class="card-header bg-white p-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label small fw-bold mb-0"><i class="bi bi-person-fill text-primary me-1"></i>Pelanggan</label>
                    <button type="button" class="btn btn-xs btn-outline-primary py-0" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal">
                        + Pelanggan Baru
                    </button>
                </div>
                <div class="position-relative">
                    <input type="text" class="form-control form-control-sm" id="customerSearchInput" placeholder="Ketik nama / no. WhatsApp..." autocomplete="off">
                    <div id="customerSearchResults" class="list-group position-absolute w-100 shadow mt-1 d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></div>
                </div>

                <!-- Selected Customer Card -->
                <div id="selectedCustomerDisplay" class="d-none mt-2 p-2 bg-light border rounded small">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-primary" id="custName"></span>
                            <span class="badge bg-secondary ms-1" id="custCode"></span>
                            <div class="text-muted" style="font-size: 0.75rem;" id="custPhone"></div>
                        </div>
                        <button type="button" class="btn-close btn-sm" onclick="clearCustomer()"></button>
                    </div>
                    <div class="d-flex justify-content-between mt-1 pt-1 border-top text-muted" style="font-size: 0.75rem;">
                        <span>Level: <strong id="custTier" class="text-dark"></strong></span>
                        <span>Deposit: <strong id="custDeposit" class="text-success"></strong></span>
                    </div>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="card-body p-2 flex-grow-1 overflow-auto" id="cartItemsContainer" style="max-height: 280px; min-height: 180px;">
                <div class="text-center py-5 text-muted small" id="emptyCartMessage">
                    <i class="bi bi-cart-x fs-2 d-block mb-1 opacity-50"></i>
                    Keranjang masih kosong.<br>Pilih layanan di sebelah kiri.
                </div>
                <div id="cartItemsList" class="d-flex flex-column gap-2"></div>
            </div>

            <!-- Order Speed, Addons & Vouchers -->
            <div class="p-3 bg-light border-top border-bottom small">
                <!-- Order Speed -->
                <div class="mb-2">
                    <label class="form-label fw-bold mb-1" style="font-size: 0.75rem;">Prioritas Pengerjaan:</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="order_type" id="type_reg" value="regular" checked onchange="updateCalculations()">
                        <label class="btn btn-sm btn-outline-secondary py-1" for="type_reg">Reguler</label>

                        <input type="radio" class="btn-check" name="order_type" id="type_exp" value="express" onchange="updateCalculations()">
                        <label class="btn btn-sm btn-outline-warning py-1" for="type_exp">Express</label>

                        <input type="radio" class="btn-check" name="order_type" id="type_prio" value="priority" onchange="updateCalculations()">
                        <label class="btn btn-sm btn-outline-danger py-1" for="type_prio">Kilat (6 Jam)</label>
                    </div>
                </div>

                <!-- Addons Dropdown Accordion -->
                <div class="accordion accordion-flush mb-2" id="addonAccordion">
                    <div class="accordion-item bg-transparent">
                        <button class="accordion-button collapsed py-1 px-0 bg-transparent small fw-semibold text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#addonListCollapse">
                            <i class="bi bi-stars text-warning me-1"></i> Tambahan / Add-ons (+<span id="addonCountDisplay">0</span>)
                        </button>
                        <div id="addonListCollapse" class="accordion-collapse collapse">
                            <div class="py-2">
                                <?php foreach ($addons as $ad): ?>
                                <div class="form-check form-check-inline mb-1">
                                    <input class="form-check-input addon-checkbox" type="checkbox" id="ad_<?= $ad['id'] ?>" value="<?= $ad['id'] ?>" data-price="<?= $ad['price'] ?>" onchange="updateCalculations()">
                                    <label class="form-check-label" for="ad_<?= $ad['id'] ?>" style="font-size:0.75rem;">
                                        <?= e($ad['name']) ?> (+<?= format_rupiah($ad['price']) ?>)
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Input -->
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
                    <input type="text" class="form-control text-uppercase" id="voucherCodeInput" placeholder="Kode Voucher">
                    <button class="btn btn-outline-primary" type="button" onclick="applyVoucher()">Terapkan</button>
                </div>
                <div id="voucherMsg" class="small mt-1 text-success d-none"></div>
            </div>

            <!-- Financial Summary & Checkout Button -->
            <div class="card-footer bg-white p-3 border-0">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Subtotal:</span>
                    <span id="subtotalDisplay">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between small text-muted mb-1" id="addonRow" style="display:none !important;">
                    <span>Tambahan (Addon):</span>
                    <span id="addonDisplay">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between small text-success mb-1" id="discountRow" style="display:none !important;">
                    <span>Diskon Member (<span id="discountPercentDisplay">0</span>%):</span>
                    <span id="discountDisplay">-Rp 0</span>
                </div>
                <div class="d-flex justify-content-between small text-success mb-2" id="voucherRow" style="display:none !important;">
                    <span>Potongan Voucher:</span>
                    <span id="voucherDisplay">-Rp 0</span>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-3">
                    <span class="fw-bold">TOTAL BAYAR:</span>
                    <h4 class="fw-bold text-primary mb-0" id="grandTotalDisplay">Rp 0</h4>
                </div>

                <button type="button" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow" id="btnOpenPayment" onclick="openPaymentModal()" disabled>
                    <i class="bi bi-wallet2"></i>
                    <span>PROSES PEMBAYARAN</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pembayaran POS -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Pembayaran & Selesai Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded text-center mb-3">
                    <div class="text-muted small">Total Tagihan Pesanan</div>
                    <h2 class="fw-bold text-primary mb-0" id="payModalGrandTotal">Rp 0</h2>
                </div>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Metode Pembayaran</label>
                    <select class="form-select" id="payMethodSelect" onchange="onPaymentMethodChange()">
                        <?php foreach ($paymentMethods as $pm): ?>
                            <option value="<?= $pm['id'] ?>" data-category="<?= $pm['category'] ?>"><?= e($pm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Paid Amount -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nominal Dibayar (Rp)</label>
                    <input type="number" class="form-control form-control-lg fw-bold" id="payAmountInput" oninput="calculateChange()">
                </div>

                <!-- Quick Amount Buttons -->
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPayAmount('exact')">Uang Pas</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPayAmount(50000)">Rp 50.000</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPayAmount(100000)">Rp 100.000</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="setPayAmount('dp50')">DP 50%</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="setPayAmount(0)">Bayar Nanti</button>
                </div>

                <!-- Change or Remaining Due -->
                <div class="p-2 border rounded bg-light small mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Kembalian:</span>
                        <strong class="text-success" id="payChangeDisplay">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Sisa Tagihan (Kurang Bayar):</span>
                        <strong class="text-danger" id="payDueDisplay">Rp 0</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Catatan Khusus Pesanan</label>
                    <textarea class="form-control" id="orderNotesInput" rows="2" placeholder="Catatan baju luntur, noda oli, atau permintaan khusus..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btnSubmitCheckout" onclick="submitCheckout()">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Transaksi & Cetak
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cepat Tambah Pelanggan -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Pelanggan Baru Cepat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap</label>
                    <input type="text" class="form-control" id="newCustName" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor WhatsApp (Aktif)</label>
                    <input type="text" class="form-control" id="newCustPhone" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Lengkap (Opsional)</label>
                    <textarea class="form-control" id="newCustAddress" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickCustomer()">Simpan & Pilih Pelanggan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Garment Details -->
<div class="modal fade" id="garmentDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Catat Rincian Pakaian / Defek</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="garmentServiceTitle"></p>
                <div id="garmentDetailsList" class="d-flex flex-column gap-2 mb-3"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addGarmentDetailRow()">+ Tambah Baris Pakaian</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Simpan Rincian</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sukses Checkout -->
<div class="modal fade" id="checkoutSuccessModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered text-center">
        <div class="modal-content p-4">
            <div class="mb-3">
                <span class="rounded-circle bg-success text-white d-inline-flex p-3 shadow">
                    <i class="bi bi-check-lg fs-1"></i>
                </span>
            </div>
            <h4 class="fw-bold mb-1">Transaksi Berhasil Disimpan!</h4>
            <div class="text-muted small mb-3">No. Nota: <strong class="text-primary fs-5" id="successOrderNo"></strong></div>
            <div class="d-grid gap-2 mb-2">
                <a href="#" target="_blank" class="btn btn-success py-2 fw-bold" id="successPrintBtn">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Nota Thermal
                </a>
                <a href="#" class="btn btn-outline-primary" id="successDetailBtn">
                    <i class="bi bi-receipt me-1"></i> Lihat Detail Pesanan
                </a>
                <button type="button" class="btn btn-light border" onclick="resetPos()">
                    <i class="bi bi-cart-plus me-1"></i> Transaksi Baru
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// State Management
let currentBranchId = <?= (int)$branch['id'] ?>;
let selectedCustomer = null;
let cart = [];
let appliedVoucher = null;
let activeGarmentItemIndex = null;

function filterCategory(catId, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-primary', 'active');

    const cards = document.querySelectorAll('.service-card-wrapper');
    cards.forEach(c => {
        if (catId === 'all' || c.getAttribute('data-category') == catId) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}

function addToCart(service) {
    const existing = cart.find(i => i.service_id === service.id);
    if (existing) {
        if (service.unit_type === 'kg') {
            existing.weight += 1;
        } else {
            existing.quantity += 1;
        }
    } else {
        cart.push({
            service_id: service.id,
            service_name: service.name,
            unit_type: service.unit_type,
            unit_price: service.price,
            min_order: service.min_order,
            weight: service.unit_type === 'kg' ? Math.max(1, service.min_order) : 0,
            quantity: service.unit_type === 'kg' ? 0 : 1,
            notes: '',
            details: []
        });
    }
    renderCart();
    updateCalculations();
}

function renderCart() {
    const container = document.getElementById('cartItemsList');
    const emptyMsg = document.getElementById('emptyCartMessage');
    const checkoutBtn = document.getElementById('btnOpenPayment');

    if (cart.length === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = 'block';
        checkoutBtn.disabled = true;
        return;
    }

    emptyMsg.style.display = 'none';
    checkoutBtn.disabled = false;

    let html = '';
    cart.forEach((item, index) => {
        const itemTotal = item.unit_type === 'kg' 
            ? Math.round(Math.max(item.weight, item.min_order) * item.unit_price)
            : (item.quantity * item.unit_price);

        html += `
            <div class="card p-2 border rounded shadow-none bg-light-subtle">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="fw-semibold small text-truncate" style="max-width: 190px;">${item.service_name}</div>
                    <button type="button" class="btn-close btn-sm" onclick="removeFromCart(${index})"></button>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-1">
                        ${item.unit_type === 'kg' ? `
                            <input type="number" step="0.1" min="0.1" class="form-control form-control-sm text-center" style="width: 70px;" value="${item.weight}" onchange="updateCartWeight(${index}, this.value)">
                            <span class="small text-muted">kg</span>
                        ` : `
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="updateCartQty(${index}, -1)">-</button>
                            <input type="number" min="1" class="form-control form-control-sm text-center" style="width: 50px;" value="${item.quantity}" onchange="setCartQty(${index}, this.value)">
                            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="updateCartQty(${index}, 1)">+</button>
                            <span class="small text-muted">pcs</span>
                        `}
                    </div>
                    <div class="text-end">
                        <div class="fw-bold small text-primary">${LaundryPro.formatRupiah(itemTotal)}</div>
                        <button type="button" class="btn btn-link p-0 text-muted" style="font-size:0.7rem;" onclick="openGarmentDetailModal(${index})">
                            <i class="bi bi-tag me-1"></i>Rincian (${item.details.length})
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function updateCartWeight(index, val) {
    cart[index].weight = Math.max(0.1, parseFloat(val) || 0.1);
    updateCalculations();
}

function updateCartQty(index, change) {
    cart[index].quantity = Math.max(1, cart[index].quantity + change);
    renderCart();
    updateCalculations();
}

function setCartQty(index, val) {
    cart[index].quantity = Math.max(1, parseInt(val) || 1);
    updateCalculations();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
    updateCalculations();
}

function updateCalculations() {
    let subtotal = 0;
    cart.forEach(item => {
        if (item.unit_type === 'kg') {
            const w = Math.max(item.weight, item.min_order);
            subtotal += Math.round(w * item.unit_price);
        } else {
            subtotal += item.quantity * item.unit_price;
        }
    });

    // Addons
    let addonTotal = 0;
    let addonCount = 0;
    document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
        addonTotal += parseInt(cb.getAttribute('data-price')) || 0;
        addonCount++;
    });
    document.getElementById('addonCountDisplay').innerText = addonCount;

    // Member discount
    let discountPercent = selectedCustomer ? (parseFloat(selectedCustomer.discount_percent) || 0) : 0;
    let discountTotal = Math.round((subtotal * discountPercent) / 100);

    // Voucher
    let voucherDiscount = 0;
    if (appliedVoucher) {
        if (subtotal >= appliedVoucher.min_spend) {
            if (appliedVoucher.type === 'percent') {
                voucherDiscount = Math.round((subtotal * appliedVoucher.value) / 100);
                if (appliedVoucher.max_discount > 0 && voucherDiscount > appliedVoucher.max_discount) {
                    voucherDiscount = appliedVoucher.max_discount;
                }
            } else {
                voucherDiscount = appliedVoucher.value;
            }
        }
    }

    const grandTotal = Math.max(0, (subtotal + addonTotal) - (discountTotal + voucherDiscount));

    document.getElementById('subtotalDisplay').innerText = LaundryPro.formatRupiah(subtotal);

    const addonRow = document.getElementById('addonRow');
    if (addonTotal > 0) {
        addonRow.style.setProperty('display', 'flex', 'important');
        document.getElementById('addonDisplay').innerText = '+' + LaundryPro.formatRupiah(addonTotal);
    } else {
        addonRow.style.setProperty('display', 'none', 'important');
    }

    const discountRow = document.getElementById('discountRow');
    if (discountTotal > 0) {
        discountRow.style.setProperty('display', 'flex', 'important');
        document.getElementById('discountPercentDisplay').innerText = discountPercent;
        document.getElementById('discountDisplay').innerText = '-' + LaundryPro.formatRupiah(discountTotal);
    } else {
        discountRow.style.setProperty('display', 'none', 'important');
    }

    const voucherRow = document.getElementById('voucherRow');
    if (voucherDiscount > 0) {
        voucherRow.style.setProperty('display', 'flex', 'important');
        document.getElementById('voucherDisplay').innerText = '-' + LaundryPro.formatRupiah(voucherDiscount);
    } else {
        voucherRow.style.setProperty('display', 'none', 'important');
    }

    document.getElementById('grandTotalDisplay').innerText = LaundryPro.formatRupiah(grandTotal);
    window.currentGrandTotal = grandTotal;
}

// Customer autocomplete
let custSearchTimer = null;
const custSearchInput = document.getElementById('customerSearchInput');
const custResults = document.getElementById('customerSearchResults');

custSearchInput.addEventListener('input', function() {
    clearTimeout(custSearchTimer);
    const q = this.value.trim();
    if (q.length < 2) {
        custResults.classList.add('d-none');
        return;
    }
    custSearchTimer = setTimeout(() => {
        LaundryPro.fetch('<?= url('/customers/search?q=') ?>' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) {
                    custResults.innerHTML = '<div class="list-group-item small text-muted">Pelanggan tidak ditemukan.</div>';
                } else {
                    custResults.innerHTML = data.map(c => `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action small py-2" onclick='selectCustomer(${JSON.stringify(c)})'>
                            <div class="fw-bold">${c.name} <span class="badge bg-secondary ms-1">${c.code}</span></div>
                            <div class="text-muted" style="font-size:0.75rem;">${c.whatsapp} &bull; ${c.tier_name || 'Regular'}</div>
                        </a>
                    `).join('');
                }
                custResults.classList.remove('d-none');
            });
    }, 250);
});

function selectCustomer(c) {
    selectedCustomer = c;
    custResults.classList.add('d-none');
    custSearchInput.value = '';
    custSearchInput.classList.add('d-none');

    document.getElementById('selectedCustomerDisplay').classList.remove('d-none');
    document.getElementById('custName').innerText = c.name;
    document.getElementById('custCode').innerText = c.code;
    document.getElementById('custPhone').innerText = c.whatsapp;
    document.getElementById('custTier').innerText = (c.tier_name || 'Regular') + (c.discount_percent > 0 ? ` (Disc ${c.discount_percent}%)` : '');
    document.getElementById('custDeposit').innerText = LaundryPro.formatRupiah(c.deposit_balance);

    updateCalculations();
}

function clearCustomer() {
    selectedCustomer = null;
    document.getElementById('selectedCustomerDisplay').classList.add('d-none');
    custSearchInput.classList.remove('d-none');
    custSearchInput.focus();
    updateCalculations();
}

function submitQuickCustomer() {
    const name = document.getElementById('newCustName').value.trim();
    const phone = document.getElementById('newCustPhone').value.trim();
    const address = document.getElementById('newCustAddress').value.trim();

    if (!name || !phone) {
        LaundryPro.toast('Nama dan WhatsApp wajib diisi.', 'danger');
        return;
    }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('whatsapp', phone);
    formData.append('address', address);
    formData.append('favorite_branch_id', currentBranchId);

    LaundryPro.fetch('<?= url('/customers') ?>', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
      .then(res => {
          if (res.success && res.customer) {
              bootstrap.Modal.getInstance(document.getElementById('quickAddCustomerModal')).hide();
              selectCustomer(res.customer);
              LaundryPro.toast('Pelanggan berhasil didaftarkan!', 'success');
          } else {
              LaundryPro.toast(res.message || 'Gagal mendaftar pelanggan', 'danger');
          }
      });
}

// Voucher
function applyVoucher() {
    const code = document.getElementById('voucherCodeInput').value.trim();
    const msg = document.getElementById('voucherMsg');
    if (!code) {
        appliedVoucher = null;
        msg.classList.add('d-none');
        updateCalculations();
        return;
    }

    if (code.toUpperCase() === 'BERSIHHEBAT') {
        appliedVoucher = { code: 'BERSIHHEBAT', type: 'percent', value: 10, min_spend: 30000, max_discount: 15000 };
        msg.innerText = 'Voucher Diskon 10% berhasil diterapkan!';
        msg.className = 'small mt-1 text-success';
        msg.classList.remove('d-none');
    } else if (code.toUpperCase() === 'HEMAT20K') {
        appliedVoucher = { code: 'HEMAT20K', type: 'fixed', value: 20000, min_spend: 100000, max_discount: 20000 };
        msg.innerText = 'Voucher Potongan Rp 20.000 berhasil diterapkan!';
        msg.className = 'small mt-1 text-success';
        msg.classList.remove('d-none');
    } else {
        appliedVoucher = null;
        msg.innerText = 'Kode voucher tidak valid atau kedaluwarsa.';
        msg.className = 'small mt-1 text-danger';
        msg.classList.remove('d-none');
    }
    updateCalculations();
}

// Payment Modal
function openPaymentModal() {
    if (!selectedCustomer) {
        LaundryPro.toast('Silakan pilih pelanggan terlebih dahulu.', 'warning');
        custSearchInput.focus();
        return;
    }
    if (cart.length === 0) {
        LaundryPro.toast('Keranjang masih kosong.', 'warning');
        return;
    }

    document.getElementById('payModalGrandTotal').innerText = LaundryPro.formatRupiah(window.currentGrandTotal);
    document.getElementById('payAmountInput').value = window.currentGrandTotal;
    calculateChange();
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function setPayAmount(val) {
    const input = document.getElementById('payAmountInput');
    if (val === 'exact') {
        input.value = window.currentGrandTotal;
    } else if (val === 'dp50') {
        input.value = Math.round(window.currentGrandTotal / 2);
    } else {
        input.value = val;
    }
    calculateChange();
}

function calculateChange() {
    const paid = parseInt(document.getElementById('payAmountInput').value) || 0;
    const total = window.currentGrandTotal || 0;

    const change = Math.max(0, paid - total);
    const due = Math.max(0, total - paid);

    document.getElementById('payChangeDisplay').innerText = LaundryPro.formatRupiah(change);
    document.getElementById('payDueDisplay').innerText = due > 0 ? LaundryPro.formatRupiah(due) : 'LUNAS';
}

function onPaymentMethodChange() {
    const sel = document.getElementById('payMethodSelect');
    const opt = sel.options[sel.selectedIndex];
    const cat = opt.getAttribute('data-category');

    if (cat === 'deposit') {
        if (!selectedCustomer || selectedCustomer.deposit_balance < window.currentGrandTotal) {
            LaundryPro.toast('Saldo deposit pelanggan tidak mencukupi (' + LaundryPro.formatRupiah(selectedCustomer?.deposit_balance) + ')', 'warning');
        }
    }
}

// Checkout Submit
function submitCheckout() {
    const btn = document.getElementById('btnSubmitCheckout');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const orderType = document.querySelector('input[name="order_type"]:checked')?.value || 'regular';
    const selectedAddons = Array.from(document.querySelectorAll('.addon-checkbox:checked')).map(cb => parseInt(cb.value));
    const paidAmount = parseInt(document.getElementById('payAmountInput').value) || 0;
    const paymentMethodId = parseInt(document.getElementById('payMethodSelect').value);
    const notes = document.getElementById('orderNotesInput').value.trim();

    const payload = {
        customer_id: selectedCustomer.id,
        branch_id: currentBranchId,
        order_type: orderType,
        items: cart,
        addons: selectedAddons,
        voucher_code: appliedVoucher ? appliedVoucher.code : '',
        payment_method_id: paymentMethodId,
        paid_amount: paidAmount,
        notes: notes
    };

    LaundryPro.fetch('<?= url('/pos/checkout') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(res => res.json())
      .then(res => {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan Transaksi & Cetak';

          if (res.success) {
              bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
              document.getElementById('successOrderNo').innerText = res.order_no;
              document.getElementById('successPrintBtn').href = res.print_url;
              document.getElementById('successDetailBtn').href = res.show_url;
              new bootstrap.Modal(document.getElementById('checkoutSuccessModal')).show();
          } else {
              LaundryPro.toast(res.message || 'Gagal menyimpan transaksi.', 'danger');
          }
      }).catch(err => {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan Transaksi & Cetak';
          LaundryPro.toast('Terjadi kesalahan jaringan atau server.', 'danger');
      });
}

function resetPos() {
    cart = [];
    selectedCustomer = null;
    appliedVoucher = null;
    clearCustomer();
    renderCart();
    updateCalculations();
    document.querySelectorAll('.addon-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('voucherCodeInput').value = '';
    document.getElementById('voucherMsg').classList.add('d-none');
    bootstrap.Modal.getInstance(document.getElementById('checkoutSuccessModal')).hide();
}

// Garment Details Modal
function openGarmentDetailModal(index) {
    activeGarmentItemIndex = index;
    const item = cart[index];
    document.getElementById('garmentServiceTitle').innerText = 'Layanan: ' + item.service_name;
    const list = document.getElementById('garmentDetailsList');
    list.innerHTML = '';

    if (item.details.length === 0) {
        addGarmentDetailRow();
    } else {
        item.details.forEach((det, dIdx) => {
            renderGarmentDetailRow(det, dIdx);
        });
    }
    new bootstrap.Modal(document.getElementById('garmentDetailModal')).show();
}

function addGarmentDetailRow() {
    if (activeGarmentItemIndex === null) return;
    const item = cart[activeGarmentItemIndex];
    item.details.push({ item_name: 'Pakaian', color: '', brand: '', defect_notes: '', quantity: 1 });
    renderGarmentDetailRow(item.details[item.details.length - 1], item.details.length - 1);
}

function renderGarmentDetailRow(det, dIdx) {
    const list = document.getElementById('garmentDetailsList');
    const div = document.createElement('div');
    div.className = 'p-2 bg-light border rounded small';
    div.innerHTML = `
        <div class="row g-1">
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" placeholder="Jenis (Kemeja/Celana)" value="${det.item_name}" onchange="updateGarmentDetail(${dIdx}, 'item_name', this.value)">
            </div>
            <div class="col-3">
                <input type="text" class="form-control form-control-sm" placeholder="Warna" value="${det.color || ''}" onchange="updateGarmentDetail(${dIdx}, 'color', this.value)">
            </div>
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" placeholder="Catatan noda/kancing" value="${det.defect_notes || ''}" onchange="updateGarmentDetail(${dIdx}, 'defect_notes', this.value)">
            </div>
            <div class="col-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeGarmentDetailRow(${dIdx})"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    `;
    list.appendChild(div);
}

function updateGarmentDetail(dIdx, key, val) {
    if (activeGarmentItemIndex !== null && cart[activeGarmentItemIndex].details[dIdx]) {
        cart[activeGarmentItemIndex].details[dIdx][key] = val;
    }
}

function removeGarmentDetailRow(dIdx) {
    if (activeGarmentItemIndex !== null) {
        cart[activeGarmentItemIndex].details.splice(dIdx, 1);
        openGarmentDetailModal(activeGarmentItemIndex);
    }
}
</script>
