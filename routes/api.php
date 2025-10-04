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
use App\Http\Controllers\Api\ExternalIntegrationController;
use App\Http\Controllers\SubstitutionController;

// -----------------------------
// PUBLIC ROUTES
// -----------------------------
Route::post('/login', [AuthController::class, 'login'])->middleware('rate.limit:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('rate.limit:3,1'); // optional if you want registration
Route::get('/test', function() {
    return response()->json(['message' => 'API is working!']);
});

// -----------------------------
// PROTECTED ROUTES (Sanctum)
// -----------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('rate.limit:10,1');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

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
    // Bell Timing Routes
    Route::apiResource('bell-timings', BellTimingController::class);
    Route::get('bell-timings/schedule/current', [BellTimingController::class, 'getCurrentSchedule']);
    Route::get('bell-timings/schedule/enhanced', [BellTimingController::class, 'getCurrentScheduleEnhanced']);
    Route::get('bell-timings/notification/check', [BellTimingController::class, 'checkBellNotification']);
    Route::patch('bell-timings/{bellTiming}/toggle', [BellTimingController::class, 'toggleActive']);
    Route::patch('bell-timings/order/update', [BellTimingController::class, 'updateOrder']);
    
    // Season switching endpoints
    Route::get('bell-timings/season/info', [BellTimingController::class, 'getSeasonInfo']);
    Route::post('bell-timings/season/switch', [BellTimingController::class, 'switchSeason']);
    Route::delete('bell-timings/season/override', [BellTimingController::class, 'clearSeasonOverride']);
    Route::post('bell-timings/season/check', [BellTimingController::class, 'checkSeasonSwitch']);

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

    // Students
    Route::get('/students/verify/{id}', [App\Http\Controllers\StudentVerificationController::class, 'verify'])->middleware('rate.limit:10,1');
    
    // Attendance
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->middleware('rate.limit:30,1');
    Route::get('/attendance/student/{id}', [AttendanceController::class, 'getStudentAttendance'])->middleware('rate.limit:20,1');
    
    // Fee Payments
    Route::post('/fees/payment', [FeeController::class, 'processPayment'])->middleware('rate.limit:5,1');
    Route::get('/fees/student/{id}', [FeeController::class, 'getStudentFees'])->middleware('rate.limit:20,1');
    
    // Salary Payments
    Route::post('/salary/payment', [SalaryController::class, 'processPayment'])->middleware('rate.limit:5,1');
    Route::get('/salary/teacher/{id}', [SalaryController::class, 'getTeacherSalary'])->middleware('rate.limit:20,1');

    // Substitute Notifications
    Route::get('substitute/notifications', [SubstitutionController::class, 'getNotifications'])->name('api.substitute.notifications');
    Route::post('substitute/notifications/{notification}/action', [SubstitutionController::class, 'handleNotificationAction']);
    Route::post('substitute/notifications/{notification}/dismiss', [SubstitutionController::class, 'dismissNotification']);
    Route::post('substitute/notifications/clear', [SubstitutionController::class, 'clearAllNotifications']);

    // External Integrations
    Route::prefix('external')->name('external.')
        ->middleware('external.integration')
        ->group(function () {
            // Aadhaar Verification
            Route::post('aadhaar/verify', [ExternalIntegrationController::class, 'verifyAadhaar'])
                ->middleware(['rate.limit:10,1', 'role:admin,principal,teacher']);
            Route::post('aadhaar/bulk-verify', [ExternalIntegrationController::class, 'bulkVerifyAadhaar'])
                ->middleware(['rate.limit:5,1', 'role:admin,principal']);
            Route::get('aadhaar/stats', [ExternalIntegrationController::class, 'getAadhaarStats'])
                ->middleware('role:admin,principal');

            // Biometric Device Integration
            Route::post('biometric/import', [ExternalIntegrationController::class, 'importBiometricData'])
                ->middleware(['rate.limit:3,1', 'role:admin,principal,teacher']);
            Route::get('biometric/import-status/{importId}', [ExternalIntegrationController::class, 'getImportStatus'])
                ->middleware('role:admin,principal,teacher');
            Route::get('biometric/stats', [ExternalIntegrationController::class, 'getBiometricStats'])
                ->middleware('role:admin,principal');

            // Browser Notifications
            Route::post('notifications/send', [ExternalIntegrationController::class, 'sendBrowserNotification'])
                ->middleware(['rate.limit:20,1', 'role:admin,principal,teacher']);
            Route::post('notifications/subscribe', [ExternalIntegrationController::class, 'subscribeUser'])
                ->middleware('rate.limit:10,1');
            Route::get('notifications/vapid-key', [ExternalIntegrationController::class, 'getVapidPublicKey']);
        });
});


