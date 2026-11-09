# LaundryPro — Smart Laundry Business Management System

> **Aplikasi Sistem Manajemen Bisnis Laundry Profesional, Lengkap, Modern, Multi-Cabang, Aman, Responsif, dan Siap Digunakan untuk Operasional Nyata.**

---

## 🌟 Ikhtisar Sistem & Keunggulan Arsitektur

**LaundryPro** dibangun dengan arsitektur **Modular Monolith** berbasis **PHP Native OOP** dan **SQLite 3 (PDO SQLite)** dengan optimasi **Write-Ahead Logging (WAL)**. Sistem ini dirancang untuk dapat berjalan langsung pada lingkungan *Shared Hosting* cPanel konvensional tanpa mewajibkan Node.js, VPS, Docker, ataupun dependensi build-step runtime, sekaligus menyediakan kapabilitas enterprise setara sistem cloud modern.

### ✨ Karakteristik Utama:
1. **Multi-Cabang Sejati (Multi-Branch)**: Mendukung satu perusahaan induk dengan banyak cabang independen. Pengguna dibatasi berdasarkan hak akses cabang di sisi server (*server-side branch isolation*).
2. **Kombinasi Transaksi Kiloan & Satuan**: Kasir POS layar sentuh intuitif yang mendukung layanan kiloan (kg), satuan (pcs), layanan tambahan (*addons*), diskon membership, dan voucher promo dalam satu nota.
3. **Penomoran Nota Bebas Race-Condition**: Generator nomor nota atomik sekuensial dengan format `LP-[KODE CABANG]-[YYYYMMDD]-[NOMOR URUT]` tanpa menggunakan `COUNT()` tabel.
4. **Snapshot Harga Historis**: Setiap transaksi membekukan (*snapshot*) harga layanan dan metadata item pada saat transaksi dibuat; perubahan tarif di masa depan tidak mengubah nilai pembukuan lama.
5. **7-Tahap Alur Produksi & QC**: Antrean pekerjaan visual (`Menunggu` ➔ `Pencucian` ➔ `Pengeringan` ➔ `Penyetrikaan` ➔ `Quality Control` ➔ `Pengemasan` ➔ `Siap Diambil`) terintegrasi dengan status operasional mesin (*idle/in_use*) dan audit inspeksi kualitas (1-5 bintang).
6. **Pembukuan Finansial Double-Entry**: Setiap penerimaan kas, piutang, deposit, pengeluaran beban, dan pembayaran QRIS secara otomatis dicatat ke dalam buku besar berpasangan (*balanced debit & credit*) yang selalu seimbang.
7. **Portal Pelanggan & Progressive Web App (PWA)**: Akses mandiri bagi pelanggan untuk melakukan pemesanan online, permintaan pickup, pelacakan cucian via token QR publik, unduh invoice PDF/thermal, pembayaran QRIS, dan ulasan rating bintang.
8. **Automasi WhatsApp Gateway**: Antrean pengiriman pesan otomatis saat pesanan diterima, selesai diproduksi, siap diambil, atau diserahkan, dengan dukungan variabel template dinamis.
9. **30 Laporan Analisis & Eksekutif**: Laporan operasional, penjualan, kurir, CRM, inventaris, hingga Laba Rugi (P&L) komparatif antar-cabang dengan fitur ekspor CSV dan cetak rapi.

---

## 👥 Hak Akses & Akun Pengguna Bawaan (RBAC)

Sistem telah dilengkapi data awal (*seeder*) lengkap dengan 9 tingkatan peran pengguna (*default roles*). Password untuk seluruh akun staf adalah: **`Admin@123`**.

