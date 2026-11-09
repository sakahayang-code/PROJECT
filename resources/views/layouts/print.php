<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Nota — <?= e($order['order_no'] ?? 'SimLaundry') ?></title>
    <link rel="stylesheet" href="<?= asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/laundrypro.css') ?>">
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <style>
        body {
            background-color: #F8FAFC;
            color: #000;
        }
        @media print {
            body {
                background-color: #FFF;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="py-4">

<div class="container text-center mb-4 no-print">
    <div class="btn-group shadow-sm">
        <a href="?size=58mm" class="btn btn-sm <?= ($paperSize ?? '58mm') === '58mm' ? 'btn-primary' : 'btn-outline-primary' ?>">Format 58mm (Thermal Mini)</a>
        <a href="?size=80mm" class="btn btn-sm <?= ($paperSize ?? '58mm') === '80mm' ? 'btn-primary' : 'btn-outline-primary' ?>">Format 80mm (Thermal Kasir)</a>
        <button onclick="window.print()" class="btn btn-sm btn-success fw-bold">
            <i class="bi bi-printer me-1"></i> Cetak Sekarang
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-light border">Tutup</button>
    </div>
</div>

<div class="printable-receipt <?= ($paperSize ?? '58mm') === '80mm' ? 'receipt-80mm' : 'receipt-58mm' ?> bg-white p-3 shadow-sm border rounded">
    <?= $content ?? '' ?>
</div>

</body>
</html>
