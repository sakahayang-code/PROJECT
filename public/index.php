<?php

/**
 * LaundryPro Front Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Session;
use App\Core\Router;
use App\Core\Response;

// Load App Configuration
$config = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Jakarta');

// Start Session
Session::start();

// Load Web and API Routes
require_once dirname(__DIR__) . '/routes/web.php';
require_once dirname(__DIR__) . '/routes/api.php';

// Dispatch Request
try {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    Router::dispatch($requestUri, $requestMethod);
} catch (\Throwable $e) {
    if ($config['debug'] ?? false) {
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            Response::json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }

        echo "<!DOCTYPE html><html><head><title>500 Internal Error</title>";
        echo "<link rel='stylesheet' href='" . url('assets/css/bootstrap.min.css') . "'>";
        echo "</head><body class='bg-light p-4'><div class='container'>";
        echo "<div class='card shadow-sm border-danger'>";
        echo "<div class='card-header bg-danger text-white fw-bold'>Internal Server Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='card-body'>";
        echo "<p class='text-muted'>File: " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>";
        echo "<pre class='bg-dark text-white p-3 rounded' style='max-height:400px;overflow:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div></div></div></body></html>";
        exit;
    }

    Response::json(['error' => 'Terjadi kesalahan sistem pada server.'], 500);
}
