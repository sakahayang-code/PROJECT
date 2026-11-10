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
        // Resolve relative URL to full absolute URL to avoid proxy/web server issues
        if (str_starts_with($url, '/')) {
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                       (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url = "{$scheme}://{$host}{$url}";
        }

        // Standard Post-Redirect-Get (PRG): If request was POST, use 303 See Other
        // so browser is strictly instructed to switch to a GET request
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($requestMethod === 'POST' && $status === 302) {
            $status = 303;
        }

        // Clean any active output buffers so no stray bytes interfere with redirect
        if (!defined('TESTING_MODE')) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }

        if (!headers_sent()) {
            http_response_code($status);
            header("Location: {$url}", true, $status);
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
            header("Connection: close");
        }

        // HTML & JavaScript fallback body: Guarantee the browser never displays a blank white screen
        // even if headers were sent prematurely by the server or if HTTP redirect was skipped
        echo '<!DOCTYPE html>' . "\n";
        echo '<html lang="id">' . "\n";
        echo '<head>' . "\n";
        echo '    <meta charset="UTF-8">' . "\n";
        echo '    <meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        echo '    <title>Mengarahkan...</title>' . "\n";
        echo '    <script>window.location.replace(' . json_encode($url) . ');</script>' . "\n";
        echo '    <style>' . "\n";
        echo '        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }' . "\n";
        echo '        .card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); padding: 32px 24px; text-align: center; max-width: 380px; width: 100%; border: 1px solid #e2e8f0; }' . "\n";
        echo '        .spinner { width: 36px; height: 36px; border: 3px solid rgba(19, 175, 168, 0.2); border-top-color: #13AFA8; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }' . "\n";
        echo '        @keyframes spin { to { transform: rotate(360deg); } }' . "\n";
        echo '        h4 { margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #0f172a; }' . "\n";
        echo '        p { margin: 0 0 20px; font-size: 14px; color: #64748b; line-height: 1.5; }' . "\n";
        echo '        .btn { display: inline-block; padding: 10px 20px; background: #13AFA8; color: #ffffff; text-decoration: none; border-radius: 10px; font-size: 14px; font-weight: 600; transition: background 0.2s; }' . "\n";
        echo '        .btn:hover { background: #0b817c; }' . "\n";
        echo '    </style>' . "\n";
        echo '</head>' . "\n";
        echo '<body>' . "\n";
        echo '    <div class="card">' . "\n";
        echo '        <div class="spinner"></div>' . "\n";
        echo '        <h4>Data Berhasil Disimpan</h4>' . "\n";
        echo '        <p>Sedang mengalihkan Anda kembali ke halaman aplikasi...</p>' . "\n";
        echo '        <a class="btn" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Klik jika tidak dialihkan</a>' . "\n";
        echo '    </div>' . "\n";
        echo '</body>' . "\n";
        echo '</html>' . "\n";

        if (php_sapi_name() === 'cli' && defined('TESTING_MODE')) {
            return;
        }
        exit;
    }
}
