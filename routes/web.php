<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\NewAuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Api\PerformanceApiController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\NotificationController;
// ReportsController routes are defined in routes/auth.php with role-based middleware
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClassDataAuditController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [NewAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [NewAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [NewAuthController::class, 'logout'])->name('logout');

// Email verification routes
Route::get('/email/verify', [EmailVerificationController::class, 'show'])
    ->middleware(['auth'])
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware(['throttle:6,1'])
    ->name('verification.send');

// Named dashboard route bound to controller (available for views)
Route::get('/dashboard', [NewAuthController::class, 'redirectToDashboard'])
    ->middleware(['auth'])
    ->name('dashboard');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard routes are defined in routes/auth.php with role-based middleware.
    // Keep web.php focused on feature modules and avoid duplicate route bindings.

    
    // Students
    Route::resource('students', StudentController::class);
    Route::get('/students/{student}/profile', [StudentController::class, 'profile'])->name('students.profile');
    
    // Teachers
    Route::resource('teachers', TeacherController::class);
    Route::get('/teachers/{teacher}/profile', [TeacherController::class, 'profile'])->name('teachers.profile');
    
    // Classes
    Route::resource('classes', ClassController::class);
    Route::get('/classes/{class}/students', [ClassController::class, 'students'])->name('classes.students');
    
    // Subjects
    Route::resource('subjects', SubjectController::class);
    
    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{date}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/attendance/student/{student}', [AttendanceController::class, 'studentAttendance'])->name('attendance.student');
    
    // Exams
    Route::resource('exams', ExamController::class);
    Route::get('/exams/{exam}/results', [ExamController::class, 'results'])->name('exams.results');
    
    // Grades
    // Removed invalid GradeController routes; grades are handled via reports and results APIs.
    
    // Fees
    Route::resource('fees', FeeController::class);
    Route::get('/fees/student/{student}', [FeeController::class, 'studentFees'])->name('fees.student');
    Route::post('/fees/{fee}/pay', [FeeController::class, 'pay'])->name('fees.pay');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    
    // Reports routes are provided in routes/auth.php (role-aware).
    // Keeping web.php clean avoids duplicate bindings and undefined controllers.
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // Users
    Route::resource('users', UserController::class)->middleware('can:manage-users');

    // Sidebar feature routes (placeholders to satisfy layout links)
    Route::get('/class-teacher-permissions', function () {
        return view('layouts.app');
    })->name('class-teacher-permissions.index');

    Route::get('/sr-register', function () {
        return view('layouts.app');
    })->name('sr-register.index');

    Route::get('/biometric-attendance', function () {
        return view('layouts.app');
    })->name('biometric-attendance.index');

    Route::get('/exam-papers', function () {
        return view('layouts.app');
    })->name('exam-papers.index');

    Route::get('/teacher-documents', function () {
        return view('layouts.app');
    })->middleware('can:teacher-access')->name('teacher-documents.index');

    // Class Data Audit routes
    Route::prefix('class-data-audit')->name('class-data-audit.')->group(function () {
        // Dashboard index
        Route::get('/', [ClassDataAuditController::class, 'index'])
            ->middleware('permission:view-class-audit')
            ->name('index');

        // Detailed audit view
        Route::get('/{audit}', [ClassDataAuditController::class, 'show'])
            ->middleware('permission:view-class-audit')
            ->name('show');

        // Analytics dashboard
        Route::get('/analytics', [ClassDataAuditController::class, 'analytics'])
            ->middleware('permission:view_audit_statistics')
            ->name('analytics');

        // Export operations
        Route::post('/export', [ClassDataAuditController::class, 'export'])
            ->middleware('permission:export_audit_reports')
            ->name('export');
        Route::get('/download-export/{token}', [ClassDataAuditController::class, 'downloadExport'])
            ->middleware('permission:export_audit_reports')
            ->name('download-export');

        // Approval actions
        Route::post('/{audit}/approve', [ClassDataAuditController::class, 'approve'])
            ->middleware('permission:approve_audit_changes')
            ->name('approve');
        Route::post('/reject', [ClassDataAuditController::class, 'reject'])
            ->middleware('permission:approve_audit_changes')
            ->name('reject');
        Route::post('/delegate', [ClassDataAuditController::class, 'delegate'])
            ->middleware('permission:delegate_audit_approvals')
            ->name('delegate');
        Route::post('/bulk-approve', [ClassDataAuditController::class, 'bulkApprove'])
            ->middleware('permission:bulk_approve_audits')
            ->name('bulk-approve');
        Route::post('/bulk-action', [ClassDataAuditController::class, 'bulkAction'])
            ->middleware('permission:manage-class-audit')
            ->name('bulk-action');

        // Rollback operations
        Route::post('/{audit}/rollback', [ClassDataAuditController::class, 'rollback'])
            ->middleware('permission:manage-class-audit')
            ->name('rollback');
        Route::get('/{audit}/versions', [ClassDataAuditController::class, 'versionHistory'])
            ->middleware('permission:view-class-audit')
            ->name('versions');
        Route::post('/{audit}/compare-versions', [ClassDataAuditController::class, 'compareVersions'])
            ->middleware('permission:view-class-audit')
            ->name('compare-versions');
        Route::post('/{audit}/rollback-to-version', [ClassDataAuditController::class, 'rollbackToVersion'])
            ->middleware('permission:manage-class-audit')
            ->name('rollback-to-version');

        // Approval status
        Route::get('/{audit}/approval-status', [ClassDataAuditController::class, 'approvalStatus'])
            ->middleware('permission:view-class-audit')
            ->name('approval-status');
    });
});

