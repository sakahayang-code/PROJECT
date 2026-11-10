#!/usr/bin/env python3
"""
SimLaundry Catalog Screenshot & Showcase Generator
Captures high-resolution desktop and mobile screenshots from the running app,
and creates professional e-commerce catalog promotional graphics.
"""

import os
import subprocess
import time
from PIL import Image, ImageDraw, ImageFont, ImageFilter

PORT = 8999
BASE_URL = f"http://127.0.0.1:{PORT}"
OUT_DIR = "/home/antigravity/sakahayang-code/PROJECT/public/assets/catalog"
os.makedirs(OUT_DIR, exist_ok=True)

FONT_BOLD = "/usr/share/fonts/truetype/roboto/unhinted/RobotoTTF/Roboto-Bold.ttf"
FONT_REG = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"

font_hero = ImageFont.truetype(FONT_BOLD, 38)
font_title = ImageFont.truetype(FONT_BOLD, 28)
font_subtitle = ImageFont.truetype(FONT_REG, 16)
font_badge = ImageFont.truetype(FONT_BOLD, 13)
font_bullet = ImageFont.truetype(FONT_REG, 15)

# 1. Setup Capture Helper Endpoint
CAPTURE_HELPER_CODE = """<?php
require_once dirname(__DIR__) . '/app/autoload.php';
$config = require dirname(__DIR__) . '/config/app.php';
\\App\\Core\\Session::start();

$route = $_GET['route'] ?? '/dashboard';

if (str_starts_with($route, '/portal')) {
    \\App\\Core\\Auth::loginCustomer(1);
} else {
    \\App\\Core\\Auth::login([
        'id' => 1,
        'username' => 'admin',
        'name' => 'Super Admin',
        'email' => 'admin@simlaundry.com',
        'role_id' => 1,
        'company_id' => 1,
        'branch_id' => 1
    ]);
    $_SESSION['active_branch_id'] = 1;
}

require_once dirname(__DIR__) . '/routes/web.php';
\\App\\Core\\Router::dispatch($route, 'GET');
"""

def main():
    helper_path = "/home/antigravity/sakahayang-code/PROJECT/public/capture_preview.php"
    with open(helper_path, "w", encoding="utf-8") as f:
        f.write(CAPTURE_HELPER_CODE)

    # Start local PHP server
    print("Starting local PHP server on port", PORT)
    server_proc = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{PORT}", "-t", "public"],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )
    time.sleep(1.5)

    try:
        pages = [
            ("dashboard", "/dashboard", "Dashboard Bisnis & Analitik Real-Time", "Pantau omzet harian, bulanan, pesanan aktif, mesin & saldo kas"),
            ("pos_kasir", "/pos", "Kasir POS Touchscreen Kilat", "Input kiloan & satuan, scan barcode, diskon voucher, & QRIS dinamis"),
            ("produksi_qc", "/production", "Monitoring Produksi 7-Tahap & QC", "Tracking alur cuci, pengeringan, setrika, packing, hingga siap diambil"),
            ("kurir_delivery", "/delivery", "Pickup & Delivery Terpadu", "Penugasan kurir, navigasi peta, dan foto bukti serah terima (POD)"),
            ("whatsapp_gateway", "/whatsapp", "WhatsApp Bot & Gateway Otomatis", "Kirim nota otomatis & pesan massal (broadcast promo) tanpa ketik manual"),
            ("membership_poin", "/membership", "Membership Loyalitas & Voucher Diskon", "Tingkatkan retensi dengan sistem level membership, poin & promo"),
            ("laporan_keuangan", "/reports", "30 Laporan Eksekutif & Akuntansi", "Laba rugi, neraca, arus kas, performa cabang & analisis omzet"),
            ("portal_pelanggan", "/portal", "Portal Pelanggan & Lacak Cucian PWA", "Pelanggan dapat lacak status cuci, pesan antar-jemput, & cek poin"),
        ]

        # A. Capture Raw Desktop Screenshots (1440 x 900)
        for key, route, title, desc in pages:
            url = f"{BASE_URL}/capture_preview.php?route={route}"
            out_file = os.path.join(OUT_DIR, f"screenshot_{key}.png")
            print(f"Capturing Desktop: {key} ({route})...")
            cmd = [
                "chromium", "--headless", "--no-sandbox", "--disable-gpu",
                "--hide-scrollbars",
                "--window-size=1440,900",
                f"--screenshot={out_file}",
                url
            ]
            subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

        # B. Capture Raw Mobile Screenshots (412 x 915)
        mobile_pages = [
            ("mobile_dashboard", "/dashboard"),
            ("mobile_pos", "/pos"),
            ("mobile_kurir", "/delivery"),
            ("mobile_portal", "/portal"),
        ]
        for key, route in mobile_pages:
            url = f"{BASE_URL}/capture_preview.php?route={route}"
            out_file = os.path.join(OUT_DIR, f"screenshot_{key}.png")
            print(f"Capturing Mobile: {key} ({route})...")
            cmd = [
                "chromium", "--headless", "--no-sandbox", "--disable-gpu",
                "--hide-scrollbars",
                "--window-size=412,915",
                f"--screenshot={out_file}",
                url
            ]
            subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

        # C. Create High-Impact Catalog Showcase Cards (1280 x 800)
        print("Creating branded catalog presentation graphics...")
        create_hero_banner()
        for idx, (key, route, title, desc) in enumerate(pages, 1):
            create_showcase_card(idx, key, title, desc)

        print("Catalog screenshots & showcases generated successfully in:", OUT_DIR)

    finally:
        server_proc.terminate()
        server_proc.wait()
        if os.path.exists(helper_path):
            os.remove(helper_path)


