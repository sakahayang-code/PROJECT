<?php

namespace App\Modules\Payments;

use App\Core\Controller;
use App\Services\PaymentGatewayAdapter;

class PaymentWebhookController extends Controller
{
    public function handle(): void
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!$payload) {
            $this->json(['error' => 'Invalid JSON payload'], 400);
            return;
        }

        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_SIGNATURE'] ?? null;
        $adapter = new PaymentGatewayAdapter();
        $result = $adapter->handleWebhook($payload, $signature);

        if ($result['success'] ?? false) {
            $this->json($result, 200);
        } else {
            $this->json($result, 400);
        }
    }
}
