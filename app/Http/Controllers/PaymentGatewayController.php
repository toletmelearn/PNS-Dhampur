<?php

namespace App\Http\Controllers;

use App\Models\PaymentGatewayConfig;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin,admin']);
    }

    public function index()
    {
        $configs = PaymentGatewayConfig::orderBy('provider')->get();
        return view('finance.payment.settings', compact('configs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:razorpay,stripe',
            'mode' => 'required|string|in:test,live',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        // Deactivate other configs if setting active one
        if (!empty($validated['is_active'])) {
            PaymentGatewayConfig::query()->update(['is_active' => false]);
        }

        PaymentGatewayConfig::updateOrCreate(
            ['provider' => $validated['provider'], 'mode' => $validated['mode']],
            [
                'api_key' => $validated['api_key'] ?? null,
                'api_secret' => $validated['api_secret'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]
        );

        return redirect()->route('payment.settings')->with('success', 'Payment gateway settings saved');
    }
}