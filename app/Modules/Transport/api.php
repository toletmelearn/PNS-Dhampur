<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Transport\Controllers\TransportController;

Route::post('/bus/{id}/location', [TransportController::class, 'updateLocation']);
