<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\DataCleanupController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemMaintenanceController;

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

// Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Routes
Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('password.change');
Route::post('/password/change', [PasswordController::class, 'changePassword'])->name('password.update');

// Home Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Protected Routes (require authentication)
Route::middleware(['auth'])->group(function () {
    
    // Student Management
    Route::resource('students', StudentController::class);
    Route::get('students/{student}/profile', [StudentController::class, 'profile'])->name('students.profile');
    Route::post('students/{student}/upload-photo', [StudentController::class, 'uploadPhoto'])->name('students.upload-photo');
    
    // Teacher Management
    Route::resource('teachers', TeacherController::class);
    Route::get('teachers/{teacher}/profile', [TeacherController::class, 'profile'])->name('teachers.profile');
    
    // Class Management
    Route::resource('classes', ClassController::class);
    Route::get('classes/{class}/students', [ClassController::class, 'students'])->name('classes.students');
    
    // Subject Management
    Route::resource('subjects', SubjectController::class);
    
    // Exam Management
    Route::resource('exams', ExamController::class);
    Route::get('exams/{exam}/results', [ExamController::class, 'results'])->name('exams.results');
    Route::post('exams/{exam}/results', [ExamController::class, 'storeResults'])->name('exams.store-results');
    
    // Attendance Management
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('attendance/{class}/date/{date}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::put('attendance/{class}/date/{date}', [AttendanceController::class, 'update'])->name('attendance.update');
    
    // Fee Management
    Route::resource('fees', FeeController::class);
    Route::get('fees/{student}/payment', [FeeController::class, 'payment'])->name('fees.payment');
    Route::post('fees/{student}/payment', [FeeController::class, 'processPayment'])->name('fees.process-payment');
    
    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
    Route::get('reports/fees', [ReportController::class, 'fees'])->name('reports.fees');
    Route::get('reports/academic', [ReportController::class, 'academic'])->name('reports.academic');
    
    // User Management (Admin only)
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // Data Cleanup Routes
        Route::prefix('admin/data-cleanup')->name('admin.data-cleanup.')->group(function () {
            Route::get('/', [DataCleanupController::class, 'index'])->name('index');
            Route::get('/orphaned-records', [DataCleanupController::class, 'orphanedRecords'])->name('orphaned-records');
            Route::post('/orphaned-records/fix', [DataCleanupController::class, 'fixOrphanedRecords'])->name('fix-orphaned-records');
            Route::get('/duplicate-detection', [DataCleanupController::class, 'duplicateDetection'])->name('duplicate-detection');
            Route::post('/duplicate-detection/merge', [DataCleanupController::class, 'mergeDuplicates'])->name('merge-duplicates');
            Route::get('/consistency-checks', [DataCleanupController::class, 'consistencyChecks'])->name('consistency-checks');
            Route::post('/consistency-checks/fix', [DataCleanupController::class, 'fixConsistencyIssues'])->name('fix-consistency-issues');
            Route::get('/archive-purge', [DataCleanupController::class, 'archivePurge'])->name('archive-purge');
            Route::post('/archive-purge/archive', [DataCleanupController::class, 'archiveData'])->name('archive-data');
            Route::get('/archive/progress/{jobId}', [DataCleanupController::class, 'archiveProgress'])->name('archive-progress');
            Route::post('/archive/purge', [DataCleanupController::class, 'purgeArchive'])->name('purge-archive');
            Route::get('/archive/download/{id?}', [DataCleanupController::class, 'downloadArchive'])->name('download-archive');
        });
        
        // Configuration Management Routes
        Route::prefix('admin/configuration')->name('admin.configuration.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ConfigurationController::class, 'index'])->name('index');
            
            // System Settings
            Route::get('/system-settings', [\App\Http\Controllers\Admin\ConfigurationController::class, 'systemSettings'])->name('system-settings');
            Route::post('/system-settings', [\App\Http\Controllers\Admin\ConfigurationController::class, 'updateSystemSettings'])->name('update-system-settings');
            
            // Academic Years
            Route::get('/academic-years', [\App\Http\Controllers\Admin\ConfigurationController::class, 'academicYears'])->name('academic-years');
            Route::get('/academic-years/create', [\App\Http\Controllers\Admin\ConfigurationController::class, 'createAcademicYear'])->name('create-academic-year');
            Route::post('/academic-years', [\App\Http\Controllers\Admin\ConfigurationController::class, 'storeAcademicYear'])->name('store-academic-year');
            Route::get('/academic-years/{academicYear}/edit', [\App\Http\Controllers\Admin\ConfigurationController::class, 'editAcademicYear'])->name('edit-academic-year');
            Route::put('/academic-years/{academicYear}', [\App\Http\Controllers\Admin\ConfigurationController::class, 'updateAcademicYear'])->name('update-academic-year');
            Route::post('/academic-years/{academicYear}/set-current', [\App\Http\Controllers\Admin\ConfigurationController::class, 'setCurrentAcademicYear'])->name('set-current-academic-year');
            Route::post('/academic-years/{academicYear}/toggle', [\App\Http\Controllers\Admin\ConfigurationController::class, 'toggleAcademicYear'])->name('toggle-academic-year');
            
            // Holidays
            Route::get('/holidays', [\App\Http\Controllers\Admin\ConfigurationController::class, 'holidays'])->name('holidays');
            Route::get('/holidays/create', [\App\Http\Controllers\Admin\ConfigurationController::class, 'createHoliday'])->name('create-holiday');
            Route::post('/holidays', [\App\Http\Controllers\Admin\ConfigurationController::class, 'storeHoliday'])->name('store-holiday');
            Route::get('/holidays/{holiday}/edit', [\App\Http\Controllers\Admin\ConfigurationController::class, 'editHoliday'])->name('edit-holiday');
            Route::put('/holidays/{holiday}', [\App\Http\Controllers\Admin\ConfigurationController::class, 'updateHoliday'])->name('update-holiday');
            Route::post('/holidays/{holiday}/toggle', [\App\Http\Controllers\Admin\ConfigurationController::class, 'toggleHoliday'])->name('toggle-holiday');
            Route::delete('/holidays/{holiday}', [\App\Http\Controllers\Admin\ConfigurationController::class, 'deleteHoliday'])->name('delete-holiday');
            
            // Notification Templates
            Route::get('/notification-templates', [\App\Http\Controllers\Admin\ConfigurationController::class, 'notificationTemplates'])->name('notification-templates');
            Route::get('/notification-templates/create', [\App\Http\Controllers\Admin\ConfigurationController::class, 'createNotificationTemplate'])->name('create-notification-template');
            Route::post('/notification-templates', [\App\Http\Controllers\Admin\ConfigurationController::class, 'storeNotificationTemplate'])->name('store-notification-template');
            Route::get('/notification-templates/{notificationTemplate}/edit', [\App\Http\Controllers\Admin\ConfigurationController::class, 'editNotificationTemplate'])->name('edit-notification-template');
            Route::put('/notification-templates/{notificationTemplate}', [\App\Http\Controllers\Admin\ConfigurationController::class, 'updateNotificationTemplate'])->name('update-notification-template');
            Route::post('/notification-templates/{notificationTemplate}/toggle', [\App\Http\Controllers\Admin\ConfigurationController::class, 'toggleNotificationTemplate'])->name('toggle-notification-template');
            Route::delete('/notification-templates/{notificationTemplate}', [\App\Http\Controllers\Admin\ConfigurationController::class, 'deleteNotificationTemplate'])->name('delete-notification-template');
            Route::get('/notification-templates/{notificationTemplate}/preview', [\App\Http\Controllers\Admin\ConfigurationController::class, 'previewNotificationTemplate'])->name('preview-notification-template');
        });
        
        // Performance Monitoring Routes
        Route::prefix('admin/performance')->name('admin.performance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'index'])->name('index');
            Route::get('/system-health', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'systemHealth'])->name('system-health');
            Route::get('/metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'metrics'])->name('metrics');
            Route::get('/errors', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'errors'])->name('errors');
            Route::get('/activities', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'activities'])->name('activities');
            Route::post('/errors/{error}/resolve', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'resolveError'])->name('resolve-error');
            Route::get('/export', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'export'])->name('export');
            Route::get('/activities/export', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'exportActivities'])->name('activities.export');
        });
    });
    
    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // System Maintenance
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [SystemMaintenanceController::class, 'index'])->name('index');
        Route::post('/clear-cache', [SystemMaintenanceController::class, 'clearCache'])->name('clear-cache');
        Route::post('/optimize-database', [SystemMaintenanceController::class, 'optimizeDatabase'])->name('optimize-database');
        Route::get('/logs', [SystemMaintenanceController::class, 'viewLogs'])->name('logs');
        Route::post('/system-update', [SystemMaintenanceController::class, 'systemUpdate'])->name('system-update');
        Route::get('/system-info', [SystemMaintenanceController::class, 'getSystemInfo'])->name('system-info');
    });
});

// API Routes for AJAX calls
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('students/search', [StudentController::class, 'search'])->name('api.students.search');
    Route::get('teachers/search', [TeacherController::class, 'search'])->name('api.teachers.search');
    Route::get('classes/{class}/students', [ClassController::class, 'getStudents'])->name('api.classes.students');
    
    // Data Cleanup API Routes
    Route::middleware(['admin'])->prefix('data-cleanup')->group(function () {
        Route::get('/orphaned-students', [DataCleanupController::class, 'getOrphanedStudents']);
        Route::get('/duplicate-students', [DataCleanupController::class, 'getDuplicateStudents']);
        Route::get('/consistency-issues', [DataCleanupController::class, 'getConsistencyIssues']);
        Route::get('/archivable-data', [DataCleanupController::class, 'getArchivableData']);
    });
});


