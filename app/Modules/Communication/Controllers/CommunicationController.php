<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Services\SmsService;

class CommunicationController extends Controller
{
    public function index()
    {
        return view('communication.index');
    }

    public function send(Request $request, SmsService $smsService)
    {
        $request->validate([
            'channel' => 'required|in:sms,email,both',
            'recipients' => 'required|string',
            'message' => 'required|string|max:500',
            'subject' => 'nullable|string|max:120',
        ]);

        $recipients = array_filter(array_map('trim', preg_split('/[,\n]/', $request->recipients)));
        $sent = [
            'sms' => 0,
            'email' => 0,
        ];

        if (in_array($request->channel, ['sms', 'both'])) {
            foreach ($recipients as $to) {
                if (preg_match('/^\+?\d{10,15}$/', $to)) {
                    $smsService->send($to, $request->message);
                    $sent['sms']++;
                }
            }
        }

        if (in_array($request->channel, ['email', 'both'])) {
            foreach ($recipients as $to) {
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    Mail::raw($request->message, function ($m) use ($to, $request) {
                        $m->to($to)->subject($request->subject ?: 'Notification');
                    });
                    $sent['email']++;
                }
            }
        }

        return redirect()->route('communication.index')->with('status', "Sent: SMS {$sent['sms']}, Email {$sent['email']}");
    }
}
