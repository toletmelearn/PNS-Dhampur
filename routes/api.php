<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportsController;
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
use App\Http\Controllers\Api\SuperAdminApiController;
use App\Http\Controllers\AdmitApiController;
use App\Http\Controllers\DailySyllabusManagementController;
use App\Http\Controllers\VendorManagementController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\AssetAllocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\InventoryManagementController;
use App\Http\Controllers\BudgetManagementController;
use App\Http\Controllers\ExamPaperManagementController;
use App\Modules\Student\Controllers\StudentController as ModuleStudentController;
use App\Http\Controllers\ClassTeacherDataController;
use App\Http\Controllers\SRRegisterApiController;
use App\Http\Controllers\API\AlumniApiController;

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
Route::prefix('v1')->middleware(['security', 'audit'])->group(function () {
    // Authentication endpoints
    Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:10,1']);
    Route::post('/register', [AuthController::class, 'register'])->middleware(['throttle:5,1']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['throttle:3,1']);
    
    // System status
    Route::get('/status', function () {
        return response()->json([
            'status' => 'online',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ]);
    });
    
    // Test endpoint
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API is working',
            'status' => 'success',
            'timestamp' => now()->toISOString()
        ]);
    });
    
    // Public school information
    Route::get('/school/info', function () {
        return response()->json([
            'name' => config('app.name'),
            'description' => 'Premier educational institution',
            'contact' => [
                'phone' => '+91-XXXXXXXXXX',
                'email' => 'info@school.edu',
                'address' => 'School Address'
            ]
        ]);
    })->middleware(['throttle:60,1']);
    
    // Public notices and events
    Route::get('/notices', function () {
        return response()->json([
            'notices' => [],
            'events' => []
        ]);
    })->middleware(['throttle:60,1']);
});

// Mobile Application API Routes
Route::prefix('mobile')->middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])->group(function () {
    
    // Student Mobile App Routes
    Route::prefix('student')->middleware(['module:student'])->group(function () {
        Route::get('/profile', [ModuleStudentController::class, 'mobileProfile']);
        Route::put('/profile', [ModuleStudentController::class, 'updateMobileProfile']);
        Route::get('/attendance', [ModuleAttendanceController::class, 'studentAttendance']);
        Route::get('/results', [ModuleStudentController::class, 'mobileResults']);
        Route::get('/fees', [ModuleStudentController::class, 'mobileFees']);
        Route::get('/timetable', [ModuleStudentController::class, 'mobileTimetable']);
        Route::get('/assignments', [ModuleStudentController::class, 'mobileAssignments']);
        Route::get('/notifications', [ModuleStudentController::class, 'mobileNotifications']);
        Route::post('/notifications/{id}/read', [ModuleStudentController::class, 'markNotificationRead']);
    });
    
    // Teacher Mobile App Routes
    Route::prefix('teacher')->middleware(['module:teacher'])->group(function () {
        Route::get('/profile', [ModuleTeacherController::class, 'mobileProfile']);
        Route::put('/profile', [ModuleTeacherController::class, 'updateMobileProfile']);
        Route::get('/classes', [ModuleTeacherController::class, 'mobileClasses']);
        Route::get('/attendance/mark', [ModuleAttendanceController::class, 'mobileMarkAttendance']);
        Route::post('/attendance/mark', [ModuleAttendanceController::class, 'storeMobileAttendance']);
        Route::get('/timetable', [ModuleTeacherController::class, 'mobileTimetable']);
        Route::get('/students/{class}', [ModuleTeacherController::class, 'mobileClassStudents']);
        Route::get('/assignments', [ModuleTeacherController::class, 'mobileAssignments']);
        Route::post('/assignments', [ModuleTeacherController::class, 'createMobileAssignment']);
        Route::get('/notifications', [ModuleTeacherController::class, 'mobileNotifications']);
    });
    
    // Parent Mobile App Routes
    Route::prefix('parent')->middleware(['module:student'])->group(function () {
        Route::get('/children', [ModuleStudentController::class, 'parentChildren']);
        Route::get('/child/{id}/attendance', [ModuleAttendanceController::class, 'childAttendance']);
        Route::get('/child/{id}/results', [ModuleStudentController::class, 'childResults']);
        Route::get('/child/{id}/fees', [ModuleStudentController::class, 'childFees']);
        Route::get('/child/{id}/timetable', [ModuleStudentController::class, 'childTimetable']);
        Route::get('/notifications', [ModuleStudentController::class, 'parentNotifications']);
        Route::post('/meetings/request', [ModuleStudentController::class, 'requestMeeting']);
    });
});