def create_showcase_card(idx, key, title, desc):
    """Creates an elegant mockup presentation card for CodeCanyon / Catalog Web"""
    W, H = 1280, 800
    card = Image.new("RGB", (W, H), (15, 23, 42)) # Deep Slate Blue
    draw = ImageDraw.Draw(card)

    # Ambient Glow
    draw.ellipse([W - 500, -100, W + 300, 700], fill=(13, 148, 136)) # Teal
    draw.ellipse([-200, H - 400, 400, H + 200], fill=(30, 41, 59))
    # Soft box blur
    card = card.filter(ImageFilter.GaussianBlur(radius=60))
    draw = ImageDraw.Draw(card)

    # Top Header & Badge
    badge_text = f"FEATURE HIGHLIGHT #{idx}"
    draw.rounded_rectangle([60, 45, 250, 75], radius=15, fill=(19, 175, 168))
    draw.text((155, 60), badge_text, font=font_badge, fill=(255, 255, 255), anchor="mm")

    draw.text((60, 95), title, font=font_title, fill=(255, 255, 255))
    draw.text((60, 135), desc, font=font_subtitle, fill=(203, 213, 225))

    # Laptop / Browser Window Mockup (Centered bottom)
    win_x = 60
    win_y = 175
    win_w = 1160
    win_h = 585
    bar_h = 36

    # Shadow
    draw.rounded_rectangle([win_x - 10, win_y - 6, win_x + win_w + 10, win_y + win_h + 16], radius=22, fill=(5, 9, 20))
    # Window Frame
    draw.rounded_rectangle([win_x, win_y, win_x + win_w, win_y + win_h], radius=18, fill=(30, 41, 59), outline=(51, 65, 85), width=2)
    # Window Header Bar
    draw.rounded_rectangle([win_x, win_y, win_x + win_w, win_y + bar_h], radius=18, fill=(30, 41, 59))
    draw.rectangle([win_x, win_y + 18, win_x + win_w, win_y + bar_h], fill=(30, 41, 59))
    # Mac Dots
    draw.ellipse([win_x + 18, win_y + 12, win_x + 30, win_y + 24], fill=(239, 68, 68))
    draw.ellipse([win_x + 38, win_y + 12, win_x + 50, win_y + 24], fill=(245, 158, 11))
    draw.ellipse([win_x + 58, win_y + 12, win_x + 70, win_y + 24], fill=(16, 185, 129))

    # URL Bar
    draw.rounded_rectangle([win_x + 100, win_y + 7, win_x + win_w - 100, win_y + 29], radius=6, fill=(15, 23, 42))
    draw.text((win_x + win_w // 2, win_y + 18), f"https://simlaundry.com/{key.replace('_', '/')}", font=font_badge, fill=(148, 163, 184), anchor="mm")

    # Load & Paste Raw Screenshot
    raw_path = os.path.join(OUT_DIR, f"screenshot_{key}.png")
    if os.path.exists(raw_path):
        raw_img = Image.open(raw_path).convert("RGB")
        target_scr_w = win_w - 4
        target_scr_h = win_h - bar_h - 4
        raw_resized = raw_img.resize((target_scr_w, target_scr_h), Image.Resampling.LANCZOS)
        card.paste(raw_resized, (win_x + 2, win_y + bar_h))

    out_showcase = os.path.join(OUT_DIR, f"showcase_{idx:02d}_{key}.png")
    card.save(out_showcase, quality=95)
    print(f"Saved: {out_showcase}")


def create_hero_banner():
    """Creates a 1200x630 Hero Cover Banner for the Catalog / Marketplace"""
    W, H = 1200, 630
    banner = Image.new("RGB", (W, H), (11, 19, 43))
    draw = ImageDraw.Draw(banner)

    # Ambient Orbs
    draw.ellipse([-100, -100, 500, 500], fill=(19, 175, 168))
    draw.ellipse([W - 400, 100, W + 200, 700], fill=(59, 130, 246))
    banner = banner.filter(ImageFilter.GaussianBlur(radius=80))
    draw = ImageDraw.Draw(banner)

    # Brand Title & Tagline
    draw.rounded_rectangle([60, 45, 240, 78], radius=16, fill=(19, 175, 168))
    draw.text((150, 61), "★ BEST SELLER 2026", font=font_badge, fill=(255, 255, 255), anchor="mm")

    draw.text((60, 95), "SIMLAUNDRY PRO ENTERPRISE", font=font_hero, fill=(255, 255, 255))
    draw.text((60, 142), "Sistem Kasir POS & Manajemen Laundry Multi-Cabang Terlengkap", font=font_subtitle, fill=(0, 220, 200))

    # Feature Checklist
    bullets = [
        "✓ Kasir POS Touchscreen & Cetak Nota Bluetooth (58/80mm)",
        "✓ Integrasi WhatsApp Gateway (Nota & Status Cucian Otomatis)",
        "✓ Monitoring Produksi 7-Tahap & Quality Control (QC)",
        "✓ Modul Kurir Antar-Jemput & Bukti Serah Terima Foto (POD)",
        "✓ Multi-Cabang, Manajemen Karyawan, Shift & Hak Akses (RBAC)",
        "✓ Laporan Keuangan Neraca & Laba Rugi Otomatis (Double-Entry)",
        "✓ Portal Pelanggan Online & Notifikasi Siap Ambil (PWA Mobile)",
    ]
    for i, b in enumerate(bullets):
        draw.text((60, 190 + i * 36), b, font=font_bullet, fill=(241, 245, 249))

    # Technology Badges
    techs = ["PHP 8.2+", "SQLite 3 WAL", "PWA Ready", "Bootstrap 5", "Data Armor Safe", "No NPM Required"]
    for j, t in enumerate(techs):
        bx = 60 + j * 165
        draw.rounded_rectangle([bx, 480, bx + 150, 515], radius=10, fill=(30, 41, 59), outline=(71, 85, 105), width=1)
        draw.text((bx + 75, 497), t, font=font_badge, fill=(203, 213, 225), anchor="mm")

    # CTA Button
    draw.rounded_rectangle([60, 545, 360, 595], radius=25, fill=(19, 175, 168))
    draw.text((210, 570), "READY TO DEPLOY • PLUG & PLAY", font=font_badge, fill=(255, 255, 255), anchor="mm")

    out_hero = os.path.join(OUT_DIR, "catalog_hero_cover.png")
    banner.save(out_hero, quality=95)
    print("Saved Hero Banner:", out_hero)


if __name__ == "__main__":
    main()
