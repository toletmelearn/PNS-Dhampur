<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Student\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| Student Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Student module. These routes are loaded
| by the RouteServiceProvider and are assigned to the "web" middleware group.
|
*/

// Web Routes for Student Module
Route::middleware(['web', 'auth'])->prefix('students')->name('students.')->group(function () {
    
    // Main CRUD routes
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::get('/create', [StudentController::class, 'create'])->name('create');
    Route::post('/', [StudentController::class, 'store'])->name('store');
    Route::get('/{id}', [StudentController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [StudentController::class, 'edit'])->name('edit');
    Route::put('/{id}', [StudentController::class, 'update'])->name('update');
    Route::delete('/{id}', [StudentController::class, 'destroy'])->name('destroy');
    
    // Academic records
    Route::get('/{id}/academic-records', [StudentController::class, 'academicRecords'])->name('academic-records');
    
    // Bulk operations
    Route::post('/bulk-action', [StudentController::class, 'bulkAction'])->name('bulk-action');
    
    // Additional student-specific routes
    Route::prefix('{id}')->group(function () {
        // Attendance routes
        Route::get('/attendance', [StudentController::class, 'attendance'])->name('attendance');
        Route::get('/attendance/report', [StudentController::class, 'attendanceReport'])->name('attendance.report');
        
        // Exam and results routes
        Route::get('/results', [StudentController::class, 'results'])->name('results');
        Route::get('/results/{exam_id}', [StudentController::class, 'examResult'])->name('results.exam');
        Route::get('/report-card', [StudentController::class, 'reportCard'])->name('report-card');
        Route::get('/report-card/download', [StudentController::class, 'downloadReportCard'])->name('report-card.download');
        
        // Fee routes
        Route::get('/fees', [StudentController::class, 'fees'])->name('fees');
        Route::get('/fees/history', [StudentController::class, 'feeHistory'])->name('fees.history');
        Route::get('/fees/receipt/{transaction_id}', [StudentController::class, 'feeReceipt'])->name('fees.receipt');
        
        // Documents and media
        Route::get('/documents', [StudentController::class, 'documents'])->name('documents');
        Route::post('/documents/upload', [StudentController::class, 'uploadDocument'])->name('documents.upload');
        Route::delete('/documents/{document_id}', [StudentController::class, 'deleteDocument'])->name('documents.delete');
        Route::post('/photo/upload', [StudentController::class, 'uploadPhoto'])->name('photo.upload');
        Route::delete('/photo', [StudentController::class, 'deletePhoto'])->name('photo.delete');
        
        // Parent/Guardian routes
        Route::get('/parent', [StudentController::class, 'parentInfo'])->name('parent');
        Route::put('/parent', [StudentController::class, 'updateParent'])->name('parent.update');
        
        // Timeline and activities
        Route::get('/timeline', [StudentController::class, 'timeline'])->name('timeline');
        Route::get('/activities', [StudentController::class, 'activities'])->name('activities');
        
        // Health and medical
        Route::get('/medical', [StudentController::class, 'medical'])->name('medical');
        Route::put('/medical', [StudentController::class, 'updateMedical'])->name('medical.update');
        
        // Disciplinary records
        Route::get('/disciplinary', [StudentController::class, 'disciplinary'])->name('disciplinary');
        Route::post('/disciplinary', [StudentController::class, 'addDisciplinaryRecord'])->name('disciplinary.add');
        
        // Transportation
        Route::get('/transportation', [StudentController::class, 'transportation'])->name('transportation');
        Route::put('/transportation', [StudentController::class, 'updateTransportation'])->name('transportation.update');
    });
    
    // Utility routes
    Route::get('/search/suggestions', [StudentController::class, 'searchSuggestions'])->name('search.suggestions');
    Route::get('/export', [StudentController::class, 'export'])->name('export');
    Route::post('/import', [StudentController::class, 'import'])->name('import');
    Route::get('/import/template', [StudentController::class, 'importTemplate'])->name('import.template');
    
    // Reports and analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/summary', [StudentController::class, 'summaryReport'])->name('summary');
        Route::get('/class-wise', [StudentController::class, 'classWiseReport'])->name('class-wise');
        Route::get('/attendance-summary', [StudentController::class, 'attendanceSummaryReport'])->name('attendance-summary');
        Route::get('/performance', [StudentController::class, 'performanceReport'])->name('performance');
        Route::get('/fee-defaulters', [StudentController::class, 'feeDefaultersReport'])->name('fee-defaulters');
        Route::get('/birthday-list', [StudentController::class, 'birthdayReport'])->name('birthday-list');
    });
});

