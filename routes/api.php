<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ClassModelController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BellTimingController;
use App\Http\Controllers\TeacherSubstitutionController;
use App\Http\Controllers\TeacherAvailabilityController;

// -----------------------------
// PUBLIC ROUTES
// -----------------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // optional if you want registration
Route::get('/test', function() {
    return response()->json(['message' => 'API is working!']);
});

// -----------------------------
// PROTECTED ROUTES (Sanctum)
// -----------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Students
    Route::apiResource('students', StudentController::class);
    Route::post('students/{student}/verify', [StudentController::class, 'verify']);

    // Teachers
    Route::apiResource('teachers', TeacherController::class);

    // Classes
    Route::apiResource('classes', ClassModelController::class);

    // Attendance API with comprehensive security middleware
    Route::prefix('attendances')->name('attendances.')
        ->middleware(['auth:sanctum', 'attendance.security', 'role:admin,teacher,principal,class_teacher'])
        ->group(function () {
            // List attendances - requires view permission
            Route::get('/', [AttendanceController::class, 'index'])
                ->middleware('permission:view_attendance');
            
            // Create attendance - requires mark permission
            Route::post('/', [AttendanceController::class, 'store'])
                ->middleware('permission:mark_attendance');
            
            // Show specific attendance - requires view permission
            Route::get('/{attendance}', [AttendanceController::class, 'show'])
                ->middleware('permission:view_attendance');
            
            // Update attendance - requires edit permission
            Route::put('/{attendance}', [AttendanceController::class, 'update'])
                ->middleware(['role:admin,teacher,principal,class_teacher', 'permission:edit_attendance']);
            
            // Delete attendance - admin and principal only
            Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])
                ->middleware(['role:admin,principal', 'permission:delete_attendance']);
        });

    // Fees
    Route::apiResource('fees', FeeController::class);
    Route::post('fees/{id}/pay', [FeePaymentController::class, 'pay']);
    Route::get('fees/{id}/receipt', [FeePaymentController::class, 'receipt']);

    // Salaries
    Route::apiResource('salaries', SalaryController::class);
    Route::post('salaries/{id}/pay', [SalaryController::class, 'pay']); // Pay salary endpoint

    // Exams
    Route::apiResource('exams', ExamController::class);

    // Results
    Route::apiResource('results', ResultController::class);

    // Syllabus
    Route::apiResource('syllabus', SyllabusController::class);

    // Inventory
    Route::apiResource('inventory', InventoryController::class);

    // Budget
    Route::apiResource('budgets', BudgetController::class);

    // Bell Timings
    Route::apiResource('bell-timings', BellTimingController::class);
    Route::get('bell-timings/schedule/current', [BellTimingController::class, 'getCurrentSchedule']);
    Route::get('bell-timings/notification/check', [BellTimingController::class, 'checkBellNotification']);
    Route::patch('bell-timings/{bellTiming}/toggle', [BellTimingController::class, 'toggleActive']);
    Route::patch('bell-timings/order/update', [BellTimingController::class, 'updateOrder']);

    // Teacher Substitutions
    Route::apiResource('teacher-substitutions', TeacherSubstitutionController::class);
    Route::get('teacher-substitutions/{substitution}/available-substitutes', [TeacherSubstitutionController::class, 'getAvailableSubstitutes']);
    Route::post('teacher-substitutions/{substitution}/assign', [TeacherSubstitutionController::class, 'assignSubstitute']);
    Route::post('teacher-substitutions/auto-assign', [TeacherSubstitutionController::class, 'autoAssignSubstitutes']);
    Route::get('teacher-substitutions/dashboard/stats', [TeacherSubstitutionController::class, 'getDashboardStats']);

    // Teacher Availability
    Route::apiResource('teacher-availability', TeacherAvailabilityController::class);
    Route::get('teachers/{teacher}/availability', [TeacherAvailabilityController::class, 'getTeacherAvailability']);
    Route::post('teacher-availability/weekly', [TeacherAvailabilityController::class, 'createWeeklyAvailability']);
    Route::get('teacher-availability/available-teachers', [TeacherAvailabilityController::class, 'getAvailableTeachers']);
    Route::post('teacher-availability/create-default-all', [TeacherAvailabilityController::class, 'createDefaultAvailabilityForAllTeachers']);
});


