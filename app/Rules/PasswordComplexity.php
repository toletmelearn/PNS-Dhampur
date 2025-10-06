<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class PasswordComplexity implements ValidationRule
{
    protected $user;
    protected $role;

    public function __construct($user = null, $role = null)
    {
        $this->user = $user;
        $this->role = $role ?? ($user ? $user->role : (Auth::user() ? Auth::user()->role : 'student'));
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $policy = config('password_policy');
        $rolePolicy = $policy['roles'][$this->role] ?? [];
        
        // Get effective settings (role-specific overrides general settings)
        $minLength = $rolePolicy['min_length'] ?? $policy['complexity']['min_length'];
        $maxLength = $policy['complexity']['max_length'];
        $requireUppercase = $rolePolicy['require_all_complexity'] ?? $policy['complexity']['require_uppercase'];
        $requireLowercase = $rolePolicy['require_all_complexity'] ?? $policy['complexity']['require_lowercase'];
        $requireNumbers = $rolePolicy['require_all_complexity'] ?? $policy['complexity']['require_numbers'];
        $requireSpecialChars = $rolePolicy['require_all_complexity'] ?? $policy['complexity']['require_special_chars'];
        $allowedSpecialChars = $policy['complexity']['allowed_special_chars'];
        $minUniqueChars = $policy['complexity']['min_unique_chars'];

        // Length validation
        if (strlen($value) < $minLength) {
            $fail(__($policy['validation_messages']['min_length'], ['min' => $minLength]));
            return;
        }

        if (strlen($value) > $maxLength) {
            $fail(__($policy['validation_messages']['max_length'], ['max' => $maxLength]));
            return;
        }

        // Complexity validation
        if ($requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $fail(__($policy['validation_messages']['uppercase']));
            return;
        }

        if ($requireLowercase && !preg_match('/[a-z]/', $value)) {
            $fail(__($policy['validation_messages']['lowercase']));
            return;
        }

        if ($requireNumbers && !preg_match('/\d/', $value)) {
            $fail(__($policy['validation_messages']['numbers']));
            return;
        }

        if ($requireSpecialChars && !preg_match('/[' . preg_quote($allowedSpecialChars, '/') . ']/', $value)) {
            $fail(__($policy['validation_messages']['special_chars'], ['chars' => $allowedSpecialChars]));
            return;
        }

        // Unique characters validation
        $uniqueChars = count(array_unique(str_split($value)));
        if ($uniqueChars < $minUniqueChars) {
            $fail(__($policy['validation_messages']['unique_chars'], ['min' => $minUniqueChars]));
            return;
        }

        // Common password check
        if ($policy['strength']['check_common_passwords'] && $this->isCommonPassword($value)) {
            $fail(__($policy['validation_messages']['common_password']));
            return;
        }

        // Personal information check
        if ($policy['strength']['check_personal_info'] && $this->containsPersonalInfo($value)) {
            $fail(__($policy['validation_messages']['personal_info']));
            return;
        }
    }

    /**
     * Check if password is in common passwords list
     */
    protected function isCommonPassword($password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123', 'password123',
            'admin', 'letmein', 'welcome', 'monkey', '1234567890', 'password1',
            'qwerty123', 'admin123', 'root', 'toor', 'pass', 'test', 'guest',
            'user', 'login', 'changeme', 'newpassword', 'secret', 'default'
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Check if password contains personal information
     */
    protected function containsPersonalInfo($password): bool
    {
        if (!$this->user) {
            return false;
        }

        $password = strtolower($password);
        $personalInfo = [
            strtolower($this->user->name ?? ''),
            strtolower($this->user->email ?? ''),
            strtolower(explode('@', $this->user->email ?? '')[0] ?? ''),
        ];

        foreach ($personalInfo as $info) {
            if (!empty($info) && strlen($info) > 2 && strpos($password, $info) !== false) {
                return true;
            }
        }

        return false;
    }
}