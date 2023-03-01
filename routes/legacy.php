<?php


use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function(){
    Route::get('menu/{sub}/', LegacyController::class)->name('menu');
    Route::get('konto/{hhp?}/{konto?}', LegacyController::class)->name('konto');
    Route::get('booking', LegacyController::class)->name('booking');
    Route::get('hhp', LegacyController::class)->name('hhp');
    Route::get('projekt/create', LegacyController::class)->name('new-project');

    Route::post('rest', LegacyController::class)->name('rest');

    Route::any('{path}', LegacyController::class)->where('path', '.*');
});
