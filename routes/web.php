<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassTeacherPermissionController;
use App\Http\Controllers\SRRegisterController;
use App\Http\Controllers\BiometricAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExamPaperController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\TeacherDocumentController;
use App\Http\Controllers\BellTimingController;
use App\Http\Controllers\BellNotificationController;
use App\Http\Controllers\SpecialScheduleController;
use App\Http\Controllers\SubstitutionController;
// Digital Learning Management Portal Controllers
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\LearningApiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ClassDataAuditController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\AssetDepreciationController;
use App\Http\Controllers\BudgetReportController;

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

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

// Teacher Substitution Platform Routes
Route::middleware(['auth'])->group(function () {
    // Substitution Dashboard
    Route::get('/substitutions', [SubstitutionController::class, 'index'])->name('substitutions.index');
    Route::get('/substitutions/dashboard', [SubstitutionController::class, 'index'])->name('substitutions.dashboard');
    
    // Substitution Management
    Route::get('/substitutions/list', [SubstitutionController::class, 'substitutions'])->name('substitutions.list');
    Route::post('/substitutions', [SubstitutionController::class, 'store'])->name('substitutions.store');
    Route::get('/substitutions/{id}', [SubstitutionController::class, 'show'])->name('substitutions.show');
    Route::put('/substitutions/{id}', [SubstitutionController::class, 'update'])->name('substitutions.update');
    Route::delete('/substitutions/{id}', [SubstitutionController::class, 'destroy'])->name('substitutions.destroy');
    
    // Teacher Assignment
    Route::get('/substitutions/available-teachers', [SubstitutionController::class, 'findAvailableTeachers'])->name('substitutions.available-teachers');
    Route::post('/substitutions/{id}/assign', [SubstitutionController::class, 'assignSubstitute'])->name('substitutions.assign');
    Route::post('/substitutions/{id}/auto-assign', [SubstitutionController::class, 'autoAssignSubstitute'])->name('substitutions.auto-assign');
    
    // Substitution Actions
    Route::post('/substitutions/{id}/confirm', [SubstitutionController::class, 'confirmSubstitution'])->name('substitutions.confirm');
    Route::post('/substitutions/{id}/decline', [SubstitutionController::class, 'declineSubstitution'])->name('substitutions.decline');
    Route::post('/substitutions/{id}/complete', [SubstitutionController::class, 'completeSubstitution'])->name('substitutions.complete');
    Route::post('/substitutions/{id}/cancel', [SubstitutionController::class, 'cancelSubstitution'])->name('substitutions.cancel');
    
    // Analytics and Reports
    Route::get('/substitutions/statistics', [SubstitutionController::class, 'getStatistics'])->name('substitutions.statistics');
    Route::get('/substitutions/teacher-performance/{teacherId}', [SubstitutionController::class, 'getTeacherPerformance'])->name('substitutions.teacher-performance');
    Route::get('/substitutions/reports', [SubstitutionController::class, 'generateReport'])->name('substitutions.reports');
    Route::get('/substitutions/export', [SubstitutionController::class, 'exportData'])->name('substitutions.export');
    
    // My Substitutions (for substitute teachers)
    Route::get('/my-substitutions', [SubstitutionController::class, 'mySubstitutions'])->name('substitutions.my');
    
    // Mobile Notifications for Substitutes
    Route::get('/substitutions/mobile-notifications', function () {
        return view('substitution.mobile-notifications');
    })->name('substitutions.mobile-notifications');
});

// Authentication Routes (manual implementation)
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function () {
    // Login logic here
})->name('login.post')->middleware('guest');

Route::post('/logout', function () {
    // Logout logic here
})->name('logout')->middleware('auth');

Route::get('/register', function () {
    return view('auth.register');
})->name('register')->middleware('guest');

Route::post('/register', function () {
    // Registration logic here
})->name('register.post')->middleware('guest');

