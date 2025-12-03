<?php

// in this file all routes are included which are only visible in STUFIS_FEATURE_BRANCH=preview
Route::middleware(['auth'])->group(function (): void {
    Route::resource('project' , \App\Http\Controllers\ProjectController::class);
});