// Protected API Routes (Sanctum Authentication)
Route::middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])->group(function () {

    // Authentication Management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    
    // Module-based API Routes
    
    // Student Module API
    Route::prefix('students')->middleware(['module:student'])->group(function () {
        Route::get('/', [ModuleStudentController::class, 'apiIndex']);
        Route::post('/', [ModuleStudentController::class, 'apiStore']);
        Route::get('/{student}', [ModuleStudentController::class, 'apiShow']);
        Route::put('/{student}', [ModuleStudentController::class, 'apiUpdate']);
        Route::delete('/{student}', [ModuleStudentController::class, 'apiDestroy']);
        
        // Student-specific API endpoints
        Route::get('/{student}/attendance', [ModuleStudentController::class, 'apiAttendance']);
        Route::get('/{student}/results', [ModuleStudentController::class, 'apiResults']);
        Route::get('/{student}/fees', [ModuleStudentController::class, 'apiFees']);
        Route::get('/{student}/documents', [ModuleStudentController::class, 'apiDocuments']);
        Route::post('/{student}/documents', [ModuleStudentController::class, 'apiUploadDocument']);
        Route::get('/{student}/timeline', [ModuleStudentController::class, 'apiTimeline']);
        
        // Bulk operations
        Route::post('/bulk/import', [ModuleStudentController::class, 'apiBulkImport']);
        Route::post('/bulk/export', [ModuleStudentController::class, 'apiBulkExport']);
        Route::post('/bulk/update', [ModuleStudentController::class, 'apiBulkUpdate']);
        Route::post('/bulk/delete', [ModuleStudentController::class, 'apiBulkDelete']);
        
        // Search and filters
        Route::get('/search/advanced', [ModuleStudentController::class, 'apiAdvancedSearch']);
        Route::get('/filters/options', [ModuleStudentController::class, 'apiFilterOptions']);
    });
    
    // Teacher Module API
    Route::prefix('teachers')->middleware(['module:teacher'])->group(function () {
        Route::get('/', [ModuleTeacherController::class, 'apiIndex']);
        Route::post('/', [ModuleTeacherController::class, 'apiStore']);
        Route::get('/{teacher}', [ModuleTeacherController::class, 'apiShow']);
        Route::put('/{teacher}', [ModuleTeacherController::class, 'apiUpdate']);
        Route::delete('/{teacher}', [ModuleTeacherController::class, 'apiDestroy']);
        
        // Teacher-specific API endpoints
        Route::get('/{teacher}/classes', [ModuleTeacherController::class, 'apiClasses']);
        Route::get('/{teacher}/timetable', [ModuleTeacherController::class, 'apiTimetable']);
        Route::get('/{teacher}/attendance', [ModuleTeacherController::class, 'apiAttendance']);
        Route::get('/{teacher}/performance', [ModuleTeacherController::class, 'apiPerformance']);
        Route::get('/{teacher}/documents', [ModuleTeacherController::class, 'apiDocuments']);
        Route::post('/{teacher}/documents', [ModuleTeacherController::class, 'apiUploadDocument']);
        
        // Class assignments
        Route::post('/{teacher}/assign-class', [ModuleTeacherController::class, 'apiAssignClass']);
        Route::delete('/{teacher}/unassign-class/{class}', [ModuleTeacherController::class, 'apiUnassignClass']);
        
        // Bulk operations
        Route::post('/bulk/import', [ModuleTeacherController::class, 'apiBulkImport']);
        Route::post('/bulk/export', [ModuleTeacherController::class, 'apiBulkExport']);
    });
    
    // Attendance Module API
    Route::prefix('attendance')->middleware(['module:attendance'])->group(function () {
        Route::get('/', [ModuleAttendanceController::class, 'apiIndex']);
        Route::post('/mark', [ModuleAttendanceController::class, 'apiMark']);
        Route::put('/{attendance}', [ModuleAttendanceController::class, 'apiUpdate']);
        Route::delete('/{attendance}', [ModuleAttendanceController::class, 'apiDestroy']);
        
        // Attendance reports and analytics
        Route::get('/reports/daily', [ModuleAttendanceController::class, 'apiDailyReport']);
        Route::get('/reports/monthly', [ModuleAttendanceController::class, 'apiMonthlyReport']);
        Route::get('/reports/class/{class}', [ModuleAttendanceController::class, 'apiClassReport']);
        Route::get('/reports/student/{student}', [ModuleAttendanceController::class, 'apiStudentReport']);
        
        // Quick actions
        Route::post('/quick-mark', [ModuleAttendanceController::class, 'apiQuickMark']);
        Route::post('/bulk-mark', [ModuleAttendanceController::class, 'apiBulkMark']);
        Route::get('/statistics', [ModuleAttendanceController::class, 'apiStatistics']);
        Route::get('/trends', [ModuleAttendanceController::class, 'apiTrends']);
    });
    
    // File Management API
    Route::prefix('files')->middleware(['throttle:30,1'])->group(function () {
        Route::post('/upload', function (Request $request) {
            // File upload logic
            return response()->json(['message' => 'File uploaded successfully']);
        });
        
        Route::get('/download/{type}/{id}', function ($type, $id) {
            // File download logic
            return response()->json(['download_url' => "files/{$type}/{$id}"]);
        });
    });
    
    // Reports API
    Route::prefix('reports')->middleware(['throttle:20,1'])->group(function () {
        Route::get('/attendance', [ReportsController::class, 'attendanceReports']);
        Route::get('/academic', [ReportsController::class, 'academicReports']);
        Route::get('/financial', [ReportsController::class, 'financialReports']);
        Route::get('/performance', [ReportsController::class, 'performanceReports']);
        Route::get('/administrative', [ReportsController::class, 'administrativeReports']);

        // Export endpoint used by the reports view: /api/reports/export?type=...&format=...
        Route::get('/export', [ReportsController::class, 'exportReport']);
    });
    
    // Dashboard API
    Route::prefix('dashboard')->middleware(['throttle:60,1'])->group(function () {
        Route::get('/stats', function () {
            return response()->json([
                'total_students' => 0,
                'total_teachers' => 0,
                'attendance_today' => 0,
                'pending_fees' => 0
            ]);
        });
        
        Route::get('/recent-activities', function () {
            return response()->json(['activities' => []]);
        });
        
        Route::get('/notifications', function () {
            return response()->json(['notifications' => []]);
        });
    });
    
    // System Administration API (Admin only)
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'apiIndex']);
        Route::post('/users', [UserController::class, 'apiStore']);
        Route::put('/users/{user}', [UserController::class, 'apiUpdate']);
        Route::delete('/users/{user}', [UserController::class, 'apiDestroy']);
        
        Route::get('/system/health', function () {
            return response()->json([
                'status' => 'healthy',
                'database' => 'connected',
                'cache' => 'active',
                'storage' => 'available'
            ]);
        });
        
        Route::get('/system/logs', function () {
            return response()->json(['logs' => []]);
        });
        
        Route::post('/system/backup', function () {
            return response()->json(['message' => 'Backup initiated']);
        });
    });
});

