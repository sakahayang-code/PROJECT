<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class GuestMiddleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
            return false;
        }
        return true;
    }
}
