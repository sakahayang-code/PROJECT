<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Response;

class CsrfMiddleware
{
    private array $except = [
        '/api/webhooks/payment',
        '/api/webhooks/whatsapp',
    ];

    public function handle(): bool
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        foreach ($this->except as $exemptPath) {
            if (str_starts_with($uri, $exemptPath)) {
                return true;
            }
        }

        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!Session::validateCsrfToken($token)) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                Response::json(['error' => 'Token CSRF tidak valid atau telah kedaluwarsa. Muat ulang halaman.'], 419);
                return false;
            }

            Session::flash('error', 'Token keamanan CSRF tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? url('/dashboard'));
            return false;
        }

        return true;
    }
}
