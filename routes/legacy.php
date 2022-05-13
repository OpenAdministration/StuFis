<?php

use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;


Route::any('{path}', LegacyController::class)->where('path', '.*');

