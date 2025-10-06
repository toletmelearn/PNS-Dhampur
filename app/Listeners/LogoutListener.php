<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\AuditTrail;
use App\Models\UserSession;
use Illuminate\Http\Request;

class LogoutListener
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;
        $request = request();
        
        // End user session
        UserSession::endSession(session()->getId(), 'manual_logout');

        // Log logout
        AuditTrail::logActivity(
            $user,
            'user_logout',
            $user, // auditable
            null, // old_values
            [
                'logout_method' => 'manual',
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'guard' => $event->guard
            ],
            $request->fullUrl(),
            $request->ip(),
            $request->userAgent(),
            ['authentication', 'logout', 'web']
        );
    }
}