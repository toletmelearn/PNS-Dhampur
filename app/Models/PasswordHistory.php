<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password',
        'created_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that owns the password history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Clean up old password history records for a user
     */
    public static function cleanupOldPasswords($userId, $keepCount = null)
    {
        $policy = config('password_policy');
        $keepCount = $keepCount ?? $policy['history']['remember_count'];

        $oldPasswords = static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->skip($keepCount)
            ->take(100) // Limit to prevent memory issues
            ->get();

        foreach ($oldPasswords as $oldPassword) {
            $oldPassword->delete();
        }
    }

    /**
     * Add a new password to history
     */
    public static function addPassword($userId, $hashedPassword)
    {
        static::create([
            'user_id' => $userId,
            'password' => $hashedPassword,
        ]);

        // Clean up old passwords
        static::cleanupOldPasswords($userId);
    }
}