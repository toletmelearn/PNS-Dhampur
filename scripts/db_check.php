<?php
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\AdmitCard;

function safeCount($modelClass) {
    try {
        return $modelClass::count();
    } catch (\Throwable $e) {
        return 'ERR: ' . $e->getMessage();
    }
}

$results = [
    'students' => safeCount(Student::class),
    'classes' => safeCount(ClassModel::class),
    'exams' => safeCount(Exam::class),
    'admit_cards' => safeCount(AdmitCard::class),
];

echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;