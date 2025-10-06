<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\AuditTrail;
use App\Models\UserSession;
use Illuminate\Http\Request;

class LoginListener
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $request = request();
        
        // Start user session
        $sessionData = UserSession::startSession(
            $user->id,
            session()->getId(),
            $request->ip(),
            $request->userAgent(),
            'web_session'
        );

        // Log successful login
        AuditTrail::logActivity(
            $user,
            'user_login',
            $user, // auditable
            null, // old_values
            [
                'login_method' => 'web_session',
                'session_id' => $sessionData->session_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'guard' => $event->guard
            ],
            $request->fullUrl(),
            $request->ip(),
            $request->userAgent(),
            ['authentication', 'login', 'web']
        );
    }
}