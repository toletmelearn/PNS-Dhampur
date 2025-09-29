<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // For API requests, return null to give JSON 401
        if ($request->expectsJson()) {
            return null;
        }

        // For web requests, redirect to home page (make sure this route exists)
        return route('home'); // You may also just use '/' if no named route
    }
}
