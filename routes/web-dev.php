<?php

use App\Http\Controllers\BudgetPlanController;

// in this file all routes are included which are only visible in STUFIS_FEATURE_BRANCH=dev

Route::middleware(['auth'])->group(function (): void {
    // Feature External
    // Route::livewire('antrag/create', \App\Livewire\CreateAntrag::class)->name('antrag.create');
    // Route::livewire('antrag/new-org', \App\Livewire\PTF15\CreateAntrag\NewOrganisation::class)->name('antrag.new-org');

    // Feature Budget Plans
    Route::get('plan', [BudgetPlanController::class, 'index'])->name('budget-plan.index');
    Route::get('plan/create', [BudgetPlanController::class, 'create'])->name('budget-plan.create');
    Route::get('plan/{plan_id}', [BudgetPlanController::class, 'show'])->name('budget-plan.view');
    Route::livewire('plan/{plan_id}/edit', 'pages::budget-plan.plan-edit')->name('budget-plan.edit');

    Route::livewire('year/create', 'pages::fiscal-year.edit-fiscal-year')->name('fiscal-year.create');
    Route::livewire('year/{year_id}', 'pages::fiscal-year.edit-fiscal-year')->name('fiscal-year.edit');

});
