<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                Response::json(['error' => 'Sesi Anda telah berakhir. Silakan login kembali.'], 401);
                return false;
            }
            Response::redirect(url('/login'));
            return false;
        }
        return true;
    }
}
