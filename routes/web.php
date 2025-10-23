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
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\StudentFeeController;
use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BellTimingController;
// ReportsController routes are defined in routes/auth.php with role-based middleware
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClassDataAuditController;
use App\Http\Controllers\TeacherDocumentController;
use App\Http\Controllers\TeacherExperienceController;
use App\Http\Controllers\AdmitCardController;

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

    // Teacher Portfolio & Experience Tracking
    Route::prefix('teachers/{teacher}')->name('teachers.')->group(function () {
        Route::get('/portfolio', [TeacherExperienceController::class, 'portfolio'])->name('portfolio');

        // Experience summary
        Route::post('/portfolio/experience', [TeacherExperienceController::class, 'storeExperience'])->name('portfolio.experience.store');

        // Employment history
        Route::post('/portfolio/employment', [TeacherExperienceController::class, 'storeEmployment'])->name('portfolio.employment.store');
        Route::put('/portfolio/employment/{id}', [TeacherExperienceController::class, 'updateEmployment'])->name('portfolio.employment.update');
        Route::delete('/portfolio/employment/{id}', [TeacherExperienceController::class, 'deleteEmployment'])->name('portfolio.employment.delete');

        // Certifications
        Route::post('/portfolio/certification', [TeacherExperienceController::class, 'storeCertification'])->name('portfolio.certification.store');
        Route::put('/portfolio/certification/{id}', [TeacherExperienceController::class, 'updateCertification'])->name('portfolio.certification.update');
        Route::delete('/portfolio/certification/{id}', [TeacherExperienceController::class, 'deleteCertification'])->name('portfolio.certification.delete');

        // Skills
        Route::post('/portfolio/skill', [TeacherExperienceController::class, 'attachSkill'])->name('portfolio.skill.attach');
        Route::delete('/portfolio/skill/{skill}', [TeacherExperienceController::class, 'detachSkill'])->name('portfolio.skill.detach');

        // Performance Reviews
        Route::post('/portfolio/performance-review', [TeacherExperienceController::class, 'storePerformanceReview'])->name('portfolio.performance-review.store');
        Route::put('/portfolio/performance-review/{id}', [TeacherExperienceController::class, 'updatePerformanceReview'])->name('portfolio.performance-review.update');
        Route::delete('/portfolio/performance-review/{id}', [TeacherExperienceController::class, 'deletePerformanceReview'])->name('portfolio.performance-review.delete');
    });
    
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
    
    // Admit Cards
    Route::prefix('admit-cards')->name('admit-cards.')->group(function () {
        Route::get('/', [AdmitCardController::class, 'index'])->name('index');
        Route::post('/generate', [AdmitCardController::class, 'generate'])->name('generate');
        Route::post('/bulk-generate', [AdmitCardController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::get('/download-pdf', [AdmitCardController::class, 'downloadPdf'])->name('download-pdf');
        Route::get('/download-single/{examId}/{studentId}', [AdmitCardController::class, 'downloadSingle'])->name('download-single');
        Route::get('/preview', [AdmitCardController::class, 'preview'])->name('preview');
        Route::get('/exam-students/{examId}', [AdmitCardController::class, 'getExamStudents'])->name('exam-students');
    });
    
    // Grades
    // Removed invalid GradeController routes; grades are handled via reports and results APIs.
    
    // Fees
    Route::resource('fees', FeeController::class);
    Route::get('/fees/student/{student}', [FeeController::class, 'studentFees'])->name('fees.student');
    Route::post('/fees/{fee}/pay', [FeeController::class, 'pay'])->name('fees.pay');

    // Fee Structures (Super Admin/Admin)
    Route::middleware(['auth', 'role:super_admin,admin'])->group(function () {
        Route::resource('fee-structures', FeeStructureController::class);
    });

    // Student Fees listing and details (Auth required)
    Route::middleware(['auth'])->group(function () {
        Route::get('/student-fees', [StudentFeeController::class, 'index'])->name('student-fees.index');
        Route::get('/student-fees/{studentFee}', [StudentFeeController::class, 'show'])->name('student-fees.show');
    });

    // Assign fees to student (Admin/Super Admin/Principal)
    Route::middleware(['auth', 'role:super_admin,admin,principal'])->group(function () {
        Route::get('/students/{student}/fees/assign', [StudentFeeController::class, 'assign'])->name('student-fees.assign');
        Route::post('/students/{student}/fees/assign', [StudentFeeController::class, 'storeAssignment'])->name('student-fees.storeAssignment');
    });

    // Payments and Receipts
    Route::middleware(['auth'])->group(function () {
        Route::get('/fees/payment/{studentFee}/checkout', [FeePaymentController::class, 'initiate'])->name('fees.payment.checkout');
        Route::post('/fees/payment/callback', [FeePaymentController::class, 'callback'])->name('fees.payment.callback');
        Route::get('/fees/receipt/{receipt}', [FeePaymentController::class, 'receipt'])->name('fees.receipt');
    });

    // Payment Gateway Settings (Super Admin/Admin)
    Route::middleware(['auth', 'role:super_admin,admin'])->group(function () {
        Route::get('/finance/payment/settings', [PaymentGatewayController::class, 'index'])->name('payment.settings');
        Route::post('/finance/payment/settings', [PaymentGatewayController::class, 'store'])->name('payment.settings.store');
    });
    
    // Notifications
     Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
     Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
 
     // Bell Schedule dashboard + endpoints
     Route::prefix('bell-schedule')->group(function () {
         Route::get('/dashboard', [BellTimingController::class, 'dashboard'])->name('bell-schedule.dashboard');
         Route::get('/check-notification', [BellTimingController::class, 'checkBellNotification'])->name('bell-schedule.check-notification');
         Route::post('/ring-now', [BellTimingController::class, 'ringNow'])->name('bell-schedule.ring-now');
     });
     
     // Reports routes are provided in routes/auth.php (role-aware).
     // Keeping web.php clean avoids duplicate bindings and undefined controllers.
     
     // Settings
     Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
     Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
     
     // Users
     Route::resource('users', UserController::class)->middleware('can:manage-users');

    // Class Teacher Permissions Management (restored from backup)
    Route::prefix('class-teacher-permissions')->name('class-teacher-permissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'store'])->name('store');
        Route::get('/{classTeacherPermission}', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'show'])->whereNumber('classTeacherPermission')->name('show');
        Route::get('/{classTeacherPermission}/edit', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'edit'])->whereNumber('classTeacherPermission')->name('edit');
        Route::put('/{classTeacherPermission}', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'update'])->whereNumber('classTeacherPermission')->name('update');
        Route::patch('/{classTeacherPermission}/revoke', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'revoke'])->whereNumber('classTeacherPermission')->name('revoke');

        // Audit Trail routes
        Route::get('/audit-trail', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'auditTrail'])->name('audit-trail');
        Route::patch('/audit-trail/{auditTrail}/approve', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'approveCorrection'])->name('audit-trail.approve');
        Route::patch('/audit-trail/{auditTrail}/reject', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'rejectCorrection'])->name('audit-trail.reject');
        Route::post('/audit-trail/bulk-approve', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'bulkApproveCorrections'])->name('audit-trail.bulk-approve');
        Route::get('/audit-trail/export', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'exportAuditReport'])->name('audit-trail.export');

        // Dashboard route
        Route::get('/dashboard', [\App\Http\Controllers\ClassTeacherPermissionController::class, 'dashboard'])->name('dashboard');
    });

    Route::get('/sr-register', function () {
        return view('layouts.app');
    })->name('sr-register.index');

    // Biometric Attendance routes
    Route::prefix('biometric-attendance')->name('biometric-attendance.')->middleware(['auth'])->group(function () {
        // Landing page
        Route::get('/', function () {
            return view('biometric-attendance.index');
        })->name('index');

        // Pages
        Route::get('/analytics', function () {
            return view('biometric-attendance.analytics');
        })->name('analytics');
        Route::get('/regularization', function () {
            return view('biometric-attendance.regularization');
        })->name('regularization');

        // Analytics AJAX endpoints
        Route::get('/analytics-dashboard', [\App\Http\Controllers\BiometricAttendanceController::class, 'getAnalyticsDashboard'])->name('analytics-dashboard');
        Route::post('/calculate-analytics', [\App\Http\Controllers\BiometricAttendanceController::class, 'calculateMonthlyAnalytics'])->name('calculate-analytics');
        Route::get('/detailed-report', [\App\Http\Controllers\BiometricAttendanceController::class, 'getDetailedReport'])->name('detailed-report');
        Route::get('/leave-pattern-analysis', [\App\Http\Controllers\BiometricAttendanceController::class, 'getLeavePatternAnalysis'])->name('leave-pattern-analysis');

        // Regularization AJAX endpoints
        Route::get('/regularization-requests', [\App\Http\Controllers\BiometricAttendanceController::class, 'getRegularizationRequests'])->name('regularization-requests.index');
        Route::post('/regularization-requests', [\App\Http\Controllers\BiometricAttendanceController::class, 'createRegularizationRequest'])->name('regularization-requests.store');
        Route::get('/regularization-requests/{id}', [\App\Http\Controllers\BiometricAttendanceController::class, 'getRegularizationRequests'])->name('regularization-requests.show');
        Route::post('/regularization-requests/{id}/process', [\App\Http\Controllers\BiometricAttendanceController::class, 'processRegularizationRequest'])->name('regularization-requests.process');

        // Additional endpoints used by views
        Route::get('/regularization-statistics', [\App\Http\Controllers\BiometricAttendanceController::class, 'regularizationStatistics'])->name('regularization-statistics');
        Route::get('/existing-attendance', [\App\Http\Controllers\BiometricAttendanceController::class, 'existingAttendance'])->name('existing-attendance');
        Route::post('/regularization-requests/bulk-approve', [\App\Http\Controllers\BiometricAttendanceController::class, 'bulkApprove'])->name('regularization-requests.bulk-approve');
        Route::get('/regularization-requests/export', [\App\Http\Controllers\BiometricAttendanceController::class, 'exportRegularization'])->name('regularization-requests.export');
        Route::get('/export-analytics', [\App\Http\Controllers\BiometricAttendanceController::class, 'exportAnalytics'])->name('export-analytics');
    });

    Route::get('/exam-papers', function () {
        return view('layouts.app');
    })->name('exam-papers.index');

    // Replace placeholder teacher-documents route with full route group
    Route::prefix('teacher-documents')->name('teacher-documents.')->group(function () {
        // Teacher documents index/create/store
        Route::get('/', [TeacherDocumentController::class, 'index'])->name('index');
        Route::get('/create', [TeacherDocumentController::class, 'create'])->name('create');
        Route::post('/', [TeacherDocumentController::class, 'store'])->name('store');

        // Bulk upload
        Route::get('/bulk-upload', [TeacherDocumentController::class, 'bulkUpload'])->name('bulk-upload');
        Route::post('/bulk-upload', [TeacherDocumentController::class, 'storeBulkUpload'])->name('bulk-upload.store');

        // Admin management routes
        Route::middleware('role:admin,principal')->group(function () {
            Route::get('/admin', [TeacherDocumentController::class, 'adminIndex'])->name('admin.index');
            Route::post('/{document}/approve', [TeacherDocumentController::class, 'approve'])->name('approve');
            Route::post('/{document}/reject', [TeacherDocumentController::class, 'reject'])->name('reject');
            Route::post('/admin/bulk-approve', [TeacherDocumentController::class, 'bulkApprove'])->name('admin.bulk-approve');
            Route::post('/admin/bulk-action', [TeacherDocumentController::class, 'bulkAction'])->name('admin.bulk-action');
            Route::get('/admin/expiring/check', [TeacherDocumentController::class, 'checkExpiringDocuments'])->name('admin.expiring');

            // Expiry alerts dashboard and APIs
            Route::get('/expiry-alerts', [TeacherDocumentController::class, 'showExpiryAlerts'])->name('expiry-alerts');
            Route::get('/expiry-alerts/data', [TeacherDocumentController::class, 'getExpiryAlerts'])->name('expiry-alerts.data');
            Route::post('/expiry-alerts/process', [TeacherDocumentController::class, 'processExpiryAlerts'])->name('expiry-alerts.process');
            Route::get('/expiry-alerts/service-check', [TeacherDocumentController::class, 'checkExpiryAlertService'])->name('expiry-alerts.service-check');
            Route::get('/expiry-alerts/teacher/{id}', [TeacherDocumentController::class, 'getTeacherExpiryAlerts'])->name('expiry-alerts.teacher');
        });

        // Document show and download
        Route::get('/{document}', [TeacherDocumentController::class, 'show'])->name('show');
        Route::get('/{document}/download', [TeacherDocumentController::class, 'download'])->name('download');
    });

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

    // Payroll routes
    Route::prefix('payroll')->name('payroll.')->group(function () {
        // Dashboard
        Route::get('/', [\App\Http\Controllers\PayrollController::class, 'index'])->name('index');

        // Reports
        Route::get('/reports', [\App\Http\Controllers\PayrollController::class, 'reports'])->name('reports');
        Route::post('/reports/generate', [\App\Http\Controllers\PayrollController::class, 'generateReport'])->name('reports.generate');
        Route::post('/reports/export', [\App\Http\Controllers\PayrollController::class, 'exportReport'])->name('reports.export');

        // Salary Structures (view + JSON)
        Route::get('/salary-structures', [\App\Http\Controllers\PayrollController::class, 'salaryStructures'])->name('salary-structures');
        Route::get('/salary-structures/{salaryStructure}', [\App\Http\Controllers\PayrollController::class, 'getSalaryStructure'])->name('salary-structures.show');
        Route::post('/salary-structures', [\App\Http\Controllers\PayrollController::class, 'storeSalaryStructure'])->name('salary-structures.store');
        Route::put('/salary-structures/{salaryStructure}', [\App\Http\Controllers\PayrollController::class, 'updateSalaryStructure'])->name('salary-structures.update');
        Route::delete('/salary-structures/{salaryStructure}', [\App\Http\Controllers\PayrollController::class, 'destroySalaryStructure'])->name('salary-structures.destroy');

        // Deductions (view + JSON)
        Route::get('/deductions', [\App\Http\Controllers\PayrollController::class, 'deductions'])->name('deductions');
        Route::get('/deductions/{deduction}', [\App\Http\Controllers\PayrollController::class, 'getDeduction'])->name('deductions.show');
        Route::post('/deductions', [\App\Http\Controllers\PayrollController::class, 'storeDeduction'])->name('deductions.store');
        Route::put('/deductions/{deduction}', [\App\Http\Controllers\PayrollController::class, 'updateDeduction'])->name('deductions.update');
        Route::delete('/deductions/{deduction}', [\App\Http\Controllers\PayrollController::class, 'destroyDeduction'])->name('deductions.destroy');
        Route::patch('/deductions/{deduction}/approve', [\App\Http\Controllers\PayrollController::class, 'approveDeduction'])->name('deductions.approve');

        // API endpoints used by views
        Route::get('/api/statistics', [\App\Http\Controllers\PayrollController::class, 'getSalaryStructureStats'])->name('api.statistics');
        Route::get('/api/employees', [\App\Http\Controllers\PayrollController::class, 'getEmployees'])->name('api.employees');
    });

    // Salary Payslip routes
    Route::prefix('salary')->name('salary.')->group(function() {
        // Payslip routes
        Route::get('/payslip', [\App\Http\Controllers\PayslipController::class, 'index'])->name('payslip.index');
        Route::post('/payslip/generate', [\App\Http\Controllers\PayslipController::class, 'generate'])->name('payslip.generate');
        Route::get('/payslip/download', [\App\Http\Controllers\PayslipController::class, 'download'])->name('payslip.download');
        Route::post('/payslip/email', [\App\Http\Controllers\PayslipController::class, 'email'])->name('payslip.email');
    
        // Employee info (match view: query param employee_id)
        Route::get('/employee/info', [\App\Http\Controllers\PayslipController::class, 'employeeInfo'])->name('employee.info');
    
        // Bulk operations
        Route::post('/payslip/bulk-generate', [\App\Http\Controllers\PayslipController::class, 'bulkGenerate'])->name('payslip.bulk-generate');
        Route::get('/payslip/bulk-progress', [\App\Http\Controllers\PayslipController::class, 'bulkProgress'])->name('payslip.bulk-progress');
    
        // Recent payslip actions (match view: id via query/body)
        Route::get('/payslip/view', [\App\Http\Controllers\PayslipController::class, 'view'])->name('payslip.view');
        Route::get('/payslip/download-by-id', [\App\Http\Controllers\PayslipController::class, 'downloadById'])->name('payslip.download-by-id');
        Route::post('/payslip/email-by-id', [\App\Http\Controllers\PayslipController::class, 'emailById'])->name('payslip.email-by-id');
        Route::delete('/payslip/delete', [\App\Http\Controllers\PayslipController::class, 'delete'])->name('payslip.delete');
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


