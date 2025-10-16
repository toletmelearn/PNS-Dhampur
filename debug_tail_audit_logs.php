<?php
// Simple script to print the latest 15 entries from audit_logs
// Usage: php debug_tail_audit_logs.php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Bootstrap the application to use DB and models
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fetch last 15 audit_logs
try {
    $logs = DB::table('audit_logs')
        ->orderByDesc('id')
        ->limit(15)
        ->get();

    if ($logs->isEmpty()) {
        echo "No audit logs found.\n";
        exit(0);
    }

    foreach ($logs as $log) {
        // Prepare a compact line for each log
        $line = [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'action' => $log->action,
            'model_type' => $log->model_type,
            'model_id' => $log->model_id,
            'ip' => $log->ip_address,
            'created_at' => $log->created_at,
            'url' => property_exists($log, 'url') ? $log->url : null,
        ];
        echo json_encode($line, JSON_UNESCAPED_SLASHES) . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Error reading audit_logs: " . $e->getMessage() . "\n");
    exit(1);
}