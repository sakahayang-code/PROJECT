<?php

use App\Core\Router;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

Router::group(['prefix' => '/api'], function() {
    Router::get('/health', function() {
        Response::json([
            'status' => 'healthy',
            'app' => 'SimLaundry',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    });

    Router::post('/webhooks/payment', [\App\Modules\Payments\PaymentWebhookController::class, 'handle']);
});
