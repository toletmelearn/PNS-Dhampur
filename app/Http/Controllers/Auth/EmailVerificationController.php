<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NewUser;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * Show the email verification notice page for authenticated users.
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user instanceof NewUser && $user->hasVerifiedEmail()) {
            return redirect()->route('dashboard.default');
        }

        return view('auth.verify-email', ['user' => $user]);
    }

    /**
     * Send (or resend) the email verification link.
     * If authenticated, sends for current user. Otherwise, accepts email input.
     */
    public function send(Request $request)
    {
        $user = Auth::user();
        if ($user instanceof NewUser) {
            if ($user->hasVerifiedEmail()) {
                return back()->with('info', 'Your email is already verified.');
            }
            $user->sendEmailVerificationNotification();
            return back()->with('success', 'Verification email has been sent.');
        }

        // Fallback: allow requesting a verification email by entering the account email
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $target = NewUser::where('email', $validated['email'])->first();
        if (!$target) {
            // Do not reveal whether the email exists for privacy
            return back()->with('success', 'If an account exists, a verification link has been sent.');
        }

        if ($target->hasVerifiedEmail()) {
            return back()->with('info', 'This email is already verified.');
        }

        $target->sendEmailVerificationNotification();
        return back()->with('success', 'Verification email has been sent.');
    }

    /**
     * Handle an incoming email verification link.
     */
    public function verify(Request $request, $id, $hash)
    {
        /** @var NewUser|null $user */
        $user = NewUser::find($id);
        if (!$user) {
            abort(404);
        }

        // Basic hash check (Laravel signed route also validates signature via middleware)
        if (! hash_equals(sha1($user->email), (string) $hash)) {
            abort(403, 'Invalid verification link.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        // If the verifying user is currently authenticated, keep them in session
        if (Auth::check() && Auth::id() === $user->getKey()) {
            return redirect()->route('dashboard.default')->with('success', 'Email verified successfully.');
        }

        return redirect()->route('login')->with('success', 'Email verified successfully. Please log in.');
    }
}