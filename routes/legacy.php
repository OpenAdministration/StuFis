<?php


use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function(){
    Route::get('menu/{sub}/', [LegacyController::class, 'render'])->name('menu');
    Route::get('konto/{hhp?}/{konto?}', [LegacyController::class, 'render'])->name('konto');
    Route::get('booking', [LegacyController::class, 'render'])->name('booking');
    Route::get('hhp', [LegacyController::class, 'render'])->name('hhp');
    Route::get('projekt/create', [LegacyController::class, 'render'])->name('new-project');
    Route::get('files/get/{auslagen_id}/{hash}', [LegacyController::class, 'renderFile'])->name('get-file');
    Route::post('rest', [LegacyController::class, 'render'])->name('rest');
    Route::get('auslagen/{auslagen_id}/{fileHash}/{filename}.pdf', [LegacyController::class, 'deliverFile']);
    // catch all
    Route::any('{path}', [LegacyController::class, 'render'])->where('path', '.*');
});
