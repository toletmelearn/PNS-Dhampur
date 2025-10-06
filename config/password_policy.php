<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Policy Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all password security policies for the application.
    | These settings control password complexity, expiration, and history.
    |
    */

    'complexity' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'max_length' => env('PASSWORD_MAX_LENGTH', 128),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL_CHARS', true),
        'allowed_special_chars' => '@$!%*?&',
        'min_unique_chars' => env('PASSWORD_MIN_UNIQUE_CHARS', 4),
    ],

    'expiration' => [
        'enabled' => env('PASSWORD_EXPIRATION_ENABLED', true),
        'days' => env('PASSWORD_EXPIRATION_DAYS', 90),
        'warning_days' => env('PASSWORD_EXPIRATION_WARNING_DAYS', 7),
        'grace_period_days' => env('PASSWORD_GRACE_PERIOD_DAYS', 3),
    ],

    'history' => [
        'enabled' => env('PASSWORD_HISTORY_ENABLED', true),
        'remember_count' => env('PASSWORD_HISTORY_COUNT', 5),
    ],

    'lockout' => [
        'enabled' => env('PASSWORD_LOCKOUT_ENABLED', true),
        'max_attempts' => env('PASSWORD_MAX_ATTEMPTS', 5),
        'lockout_duration' => env('PASSWORD_LOCKOUT_DURATION', 30), // minutes
    ],

    'strength' => [
        'check_common_passwords' => env('PASSWORD_CHECK_COMMON', true),
        'check_personal_info' => env('PASSWORD_CHECK_PERSONAL_INFO', true),
        'min_entropy' => env('PASSWORD_MIN_ENTROPY', 3.0),
    ],

    'validation_messages' => [
        'min_length' => 'Password must be at least :min characters long.',
        'max_length' => 'Password must not exceed :max characters.',
        'uppercase' => 'Password must contain at least one uppercase letter.',
        'lowercase' => 'Password must contain at least one lowercase letter.',
        'numbers' => 'Password must contain at least one number.',
        'special_chars' => 'Password must contain at least one special character (:chars).',
        'unique_chars' => 'Password must contain at least :min unique characters.',
        'common_password' => 'This password is too common. Please choose a more secure password.',
        'personal_info' => 'Password cannot contain personal information like name or email.',
        'history' => 'Password cannot be one of your last :count passwords.',
        'expired' => 'Your password has expired. Please change it to continue.',
        'expires_soon' => 'Your password will expire in :days days. Please change it soon.',
    ],

    'roles' => [
        'admin' => [
            'min_length' => 12,
            'require_all_complexity' => true,
            'expiration_days' => 60,
            'history_count' => 8,
        ],
        'teacher' => [
            'min_length' => 10,
            'require_all_complexity' => true,
            'expiration_days' => 90,
            'history_count' => 5,
        ],
        'student' => [
            'min_length' => 8,
            'require_all_complexity' => false,
            'expiration_days' => 180,
            'history_count' => 3,
        ],
    ],
];