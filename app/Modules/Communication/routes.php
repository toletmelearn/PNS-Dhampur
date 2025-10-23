<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Communication\Controllers\CommunicationController;

Route::middleware(['role:admin,principal,teacher'])
    ->group(function () {
        Route::get('/', [CommunicationController::class, 'index'])->name('index');
        Route::post('/send', [CommunicationController::class, 'send'])->name('send');
    });
