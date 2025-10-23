<?php
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Exam;
use App\Models\AdmitTemplate;
use App\Services\AdmitCardGenerationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    // Create a class if none exists
    $class = ClassModel::first();
    if (!$class) {
        $class = ClassModel::create([
            'name' => 'Class 10',
            'section' => 'A',
            'capacity' => 60,
            'is_active' => true,
            'description' => 'Demo class for admit cards'
        ]);
    }

    // Create an active admit template if none exists
    $template = AdmitTemplate::where('is_active', true)->first();
    if (!$template) {
        $template = AdmitTemplate::create([
            'name' => 'Default A4',
            'format' => 'A4',
            'settings' => [
                'header' => 'School Admit Card',
                'show_logo' => true
            ],
            'is_active' => true,
            'created_by' => null
        ]);
    }

    // Seed a few students if none exist
    if (Student::count() === 0) {
        for ($i = 1; $i <= 10; $i++) {
            $student = new Student();
            $student->admission_no = 'ADM' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $student->admission_number = $student->admission_no;
            $student->name = 'Demo Student ' . $i;
            $student->father_name = 'Father ' . $i;
            $student->mother_name = 'Mother ' . $i;
            $student->dob = now()->subYears(14)->subDays($i)->toDateString();
            $student->aadhaar = '1234567890' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $student->class_id = $class->id;
            $student->documents = [];
            $student->documents_verified_data = [];
            $student->verification_status = 'verified';
            $student->status = 'active';
            $student->verified = true;
            $student->meta = [
                'blood_group' => 'O+',
                'emergency_contact' => '9999999999',
                'address' => 'Demo Address ' . $i
            ];
            // Explicit roll number
            $student->setAttribute('roll_number', $i);
            $student->save();
        }
    } else {
        // Ensure at least 5 students are verified and have roll numbers
        $students = Student::where('class_id', $class->id)->take(5)->get();
        $n = 1;
        foreach ($students as $s) {
            $s->verified = true;
            $s->verification_status = 'verified';
            $s->setAttribute('roll_number', $n);
            $s->save();
            $n++;
        }
    }

    // Create an exam for the class (align with actual schema)
    $exam = new Exam();
    $exam->name = 'Midterm Test';
    $exam->class_id = $class->id;
    $exam->subject = 'Mathematics';
    $exam->exam_date = now()->addDays(7)->format('Y-m-d');
    $exam->start_time = '10:00:00';
    // End time column may not exist in current schema; skip setting it
    $exam->duration = 120; // minutes
    $exam->total_marks = 100;
    $exam->status = 'active'; // enum: active|inactive|completed
    $exam->save();

    DB::commit();

    // Generate admit cards
    $service = app(AdmitCardGenerationService::class);
    $result = $service->generateAdmitCards($exam->id, $class->id);

    echo json_encode([
        'class_id' => $class->id,
        'exam_id' => $exam->id,
        'generated' => $result['generated'] ?? 0,
        'message' => $result['message'] ?? 'done',
        'success' => $result['success'] ?? false,
        'paths' => $result['paths'] ?? []
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}