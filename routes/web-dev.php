<?php

use App\Http\Controllers\BudgetPlanController;
use App\Livewire\BudgetPlan\BudgetPlanEdit;
use App\Livewire\CreateAntrag;
use App\Livewire\CreateAntrag\NewOrganisation;

// in this file all routes are included which are only visible in STUFIS_FEATURE_BRANCH=dev

Route::middleware(['auth'])->group(function (): void {
    // Feature External
    Route::get('antrag/create', CreateAntrag::class)->name('antrag.create');
    Route::get('antrag/new-org', NewOrganisation::class)->name('antrag.new-org');

    // Feature Budget Plans
    Route::get('plan', [BudgetPlanController::class, 'index'])->name('budget-plan.index');
    Route::get('plan/create', [BudgetPlanController::class, 'create'])->name('budget-plan.create');
    Route::get('plan/{plan_id}', [BudgetPlanController::class, 'show'])->name('budget-plan.view');
    Route::get('plan/{plan_id}/edit', BudgetPlanEdit::class)->name('budget-plan.edit');
    // Route::get('plan/{plan_id}/edit', \App\Http\Livewire\BudgetPlanLivewire::class)->name('budget-plan.edit');

});
