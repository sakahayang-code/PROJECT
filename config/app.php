<?php

return [
    'name' => 'LaundryPro',
    'tagline' => 'Smart Laundry Business Management System',
    'version' => '1.0.0',
    'env' => 'production',
    'debug' => true,
    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000'),
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id_ID',
    'currency' => 'IDR',
    'storage_path' => dirname(__DIR__) . '/storage',
    'upload_path' => dirname(__DIR__) . '/public/uploads',
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
];