| No | Peran / Role | Username | Password | Deskripsi Kewenangan |
|---|---|---|---|---|
| 1 | **Super Admin** | `superadmin` | `Admin@123` | Akses penuh seluruh perusahaan, semua cabang, konfigurasi sistem, RBAC, dan audit trail. |
| 2 | **Owner / Pemilik** | `owner` | `Admin@123` | Dashboard konsolidasi seluruh cabang, laporan keuangan, laba rugi, dan performa bisnis. |
| 3 | **Manager Pusat** | `manager.pusat` | `Admin@123` | Operasional dan manajemen staf Cabang Pusat Sudirman. |
| 4 | **Manager Cabang** | `manager.cabang` | `Admin@123` | Operasional dan manajemen staf Cabang Tebet. |
| 5 | **Kasir Pusat** | `kasir.pusat` | `Admin@123` | Transaksi POS kasir, pembayaran, deposit, dan cetak nota Cabang Pusat. |
| 6 | **Kasir Cabang** | `kasir.cabang` | `Admin@123` | Transaksi POS kasir Cabang Tebet. |
| 7 | **Petugas Produksi** | `operator.cuci` | `Admin@123` | Antrean cuci, kering, setrika, penugasan mesin, dan Quality Control (QC). |
| 8 | **Kurir** | `kurir.pusat` | `Admin@123` | Tugas penjemputan (*pickup*), pengantaran (*delivery*), dan upload bukti serah terima (*POD*). |
| 9 | **Petugas Gudang** | `gudang.pusat` | `Admin@123` | Stok inventaris, mutasi masuk/keluar, transfer antar-cabang, dan stok opname. |
| 10 | **Akuntan / Keuangan** | `akuntan.pusat` | `Admin@123` | Akun kas/bank, pencatatan beban biaya, jurnal memorial, dan pembukuan. |
| 11 | **Pelanggan Portal** | `anisa.portal` | `Rahasia@123` | Portal pelanggan (Order online, tracking cucian, bayar QRIS, riwayat poin). |

---

## 🏗️ Struktur Direktori Proyek

```text
├── app/
│   ├── Core/                # Framework engine: Database, Router, Controller, Session, Auth, Permission, Validator, View, Response
│   ├── Helpers/             # Helper fungsi global (format_rupiah, url, asset, base_path, storage_path, NumberGenerator)
│   ├── Middleware/          # AuthMiddleware, GuestMiddleware, CsrfMiddleware, CustomerAuthMiddleware
│   ├── Modules/             # Modular Monolith Controller per domain bisnis:
│   │   ├── Accounting/      # Akun kas/bank, beban pengeluaran, jurnal umum, rekonsiliasi QRIS
│   │   ├── Branches/        # Manajemen cabang, switcher cabang aktif, matriks harga cabang
│   │   ├── Customers/       # CRM pelanggan, deposit saldo, riwayat transaksi lintas cabang
│   │   ├── Dashboard/       # Dashboard metrik konsolidasi Owner & Cabang
│   │   ├── Delivery/        # Pickup & Delivery order, tracking kurir, upload bukti serah terima
│   │   ├── Employees/       # Manajemen karyawan, jadwal shift, absensi kehadiran
│   │   ├── Inventory/       # Master barang, mutasi stok, transfer antar-cabang, stok opname
│   │   ├── Machines/        # Armada mesin (cuci/kering/setrika), siklus cuci, log perawatan
│   │   ├── Membership/      # Level member (Silver/Gold/Platinum), poin loyalitas, voucher diskon
│   │   ├── Orders/          # POS kasir touchscreen, kalkulator combo, nota thermal, tracking QR
│   │   ├── Portal/          # Customer Portal PWA (pemesanan online, bayar QRIS, ulasan/rating)
│   │   ├── Production/      # Kanban antrean produksi 7 tahap, alokasi mesin, scoring QC
│   │   ├── Reports/         # Engine pelaporan terpadu (30 laporan bisnis, filter, ekspor CSV)
│   │   ├── Services/        # Master layanan kiloan/satuan, kategori, dan addons
│   │   ├── Settings/        # Profil perusahaan, konfigurasi nota printer, backup & audit logs
│   │   ├── Users/           # Autentikasi staf, profil, manajemen pengguna, matriks RBAC
│   │   └── WhatsApp/        # Integrasi WA Gateway, manajemen template variabel, antrean pesan
│   ├── Services/            # Service integrasi: QrisPaymentAdapter, WhatsAppAdapter, ThermalReceiptBuilder
│   └── autoload.php         # PSR-4 compliant autoloader mandiri
├── config/                  # Konfigurasi aplikasi, database, dan payment gateway
├── console.php              # CLI tool (migrate, seed, backup)
├── database/
│   ├── laundrypro.sqlite    # Database SQLite 3 aktif (WAL Mode, Foreign Keys Strict)
│   ├── Migrator.php         # Database migration runner
│   ├── migrations/          # 15 file migrasi skema tabel
│   └── seeders/             # DatabaseSeeder dengan data real operasional
├── public/                  # Document root web server
│   ├── assets/              # CSS custom, JS, icon PWA, vendor Bootstrap 5, Chart.js
│   ├── index.php            # Entry point web application
│   ├── manifest.json        # Progressive Web App Webmanifest
│   ├── offline.html         # Halaman fallback offline PWA
│   └── service-worker.js    # Service Worker caching & offline handler
├── resources/views/         # Template antarmuka pengguna (layouts, partials, dan views per modul)
├── routes/                  # Definisi rute web (routes/web.php)
├── storage/                 # Direktori file dinamis (backups, invoices, logs, upload foto POD)
└── tests/                   # Test suite otomatis (Phase1 s/d Phase11, FullSystemTest)
```

