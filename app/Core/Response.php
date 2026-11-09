<?php

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            foreach ($headers as $key => $val) {
                header("$key: $val");
            }
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
            return;
        }
        exit;
    }

    public static function success(string $message = 'Berhasil', mixed $data = null, int $status = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message = 'Terjadi kesalahan', mixed $errors = null, int $status = 400): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        if (!headers_sent()) {
            header("Location: {$url}", true, $status);
        }
        if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
            return;
        }
        exit;
    }
}
