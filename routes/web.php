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
});

// Authentication Routes (manual implementation)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Login logic here
})->name('login.post');

Route::post('/logout', function () {
    // Logout logic here
})->name('logout');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function () {
    // Registration logic here
})->name('register.post');

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
            
            // Export reports - requires export permission
            Route::get('/export', [BiometricAttendanceController::class, 'exportReport'])->name('export')
                ->middleware('permission:export_reports');
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
        
        // System audit and reports
        Route::get('/system/audit-report', [ClassTeacherPermissionController::class, 'systemAuditReport'])->name('system.audit-report');
        Route::get('/system/permissions-report', [ClassTeacherPermissionController::class, 'permissionsReport'])->name('system.permissions-report');
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
});