---

## 📊 Daftar 30 Laporan Bisnis Lengkap

Modul laporan terintegrasi pada menu **Laporan & Analisis** (`/reports`) dengan filter rentang tanggal, filter cabang, ringkasan KPI, dan fitur ekspor CSV instan:

### 1. Penjualan & Kasir (8 Laporan)
- `sales_daily`: Laporan Penjualan Harian
- `sales_weekly`: Laporan Penjualan Mingguan
- `sales_monthly`: Laporan Penjualan Bulanan
- `sales_yearly`: Laporan Penjualan Tahunan
- `sales_branch`: Laporan Penjualan per Cabang
- `sales_cashier`: Laporan Penjualan per Kasir
- `sales_service`: Laporan Penjualan per Layanan
- `sales_kilo_vs_satuan`: Laporan Penjualan Kiloan vs Satuan

### 2. Operasional & Produksi (8 Laporan)
- `orders_active`: Laporan Pesanan Aktif (Dalam Proses)
- `orders_completed`: Laporan Pesanan Selesai / Diserahkan
- `orders_late`: Laporan Pesanan Terlambat (*Overdue Tracker*)
- `orders_cancelled`: Laporan Pembatalan Pesanan & Audit Trail
- `productivity`: Laporan Produktivitas Produksi Staf
- `machine_performance`: Laporan Kinerja & Siklus Mesin
- `machine_utilization`: Laporan Utilisasi Mesin (Menit Aktif)
- `courier_tasks`: Laporan Tugas Penjemputan & Pengantaran Kurir

### 3. CRM & Pelanggan (4 Laporan)
- `customers_new`: Laporan Pelanggan Baru Terdaftar
- `customers_inactive`: Laporan Pelanggan Tidak Aktif (>30 Hari)
- `voucher_usage`: Laporan Penggunaan Voucher Diskon
- `loyalty_points`: Laporan Poin Loyalitas & Estimasi Liabilitas

### 4. Keuangan & Kas (4 Laporan)
- `cash_receipts`: Laporan Penerimaan Kas & Bank
- `expenses`: Laporan Pengeluaran Beban Biaya
- `receivables`: Laporan Piutang Pelanggan (Belum Lunas)
- `purchases`: Laporan Pembelian Barang & PO Supplier

### 5. Inventaris & Gudang (3 Laporan)
- `stock_current`: Laporan Posisi Stok Inventaris Saat Ini & Valuasi
- `stock_movements`: Laporan Log Mutasi Stok Masuk & Keluar
- `stock_low`: Laporan Peringatan Stok Menipis (*Under Min Stock*)

### 6. Eksekutif & Laba Rugi (3 Laporan)
- `income_statement`: Laporan Laba Rugi Operasional (*P&L Income Statement*)
- `branch_comparison`: Laporan Perbandingan Performa Multi-Cabang
- `executive_summary`: Laporan Ringkasan Eksekutif Bisnis Konsolidasi

---

## 🚀 Panduan Instalasi & Deployment

### Persyaratan Server
- **PHP**: Versi 8.1 atau 8.2+
- **Ekstensi PHP Wajib**: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `json`
- **Web Server**: Apache dengan `mod_rewrite` aktif, LiteSpeed, atau Nginx
- **Tidak Memerlukan**: Node.js, NPM, Composer di server live (seluruh vendor telah di-bundle secara native).

### Langkah Instalasi di cPanel / Shared Hosting:
1. **Unggah Berkas**:
   - Ekstrak seluruh file proyek ke folder publik website Anda (misal `public_html/` atau subfolder domain).
2. **Atur Izin Direktori (Permissions)**:
   - Pastikan direktori `storage/` dan `database/` memiliki izin tulis oleh web server:
     ```bash
     chmod -R 775 storage database
     ```