// Webhook Routes (No authentication, but with security middleware)
Route::prefix('webhooks')->middleware(['security', 'audit', 'throttle:30,1'])->group(function () {
    Route::post('/payment/success', function (Request $request) {
        // Payment success webhook
        return response()->json(['status' => 'received']);
    });
    
    Route::post('/payment/failure', function (Request $request) {
        // Payment failure webhook
        return response()->json(['status' => 'received']);
    });
    
    Route::post('/sms/delivery', function (Request $request) {
        // SMS delivery status webhook
        return response()->json(['status' => 'received']);
    });
    
    Route::post('/biometric/sync', [BiometricController::class, 'webhookSync']);
});

// Legacy API Routes (for backward compatibility)
Route::prefix('legacy')->middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])->group(function () {
    // Student Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('students', StudentController::class)->names([
            'index' => 'api.legacy.students.index',
            'store' => 'api.legacy.students.store',
            'show' => 'api.legacy.students.show',
            'update' => 'api.legacy.students.update',
            'destroy' => 'api.legacy.students.destroy'
        ]);
        Route::post('students/{student}/verify', [StudentController::class, 'verify']);
    });

    // Teacher Management (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('teachers', TeacherController::class)->names([
            'index' => 'api.legacy.teachers.index',
            'store' => 'api.legacy.teachers.store',
            'show' => 'api.legacy.teachers.show',
            'update' => 'api.legacy.teachers.update',
            'destroy' => 'api.legacy.teachers.destroy'
        ]);
    });

    // Class Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('classes', ClassModelController::class)->names([
            'index' => 'api.legacy.classes.index',
            'store' => 'api.legacy.classes.store',
            'show' => 'api.legacy.classes.show',
            'update' => 'api.legacy.classes.update',
            'destroy' => 'api.legacy.classes.destroy'
        ]);
    });

    // Attendance API with comprehensive security middleware
    Route::prefix('attendances')->name('attendances.')
        ->middleware(['attendance.security', 'role:admin,teacher,principal,class_teacher'])
        ->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])
                ->middleware('permission:view_attendance');
            Route::post('/', [AttendanceController::class, 'store'])
                ->middleware('permission:mark_attendance');
            Route::get('/{attendance}', [AttendanceController::class, 'show'])
                ->middleware('permission:view_attendance');
            Route::put('/{attendance}', [AttendanceController::class, 'update'])
                ->middleware(['role:admin,teacher,principal,class_teacher', 'permission:edit_attendance']);
            Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])
                ->middleware(['role:admin,principal', 'permission:delete_attendance']);
            Route::post('/bulk-update', [AttendanceController::class, 'bulkUpdate'])
                ->middleware('permission:mark_attendance');
        });

    // Fee Management (Admin/Principal only)
    Route::middleware(['role:admin,principal'])->group(function () {
        Route::apiResource('fees', FeeController::class)->names([
            'index' => 'api.legacy.fees.index',
            'store' => 'api.legacy.fees.store',
            'show' => 'api.legacy.fees.show',
            'update' => 'api.legacy.fees.update',
            'destroy' => 'api.legacy.fees.destroy'
        ]);
        Route::post('fees/{id}/pay', [FeePaymentController::class, 'pay']);
        Route::get('fees/{id}/receipt', [FeePaymentController::class, 'receipt']);
    });

    // Exam Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('exams', ExamController::class)->names([
            'index' => 'api.legacy.exams.index',
            'store' => 'api.legacy.exams.store',
            'show' => 'api.legacy.exams.show',
            'update' => 'api.legacy.exams.update',
            'destroy' => 'api.legacy.exams.destroy'
        ]);
    });

    // Results Management (Role-based access)
    Route::middleware(['role:admin,principal,teacher'])->group(function () {
        Route::apiResource('results', ResultController::class)->names([
            'index' => 'api.legacy.results.index',
            'store' => 'api.legacy.results.store',
            'show' => 'api.legacy.results.show',
            'update' => 'api.legacy.results.update',
            'destroy' => 'api.legacy.results.destroy'
        ]);
    });

    // External Integrations
    Route::prefix('external')->name('external.')
        ->middleware('external.integration')
        ->group(function () {
            // Aadhaar Verification
            Route::post('aadhaar/verify', [ExternalIntegrationController::class, 'verifyAadhaar'])
                ->middleware(['throttle:10,1', 'role:admin,principal,teacher']);
            Route::post('aadhaar/bulk-verify', [ExternalIntegrationController::class, 'bulkVerifyAadhaar'])
                ->middleware(['throttle:5,1', 'role:admin,principal']);
            Route::get('aadhaar/service-status', [ExternalIntegrationController::class, 'getAadhaarServiceStatus'])
                ->middleware(['throttle:10,1', 'role:admin,principal,teacher']);

            // Biometric Device Integration
            Route::post('biometric/import', [ExternalIntegrationController::class, 'importBiometricData'])
                ->middleware(['throttle:3,1', 'role:admin,principal,teacher']);
            Route::get('biometric/import-status/{importId}', [ExternalIntegrationController::class, 'getImportStatus'])
                ->middleware('role:admin,principal,teacher');

            // Real-time Biometric Device Integration
            Route::prefix('biometric')->group(function () {
                Route::post('import-data', [BiometricController::class, 'importData'])
                    ->middleware(['throttle:10,1', 'role:admin,principal,teacher']);
                Route::post('stream', [BiometricController::class, 'handleDeviceStream'])
                    ->middleware(['throttle:100,1', 'role:admin,principal,teacher']);
                Route::post('bulk-sync/{deviceId}', [BiometricController::class, 'bulkSyncFromDevice'])
                    ->middleware(['throttle:5,1', 'role:admin,principal,teacher']);
                Route::get('device-status/{deviceId}', [BiometricController::class, 'getDeviceStatus']);
            });
        });

    // Browser Notifications
    Route::post('notifications/send', [ExternalIntegrationController::class, 'sendBrowserNotification'])
        ->middleware(['rate.limit:20,1', 'role:admin,principal,teacher']);
    Route::post('notifications/subscribe', [ExternalIntegrationController::class, 'subscribeUser'])
        ->middleware('rate.limit:10,1');
    Route::get('notifications/vapid-key', [ExternalIntegrationController::class, 'getVapidPublicKey'])
        ->middleware('cache.response:static');

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

