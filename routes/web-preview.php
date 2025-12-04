<?php

// in this file all routes are included which are only visible in STUFIS_FEATURE_BRANCH=preview
Route::middleware(['auth'])->group(function (): void {
    Route::get('project/{project_id}' , \App\Livewire\Project\ShowProject::class)->name('project.show');
    Route::get('project/{project_id}/history' , \App\Livewire\Project\ShowProject::class)->name('project.history');
    // Route::resource('project' , \App\Http\Controllers\ProjectController::class);
});
