<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class CustomerAuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::checkCustomer()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                Response::json(['error' => 'Silakan login ke portal pelanggan terlebih dahulu.'], 401);
                return false;
            }
            Response::redirect(url('/portal/login'));
            return false;
        }
        return true;
    }
}