// Super Admin API Routes (Sanctum + strict role)
// Note: The global 'api' middleware group already applies 'throttle:api';
// we avoid duplicating throttle here to prevent double counting.
Route::prefix('super-admin')->middleware(['auth:sanctum', 'security', 'audit', 'session.security', 'role:super_admin'])->group(function () {
    Route::get('/users', [SuperAdminApiController::class, 'users']);
    Route::post('/users', [SuperAdminApiController::class, 'createUser']);
    Route::get('/schools', [SuperAdminApiController::class, 'schools']);
    Route::get('/reports/basic', [SuperAdminApiController::class, 'reportsBasic']);
    Route::get('/system/settings', [SuperAdminApiController::class, 'systemSettings']);
});



// Exam Paper Management (protected)
Route::middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])->prefix('exam-papers')->group(function () {
    // Admin-only template upload
    Route::post('templates/upload', [ExamPaperManagementController::class, 'uploadTemplate'])
        ->middleware(['role:admin']);

    // Teacher submission and versioning
    Route::post('{paper}/submit', [ExamPaperManagementController::class, 'submitPaper'])
        ->middleware(['role:teacher,admin']);
    Route::post('{paper}/versions', [ExamPaperManagementController::class, 'createVersion'])
        ->middleware(['role:teacher,admin']);
    Route::get('{paper}/versions', [ExamPaperManagementController::class, 'listVersions'])
        ->middleware(['role:teacher,admin']);

    // Admin approvals
    Route::post('submissions/{submission}/approve', [ExamPaperManagementController::class, 'approveSubmission'])
        ->middleware(['role:admin']);

    // Secure downloads
    Route::get('{paper}/download', [ExamPaperManagementController::class, 'downloadPaper'])
        ->middleware(['role:teacher,admin']);
    Route::post('bulk-download', [ExamPaperManagementController::class, 'bulkDownload'])
        ->middleware(['role:admin']);
});