// API Routes for Student Module
Route::middleware(['api', 'auth:sanctum'])->prefix('api/students')->name('api.students.')->group(function () {
    
    // Main API endpoints
    Route::apiResource('/', StudentController::class)->parameters(['' => 'id']);
    
    // Bulk operations API
    Route::post('/bulk-action', [StudentController::class, 'bulkAction'])->name('bulk-action');
    
    // Search and filter APIs
    Route::get('/search', [StudentController::class, 'search'])->name('search');
    Route::get('/filter', [StudentController::class, 'filter'])->name('filter');
    Route::get('/suggestions', [StudentController::class, 'suggestions'])->name('suggestions');
    
    // Academic APIs
    Route::prefix('{id}')->group(function () {
        Route::get('/academic-records', [StudentController::class, 'academicRecords'])->name('academic-records');
        Route::get('/attendance', [StudentController::class, 'attendanceApi'])->name('attendance');
        Route::get('/results', [StudentController::class, 'resultsApi'])->name('results');
        Route::get('/fees', [StudentController::class, 'feesApi'])->name('fees');
        Route::get('/timeline', [StudentController::class, 'timelineApi'])->name('timeline');
    });
    
    // Statistics and analytics APIs
    Route::get('/statistics', [StudentController::class, 'statistics'])->name('statistics');
    Route::get('/analytics/class-distribution', [StudentController::class, 'classDistribution'])->name('analytics.class-distribution');
    Route::get('/analytics/gender-distribution', [StudentController::class, 'genderDistribution'])->name('analytics.gender-distribution');
    Route::get('/analytics/age-distribution', [StudentController::class, 'ageDistribution'])->name('analytics.age-distribution');
    Route::get('/analytics/attendance-trends', [StudentController::class, 'attendanceTrends'])->name('analytics.attendance-trends');
    Route::get('/analytics/performance-trends', [StudentController::class, 'performanceTrends'])->name('analytics.performance-trends');
    
    // Export APIs
    Route::get('/export/csv', [StudentController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/excel', [StudentController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [StudentController::class, 'exportPdf'])->name('export.pdf');
    
    // Import APIs
    Route::post('/import/csv', [StudentController::class, 'importCsv'])->name('import.csv');
    Route::post('/import/excel', [StudentController::class, 'importExcel'])->name('import.excel');
    Route::get('/import/template', [StudentController::class, 'importTemplateApi'])->name('import.template');
    Route::post('/import/validate', [StudentController::class, 'validateImport'])->name('import.validate');
});

// Public API routes (no authentication required)
Route::middleware(['api'])->prefix('api/public/students')->name('api.public.students.')->group(function () {
    
    // Student verification (for parents/guardians)
    Route::post('/verify', [StudentController::class, 'verifyStudent'])->name('verify');
    Route::get('/{student_id}/basic-info', [StudentController::class, 'basicInfo'])->name('basic-info');
    
    // Public reports (with limited data)
    Route::get('/class-strength', [StudentController::class, 'classStrength'])->name('class-strength');
    Route::get('/admission-statistics', [StudentController::class, 'admissionStatistics'])->name('admission-statistics');
});

// Admin-only routes
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin/students')->name('admin.students.')->group(function () {
    
    // Advanced management
    Route::get('/management', [StudentController::class, 'management'])->name('management');
    Route::post('/promote-class', [StudentController::class, 'promoteClass'])->name('promote-class');
    Route::post('/transfer-students', [StudentController::class, 'transferStudents'])->name('transfer');
    Route::post('/graduate-students', [StudentController::class, 'graduateStudents'])->name('graduate');
    
    // Data management
    Route::get('/data-cleanup', [StudentController::class, 'dataCleanup'])->name('data-cleanup');
    Route::post('/merge-duplicates', [StudentController::class, 'mergeDuplicates'])->name('merge-duplicates');
    Route::get('/audit-log', [StudentController::class, 'auditLog'])->name('audit-log');
    
    // System reports
    Route::get('/system-reports', [StudentController::class, 'systemReports'])->name('system-reports');
    Route::get('/data-integrity', [StudentController::class, 'dataIntegrity'])->name('data-integrity');
});

// Teacher-specific routes
Route::middleware(['web', 'auth', 'role:teacher'])->prefix('teacher/students')->name('teacher.students.')->group(function () {
    
    // Class-specific student management
    Route::get('/my-classes', [StudentController::class, 'myClassStudents'])->name('my-classes');
    Route::get('/class/{class_id}', [StudentController::class, 'classStudents'])->name('class');
    Route::get('/class/{class_id}/section/{section}', [StudentController::class, 'sectionStudents'])->name('section');
    
    // Quick actions for teachers
    Route::post('/mark-attendance', [StudentController::class, 'markAttendance'])->name('mark-attendance');
    Route::post('/add-remarks', [StudentController::class, 'addRemarks'])->name('add-remarks');
    Route::get('/attendance-sheet/{class_id}', [StudentController::class, 'attendanceSheet'])->name('attendance-sheet');
});

// Parent/Guardian routes
Route::middleware(['web', 'auth', 'role:parent'])->prefix('parent/students')->name('parent.students.')->group(function () {
    
    // Parent can only view their children
    Route::get('/my-children', [StudentController::class, 'myChildren'])->name('my-children');
    Route::get('/{id}/profile', [StudentController::class, 'childProfile'])->name('child-profile');
    Route::get('/{id}/attendance', [StudentController::class, 'childAttendance'])->name('child-attendance');
    Route::get('/{id}/results', [StudentController::class, 'childResults'])->name('child-results');
    Route::get('/{id}/fees', [StudentController::class, 'childFees'])->name('child-fees');
    Route::get('/{id}/timeline', [StudentController::class, 'childTimeline'])->name('child-timeline');
    
    // Parent can update limited information
    Route::put('/{id}/emergency-contact', [StudentController::class, 'updateEmergencyContact'])->name('update-emergency-contact');
    Route::put('/{id}/parent-info', [StudentController::class, 'updateParentInfo'])->name('update-parent-info');
});