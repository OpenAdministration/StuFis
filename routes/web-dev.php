<?php

// in this file all routes are included which are only visible in STUFIS_FEATURE_BRANCH=dev

Route::middleware(['auth'])->group(function (): void {
    // Feature External
    Route::get('antrag/create', \App\Livewire\CreateAntrag::class)->name('antrag.create');
    Route::get('antrag/new-org', \App\Livewire\CreateAntrag\NewOrganisation::class)->name('antrag.new-org');

    // Feature Budget Plans
    Route::get('plan', [\App\Http\Controllers\BudgetPlanController::class, 'index'])->name('budget-plan.index');
    Route::get('plan/create', [\App\Http\Controllers\BudgetPlanController::class, 'create'])->name('budget-plan.create');
    Route::get('plan/{plan_id}', [\App\Http\Controllers\BudgetPlanController::class, 'show'])->name('budget-plan.show');
    Route::get('plan/{plan_id}/edit', \App\Livewire\Budgetplan\BudgetPlanEdit::class)->name('budget-plan.edit');
    // Route::get('plan/{plan_id}/edit', \App\Http\Livewire\BudgetPlanLivewire::class)->name('budget-plan.edit');

});