// Daily Syllabus Management System (protected)
Route::middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])
    ->prefix('daily-syllabus')
    ->group(function () {
        // Teacher: create daily syllabus + materials
        Route::post('upload', [DailySyllabusManagementController::class, 'teacherUploadDailyWork'])
            ->middleware(['role:teacher']);

        // Student: list syllabi for own class
        Route::get('student/list', [DailySyllabusManagementController::class, 'listDailyForStudent'])
            ->middleware(['role:student']);

        // Secure download for materials
        Route::get('materials/{id}/download', [DailySyllabusManagementController::class, 'downloadMaterial'])
            ->middleware(['role:student,teacher,admin,principal']);

        // Progress updates and summary
        Route::post('progress/update', [DailySyllabusManagementController::class, 'updateSyllabusProgress'])
            ->middleware(['role:teacher,admin,principal']);
        Route::get('progress/summary', [DailySyllabusManagementController::class, 'progressSummary'])
            ->middleware(['role:teacher,admin,principal']);

        // Comments on materials
        Route::post('materials/{id}/comments', [DailySyllabusManagementController::class, 'addComment'])
            ->middleware(['role:student,teacher,admin,principal']);
        Route::get('materials/{id}/comments', [DailySyllabusManagementController::class, 'listComments'])
            ->middleware(['role:student,teacher,admin,principal']);
    });



