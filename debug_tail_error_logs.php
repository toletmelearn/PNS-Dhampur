<?php
// Simple script to print the latest 15 entries from error_logs
// Usage: php debug_tail_error_logs.php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Bootstrap the application to use DB and models
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fetch last 15 error_logs
try {
    $logs = DB::table('error_logs')
        ->orderByDesc('id')
        ->limit(15)
        ->get();

    if ($logs->isEmpty()) {
        echo "No error logs found.\n";
        exit(0);
    }

    foreach ($logs as $log) {
        // Prepare a compact line for each log
        $line = [
            'id' => $log->id,
            'level' => $log->level,
            'message' => $log->message,
            'url' => $log->url ?? null,
            'method' => $log->method ?? null,
            'ip' => $log->ip_address ?? null,
            'user_id' => $log->user_id ?? null,
            'file' => $log->file ?? null,
            'line' => $log->line ?? null,
            'created_at' => $log->created_at,
        ];
        echo json_encode($line, JSON_UNESCAPED_SLASHES) . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error reading error_logs: " . $e->getMessage() . "\n");
    exit(1);
}