// Protected routes that require authentication
Route::middleware(['auth'])->group(function () {
    
    // Dashboard routes
    Route::get('/dashboard', [ClassTeacherPermissionController::class, 'dashboard'])->name('dashboard');
    
    // Class Teacher Permissions Management
    Route::prefix('class-teacher-permissions')->name('class-teacher-permissions.')->group(function () {
        Route::get('/', [ClassTeacherPermissionController::class, 'index'])->name('index');
        Route::get('/create', [ClassTeacherPermissionController::class, 'create'])->name('create');
        Route::post('/', [ClassTeacherPermissionController::class, 'store'])->name('store');
        Route::get('/{classTeacherPermission}', [ClassTeacherPermissionController::class, 'show'])->name('show');
        Route::get('/{classTeacherPermission}/edit', [ClassTeacherPermissionController::class, 'edit'])->name('edit');
        Route::put('/{classTeacherPermission}', [ClassTeacherPermissionController::class, 'update'])->name('update');
        Route::patch('/{classTeacherPermission}/revoke', [ClassTeacherPermissionController::class, 'revoke'])->name('revoke');
        
        // Audit Trail routes
        Route::get('/audit-trail', [ClassTeacherPermissionController::class, 'auditTrail'])->name('audit-trail');
        Route::patch('/audit-trail/{auditTrail}/approve', [ClassTeacherPermissionController::class, 'approveCorrection'])->name('audit-trail.approve');
        Route::patch('/audit-trail/{auditTrail}/reject', [ClassTeacherPermissionController::class, 'rejectCorrection'])->name('audit-trail.reject');
        Route::post('/audit-trail/bulk-approve', [ClassTeacherPermissionController::class, 'bulkApproveCorrections'])->name('audit-trail.bulk-approve');
        Route::get('/audit-trail/export', [ClassTeacherPermissionController::class, 'exportAuditReport'])->name('audit-trail.export');
    });
    
    // SR Register routes with permission middleware
    Route::prefix('sr-register')->name('sr-register.')->middleware('class.teacher.permission')->group(function () {
        Route::get('/', [SRRegisterController::class, 'index'])->name('index');
        Route::get('/create', [SRRegisterController::class, 'create'])->name('create')->middleware('class.teacher.permission:can_add_records');
        Route::post('/', [SRRegisterController::class, 'store'])->name('store')->middleware('class.teacher.permission:can_add_records');
        Route::get('/{srRegister}', [SRRegisterController::class, 'show'])->name('show');
        Route::get('/{srRegister}/edit', [SRRegisterController::class, 'edit'])->name('edit')->middleware('class.teacher.permission:can_edit_records');
        Route::put('/{srRegister}', [SRRegisterController::class, 'update'])->name('update')->middleware('class.teacher.permission:can_edit_records');
        Route::delete('/{srRegister}', [SRRegisterController::class, 'destroy'])->name('destroy')->middleware('class.teacher.permission:can_delete_records');
        
        // Bulk operations
        Route::get('/bulk-entry', [SRRegisterController::class, 'bulkEntry'])->name('bulk-entry')->middleware('class.teacher.permission:can_bulk_operations');
        Route::post('/bulk-entry', [SRRegisterController::class, 'storeBulkEntry'])->name('bulk-entry.store')->middleware('class.teacher.permission:can_bulk_operations');
        
        // Reports and exports
        Route::get('/export/report', [SRRegisterController::class, 'exportReport'])->name('export.report')->middleware('class.teacher.permission:can_export_reports');
        Route::get('/student/{student}/profile', [SRRegisterController::class, 'studentProfile'])->name('student.profile');
        Route::get('/class/{class}/report', [SRRegisterController::class, 'classReport'])->name('class.report');
        
        // AJAX routes
        Route::get('/ajax/students-by-class', [SRRegisterController::class, 'getStudentsByClass'])->name('ajax.students-by-class');
    });
    
    // Biometric Attendance routes with comprehensive security middleware
    Route::prefix('biometric-attendance')->name('biometric-attendance.')
        ->middleware(['auth', 'attendance.security', 'role:admin,teacher,principal,class_teacher'])
        ->group(function () {
            // View routes - accessible by all authorized roles
            Route::get('/', [BiometricAttendanceController::class, 'index'])->name('index')
                ->middleware('permission:view_attendance');
            
            // Marking attendance - requires specific permissions
            Route::post('/check-in', [BiometricAttendanceController::class, 'checkIn'])->name('check-in')
                ->middleware('permission:mark_attendance');
            Route::post('/check-out', [BiometricAttendanceController::class, 'checkOut'])->name('check-out')
                ->middleware('permission:mark_attendance');
            Route::post('/mark-absent', [BiometricAttendanceController::class, 'markAbsent'])->name('mark-absent')
                ->middleware('permission:mark_attendance');
            
            // Bulk operations - admin and principal only
            Route::post('/bulk-check-in', [BiometricAttendanceController::class, 'bulkCheckIn'])->name('bulk-check-in')
                ->middleware(['role:admin,principal', 'permission:bulk_operations']);
            
            // CSV Import - admin and principal only
            Route::post('/import-csv', [BiometricAttendanceController::class, 'importCsvData'])->name('import-csv')
                ->middleware(['role:admin,principal', 'permission:bulk_operations']);
            
            // Analytics Dashboard - requires view reports permission
            Route::get('/analytics', [BiometricAttendanceController::class, 'getAnalyticsDashboard'])->name('analytics')
                ->middleware('permission:view_reports');
            
            // Monthly Analytics Calculation - admin only
            Route::post('/calculate-analytics', [BiometricAttendanceController::class, 'calculateMonthlyAnalytics'])->name('calculate-analytics')
                ->middleware(['role:admin,principal', 'permission:bulk_operations']);
            
            // Detailed Reports - requires view reports permission
            Route::get('/detailed-report', [BiometricAttendanceController::class, 'getDetailedReport'])->name('detailed-report')
                ->middleware('permission:view_reports');
            
            // Leave Pattern Analysis - requires view reports permission
            Route::get('/leave-pattern-analysis', [BiometricAttendanceController::class, 'getLeavePatternAnalysis'])->name('leave-pattern-analysis')
                ->middleware('permission:view_reports');
            
            // Teacher Status - accessible by all authorized roles
            Route::get('/teacher-status/{teacher}', [BiometricAttendanceController::class, 'getTeacherStatus'])->name('teacher-status')
                ->middleware('permission:view_attendance');
            
            // Daily and Monthly Reports
            Route::get('/daily-report', [BiometricAttendanceController::class, 'getDailyReport'])->name('daily-report')
                ->middleware('permission:view_reports');
            Route::get('/monthly-report', [BiometricAttendanceController::class, 'getMonthlyReport'])->name('monthly-report')
                ->middleware('permission:view_reports');
            
            // Export reports - requires export permission
            Route::get('/export', [BiometricAttendanceController::class, 'exportReport'])->name('export')
                ->middleware('permission:export_reports');
            
            // Regularization Management
            Route::prefix('regularization')->name('regularization.')->group(function () {
                // View regularization requests
                Route::get('/', [BiometricAttendanceController::class, 'getRegularizationRequests'])->name('index')
                    ->middleware('permission:view_attendance');
                
                // Create regularization request - teachers can create for themselves
                Route::post('/', [BiometricAttendanceController::class, 'createRegularizationRequest'])->name('store')
                    ->middleware('permission:mark_attendance');
                
                // Process regularization requests - admin and principal only
                Route::patch('/{id}/process', [BiometricAttendanceController::class, 'processRegularizationRequest'])->name('process')
                    ->middleware(['role:admin,principal', 'permission:approve_corrections']);
                
                // Bulk approve/reject regularization requests - admin and principal only
                Route::post('/bulk-approve', [BiometricAttendanceController::class, 'bulkApproveRegularizations'])->name('bulk-approve')
                    ->middleware(['role:admin,principal', 'permission:approve_corrections']);
                Route::post('/bulk-reject', [BiometricAttendanceController::class, 'bulkRejectRegularizations'])->name('bulk-reject')
                    ->middleware(['role:admin,principal', 'permission:approve_corrections']);
                
                // Export regularization reports
                Route::get('/export', [BiometricAttendanceController::class, 'exportRegularizationReport'])->name('export')
                    ->middleware('permission:export_reports');
            });
        });
    
    // Student Attendance routes with comprehensive security middleware
    Route::prefix('attendance')->name('attendance.')
        ->middleware(['auth', 'attendance.security', 'role:admin,teacher,principal,class_teacher,student'])
        ->group(function () {
            // View routes - accessible by all authorized roles
            Route::get('/', [AttendanceController::class, 'index'])->name('index')
                ->middleware('permission:view_attendance');
            
            // Marking attendance - teachers and above only
            Route::get('/mark', [AttendanceController::class, 'markAttendance'])->name('mark')
                ->middleware(['role:admin,teacher,principal,class_teacher', 'permission:mark_attendance']);
            Route::post('/mark', [AttendanceController::class, 'storeAttendance'])->name('store')
                ->middleware(['role:admin,teacher,principal,class_teacher', 'permission:mark_attendance']);
            
            // Bulk attendance marking - admin and principal only
            Route::get('/bulk-mark', [AttendanceController::class, 'bulkMarkView'])->name('bulk-mark')
                ->middleware(['role:admin,principal', 'permission:bulk_operations']);
            Route::post('/bulk-mark', [AttendanceController::class, 'bulkMarkAttendance'])->name('bulk-mark.store')
                ->middleware(['role:admin,principal', 'permission:bulk_operations']);
            Route::get('/bulk-mark/students', [AttendanceController::class, 'getBulkStudents'])->name('bulk-mark.students')
                ->middleware(['role:admin,principal', 'permission:bulk_operations']);
            
            // Individual attendance operations - edit/delete permissions
            Route::get('/{attendance}/edit', [AttendanceController::class, 'edit'])->name('edit')
                ->middleware(['role:admin,teacher,principal,class_teacher', 'permission:edit_attendance']);
            Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update')
                ->middleware(['role:admin,teacher,principal,class_teacher', 'permission:edit_attendance']);
            Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy')
                ->middleware(['role:admin,principal', 'permission:delete_attendance']);
            
            // Analytics and Reports - view permissions required
            Route::get('/analytics', [AttendanceController::class, 'analytics'])->name('analytics')
                ->middleware('permission:view_reports');
            Route::get('/reports', [AttendanceController::class, 'reports'])->name('reports')
                ->middleware('permission:view_reports');
            Route::get('/reports/export', [AttendanceController::class, 'exportReport'])->name('reports.export')
                ->middleware('permission:export_reports');
            Route::get('/reports/class/{class}', [AttendanceController::class, 'classReport'])->name('reports.class')
                ->middleware('permission:view_reports');
            Route::get('/reports/student/{student}', [AttendanceController::class, 'studentReport'])->name('reports.student')
                ->middleware('permission:view_reports');
            
            // AJAX routes - require appropriate permissions
            Route::get('/ajax/students-by-class-date', [AttendanceController::class, 'getStudentsByClassAndDate'])->name('ajax.students-by-class-date')
                ->middleware('permission:view_attendance');
            Route::get('/ajax/attendance-stats', [AttendanceController::class, 'getAttendanceStats'])->name('ajax.stats')
                ->middleware('permission:view_reports');
            Route::get('/ajax/attendance-trends', [AttendanceController::class, 'getAttendanceTrends'])->name('ajax.trends')
                ->middleware('permission:view_reports');
        });
    
    // Reports routes
Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
Route::get('/api/reports/academic', [ReportsController::class, 'academicReports'])->name('api.reports.academic');
Route::get('/api/reports/financial', [ReportsController::class, 'financialReports'])->name('api.reports.financial');
Route::get('/api/reports/attendance', [ReportsController::class, 'attendanceReports'])->name('api.reports.attendance');
Route::get('/api/reports/performance', [ReportsController::class, 'performanceReports'])->name('api.reports.performance');
Route::get('/api/reports/administrative', [ReportsController::class, 'administrativeReports'])->name('api.reports.administrative');
Route::post('/api/reports/export', [ReportsController::class, 'exportPdf'])->name('api.reports.export');

// Exam Management routes
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        Route::get('/api/classes', [ExamController::class, 'getClasses']);
    });
    
    // Exam Papers routes with permission middleware
    Route::prefix('exam-papers')->name('exam-papers.')->middleware('class.teacher.permission')->group(function () {
        Route::get('/', [ExamPaperController::class, 'index'])->name('index');
        Route::get('/create', [ExamPaperController::class, 'create'])->name('create')->middleware('class.teacher.permission:can_add_records');
        Route::post('/', [ExamPaperController::class, 'store'])->name('store')->middleware('class.teacher.permission:can_add_records');
        Route::get('/{examPaper}', [ExamPaperController::class, 'show'])->name('show');
        Route::get('/{examPaper}/edit', [ExamPaperController::class, 'edit'])->name('edit')->middleware('class.teacher.permission:can_edit_records');
        Route::put('/{examPaper}', [ExamPaperController::class, 'update'])->name('update')->middleware('class.teacher.permission:can_edit_records');
        Route::delete('/{examPaper}', [ExamPaperController::class, 'destroy'])->name('destroy')->middleware('class.teacher.permission:can_delete_records');
        
        // Paper workflow
        Route::patch('/{examPaper}/publish', [ExamPaperController::class, 'publish'])->name('publish')->middleware('class.teacher.permission:can_approve_corrections');
        Route::patch('/{examPaper}/submit', [ExamPaperController::class, 'submit'])->name('submit');
        Route::patch('/{examPaper}/approve', [ExamPaperController::class, 'approve'])->name('approve')->middleware('class.teacher.permission:can_approve_corrections');
        Route::patch('/{examPaper}/reject', [ExamPaperController::class, 'reject'])->name('reject')->middleware('class.teacher.permission:can_approve_corrections');
        Route::post('/{examPaper}/duplicate', [ExamPaperController::class, 'duplicate'])->name('duplicate')->middleware('class.teacher.permission:can_add_records');
        
        // Version Control & Approval Workflow Routes
        Route::post('/{examPaper}/submit-for-approval', [ExamPaperController::class, 'submitForApproval'])->name('submit-for-approval');
        Route::post('/{examPaper}/versions/{version}/approve', [ExamPaperController::class, 'approve'])->name('version.approve')->middleware('role:admin,principal');
        Route::post('/{examPaper}/versions/{version}/reject', [ExamPaperController::class, 'reject'])->name('version.reject')->middleware('role:admin,principal');
        Route::get('/{examPaper}/version-history', [ExamPaperController::class, 'versionHistory'])->name('version-history');
        Route::get('/{examPaper}/versions/{version}/download', [ExamPaperController::class, 'downloadVersion'])->name('version.download');
        Route::get('/{examPaper}/approval-status', [ExamPaperController::class, 'approvalStatus'])->name('approval-status');
        
        // Security & Audit Routes
        Route::get('/{examPaper}/security-logs', [ExamPaperController::class, 'securityLogs'])->name('security-logs')->middleware('role:admin');
        
        // Export and question bank
        Route::get('/{examPaper}/export-pdf', [ExamPaperController::class, 'exportPdf'])->name('export-pdf')->middleware('class.teacher.permission:can_export_reports');
        Route::get('/question-bank/{subject}', [ExamPaperController::class, 'getQuestionBank'])->name('question-bank');
    });
    
    // Admin-only routes for permission management
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('classes', ClassController::class);
        Route::resource('subjects', SubjectController::class);
        Route::resource('students', StudentController::class);
        Route::resource('teachers', TeacherController::class);
        Route::resource('exams', ExamController::class);
        Route::resource('bell-timings', BellTimingController::class);
        Route::resource('substitutions', SubstitutionController::class);
        Route::resource('biometric', BiometricAttendanceController::class);
        
        // System audit and reports
        Route::get('/system/audit-report', [ClassTeacherPermissionController::class, 'systemAuditReport'])->name('system.audit-report');
        Route::get('/system/permissions-report', [ClassTeacherPermissionController::class, 'permissionsReport'])->name('system.permissions-report');
    });
    
    // Admin-only routes with middleware protection for financial modules
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::resource('salaries', SalaryController::class);
        Route::resource('budgets', BudgetController::class);
    });
    
    // Basic authenticated routes for students (accessible to all authenticated users)
    Route::middleware(['auth'])->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/api/students/classes', [StudentController::class, 'getClasses'])->name('api.students.classes');
        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        Route::get('/classes/{class}', [ClassController::class, 'show'])->name('classes.show');
        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
    });

    // Fee Management Routes
    Route::prefix('fees')->name('fees.')->group(function () {
        Route::get('/', [App\Http\Controllers\FeeController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\FeeController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\FeeController::class, 'store'])->name('store');
        Route::get('/{fee}', [App\Http\Controllers\FeeController::class, 'show'])->name('show');
        Route::get('/{fee}/edit', [App\Http\Controllers\FeeController::class, 'edit'])->name('edit');
        Route::put('/{fee}', [App\Http\Controllers\FeeController::class, 'update'])->name('update');
        Route::delete('/{fee}', [App\Http\Controllers\FeeController::class, 'destroy'])->name('destroy');
        Route::post('/{fee}/pay', [App\Http\Controllers\FeeController::class, 'recordPayment'])->name('pay');
        Route::get('/{fee}/receipt', [App\Http\Controllers\FeePaymentController::class, 'receipt'])->name('receipt');
        Route::post('/bulk-create', [App\Http\Controllers\FeeController::class, 'bulkCreateFees'])->name('bulk-create');
        Route::get('/student/{student}', [App\Http\Controllers\FeeController::class, 'getStudentFees'])->name('student');
    });

    // Teacher Document Management Routes
    Route::prefix('teacher-documents')->name('teacher-documents.')->group(function () {
        // Teacher routes - for uploading and viewing their own documents
        Route::middleware(['role:teacher,admin,principal'])->group(function () {
            Route::get('/', [TeacherDocumentController::class, 'index'])->name('index');
            Route::get('/create', [TeacherDocumentController::class, 'create'])->name('create');
            Route::post('/', [TeacherDocumentController::class, 'store'])->name('store');
            Route::get('/bulk-upload', [TeacherDocumentController::class, 'bulkUpload'])->name('bulk-upload');
            Route::post('/bulk-upload', [TeacherDocumentController::class, 'storeBulkUpload'])->name('bulk-upload.store');
            Route::get('/{document}', [TeacherDocumentController::class, 'show'])->name('show');
            Route::get('/{document}/download', [TeacherDocumentController::class, 'download'])->name('download');
            Route::delete('/{document}', [TeacherDocumentController::class, 'destroy'])->name('destroy');
        });
        
        // Admin routes - for managing all teacher documents
        Route::middleware(['role:admin,principal'])->group(function () {
            Route::get('/admin', [TeacherDocumentController::class, 'adminIndex'])->name('admin.index');
            Route::patch('/{document}/approve', [TeacherDocumentController::class, 'approve'])->name('admin.approve');
            Route::patch('/{document}/reject', [TeacherDocumentController::class, 'reject'])->name('admin.reject');
            Route::post('/bulk-action', [TeacherDocumentController::class, 'bulkAction'])->name('admin.bulk-action');
            Route::get('/expiring', [TeacherDocumentController::class, 'getExpiringDocuments'])->name('admin.expiring');
            
            // Document Expiry Alert Routes
            Route::get('/expiry-alerts', [TeacherDocumentController::class, 'showExpiryAlerts'])->name('expiry-alerts');
            Route::get('/expiry-alerts/data', [TeacherDocumentController::class, 'getExpiryAlerts'])->name('expiry-alerts.data');
            Route::post('/expiry-alerts/process', [TeacherDocumentController::class, 'processExpiryAlerts'])->name('expiry-alerts.process');
            Route::get('/expiry-alerts/service-check', [TeacherDocumentController::class, 'checkExpiryAlertService'])->name('expiry-alerts.service-check');
            Route::get('/expiry-alerts/teacher/{teacher}', [TeacherDocumentController::class, 'getTeacherExpiryAlerts'])->name('expiry-alerts.teacher');
        });
    });

    // Student Document Verification Routes
    Route::prefix('student-verifications')->name('student-verifications.')->group(function () {
        // Student routes - for uploading and viewing their own document verifications
        Route::middleware(['role:student,admin,principal'])->group(function () {
            Route::get('/', [App\Http\Controllers\StudentVerificationController::class, 'index'])->name('index');
            Route::get('/upload', [App\Http\Controllers\StudentVerificationController::class, 'uploadForm'])->name('upload');
            Route::post('/upload', [App\Http\Controllers\StudentVerificationController::class, 'upload'])->name('upload.store');
            Route::get('/{verification}', [App\Http\Controllers\StudentVerificationController::class, 'show'])->name('show');
            Route::get('/{verification}/download', [App\Http\Controllers\StudentVerificationController::class, 'download'])->name('download');
            Route::delete('/{verification}', [App\Http\Controllers\StudentVerificationController::class, 'destroy'])->name('destroy');
            Route::get('/{verification}/status', [App\Http\Controllers\StudentVerificationController::class, 'checkStatus'])->name('status');
    
    // Aadhaar Verification Routes
    Route::get('/aadhaar/verify', [App\Http\Controllers\StudentVerificationController::class, 'showAadhaarVerification'])->name('aadhaar.verify-form');
    Route::post('/aadhaar/verify', [App\Http\Controllers\StudentVerificationController::class, 'verifyAadhaar'])->name('aadhaar.verify');
    Route::get('/aadhaar/status/{student}', [App\Http\Controllers\StudentVerificationController::class, 'getAadhaarStatus'])->name('aadhaar.status');
    Route::get('/aadhaar/service-check', [App\Http\Controllers\StudentVerificationController::class, 'checkAadhaarService'])->name('aadhaar.service-check');

    // Birth Certificate OCR Routes
    Route::get('/birth-certificate/ocr', [App\Http\Controllers\StudentVerificationController::class, 'showBirthCertificateOCR'])->name('birth-certificate.ocr-form');
    Route::post('/birth-certificate/ocr', [App\Http\Controllers\StudentVerificationController::class, 'processBirthCertificateOCR'])->name('birth-certificate.ocr');
    Route::get('/birth-certificate/status', [App\Http\Controllers\StudentVerificationController::class, 'getBirthCertificateStatus'])->name('birth-certificate.status');
    Route::get('/birth-certificate/service-check', [App\Http\Controllers\StudentVerificationController::class, 'checkBirthCertificateService'])->name('birth-certificate.service-check');
        });
        
        // Admin routes - for managing all student document verifications
        Route::middleware(['role:admin,principal'])->group(function () {
            Route::get('/admin', [App\Http\Controllers\StudentVerificationController::class, 'dashboard'])->name('admin.dashboard');
            Route::patch('/{verification}/approve', [App\Http\Controllers\StudentVerificationController::class, 'approve'])->name('approve');
            Route::patch('/{verification}/reject', [App\Http\Controllers\StudentVerificationController::class, 'reject'])->name('reject');
            Route::post('/bulk-approve', [App\Http\Controllers\StudentVerificationController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/bulk-reject', [App\Http\Controllers\StudentVerificationController::class, 'bulkReject'])->name('bulk-reject');
            
            // Bulk verification routes
            Route::get('/bulk-verification', [App\Http\Controllers\StudentVerificationController::class, 'showBulkVerification'])->name('bulk-verification');
            Route::post('/bulk-verification/process', [App\Http\Controllers\StudentVerificationController::class, 'processBulkVerification'])->name('bulk-verification.process');
            Route::get('/bulk-verification/progress', [App\Http\Controllers\StudentVerificationController::class, 'getBulkVerificationProgress'])->name('bulk-verification.progress');
            Route::post('/bulk-verification/cancel', [App\Http\Controllers\StudentVerificationController::class, 'cancelBulkVerification'])->name('bulk-verification.cancel');
            Route::get('/bulk-verification/stats', [App\Http\Controllers\StudentVerificationController::class, 'getBulkVerificationStats'])->name('bulk-verification.stats');
        
        // Mismatch Resolution Routes
        Route::get('/{verification}/analyze-mismatches', [App\Http\Controllers\StudentVerificationController::class, 'analyzeMismatches'])->name('analyze-mismatches');
        Route::post('/{verification}/apply-resolution', [App\Http\Controllers\StudentVerificationController::class, 'applyAutomaticResolution'])->name('apply-resolution');
        Route::get('/{verification}/mismatch-resolution', [App\Http\Controllers\StudentVerificationController::class, 'showMismatchResolution'])->name('mismatch-resolution');
        Route::post('/batch-analyze-mismatches', [App\Http\Controllers\StudentVerificationController::class, 'batchAnalyzeMismatches'])->name('batch-analyze-mismatches');
        Route::post('/batch-apply-resolution', [App\Http\Controllers\StudentVerificationController::class, 'batchApplyAutomaticResolution'])->name('batch-apply-resolution');
        
        // Verification History and Audit Trail Routes
        Route::get('/{verification}/history', [App\Http\Controllers\StudentVerificationController::class, 'showHistory'])->name('history');
        Route::get('/student/{student}/history', [App\Http\Controllers\StudentVerificationController::class, 'showStudentHistory'])->name('student-history');
        Route::get('/audit-trail', [App\Http\Controllers\StudentVerificationController::class, 'getAuditTrail'])->name('audit-trail');
        Route::get('/audit-trail/export', [App\Http\Controllers\StudentVerificationController::class, 'exportAuditTrail'])->name('audit-trail.export');
        Route::get('/audit-trail/statistics', [App\Http\Controllers\StudentVerificationController::class, 'getAuditStatistics'])->name('audit-trail.statistics');
        
        Route::post('/{verification}/reprocess', [App\Http\Controllers\StudentVerificationController::class, 'reprocess'])->name('reprocess');
            Route::post('/process-pending', [App\Http\Controllers\StudentVerificationController::class, 'processPending'])->name('process-pending');
            Route::get('/statistics', [App\Http\Controllers\StudentVerificationController::class, 'getStatistics'])->name('statistics');
            Route::get('/{verification}/compare', [App\Http\Controllers\StudentVerificationController::class, 'compareData'])->name('compare');
        });
    });

    // Payroll Management Routes
    Route::prefix('payroll')->name('payroll.')->middleware(['role:admin,principal,hr'])->group(function () {
        Route::get('/', [App\Http\Controllers\PayrollController::class, 'index'])->name('index');
        Route::get('/dashboard', [App\Http\Controllers\PayrollController::class, 'dashboard'])->name('dashboard');
        
        // Salary Calculation Routes
        Route::post('/calculate/{user}', [App\Http\Controllers\PayrollController::class, 'calculateSalary'])->name('calculate');
        Route::post('/calculate-bulk', [App\Http\Controllers\PayrollController::class, 'calculateBulkSalary'])->name('calculate-bulk');
        Route::get('/salary-slip/{user}', [App\Http\Controllers\PayrollController::class, 'generateSalarySlip'])->name('salary-slip');
        Route::get('/payroll-summary', [App\Http\Controllers\PayrollController::class, 'generatePayrollSummary'])->name('payroll-summary');
        
        // Salary Structure Management
        Route::get('/salary-structures', [App\Http\Controllers\PayrollController::class, 'salaryStructures'])->name('salary-structures');
        Route::post('/salary-structures', [App\Http\Controllers\PayrollController::class, 'storeSalaryStructure'])->name('salary-structures.store');
        Route::put('/salary-structures/{salaryStructure}', [App\Http\Controllers\PayrollController::class, 'updateSalaryStructure'])->name('salary-structures.update');
        Route::delete('/salary-structures/{salaryStructure}', [App\Http\Controllers\PayrollController::class, 'destroySalaryStructure'])->name('salary-structures.destroy');
        
        // Deduction Management
        Route::get('/deductions', [App\Http\Controllers\PayrollController::class, 'deductions'])->name('deductions');
        Route::post('/deductions', [App\Http\Controllers\PayrollController::class, 'storeDeduction'])->name('deductions.store');
        Route::put('/deductions/{deduction}', [App\Http\Controllers\PayrollController::class, 'updateDeduction'])->name('deductions.update');
        Route::delete('/deductions/{deduction}', [App\Http\Controllers\PayrollController::class, 'destroyDeduction'])->name('deductions.destroy');
        
        // Tax and Validation
        Route::get('/annual-tax/{user}', [App\Http\Controllers\PayrollController::class, 'calculateAnnualTax'])->name('annual-tax');
        Route::post('/validate-salary', [App\Http\Controllers\PayrollController::class, 'validateSalaryCalculation'])->name('validate-salary');
        
        // API Routes for AJAX calls
        Route::get('/api/employees', [App\Http\Controllers\PayrollController::class, 'getEmployees'])->name('api.employees');
        Route::get('/api/statistics', [App\Http\Controllers\PayrollController::class, 'getStatistics'])->name('api.statistics');
        
        // Payroll Reports
        Route::get('/reports', [App\Http\Controllers\PayrollController::class, 'reports'])->name('reports');
        Route::post('/reports/generate', [App\Http\Controllers\PayrollController::class, 'generateReport'])->name('reports.generate');
        Route::post('/reports/export', [App\Http\Controllers\PayrollController::class, 'exportReport'])->name('reports.export');
    });

    // Bell Schedule Management Routes
    Route::prefix('bell-schedule')->name('bell-schedule.')->middleware(['role:admin,principal,teacher'])->group(function () {
        // Bell Timing Management
        Route::get('/', [BellTimingController::class, 'index'])->name('index');
        Route::post('/', [BellTimingController::class, 'store'])->name('store')->middleware('role:admin,principal');
        Route::get('/dashboard', [BellTimingController::class, 'dashboard'])->name('dashboard');
        Route::get('/current-schedule', [BellTimingController::class, 'getCurrentSchedule'])->name('current-schedule');
        Route::get('/enhanced-schedule', [BellTimingController::class, 'getCurrentScheduleEnhanced'])->name('enhanced-schedule');
        Route::get('/check-notification', [BellTimingController::class, 'checkBellNotification'])->name('check-notification');
        
        Route::prefix('timings')->name('timings.')->group(function () {
            Route::get('/{bellTiming}', [BellTimingController::class, 'show'])->name('show');
            Route::put('/{bellTiming}', [BellTimingController::class, 'update'])->name('update')->middleware('role:admin,principal');
            Route::delete('/{bellTiming}', [BellTimingController::class, 'destroy'])->name('destroy')->middleware('role:admin,principal');
            Route::patch('/{bellTiming}/toggle-active', [BellTimingController::class, 'toggleActive'])->name('toggle-active')->middleware('role:admin,principal');
            Route::post('/update-order', [BellTimingController::class, 'updateOrder'])->name('update-order')->middleware('role:admin,principal');
            
            // Notification management for specific bell timing
            Route::get('/{bellTiming}/notifications', [BellTimingController::class, 'getNotifications'])->name('notifications');
            Route::put('/{bellTiming}/notifications', [BellTimingController::class, 'updateNotifications'])->name('notifications.update')->middleware('role:admin,principal');
        });
    });

    // Bell Notification Management Routes
    Route::prefix('bell-notifications')->name('bell-notifications.')->middleware(['role:admin,principal'])->group(function () {
        Route::get('/', [BellNotificationController::class, 'index'])->name('index');
        Route::post('/', [BellNotificationController::class, 'store'])->name('store');
        Route::get('/active', [BellNotificationController::class, 'active'])->name('active');
        Route::get('/upcoming', [BellNotificationController::class, 'upcoming'])->name('upcoming');
        Route::get('/statistics', [BellNotificationController::class, 'statistics'])->name('statistics');
        Route::post('/bulk-update', [BellNotificationController::class, 'bulkUpdate'])->name('bulk-update');
        
        Route::prefix('{bellNotification}')->group(function () {
            Route::get('/', [BellNotificationController::class, 'show'])->name('show');
            Route::put('/', [BellNotificationController::class, 'update'])->name('update');
            Route::delete('/', [BellNotificationController::class, 'destroy'])->name('destroy');
            Route::patch('/toggle-enabled', [BellNotificationController::class, 'toggleEnabled'])->name('toggle-enabled');
            Route::post('/test', [BellNotificationController::class, 'test'])->name('test');
        });
        
        // Create default notifications for bell timing
        Route::post('/create-defaults/{bellTiming}', [BellNotificationController::class, 'createDefaults'])->name('create-defaults');
    });

    // Special Schedule Management Routes
    Route::prefix('special-schedules')->name('special-schedules.')->middleware(['role:admin,principal'])->group(function () {
        Route::get('/', [SpecialScheduleController::class, 'index'])->name('index');
        Route::post('/', [SpecialScheduleController::class, 'store'])->name('store');
        Route::get('/today', [SpecialScheduleController::class, 'today'])->name('today');
        Route::get('/upcoming', [SpecialScheduleController::class, 'upcoming'])->name('upcoming');
        Route::get('/statistics', [SpecialScheduleController::class, 'statistics'])->name('statistics');
        Route::post('/create-predefined', [SpecialScheduleController::class, 'createPredefined'])->name('create-predefined');
        
        Route::prefix('{specialSchedule}')->group(function () {
            Route::get('/', [SpecialScheduleController::class, 'show'])->name('show');
            Route::put('/', [SpecialScheduleController::class, 'update'])->name('update');
            Route::delete('/', [SpecialScheduleController::class, 'destroy'])->name('destroy');
            Route::patch('/toggle-active', [SpecialScheduleController::class, 'toggleActive'])->name('toggle-active');
        });
    });

    // Digital Learning Management Portal Routes
    Route::prefix('learning')->name('learning.')->group(function () {
        
        // Assignment Management Routes (Teachers/Admins)
        Route::prefix('assignments')->name('assignments.')->middleware(['role:admin,teacher,principal'])->group(function () {
            Route::get('/', [AssignmentController::class, 'index'])->name('index');
            Route::get('/create', [AssignmentController::class, 'create'])->name('create');
            Route::post('/', [AssignmentController::class, 'store'])->name('store');
            Route::get('/{assignment}', [AssignmentController::class, 'show'])->name('show');
            Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('edit');
            Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('update');
            Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
            
            // Assignment Actions
            Route::patch('/{assignment}/publish', [AssignmentController::class, 'publish'])->name('publish');
            Route::patch('/{assignment}/unpublish', [AssignmentController::class, 'unpublish'])->name('unpublish');
            Route::patch('/{assignment}/extend-deadline', [AssignmentController::class, 'extendDeadline'])->name('extend-deadline');
            Route::post('/{assignment}/duplicate', [AssignmentController::class, 'duplicate'])->name('duplicate');
            
            // Submission Management
            Route::prefix('{assignment}/submissions')->name('submissions.')->group(function () {
                Route::get('/', [AssignmentController::class, 'submissions'])->name('index');
                Route::get('/{submission}', [AssignmentController::class, 'viewSubmission'])->name('show');
                Route::post('/{submission}/grade', [AssignmentController::class, 'gradeSubmission'])->name('grade');
                Route::post('/bulk-grade', [AssignmentController::class, 'bulkGrade'])->name('bulk-grade');
                Route::post('/send-reminder', [AssignmentController::class, 'sendReminder'])->name('send-reminder');
                Route::get('/export', [AssignmentController::class, 'exportSubmissions'])->name('export');
            });
            
            // Assignment Analytics
            Route::get('/{assignment}/analytics', [AssignmentController::class, 'analytics'])->name('analytics');
            Route::get('/calendar', [AssignmentController::class, 'calendar'])->name('calendar');
            Route::get('/statistics', [AssignmentController::class, 'statistics'])->name('statistics');
        });

        // Syllabus Management Routes (Teachers/Admins)
        Route::prefix('syllabi')->name('syllabi.')->middleware(['role:admin,teacher,principal'])->group(function () {
            Route::get('/', [SyllabusController::class, 'index'])->name('index');
            Route::get('/create', [SyllabusController::class, 'create'])->name('create');
            Route::post('/', [SyllabusController::class, 'store'])->name('store');
            Route::get('/{syllabus}', [SyllabusController::class, 'show'])->name('show');
            Route::get('/{syllabus}/edit', [SyllabusController::class, 'edit'])->name('edit');
            Route::put('/{syllabus}', [SyllabusController::class, 'update'])->name('update');
            Route::delete('/{syllabus}', [SyllabusController::class, 'destroy'])->name('destroy');
            
            // Syllabus Actions
            Route::patch('/{syllabus}/publish', [SyllabusController::class, 'publish'])->name('publish');
            Route::patch('/{syllabus}/unpublish', [SyllabusController::class, 'unpublish'])->name('unpublish');
            Route::get('/{syllabus}/download', [SyllabusController::class, 'download'])->name('download');
            Route::post('/{syllabus}/duplicate', [SyllabusController::class, 'duplicate'])->name('duplicate');
            
            // Syllabus Analytics
            Route::get('/{syllabus}/analytics', [SyllabusController::class, 'analytics'])->name('analytics');
            Route::get('/statistics', [SyllabusController::class, 'statistics'])->name('statistics');
        });

        // Student Portal Routes
        Route::prefix('student')->name('student.')->middleware(['role:student'])->group(function () {
            Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
            
            // Student Assignment Routes
            Route::prefix('assignments')->name('assignments.')->group(function () {
                Route::get('/', [StudentPortalController::class, 'assignments'])->name('index');
                Route::get('/{assignment}', [StudentPortalController::class, 'showAssignment'])->name('show');
                Route::get('/{assignment}/submit', [StudentPortalController::class, 'submitForm'])->name('submit');
                Route::post('/{assignment}/submit', [StudentPortalController::class, 'storeSubmission'])->name('submit.store');
                Route::get('/{assignment}/submission', [StudentPortalController::class, 'viewSubmission'])->name('submission');
                Route::put('/{assignment}/submission', [StudentPortalController::class, 'updateSubmission'])->name('submission.update');
                Route::delete('/{assignment}/submission', [StudentPortalController::class, 'deleteSubmission'])->name('submission.delete');
                
                // Assignment Calendar and Export
                Route::get('/calendar', [StudentPortalController::class, 'assignmentCalendar'])->name('calendar');
                Route::get('/export', [StudentPortalController::class, 'exportAssignments'])->name('export');
            });
            
            // Student Syllabus Routes
            Route::prefix('syllabi')->name('syllabi.')->group(function () {
                Route::get('/', [StudentPortalController::class, 'syllabi'])->name('index');
                Route::get('/{syllabus}', [StudentPortalController::class, 'showSyllabus'])->name('show');
                Route::get('/{syllabus}/download', [StudentPortalController::class, 'downloadSyllabus'])->name('download');
                Route::post('/track-view', [StudentPortalController::class, 'trackSyllabusView'])->name('track-view');
                Route::post('/track-download', [StudentPortalController::class, 'trackSyllabusDownload'])->name('track-download');
                Route::get('/{syllabus}/stats', [StudentPortalController::class, 'syllabusStats'])->name('stats');
                Route::get('/export', [StudentPortalController::class, 'exportSyllabi'])->name('export');
            });
            
            // Student Progress and Reports
            Route::get('/progress', [StudentPortalController::class, 'progress'])->name('progress');
            Route::get('/submissions', [StudentPortalController::class, 'submissions'])->name('submissions');
            Route::get('/grades', [StudentPortalController::class, 'grades'])->name('grades');
        });

        // Admin Analytics Routes
        Route::prefix('admin')->name('admin.')->middleware(['role:admin,principal'])->group(function () {
            Route::get('/analytics', [AdminAnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
            
            // Assignment Analytics
            Route::prefix('analytics/assignments')->name('analytics.assignments.')->group(function () {
                Route::get('/', [AdminAnalyticsController::class, 'assignmentAnalytics'])->name('index');
                Route::get('/distribution', [AdminAnalyticsController::class, 'assignmentDistribution'])->name('distribution');
                Route::get('/trends', [AdminAnalyticsController::class, 'assignmentTrends'])->name('trends');
                Route::get('/calendar', [AdminAnalyticsController::class, 'assignmentCalendar'])->name('calendar');
            });
            
            // Student Performance Analytics
            Route::prefix('analytics/students')->name('analytics.students.')->group(function () {
                Route::get('/', [AdminAnalyticsController::class, 'studentPerformance'])->name('index');
                Route::get('/class/{class}', [AdminAnalyticsController::class, 'classPerformance'])->name('class');
                Route::get('/subject/{subject}', [AdminAnalyticsController::class, 'subjectPerformance'])->name('subject');
                Route::get('/engagement', [AdminAnalyticsController::class, 'studentEngagement'])->name('engagement');
            });
            
            // Teacher Performance Analytics
            Route::prefix('analytics/teachers')->name('analytics.teachers.')->group(function () {
                Route::get('/', [AdminAnalyticsController::class, 'teacherPerformance'])->name('index');
                Route::get('/{teacher}', [AdminAnalyticsController::class, 'teacherDetails'])->name('show');
                Route::get('/efficiency', [AdminAnalyticsController::class, 'gradingEfficiency'])->name('efficiency');
            });
            
            // Syllabus Analytics
            Route::prefix('analytics/syllabi')->name('analytics.syllabi.')->group(function () {
                Route::get('/', [AdminAnalyticsController::class, 'syllabusAnalytics'])->name('index');
                Route::get('/usage', [AdminAnalyticsController::class, 'syllabusUsage'])->name('usage');
                Route::get('/content', [AdminAnalyticsController::class, 'contentUtilization'])->name('content');
            });
            
            // Real-time Statistics and Reports
            Route::get('/real-time-stats', [AdminAnalyticsController::class, 'getRealTimeStats'])->name('real-time-stats');
            Route::get('/completion-analytics', [AdminAnalyticsController::class, 'getCompletionAnalytics'])->name('completion-analytics');
            
            // Data Export Routes
            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/assignments', [AdminAnalyticsController::class, 'exportAssignments'])->name('assignments');
                Route::get('/submissions', [AdminAnalyticsController::class, 'exportSubmissions'])->name('submissions');
                Route::get('/student-performance', [AdminAnalyticsController::class, 'exportStudentPerformance'])->name('student-performance');
                Route::get('/teacher-performance', [AdminAnalyticsController::class, 'exportTeacherPerformance'])->name('teacher-performance');
                Route::get('/syllabi', [AdminAnalyticsController::class, 'exportSyllabi'])->name('syllabi');
            });
        });

        // Common API Routes for AJAX calls
        Route::prefix('api')->name('api.')->group(function () {
            // Class and Subject data
            Route::get('/classes', [LearningApiController::class, 'getClasses'])->name('classes');
            Route::get('/subjects', [LearningApiController::class, 'getSubjects'])->name('subjects');
            Route::get('/classes/{class}/subjects', [LearningApiController::class, 'getClassSubjects'])->name('class-subjects');
            Route::get('/classes/{class}/students', [LearningApiController::class, 'getClassStudents'])->name('class-students');
            
            // Assignment data
            Route::get('/assignments/search', [LearningApiController::class, 'searchAssignments'])->name('assignments.search');
            Route::get('/assignments/filter', [LearningApiController::class, 'filterAssignments'])->name('assignments.filter');
            Route::get('/assignments/{assignment}/submissions-count', [LearningApiController::class, 'getSubmissionsCount'])->name('assignments.submissions-count');
            
            // Syllabus data
            Route::get('/syllabi/search', [LearningApiController::class, 'searchSyllabi'])->name('syllabi.search');
            Route::get('/syllabi/filter', [LearningApiController::class, 'filterSyllabi'])->name('syllabi.filter');
            
            // Statistics and Analytics
            Route::get('/dashboard-stats', [LearningApiController::class, 'getDashboardStats'])->name('dashboard-stats');
            Route::get('/assignment-stats/{assignment}', [LearningApiController::class, 'getAssignmentStats'])->name('assignment-stats');
            Route::get('/student-progress/{student}', [LearningApiController::class, 'getStudentProgress'])->name('student-progress');
        });
        
        // Notification System Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            // Main notification routes
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
            Route::get('/recent', [NotificationController::class, 'getRecent'])->name('recent');
            Route::get('/stats', [NotificationController::class, 'getStats'])->name('stats');
            Route::get('/preferences', [NotificationController::class, 'getPreferences'])->name('preferences');
            Route::put('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
            
            // Individual notification actions
            Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
            Route::put('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::put('/{notification}/unread', [NotificationController::class, 'markAsUnread'])->name('mark-unread');
            Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
            
            // Bulk actions
            Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/delete-all-read', [NotificationController::class, 'deleteAllRead'])->name('delete-all-read');
            
            // Admin/Teacher notification creation
            Route::middleware(['role:admin,teacher'])->group(function () {
                Route::post('/', [NotificationController::class, 'store'])->name('store');
            });
        });
    });
    
    // Inventory Settings routes
    Route::prefix('inventory/settings')->name('inventory.settings.')->middleware(['auth', 'role:admin,manager'])->group(function () {
        Route::get('/', [InventoryController::class, 'settingsIndex'])->name('index');
        Route::get('/general', [InventoryController::class, 'generalSettings'])->name('general');
        Route::get('/inventory', [InventoryController::class, 'inventorySettings'])->name('inventory');
        Route::get('/notifications', [InventoryController::class, 'notificationSettings'])->name('notifications');
        Route::get('/security', [InventoryController::class, 'securitySettings'])->name('security');
        Route::get('/backup', [InventoryController::class, 'backupSettings'])->name('backup');
        Route::get('/system', [InventoryController::class, 'systemSettings'])->name('system');
        Route::get('/api', [InventoryController::class, 'apiSettings'])->name('api');
        
        // Settings save routes
        Route::post('/general', [InventoryController::class, 'saveGeneralSettings'])->name('general.save');
        Route::post('/inventory', [InventoryController::class, 'saveInventorySettings'])->name('inventory.save');
        Route::post('/notifications', [InventoryController::class, 'saveNotificationSettings'])->name('notifications.save');
        Route::post('/security', [InventoryController::class, 'saveSecuritySettings'])->name('security.save');
        Route::post('/backup', [InventoryController::class, 'saveBackupSettings'])->name('backup.save');
        Route::post('/system', [InventoryController::class, 'saveSystemSettings'])->name('system.save');
        Route::post('/api', [InventoryController::class, 'saveApiSettings'])->name('api.save');
        
        // Export/Import routes
        Route::get('/export', [InventoryController::class, 'exportSettings'])->name('export');
        Route::post('/import', [InventoryController::class, 'importSettings'])->name('import');
    });
    
    // Class Data Audit System routes
    Route::prefix('class-data-audit')->name('class-data-audit.')->middleware(['auth', 'role:admin,principal,class_teacher'])->group(function () {
        // Main audit dashboard and listing
        Route::get('/', [ClassDataAuditController::class, 'index'])->name('index')
            ->middleware('permission:view_audit_trails');
        
        // Statistics and analytics (must come before {audit} route)
        Route::get('/statistics/dashboard', [ClassDataAuditController::class, 'statistics'])->name('statistics')
            ->middleware('permission:view_audit_statistics');
        Route::get('/analytics', [ClassDataAuditController::class, 'analytics'])->name('analytics')
            ->middleware('permission:view_audit_statistics');
        
        // Export functionality (must come before {audit} route)
        Route::get('/export', [ClassDataAuditController::class, 'export'])->name('export')
            ->middleware('permission:export_audit_reports');
        Route::get('/download-export/{token}', [ClassDataAuditController::class, 'downloadExport'])->name('download-export')
            ->middleware('permission:export_audit_reports');
        
        // Individual audit record details
        Route::get('/{audit}', [ClassDataAuditController::class, 'show'])->name('show')
            ->middleware('permission:view_audit_trails');
        
        // Version management
        Route::get('/{audit}/versions', [ClassDataAuditController::class, 'versionHistory'])->name('versions')
            ->middleware('permission:view_audit_trails');
        Route::get('/versions/{version1}/compare/{version2}', [ClassDataAuditController::class, 'compareVersions'])->name('versions.compare')
            ->middleware('permission:view_audit_trails');
        Route::post('/versions/{version}/rollback', [ClassDataAuditController::class, 'rollbackToVersion'])->name('versions.rollback')
            ->middleware(['permission:manage_audit_rollbacks', 'role:admin,principal']);
        
        // Direct audit rollback
        Route::post('/{audit}/rollback', [ClassDataAuditController::class, 'rollback'])->name('rollback')
            ->middleware(['permission:manage_audit_rollbacks', 'role:admin,principal']);
        
        // Approval workflow management
        Route::get('/approvals/status', [ClassDataAuditController::class, 'approvalStatus'])->name('approvals.status')
            ->middleware('permission:view_audit_approvals');
        Route::post('/{audit}/approve', [ClassDataAuditController::class, 'approve'])->name('approve')
            ->middleware('permission:approve_audit_changes');
        Route::post('/{audit}/reject', [ClassDataAuditController::class, 'reject'])->name('reject')
            ->middleware('permission:approve_audit_changes');
        Route::post('/{audit}/delegate', [ClassDataAuditController::class, 'delegate'])->name('delegate')
            ->middleware('permission:delegate_audit_approvals');
        
        // Bulk operations
        Route::post('/bulk-approve', [ClassDataAuditController::class, 'bulkApprove'])->name('bulk-approve')
            ->middleware(['permission:bulk_approve_audits', 'role:admin,principal']);
        Route::post('/bulk-action', [ClassDataAuditController::class, 'bulkAction'])->name('bulk-action')
            ->middleware(['permission:bulk_approve_audits', 'role:admin,principal']);
        
        // AJAX endpoints for dynamic functionality
        Route::get('/ajax/filter-data', [ClassDataAuditController::class, 'filterData'])->name('ajax.filter')
            ->middleware('permission:view_audit_trails');
        Route::get('/ajax/user-search', [ClassDataAuditController::class, 'searchUsers'])->name('ajax.users')
            ->middleware('permission:delegate_audit_approvals');
    });
});

