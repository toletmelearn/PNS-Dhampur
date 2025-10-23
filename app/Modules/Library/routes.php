<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Library\Controllers\LibraryController;

Route::middleware(['role:admin,principal,librarian,teacher'])
    ->group(function () {
        Route::get('/', [LibraryController::class, 'index'])->name('index');
        Route::post('/book/{id}/issue', [LibraryController::class, 'issue'])->name('issue');
        Route::post('/issue/{id}/return', [LibraryController::class, 'return'])->name('return');
    });
