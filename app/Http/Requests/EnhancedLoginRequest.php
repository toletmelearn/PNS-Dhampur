<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class EnhancedLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                function ($attribute, $value, $fail) {
                    // Check for suspicious email patterns
                    if ($this->isSuspiciousEmail($value)) {
                        $fail('The email address appears to be invalid or suspicious.');
                    }
                }
            ],
            'password' => [
                'required',
                'string',
                'min:1',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check for common attack patterns
                    if ($this->containsSqlInjectionPatterns($value)) {
                        $fail('Invalid password format.');
                    }
                }
            ],
            'remember' => 'nullable|boolean',
            'device_fingerprint' => 'nullable|string|max:500',
            'timezone' => 'nullable|string|max:50',
            'user_agent_hash' => 'nullable|string|max:64',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Email format is invalid.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password is required.',
            'password.max' => 'Password is too long.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->checkRateLimit($validator);
            $this->validateIpAddress($validator);
            $this->checkSuspiciousActivity($validator);
            $this->validateDeviceFingerprint($validator);
        });
    }

    /**
     * Check rate limiting for login attempts.
     */
    protected function checkRateLimit($validator): void
    {
        $email = $this->input('email');
        $ip = $this->ip();
        
        // Rate limit by email
        $emailKey = 'login_attempts_email:' . Str::lower($email);
        if (RateLimiter::tooManyAttempts($emailKey, 5)) {
            $seconds = RateLimiter::availableIn($emailKey);
            $validator->errors()->add('email', "Too many login attempts for this email. Try again in {$seconds} seconds.");
        }

        // Rate limit by IP
        $ipKey = 'login_attempts_ip:' . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $validator->errors()->add('email', "Too many login attempts from this IP. Try again in {$seconds} seconds.");
        }

        // Global rate limit
        $globalKey = 'login_attempts_global';
        if (RateLimiter::tooManyAttempts($globalKey, 100)) {
            $validator->errors()->add('email', 'System is temporarily unavailable. Please try again later.');
        }
    }

    /**
     * Validate IP address for suspicious patterns.
     */
    protected function validateIpAddress($validator): void
    {
        $ip = $this->ip();
        
        // Check if IP is blacklisted
        if ($this->isBlacklistedIp($ip)) {
            $validator->errors()->add('email', 'Access denied from this location.');
        }

        // Check for VPN/Proxy/Tor usage (basic check)
        if ($this->isSuspiciousIp($ip)) {
            // Log suspicious activity but don't block (configurable)
            \Log::warning('Suspicious IP detected during login attempt', [
                'ip' => $ip,
                'email' => $this->input('email'),
                'user_agent' => $this->userAgent(),
            ]);
        }
    }

    /**
     * Check for suspicious activity patterns.
     */
    protected function checkSuspiciousActivity($validator): void
    {
        $email = $this->input('email');
        $ip = $this->ip();
        $userAgent = $this->userAgent();

        // Check for rapid location changes
        $lastLoginLocation = Cache::get("last_login_location:{$email}");
        if ($lastLoginLocation && $this->isRapidLocationChange($lastLoginLocation, $ip)) {
            \Log::warning('Rapid location change detected', [
                'email' => $email,
                'previous_ip' => $lastLoginLocation,
                'current_ip' => $ip,
            ]);
        }

        // Check for user agent anomalies
        $lastUserAgent = Cache::get("last_user_agent:{$email}");
        if ($lastUserAgent && $this->isAnomalousUserAgent($lastUserAgent, $userAgent)) {
            \Log::warning('User agent anomaly detected', [
                'email' => $email,
                'previous_ua' => $lastUserAgent,
                'current_ua' => $userAgent,
            ]);
        }
    }

    /**
     * Validate device fingerprint.
     */
    protected function validateDeviceFingerprint($validator): void
    {
        $fingerprint = $this->input('device_fingerprint');
        $email = $this->input('email');

        if ($fingerprint && $email) {
            // Check if this is a known device for this user
            $knownDevices = Cache::get("known_devices:{$email}", []);
            
            if (!empty($knownDevices) && !in_array($fingerprint, $knownDevices)) {
                // New device detected - could trigger additional verification
                \Log::info('New device detected for user', [
                    'email' => $email,
                    'fingerprint' => substr($fingerprint, 0, 20) . '...',
                    'ip' => $this->ip(),
                ]);
            }
        }
    }

    /**
     * Check if email has suspicious patterns.
     */
    protected function isSuspiciousEmail(string $email): bool
    {
        // Check for temporary email domains
        $tempDomains = [
            '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
            'tempmail.org', 'throwaway.email', 'temp-mail.org'
        ];
        
        $domain = substr(strrchr($email, '@'), 1);
        
        return in_array(strtolower($domain), $tempDomains);
    }

    /**
     * Check for SQL injection patterns in password.
     */
    protected function containsSqlInjectionPatterns(string $password): bool
    {
        $patterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b\s+\d+\s*=\s*\d+|\bAND\b\s+\d+\s*=\s*\d+)/i',
            '/(\'|\")(\s*;\s*|\s*--|\s*\/\*)/i',
            '/(\bxp_cmdshell\b|\bsp_executesql\b)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is blacklisted.
     */
    protected function isBlacklistedIp(string $ip): bool
    {
        // Check against blacklisted IPs in cache/database
        return Cache::has("blacklisted_ip:{$ip}");
    }

    /**
     * Check if IP is suspicious (VPN/Proxy/Tor).
     */
    protected function isSuspiciousIp(string $ip): bool
    {
        // Basic checks for common VPN/Proxy ranges
        // In production, you might use a service like MaxMind or similar
        
        // Check for localhost/private ranges that shouldn't be accessing externally
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false; // Private/reserved IPs are not suspicious for internal access
        }

        // Check cache for known suspicious IPs
        return Cache::has("suspicious_ip:{$ip}");
    }

    /**
     * Check for rapid location changes.
     */
    protected function isRapidLocationChange(?string $lastIp, string $currentIp): bool
    {
        if (!$lastIp || $lastIp === $currentIp) {
            return false;
        }

        // Simple check - in production you'd use geolocation services
        $lastLoginTime = Cache::get("last_login_time:{$lastIp}");
        
        if ($lastLoginTime && now()->diffInMinutes($lastLoginTime) < 30) {
            // Location change within 30 minutes might be suspicious
            return true;
        }

        return false;
    }

    /**
     * Check for anomalous user agent.
     */
    protected function isAnomalousUserAgent(?string $lastUserAgent, string $currentUserAgent): bool
    {
        if (!$lastUserAgent) {
            return false;
        }

        // Check for completely different browser/OS
        $lastBrowser = $this->extractBrowserInfo($lastUserAgent);
        $currentBrowser = $this->extractBrowserInfo($currentUserAgent);

        return $lastBrowser !== $currentBrowser;
    }

    /**
     * Extract basic browser info from user agent.
     */
    protected function extractBrowserInfo(string $userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        
        return 'Unknown';
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize email
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->input('email')))
            ]);
        }

        // Generate user agent hash if not provided
        if (!$this->has('user_agent_hash')) {
            $this->merge([
                'user_agent_hash' => hash('sha256', $this->userAgent() ?? '')
            ]);
        }
    }
}