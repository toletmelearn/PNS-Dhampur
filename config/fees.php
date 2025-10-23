<?php

return [
    'late_fee' => [
        'enabled' => env('LATE_FEE_ENABLED', true),
        'type' => env('LATE_FEE_TYPE', 'per_day'), // per_day or flat
        'amount_per_day' => env('LATE_FEE_PER_DAY', 10.0),
        'flat_amount' => env('LATE_FEE_FLAT', 50.0),
        'grace_days' => env('LATE_FEE_GRACE_DAYS', 0),
        'max_late_fee' => env('LATE_FEE_MAX', null), // optional cap
    ],

    'reminders' => [
        'enabled' => env('FEE_REMINDERS_ENABLED', true),
        'upcoming_days_ahead' => env('FEE_REMINDER_DAYS_AHEAD', 3),
        'send_time' => env('FEE_REMINDER_TIME', '08:00'),
    ],
];