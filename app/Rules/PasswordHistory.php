<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use App\Models\PasswordHistory as PasswordHistoryModel;

class PasswordHistory implements ValidationRule
{
    protected $user;
    protected $role;

    public function __construct($user, $role = null)
    {
        $this->user = $user;
        $this->role = $role ?? $user->role;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $policy = config('password_policy');
        
        if (!$policy['history']['enabled']) {
            return;
        }

        $rolePolicy = $policy['roles'][$this->role] ?? [];
        $historyCount = $rolePolicy['history_count'] ?? $policy['history']['remember_count'];

        // Get user's password history
        $passwordHistories = PasswordHistoryModel::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->get();

        // Check against current password
        if ($this->user->password && Hash::check($value, $this->user->password)) {
            $fail(__($policy['validation_messages']['history'], ['count' => $historyCount]));
            return;
        }

        // Check against historical passwords
        foreach ($passwordHistories as $history) {
            if (Hash::check($value, $history->password)) {
                $fail(__($policy['validation_messages']['history'], ['count' => $historyCount]));
                return;
            }
        }
    }
}