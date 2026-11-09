<?php

declare(strict_types=1);

/**
 * LaundryPro Global Helper Functions
 * Loaded globally without namespace so they are accessible everywhere (views, controllers, services).
 */

if (!function_exists('e')) {
    function e(mixed $value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return \App\Core\Session::getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah(int|float|null $amount): string {
        return 'Rp ' . number_format((float)($amount ?? 0), 0, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'd M Y H:i'): string {
        if (empty($datetime)) return '-';
        $ts = strtotime($datetime);
        return $ts ? date($format, $ts) : '-';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $clean = ltrim($path, '/');
        // Check script base path for subfolder hosting
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $baseDir = dirname($scriptName);
        if ($baseDir === '/' || $baseDir === '\\') {
            $baseDir = '';
        }
        return $baseDir . '/' . $clean;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string {
        $root = dirname(__DIR__, 2);
        return $path === '' ? $root : $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string {
        $clean = ltrim($path, '/');
        $fullPath = base_path('public/assets/' . $clean);
        $v = file_exists($fullPath) ? (string)filemtime($fullPath) : '1';
        return url('assets/' . $clean) . '?v=' . $v;
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string {
        return base_path('storage/' . ltrim($path, '/'));
    }
}
