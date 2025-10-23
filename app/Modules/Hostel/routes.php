<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Hostel\Controllers\HostelController;

Route::middleware(['role:admin,principal,hostel_warden'])
    ->group(function () {
        Route::get('/', [HostelController::class, 'index'])->name('index');
        Route::post('/room/{id}/allocate', [HostelController::class, 'allocate'])->name('allocate');
        Route::post('/allocation/{id}/vacate', [HostelController::class, 'vacate'])->name('vacate');
    });
