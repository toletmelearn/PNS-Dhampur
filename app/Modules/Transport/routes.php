<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Transport\Controllers\TransportController;

Route::middleware(['role:admin,principal,transport_manager,parent,student'])
    ->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('index');
    });
