<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ClassModelController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BellTimingController;
use App\Http\Controllers\TeacherSubstitutionController;
use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\SubstitutionController;
use App\Http\Controllers\Api\ExternalIntegrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication endpoints
    Route::post('/login', [AuthController::class, 'login'])->middleware(['api.security', 'throttle:10,1']);
    Route::post('/register', [AuthController::class, 'register'])->middleware(['api.security', 'throttle:5,1']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['api.security', 'throttle:3,1']);
    
    // System status
    Route::get('/status', function () {
        return response()->json([
            'status' => 'online',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ]);
    })->middleware(['api.security']);
    
    // Test endpoint
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API is working',
            'status' => 'success',
            'timestamp' => now()->toISOString()
        ]);
    })->middleware(['api.security']);
});

// Test endpoint (outside v1 prefix for compatibility)
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working',
        'status' => 'success',
        'timestamp' => now()->toISOString()
    ]);
})->middleware(['api.security']);

// -----------------------------
// PROTECTED ROUTES (Sanctum)
// -----------------------------
Route::middleware(['auth:sanctum', 'rate.limit'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('rate.limit:auth.logout');
    Route::get('/user', [AuthController::class, 'me'])->middleware('cache.response:user_data');
    
    // Password Policy
    Route::get('/password-policy', [App\Http\Controllers\PasswordController::class, 'getPasswordPolicy'])
        ->middleware('cache.response:system');
    
    // Session Management
    Route::get('/session/info', [App\Http\Controllers\SessionController::class, 'getSessionInfo']);
    Route::post('/session/extend', [App\Http\Controllers\SessionController::class, 'extendSession']);
    Route::get('/session/timeout-warning', [App\Http\Controllers\SessionController::class, 'getTimeoutWarning']);
    Route::post('/session/logout', [App\Http\Controllers\SessionController::class, 'forceLogout']);
    Route::get('/session/policies', [App\Http\Controllers\SessionController::class, 'getSessionPolicies'])
        ->middleware('cache.response:system');

    // Student Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('students', StudentController::class)->names([
            'index' => 'api.students.index',
            'store' => 'api.students.store',
            'show' => 'api.students.show',
            'update' => 'api.students.update',
            'destroy' => 'api.students.destroy'
        ]);
        Route::post('students/{student}/verify', [StudentController::class, 'verify']);
        
        // Advanced student search and filter API routes
        Route::get('students/advanced-search', [StudentController::class, 'advancedSearch'])
            ->middleware('cache.response:dashboard')
            ->name('api.students.advanced-search');
        Route::post('students/save-search', [StudentController::class, 'saveSearch'])->name('api.students.save-search');
        Route::get('students/saved-searches', [StudentController::class, 'getSavedSearches'])
            ->middleware('cache.response:user_data')
            ->name('api.students.saved-searches');
        Route::delete('students/saved-searches/{savedSearch}', [StudentController::class, 'deleteSavedSearch'])->name('api.students.delete-saved-search');
        Route::get('students/search-suggestions', [StudentController::class, 'getSearchSuggestions'])
            ->middleware('cache.response:static')
            ->name('api.students.search-suggestions');
        Route::get('students/filter-stats', [StudentController::class, 'getFilterStats'])
            ->middleware('cache.response:dashboard')
            ->name('api.students.filter-stats');
        
        // Bulk operations API routes
        Route::post('students/bulk-attendance', [StudentController::class, 'bulkAttendance'])->name('api.students.bulk-attendance');
        Route::post('students/bulk-fee-collection', [StudentController::class, 'bulkFeeCollection'])->name('api.students.bulk-fee-collection');
        Route::post('students/bulk-document-upload', [StudentController::class, 'bulkDocumentUpload'])
            ->middleware('file.upload.rate.limit')
            ->name('api.students.bulk-document-upload');
        Route::post('students/bulk-status-update', [StudentController::class, 'bulkStatusUpdate'])->name('api.students.bulk-status-update');
    });

    // Teacher Management (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('teachers', TeacherController::class);
    });

    // Class Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('classes', ClassModelController::class)->names([
            'index' => 'api.classes.index',
            'store' => 'api.classes.store',
            'show' => 'api.classes.show',
            'update' => 'api.classes.update',
            'destroy' => 'api.classes.destroy'
        ]);
    });

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

    // Fee Management (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('fees', FeeController::class);
        Route::post('fees/{id}/pay', [FeePaymentController::class, 'pay']);
        Route::get('fees/{id}/receipt', [FeePaymentController::class, 'receipt']);
    });

    // Salary Management (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('salaries', SalaryController::class);
        Route::post('salaries/{id}/pay', [SalaryController::class, 'pay']); // Pay salary endpoint
    });

    // Exam Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('exams', ExamController::class)->names([
            'index' => 'api.exams.index',
            'store' => 'api.exams.store',
            'show' => 'api.exams.show',
            'update' => 'api.exams.update',
            'destroy' => 'api.exams.destroy'
        ]);
    });

    // Results Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('results', ResultController::class);
    });

    // Syllabus Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('syllabus', SyllabusController::class);
    });

    // Inventory Management (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('inventory', InventoryController::class);
    });

    // Budget Management (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('budgets', BudgetController::class);
    });

    // Bell Timing Management (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('bell-timings', BellTimingController::class);
        Route::get('bell-timings/schedule/current', [BellTimingController::class, 'getCurrentSchedule'])
            ->middleware('cache.response:static');
        Route::get('bell-timings/schedule/enhanced', [BellTimingController::class, 'getCurrentScheduleEnhanced'])
            ->middleware('cache.response:static');
    // Bell Timing Additional Routes (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('bell-timings/notification/check', [BellTimingController::class, 'checkBellNotification']);
        Route::patch('bell-timings/{bellTiming}/toggle', [BellTimingController::class, 'toggleActive']);
        Route::patch('bell-timings/order/update', [BellTimingController::class, 'updateOrder']);
        
        // Season switching endpoints
        Route::get('bell-timings/season/info', [BellTimingController::class, 'getSeasonInfo'])
            ->middleware('cache.response:static');
        Route::post('bell-timings/season/switch', [BellTimingController::class, 'switchSeason']);
        Route::delete('bell-timings/season/override', [BellTimingController::class, 'clearSeasonOverride']);
        Route::post('bell-timings/season/check', [BellTimingController::class, 'checkSeasonSwitch']);
    });

    // Teacher Substitutions (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('teacher-substitutions', TeacherSubstitutionController::class);
        Route::get('teacher-substitutions/{substitution}/available-substitutes', [TeacherSubstitutionController::class, 'getAvailableSubstitutes'])
            ->middleware('cache.response:dashboard');
        Route::post('teacher-substitutions/{substitution}/assign', [TeacherSubstitutionController::class, 'assignSubstitute']);
        Route::post('teacher-substitutions/auto-assign', [TeacherSubstitutionController::class, 'autoAssignSubstitutes']);
        Route::get('teacher-substitutions/dashboard/stats', [TeacherSubstitutionController::class, 'getDashboardStats'])
            ->middleware('cache.response:dashboard');
    });

    // Teacher Availability (Admin/Principal/Teacher access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('teacher-availability', TeacherAvailabilityController::class);
        Route::get('teachers/{teacher}/availability', [TeacherAvailabilityController::class, 'getTeacherAvailability'])
            ->middleware('cache.response:dashboard');
        Route::post('teacher-availability/weekly', [TeacherAvailabilityController::class, 'createWeeklyAvailability']);
        Route::get('teacher-availability/available-teachers', [TeacherAvailabilityController::class, 'getAvailableTeachers'])
            ->middleware('cache.response:dashboard');
        Route::post('teacher-availability/create-default-all', [TeacherAvailabilityController::class, 'createDefaultAvailabilityForAllTeachers']);
    });

    // Student Verification (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::get('/students/verify/{id}', [App\Http\Controllers\StudentVerificationController::class, 'verify'])->middleware('rate.limit:10,1');
    });
    
    // Attendance API (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->middleware('rate.limit:30,1');
        Route::get('/attendance/student/{id}', [AttendanceController::class, 'getStudentAttendance'])
            ->middleware(['rate.limit:20,1', 'cache.response:user_data']);
    });
    
    // Fee Payments API (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::post('/fees/payment', [FeeController::class, 'processPayment'])->middleware('rate.limit:5,1');
        Route::get('/fees/student/{id}', [FeeController::class, 'getStudentFees'])
            ->middleware(['rate.limit:20,1', 'cache.response:user_data']);
    });
    
    // Salary Payments API (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/salary/payment', [SalaryController::class, 'processPayment'])->middleware('rate.limit:5,1');
        Route::get('/salary/teacher/{id}', [SalaryController::class, 'getTeacherSalary'])
            ->middleware(['rate.limit:20,1', 'cache.response:user_data']);
    });

    // Substitute Notifications (Admin/Principal/Teacher access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::get('substitute/notifications', [SubstitutionController::class, 'getNotifications'])->name('api.substitute.notifications');
        Route::post('substitute/notifications/{notification}/action', [SubstitutionController::class, 'handleNotificationAction']);
        Route::post('substitute/notifications/{notification}/dismiss', [SubstitutionController::class, 'dismissNotification']);
        Route::post('substitute/notifications/clear', [SubstitutionController::class, 'clearAllNotifications']);
    });

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
                ->middleware(['role:admin,principal', 'cache.response:dashboard']);

            // Biometric Device Integration
            Route::post('biometric/import', [ExternalIntegrationController::class, 'importBiometricData'])
                ->middleware(['rate.limit:3,1', 'role:admin,principal,teacher']);
            Route::get('biometric/import-status/{importId}', [ExternalIntegrationController::class, 'getImportStatus'])
                ->middleware('role:admin,principal,teacher');
            Route::get('biometric/stats', [ExternalIntegrationController::class, 'getBiometricStats'])
                ->middleware(['role:admin,principal', 'cache.response:dashboard']);

            // Real-time Biometric Device Integration
            Route::prefix('biometric')->group(function () {
                Route::post('import-data', [BiometricController::class, 'importData'])
                    ->middleware(['rate.limit:10,1', 'role:admin,principal,teacher']);
                Route::post('stream', [BiometricController::class, 'handleDeviceStream'])
                    ->middleware(['rate.limit:100,1', 'role:admin,principal,teacher']);
                Route::post('bulk-sync/{deviceId}', [BiometricController::class, 'bulkSyncFromDevice'])
                    ->middleware(['rate.limit:5,1', 'role:admin,principal,teacher']);
                Route::get('device-status/{deviceId}', [BiometricController::class, 'getDeviceStatus'])
                    ->middleware(['role:admin,principal,teacher', 'cache.response:dashboard']);
                Route::post('device-register', [BiometricController::class, 'registerDevice'])
                    ->middleware(['rate.limit:3,1', 'role:admin,principal']);
                Route::get('devices', [BiometricController::class, 'getRegisteredDevices'])
                    ->middleware(['role:admin,principal,teacher', 'cache.response:dashboard']);
                Route::post('test-connection/{deviceId}', [BiometricController::class, 'testDeviceConnection'])
                    ->middleware(['rate.limit:10,1', 'role:admin,principal,teacher']);
            });

            // Browser Notifications
            Route::post('notifications/send', [ExternalIntegrationController::class, 'sendBrowserNotification'])
                ->middleware(['rate.limit:20,1', 'role:admin,principal,teacher']);
            Route::post('notifications/subscribe', [ExternalIntegrationController::class, 'subscribeUser'])
                ->middleware('rate.limit:10,1');
            Route::get('notifications/vapid-key', [ExternalIntegrationController::class, 'getVapidPublicKey'])
                ->middleware('cache.response:static');
        });
    
        // Performance Monitoring API Routes (Admin/Principal only)
        Route::middleware(['role:admin,principal'])->group(function () {
            Route::prefix('performance')->name('performance.')->group(function () {
                Route::get('/dashboard-stats', [\App\Http\Controllers\Api\PerformanceApiController::class, 'dashboardStats'])
                    ->middleware('cache.response:dashboard');
                Route::get('/system-health', [\App\Http\Controllers\Api\PerformanceApiController::class, 'systemHealth'])
                    ->middleware('cache.response:dashboard');
                Route::get('/system-health/chart', [\App\Http\Controllers\Api\PerformanceApiController::class, 'systemHealthChart'])
                    ->middleware('cache.response:dashboard');
                Route::get('/metrics', [\App\Http\Controllers\Api\PerformanceApiController::class, 'metrics'])
                    ->middleware('cache.response:dashboard');
                Route::get('/metrics/chart', [\App\Http\Controllers\Api\PerformanceApiController::class, 'metricsChart'])
                    ->middleware('cache.response:dashboard');
                Route::get('/errors', [\App\Http\Controllers\Api\PerformanceApiController::class, 'errors'])
                    ->middleware('cache.response:dashboard');
                Route::get('/errors/chart', [\App\Http\Controllers\Api\PerformanceApiController::class, 'errorsChart'])
                    ->middleware('cache.response:dashboard');
                Route::get('/activities', [\App\Http\Controllers\Api\PerformanceApiController::class, 'activities'])
                    ->middleware('cache.response:dashboard');
                Route::get('/activities/chart', [\App\Http\Controllers\Api\PerformanceApiController::class, 'activitiesChart'])
                    ->middleware('cache.response:dashboard');
                Route::get('/activities/user/{userId}', [\App\Http\Controllers\Api\PerformanceApiController::class, 'userActivities'])
                    ->middleware('cache.response:user_data');
                Route::get('/activities/{activityId}', [\App\Http\Controllers\Api\PerformanceApiController::class, 'activityDetails'])
                    ->middleware('cache.response:dashboard');
                Route::post('/system-health', [\App\Http\Controllers\Api\PerformanceApiController::class, 'recordSystemHealth']);
                Route::post('/errors/{errorId}/resolve', [\App\Http\Controllers\Api\PerformanceApiController::class, 'resolveError']);
            });
        });
    });
});