// Asset Depreciation Management Routes
Route::prefix('asset-depreciation')->name('asset-depreciation.')->middleware(['auth', 'role:admin,manager,accountant'])->group(function () {
    // Dashboard and Overview
    Route::get('/', [AssetDepreciationController::class, 'index'])->name('index');
    Route::get('/dashboard', [AssetDepreciationController::class, 'dashboard'])->name('dashboard');
    
    // Asset Depreciation Setup
    Route::get('/setup', [AssetDepreciationController::class, 'setup'])->name('setup');
    Route::post('/setup', [AssetDepreciationController::class, 'store'])->name('store');
    Route::get('/{assetDepreciation}/edit', [AssetDepreciationController::class, 'edit'])->name('edit');
    Route::put('/{assetDepreciation}', [AssetDepreciationController::class, 'update'])->name('update');
    Route::delete('/{assetDepreciation}', [AssetDepreciationController::class, 'destroy'])->name('destroy');
    
    // Depreciation Calculations
    Route::post('/run-calculations', [AssetDepreciationController::class, 'runCalculations'])->name('run-calculations');
    Route::post('/{assetDepreciation}/calculate', [AssetDepreciationController::class, 'calculateDepreciation'])->name('calculate');
    Route::get('/{assetDepreciation}/schedule', [AssetDepreciationController::class, 'generateSchedule'])->name('schedule');
    
    // Manual Entries and Adjustments
    Route::post('/{assetDepreciation}/manual-entry', [AssetDepreciationController::class, 'createManualEntry'])->name('manual-entry');
    Route::get('/{assetDepreciation}/history', [AssetDepreciationController::class, 'history'])->name('history');
    
    // Reports and Analytics
    Route::get('/reports', [AssetDepreciationController::class, 'reports'])->name('reports');
    Route::get('/export', [AssetDepreciationController::class, 'export'])->name('export');
    Route::get('/requires-attention', [AssetDepreciationController::class, 'requiresAttention'])->name('requires-attention');
    
    // Asset Details
    Route::get('/{assetDepreciation}', [AssetDepreciationController::class, 'show'])->name('show');
    
    // AJAX Endpoints
    Route::get('/available-assets', [AssetDepreciationController::class, 'availableAssets'])->name('available-assets');
    Route::get('/methods', [AssetDepreciationController::class, 'methods'])->name('methods');
});

