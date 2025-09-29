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

// -----------------------------
// PUBLIC ROUTES
// -----------------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // optional if you want registration

// -----------------------------
// PROTECTED ROUTES (Sanctum)
// -----------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Students
    Route::apiResource('students', StudentController::class);

    // Teachers
    Route::apiResource('teachers', TeacherController::class);

    // Classes
    Route::apiResource('classes', ClassModelController::class);

    // Attendance
    Route::apiResource('attendances', AttendanceController::class);

    // Fees
    Route::apiResource('fees', FeeController::class);
    Route::post('fees/{id}/pay', [FeePaymentController::class, 'pay']);
    Route::get('fees/{id}/receipt', [FeePaymentController::class, 'receipt']);

    // Salaries
    Route::apiResource('salaries', SalaryController::class);

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
});

Route::post('students/{student}/verify', [\App\Http\Controllers\StudentController::class, 'verify']);