Route::middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])
    ->prefix('class-data')
    ->group(function () {
        Route::get('/', [ClassTeacherDataController::class, 'index'])
            ->middleware(['role:teacher,admin,principal']);
        Route::post('/', [ClassTeacherDataController::class, 'store'])
            ->middleware(['role:teacher']);
        Route::get('/{id}', [ClassTeacherDataController::class, 'show'])
            ->middleware(['role:teacher,admin,principal']);
        Route::put('/{id}', [ClassTeacherDataController::class, 'update'])
            ->middleware(['role:teacher']);

        // Audit trail and version history
        Route::get('/{id}/audit', [ClassTeacherDataController::class, 'auditTrail'])
            ->middleware(['role:teacher,admin,principal']);
        Route::get('/{id}/history', [ClassTeacherDataController::class, 'history'])
            ->middleware(['role:teacher,admin,principal']);

        // Approval workflow (admin/principal only)
        Route::post('/{id}/approve', [ClassTeacherDataController::class, 'approve'])
            ->middleware(['role:admin,principal']);
        Route::post('/{id}/reject', [ClassTeacherDataController::class, 'reject'])
            ->middleware(['role:admin,principal']);
    });
Route::middleware(['auth:sanctum', 'security', 'audit'])->prefix('sr-register')->group(function () {
    Route::get('/', [SRRegisterApiController::class, 'index']);
    Route::get('/search', [SRRegisterApiController::class, 'search']);

    Route::get('/student/{studentId}', [SRRegisterApiController::class, 'studentProfile']);

    Route::get('/student/{studentId}/histories', [SRRegisterApiController::class, 'histories']);
    Route::post('/student/{studentId}/histories', [SRRegisterApiController::class, 'storeHistory']);

    Route::get('/student/{studentId}/promotions', [SRRegisterApiController::class, 'promotions']);
    Route::post('/student/{studentId}/promotions', [SRRegisterApiController::class, 'recordPromotion']);

    Route::get('/student/{studentId}/transfers', [SRRegisterApiController::class, 'transfers']);
    Route::post('/student/{studentId}/transfers', [SRRegisterApiController::class, 'issueTC']);

    Route::post('/stats', [SRRegisterApiController::class, 'stats']);
});
Route::middleware(['auth:sanctum', 'security', 'audit', 'throttle:api'])
    ->prefix('alumni')
    ->group(function () {
        Route::get('/', [AlumniApiController::class, 'index']);
        Route::post('/', [AlumniApiController::class, 'store']);
        Route::get('/{id}', [AlumniApiController::class, 'show']);
        Route::put('/{id}', [AlumniApiController::class, 'update']);
        Route::delete('/{id}', [AlumniApiController::class, 'destroy']);

        Route::get('/batches', [AlumniApiController::class, 'batches']);
        Route::post('/batches', [AlumniApiController::class, 'storeBatch']);

        Route::get('/{alumniId}/achievements', [AlumniApiController::class, 'achievements']);
        Route::post('/{alumniId}/achievements', [AlumniApiController::class, 'storeAchievement']);

        Route::get('/{alumniId}/contributions', [AlumniApiController::class, 'contributions']);
        Route::post('/{alumniId}/contributions', [AlumniApiController::class, 'storeContribution']);

        Route::get('/events', [AlumniApiController::class, 'events']);
        Route::post('/events', [AlumniApiController::class, 'storeEvent']);
        Route::get('/events/{eventId}', [AlumniApiController::class, 'showEvent']);
        Route::post('/events/{eventId}/register', [AlumniApiController::class, 'registerEvent']);
        Route::post('/events/{eventId}/checkin', [AlumniApiController::class, 'checkin']);
    });

