<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $columns = DB::select('SHOW COLUMNS FROM exams');
    foreach ($columns as $col) {
        echo $col->Field . "\t" . $col->Type . "\t" . ($col->Null) . "\t" . ($col->Default ?? 'NULL') . "\n";
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}