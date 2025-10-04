<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aadhaar Verification Service
    |--------------------------------------------------------------------------
    |
    | Configuration for Aadhaar verification API service.
    | In development, mock API is used for testing purposes.
    |
    */

    'aadhaar' => [
        'api_key' => env('AADHAAR_API_KEY', 'mock_api_key_for_development'),
        'api_secret' => env('AADHAAR_API_SECRET', 'mock_api_secret_for_development'),
        'base_url' => env('AADHAAR_API_BASE_URL', 'https://api.aadhaarverification.com/v1'),
        'timeout' => env('AADHAAR_API_TIMEOUT', 30),
        'mock_enabled' => env('AADHAAR_MOCK_ENABLED', true),
        'mock_success_rate' => env('AADHAAR_MOCK_SUCCESS_RATE', 0.85),
    ],

    'birth_certificate_ocr' => [
        'api_key' => env('BIRTH_CERT_OCR_API_KEY'),
        'api_secret' => env('BIRTH_CERT_OCR_API_SECRET'),
        'base_url' => env('BIRTH_CERT_OCR_BASE_URL', 'https://api.ocrservice.com/v1'),
        'timeout' => env('BIRTH_CERT_OCR_TIMEOUT', 30),
        'mock_mode' => env('BIRTH_CERT_OCR_MOCK_MODE', true),
        'mock_success_rate' => env('BIRTH_CERT_OCR_MOCK_SUCCESS_RATE', 85),
        'mock_delay' => env('BIRTH_CERT_OCR_MOCK_DELAY', 2),
        'supported_formats' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_file_size' => 10 * 1024 * 1024, // 10MB
    ],

];
