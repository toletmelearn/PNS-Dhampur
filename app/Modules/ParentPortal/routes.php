<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ParentPortal\Controllers\ParentPortalController;

// Parent Portal Web Routes
Route::middleware(['web', 'auth', 'verified', 'module:parentportal', 'role:parent,admin,principal,teacher'])
    ->prefix('parent-portal')
    ->name('parentportal.')
    ->group(function () {
        Route::get('/', [ParentPortalController::class, 'index'])->name('dashboard');
        Route::get('/child/{id}/progress', [ParentPortalController::class, 'childProgress'])->name('child.progress');
    });