3. **Database Siap Pakai**:
   - Berkas database `database/laundrypro.sqlite` telah terisi skema dan seed data lengkap secara default.
   - Jika ingin me-reset atau menjalankan migrasi ulang via terminal SSH:
     ```bash
     php console.php migrate --fresh --seed
     ```
4. **Akses Aplikasi**:
   - Buka browser pada alamat domain Anda: `https://domain-anda.com/`
   - Halaman login staf: `https://domain-anda.com/login`
   - Portal pelanggan: `https://domain-anda.com/portal/login`

---

## 🧪 Pengujian Otomatis & Verifikasi Mutu (QA)

Sistem telah dilengkapi dengan test suite lengkap berbasis PHP CLI yang menguji seluruh fungsionalitas dan integritas data dari Fase 1 hingga Fase 11, serta simulasi end-to-end menyeluruh:

Untuk menjalankan seluruh test suite:
```bash
for test in tests/Phase*Test.php tests/FullSystemTest.php; do
    php $test || exit 1
done
```

### Hasil Verifikasi:
- **`Phase1Test.php`** (7/7): Koneksi SQLite WAL, FK ON, RBAC, proteksi CSRF, validasi formulir, snapshot backup `VACUUM INTO`.
- **`Phase2Test.php`** (3/3): CRUD Cabang, isolasi akses cabang server-side, jadwal shift, dan absensi karyawan.
- **`Phase3Test.php`** (3/3): Registrasi pelanggan, auto-generate kode `CUST-XXXX`, top-up deposit, dan pembukuan berimbang.
- **`Phase4Test.php`** (4/4): Generator nota sekuensial bebas race condition, POS combo kiloan/satuan, snapshot harga, tracking QR token, dan pembatalan nota ber-audit.
- **`Phase5Test.php`** (4/4): Alur produksi 7 tahap, tracking status mesin cuci (`idle` ➔ `in_use`), kenaikan siklus cuci, dan scoring inspeksi QC bintang 1-5.
- **`Phase6Test.php`** (4/4): Buku besar COA, pencatatan beban operasional, simulasi QRIS & idempotensi webhook, neraca & laba rugi.
- **`Phase7Test.php`** (5/5): Alur pickup & delivery, penugasan kurir, upload bukti serah terima (POD), dan auto-complete order.
- **`Phase8Test.php`** (4/4): Tier membership, ledger mutasi poin loyalitas, voucher diskon, dan automasi antrean WhatsApp.
- **`Phase9Test.php`** (5/5): Master inventaris, mutasi stok, penyesuaian stok opname, transfer stok antar cabang, servis mesin armada.
- **`Phase10Test.php`** (6/6): Customer Portal, registrasi mandiri, keamanan autentikasi customer, invoice QRIS online, ulasan rating pelanggan, service worker & manifest PWA.
- **`Phase11Test.php`** (5/5): Registrasi & eksekusi 30 laporan bisnis SQLite, ekspor CSV, profil perusahaan, pengaturan nota thermal 58mm/80mm, backup & restore integritas database, audit trail.
- **`FullSystemTest.php`** (10/10): Simulasi terintegrasi 10 tahap dari pendaftaran pelanggan baru, order pickup, kurir, kasir POS, alur produksi mesin & QC, pengantaran & POD, ulasan pelanggan, mutasi stok, hingga verifikasi keseimbangan double-entry `Debit == Credit` pada buku besar.

---

## 🔒 Keamanan & Perlindungan Data

- **Perlindungan CSRF Token**: Diterapkan pada seluruh request form POST, PUT, DELETE.
- **SQL Injection Defense**: 100% kueri database menggunakan PDO Prepared Statements dengan parameter binding strictly typed.
- **XSS Sanitization**: Seluruh output dinamis di-escape menggunakan fungsi global `e()` (`htmlspecialchars` UTF-8).
- **Session Security**: Session timeout otomatis, regenerasi ID saat login, dan isolasi ketat antara session staf dan pelanggan.
- **Audit Trail & Keamanan Akses**: Setiap aksi penting (perubahan harga, pembatalan nota, pemulihan database, perubahan stok) dicatat secara otomatis ke dalam tabel `activity_logs` beserta IP address dan User Agent.

---

**LaundryPro** — *Smart Laundry Business Management System*
Dibuat dengan standar rekayasa perangkat lunak modern untuk mendukung pertumbuhan bisnis laundry Anda.
