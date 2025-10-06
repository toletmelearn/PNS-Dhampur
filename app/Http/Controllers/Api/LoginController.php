<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSession;
use App\Models\AuditTrail;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // End any existing active sessions for this user
            UserSession::endUserSessions($user->id, 'new_login');
            
            $token = $user->createToken('API Token')->plainTextToken;

            // Start new user session
            $sessionData = UserSession::startSession(
                $user->id,
                $request->session()->getId(),
                $request->ip(),
                $request->userAgent(),
                'web_session'
            );

            // Log successful login
            AuditTrail::logActivity(
                user: $user,
                event: 'login_success',
                auditable: $user,
                oldValues: null,
                newValues: [
                    'login_method' => 'web_session',
                    'session_id' => $sessionData->session_id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ],
                url: $request->fullUrl(),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                tags: ['authentication', 'login', 'web']
            );

            return response()->json([
                'user' => $user,
                'token' => $token,
                'session_id' => $sessionData->id
            ]);
        }

        // Log failed login attempt
        AuditTrail::logActivity(
            user: null,
            event: 'login_failed',
            auditable: null,
            oldValues: null,
            newValues: ['email' => $request->input('email')],
            url: $request->fullUrl(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            tags: ['authentication', 'security', 'failed_login']
        );

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
