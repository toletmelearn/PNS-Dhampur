<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send a single SMS message using the configured provider.
     */
    public function send(string $to, string $message): bool
    {
        $provider = config('services.sms.provider', env('SMS_SERVICE', 'twilio'));

        try {
            switch (strtolower($provider)) {
                case 'twilio':
                    return $this->sendViaTwilio($to, $message);
                // Additional providers can be added here (vonage, sns, etc.)
                default:
                    Log::warning('SmsService: Unknown provider, falling back to log-only', [
                        'provider' => $provider,
                        'to' => $to,
                        'message' => $message,
                    ]);
                    return false;
            }
        } catch (\Throwable $e) {
            Log::error('SmsService: Failed to send SMS', [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send a message to multiple recipients.
     */
    public function sendBulk(array $recipients, string $message): array
    {
        $results = [];
        foreach ($recipients as $to) {
            $results[$to] = $this->send($to, $message);
        }
        return $results;
    }

    /**
     * Twilio implementation over REST API via Laravel HTTP client.
     */
    protected function sendViaTwilio(string $to, string $message): bool
    {
        $sid = config('services.twilio.sid', env('TWILIO_SID'));
        $token = config('services.twilio.token', env('TWILIO_TOKEN'));
        $from = config('services.twilio.from', env('TWILIO_FROM'));

        if (!$sid || !$token || !$from) {
            Log::error('SmsService: Twilio credentials missing');
            return false;
        }

        $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $sid);

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, [
                'From' => $from,
                'To'   => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            Log::info('SmsService: Twilio SMS sent', [
                'to' => $to,
                'sid' => optional($response->json())['sid'] ?? null,
            ]);
            return true;
        }

        Log::error('SmsService: Twilio SMS failed', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        return false;
    }
}