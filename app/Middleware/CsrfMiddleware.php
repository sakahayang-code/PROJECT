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
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $refererPath = parse_url($referer, PHP_URL_PATH) ?? '';
            $safeRedirect = (empty($referer) || $refererPath === $uri) ? url('/dashboard') : $referer;
            Response::redirect($safeRedirect);
            return false;
        }

        // =========================================================================
        // IDEMPOTENCY & DOUBLE SUBMISSION / REPLAY PROTECTION
        // =========================================================================
        $postData = $_POST;
        unset($postData['_csrf_token']); // Strip token so only actual form payload is hashed
        $postSignature = md5($uri . '|' . serialize($postData));
        $lastPost = Session::get('_last_post_submission');

        if (is_array($lastPost) &&
            ($lastPost['sig'] ?? '') === $postSignature &&
            (time() - (int)($lastPost['time'] ?? 0)) < 4) {

            // Duplicate post submission detected within 4 seconds (e.g. browser refresh or rapid double click)
            $destination = $lastPost['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? url('/dashboard'));
            $destinationPath = parse_url($destination, PHP_URL_PATH) ?? '';
            if ($destinationPath === $uri) {
                $destination = url('/dashboard');
            }
            Session::flash('info', 'Data formulir telah berhasil diproses sebelumnya.');
            Response::redirect($destination);
            return false;
        }

        // Record this submission attempt
        Session::set('_last_post_submission', [
            'sig' => $postSignature,
            'time' => time(),
            'redirect' => null,
        ]);

        return true;
    }
}
