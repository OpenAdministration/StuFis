<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\BudgetPlanController;
use App\Http\Controllers\BudgetPlanExportController;
use App\Http\Controllers\DatevExportController;
use App\Http\Controllers\Legacy\TransactionView;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ViewChangelog;
use App\Models\BudgetPlan;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {

    Route::get('/', function () {
        $latestPlan = BudgetPlan::newest();

        // Fresh install with no budget plan yet: send the user to the plan overview.
        if (! $latestPlan instanceof BudgetPlan) {
            return to_route('budget-plan.index');
        }

        $sub = Auth::user()->getCommittees()->isEmpty() ? 'allgremium' : 'mygremium';

        return to_route('legacy.dashboard', ['sub' => $sub, 'hhp_id' => $latestPlan->id]);
    })->name('home');

    Route::livewire('config', 'pages::settings')->name('config');

    Route::livewire('bank-account/new', 'pages::new-banking-account')->name('bank-account.new');
    Route::livewire('bank-account/import/manual', 'pages::bank.manual-import')->name('bank-account.import.manual');
    Route::get('bank-account/{account_id}/transaction/{transaction_id}', [TransactionView::class, 'view'])->name('bank-account.transaction');

    Route::get('profile', static fn () => redirect(config('stufis.profile_url')))->name('profile');

    Route::livewire('datev/export', 'pages::datev-export')->name('datev.export');
    Route::get('datev/export/download', [DatevExportController::class, 'download'])
        ->middleware('signed')
        ->name('datev.export.download');

    Route::livewire('project/create', 'pages::project.edit-project')->name('project.create');
    Route::livewire('project/{project_id}', 'pages::project.show-project')->name('project.show');
    Route::livewire('project/{project_id}/history', 'pages::project.show-project')->name('project.history');
    Route::livewire('project/{project_id}/edit', 'pages::project.edit-project')->name('project.edit');
    Route::get('project/attachment/{attachment}/{fileName}', [ProjectController::class, 'showAttachment'])->name('project.attachment');
    Route::get('project/attachment/{attachment}/{fileName}/download', [ProjectController::class, 'downloadAttachment'])->name('project.attachment.download');

    Route::permanentRedirect('projekt/create', '/project/create');
    Route::permanentRedirect('projekt/{project_id}', '/project/{project_id}');
    Route::permanentRedirect('projekt/{project_id}/edit', '/project/{project_id}/edit');

    // Feature Budget Plans
    Route::get('plan', [BudgetPlanController::class, 'index'])->name('budget-plan.index');
    Route::livewire('plan/create', 'pages::budget-plan.plan-create')->name('budget-plan.create');
    Route::livewire('plan/{plan_id}', 'pages::budget-plan.plan-view')->name('budget-plan.view');
    Route::livewire('plan/{plan_id}/edit', 'pages::budget-plan.plan-edit')->name('budget-plan.edit');
    Route::livewire('plan/{plan_id}/amendment/{amendment_id}/edit', 'pages::budget-plan.amendment-edit')->name('budget-plan.amendment.edit');
    Route::livewire('plan/{plan_id}/item/{item_id}', 'pages::budget-plan.item-view')->name('budget-plan.item.view');
    Route::get('plan/{plan_id}/export/{filetype}', [BudgetPlanExportController::class, 'download'])->name('budget-plan.export');
    Route::livewire('year/create', 'pages::fiscal-year.edit-fiscal-year')->name('fiscal-year.create');
    Route::livewire('year/{year_id}', 'pages::fiscal-year.edit-fiscal-year')->name('fiscal-year.edit');
});

// guest routes
Route::get('changelog', ViewChangelog::class)->name('changelog');
Route::get('about', static fn () => redirect(config('stufis.about_url')))->name('about');
Route::get('privacy', static fn () => redirect(config('stufis.privacy_url')))->name('privacy');
Route::get('terms', static fn () => redirect(config('stufis.terms_url')))->name('terms');
Route::get('git-repo', static fn () => redirect(config('stufis.git_url')))->name('git-repo');
Route::get('blog', static fn () => redirect(config('stufis.blog_url')))->name('blog');
Route::get('docs', static fn () => redirect(config('stufis.docs_url')))->name('docs');
