<?php
return [
    'thresholds' => [
        // Overall confidence thresholds (percent-style, 0-100)
        'overall' => [
            'auto_resolve' => 85.0,
            'manual_review' => 60.0,
            'reject' => 40.0,
        ],
        // Field-level similarity thresholds (ratio-style, 0-1)
        'name_similarity' => [
            'high' => 0.85,
            'medium' => 0.70,
            'low' => 0.50,
        ],
        'address_similarity' => [
            'high' => 0.80,
            'medium' => 0.65,
            'low' => 0.45,
        ],
        // Date tolerance in days for minor OCR/date extraction deviations
        'date_tolerance_days' => 2,
        // Numeric similarity (e.g., Aadhaar) ratio thresholds
        'numeric_similarity' => [
            'high' => 0.95,
            'medium' => 0.85,
            'low' => 0.75,
        ],
    ],

    // Use dedicated mismatch status when manual resolution is required
    'use_mismatch_status' => true,

    // Optional: aliases to keep old controller/service code consistent
    'status_aliases' => [
        'approved' => 'verified',
        'rejected' => 'failed',
        'mismatch' => 'mismatch',
    ],
];