<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    */
    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    */
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'auth_mode' => null,
            'verify_peer' => false,
        ],

        'ses' => [
            'transport' => 'ses',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'options' => [
                'ConfigurationSetName' => env('MAIL_CONFIGURATION_SET'),
            ],
        ],

        'mailgun' => [
            'transport' => 'mailgun',
            'domain' => env('MAILGUN_DOMAIN'),
            'secret' => env('MAILGUN_SECRET'),
            'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
            'scheme' => 'https',
        ],

        'postmark' => [
            'transport' => 'postmark',
            'token' => env('POSTMARK_TOKEN'),
            'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "Reply-To" Address
    |--------------------------------------------------------------------------
    */
    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_REPLY_TO_NAME', 'No Reply'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'welcome' => [
            'subject' => 'Welcome to ' . env('APP_NAME'),
            'view' => 'emails.welcome',
        ],
        'password_reset' => [
            'subject' => 'Password Reset Request',
            'view' => 'emails.password-reset',
        ],
        'invoice' => [
            'subject' => 'Invoice Payment Reminder',
            'view' => 'emails.invoice',
        ],
        'notification' => [
            'subject' => 'Notification from ' . env('APP_NAME'),
            'view' => 'emails.notification',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => env('MAIL_QUEUE_ENABLED', true),
        'connection' => env('MAIL_QUEUE_CONNECTION', 'redis'),
        'queue' => env('MAIL_QUEUE_NAME', 'emails'),
        'delay' => env('MAIL_QUEUE_DELAY', 0),
        'tries' => env('MAIL_QUEUE_TRIES', 3),
        'timeout' => env('MAIL_QUEUE_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('MAIL_RATE_LIMIT_ENABLED', true),
        'max_emails_per_minute' => env('MAIL_RATE_LIMIT_MAX', 100),
        'max_emails_per_hour' => env('MAIL_RATE_LIMIT_HOUR_MAX', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Tracking
    |--------------------------------------------------------------------------
    */
    'tracking' => [
        'enabled' => env('MAIL_TRACKING_ENABLED', true),
        'open_tracking' => true,
        'click_tracking' => true,
        'google_analytics' => [
            'enabled' => false,
            'campaign_source' => 'email',
            'campaign_medium' => 'email',
            'campaign_name' => env('APP_NAME') . '_email_campaign',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'dkim' => [
            'enabled' => env('MAIL_DKIM_ENABLED', false),
            'selector' => env('MAIL_DKIM_SELECTOR', 'default'),
            'private_key' => env('MAIL_DKIM_PRIVATE_KEY'),
            'domain' => env('MAIL_DKIM_DOMAIN'),
        ],
        'spf' => [
            'enabled' => env('MAIL_SPF_ENABLED', true),
            'record' => env('MAIL_SPF_RECORD', 'v=spf1 include:_spf.google.com ~all'),
        ],
        'dmarc' => [
            'enabled' => env('MAIL_DMARC_ENABLED', true),
            'policy' => env('MAIL_DMARC_POLICY', 'p=quarantine'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Bounce Handling
    |--------------------------------------------------------------------------
    */
    'bounce_handling' => [
        'enabled' => env('MAIL_BOUNCE_HANDLING_ENABLED', true),
        'handler' => env('MAIL_BOUNCE_HANDLER', 'log'), // log, database, webhook
        'webhook_url' => env('MAIL_BOUNCE_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'enabled' => env('MAIL_VALIDATION_ENABLED', true),
        'timeout' => env('MAIL_VALIDATION_TIMEOUT', 5),
        'check_mx' => true,
        'check_disposable' => true,
        'check_role' => true,
    ],

];