<?php

namespace App\Services;

class AuditLogger
{
    public function log($event, $data = [])
    {
        // Example: write logs to storage/logs/audit.log
        $message = '[' . now() . "] $event: " . json_encode($data) . PHP_EOL;
        file_put_contents(storage_path('logs/audit.log'), $message, FILE_APPEND);
    }
}
