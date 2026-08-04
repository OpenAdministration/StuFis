<?php

// in this file all routes are included which are only visible in STUFIS_FEATURE_BRANCH=dev

Route::middleware(['auth'])->group(function (): void {
    // Feature External
    // Route::livewire('antrag/create', \App\Livewire\CreateAntrag::class)->name('antrag.create');
    // Route::livewire('antrag/new-org', \App\Livewire\PTF15\CreateAntrag\NewOrganisation::class)->name('antrag.new-org');

});