// Budget vs Actual Reports Routes
Route::prefix('reports/budget-vs-actual')->name('reports.budget-vs-actual.')->middleware(['auth', 'role:admin,manager,accountant'])->group(function () {
    Route::get('/', [BudgetReportController::class, 'index'])->name('index');
    Route::get('/dashboard', [BudgetReportController::class, 'dashboard'])->name('dashboard');
    Route::get('/summary', [BudgetReportController::class, 'summary'])->name('summary');
    Route::get('/monthly-comparison', [BudgetReportController::class, 'monthlyComparison'])->name('monthly-comparison');
    Route::get('/variance-analysis', [BudgetReportController::class, 'varianceAnalysis'])->name('variance-analysis');
    Route::get('/department-performance', [BudgetReportController::class, 'departmentPerformance'])->name('department-performance');
    Route::get('/trend-analysis', [BudgetReportController::class, 'trendAnalysis'])->name('trend-analysis');
    Route::get('/risk-indicators', [BudgetReportController::class, 'riskIndicators'])->name('risk-indicators');
    Route::post('/generate-monthly', [BudgetReportController::class, 'generateMonthlyReport'])->name('generate-monthly');
    Route::get('/export', [BudgetReportController::class, 'export'])->name('export');
    Route::get('/quick-stats', [BudgetReportController::class, 'quickStats'])->name('quick-stats');
    Route::get('/departments', [BudgetReportController::class, 'departments'])->name('departments');
    Route::get('/years', [BudgetReportController::class, 'years'])->name('years');
    
    // Report Management
    Route::get('/reports', [BudgetReportController::class, 'reports'])->name('reports');
    Route::get('/reports/{report}', [BudgetReportController::class, 'showReport'])->name('reports.show');
    Route::put('/reports/{report}', [BudgetReportController::class, 'updateReport'])->name('reports.update');
    Route::delete('/reports/{report}', [BudgetReportController::class, 'deleteReport'])->name('reports.delete');
});