// API routes for AJAX requests
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/dashboard/stats', [PerformanceApiController::class, 'dashboardStats'])->name('api.dashboard.stats');
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/attendance/bulk', [AttendanceController::class, 'bulkStore']);
});

// Asset optimization routes
Route::get('/assets/{path}', function ($path) {
    $fullPath = public_path('build/assets/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    $mimeType = mime_content_type($fullPath);
    $lastModified = filemtime($fullPath);
    
    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        'ETag' => '"' . md5_file($fullPath) . '"',
    ]);
})->where('path', '.*');

// Test routes for error handling and validation
Route::prefix('test')->name('test.')->group(function () {
    Route::get('/error-pages', function () {
        return view('test.error-pages');
    })->name('error.pages');
    
    Route::get('/validation-demo', function () {
        return view('test.validation-demo');
    })->name('validation.demo');
    
    // Error page tests
    Route::get('/404', [App\Http\Controllers\TestController::class, 'test404'])->name('404');
    Route::get('/403', [App\Http\Controllers\TestController::class, 'test403'])->name('403');
    Route::get('/500', [App\Http\Controllers\TestController::class, 'test500'])->name('500');
    
    // Validation and security tests
    Route::get('/auth-error', [App\Http\Controllers\TestController::class, 'testAuthError'])->name('auth.error');
    Route::post('/validation-error', [App\Http\Controllers\TestController::class, 'testValidationError'])->name('validation.error');
    Route::get('/rate-limit', [App\Http\Controllers\TestController::class, 'testRateLimit'])->name('rate.limit');
    Route::post('/csrf', [App\Http\Controllers\TestController::class, 'testCsrf'])->name('csrf');
    Route::post('/sanitization', [App\Http\Controllers\TestController::class, 'testSanitization'])->name('sanitization');
    Route::post('/logging', [App\Http\Controllers\TestController::class, 'testLogging'])->name('logging');
    Route::get('/performance', [App\Http\Controllers\TestController::class, 'testPerformance'])->name('performance');
    
    // Form submission test
    Route::post('/validation-demo', [App\Http\Controllers\TestController::class, 'submitValidationDemo'])->name('validation.demo.submit');
});

// Fallback route for SPA-like behavior (if needed)
Route::fallback(function () {
    return view('layouts.app');
});


