<?php
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admitCount = DB::table('admit_cards')->count();
$seatCount = DB::table('exam_seat_allocations')->count();
$examCount = DB::table('exams')->count();
$classCount = DB::table('class_models')->count();

$paths = DB::table('admit_cards')->orderByDesc('id')->limit(10)->pluck('pdf_path');

$result = [
    'admit_cards' => $admitCount,
    'exam_seat_allocations' => $seatCount,
    'exams' => $examCount,
    'class_models' => $classCount,
    'sample_pdf_paths' => $paths,
];

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;