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

use App\Http\Controllers\ViewChangelog;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {

    Route::get('/', function () {
        $sub = Auth::user()->getCommittees()->isEmpty() ? 'allgremium' : 'mygremium';
        $latestPlan = LegacyBudgetPlan::latest();

        return to_route('legacy.dashboard', ['sub' => $sub, 'hhp_id' => $latestPlan->id]);
    })->name('home');

    Route::get('bank-account/new', \App\Livewire\NewBankingAccount::class)->name('bank-account.new');
    Route::get('bank-account/import/manual', \App\Livewire\TransactionImportWire::class)->name('bank-account.import.csv');
    Route::get('bank-account/{account_id}/transaction/{transaction_id}', [\App\Http\Controllers\Legacy\TransactionView::class, 'view'])->name('bank-account.transaction');

    Route::get('profile', static fn () => redirect(config('stufis.profile_url')))->name('profile');

    Route::get('project/create', \App\Livewire\Project\EditProject::class)->name('project.create');
    Route::get('project/{project_id}', \App\Livewire\Project\ShowProject::class)->name('project.show');
    Route::get('project/{project_id}/history', \App\Livewire\Project\ShowProject::class)->name('project.history');
    Route::get('project/{project_id}/edit', \App\Livewire\Project\EditProject::class)->name('project.edit');
    Route::get('project/attachment/{attachment}/{fileName}', [\App\Http\Controllers\ProjectController::class, 'showAttachment'])->name('project.attachment');

});

// login routes
Route::get('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('auth/callback', [\App\Http\Controllers\AuthController::class, 'callback'])->name('login.callback');
Route::get('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// guest routes
Route::get('changelog', ViewChangelog::class)->name('changelog');
Route::get('about', static fn () => redirect(config('stufis.about_url')))->name('about');
Route::get('privacy', static fn () => redirect(config('stufis.privacy_url')))->name('privacy');
Route::get('terms', static fn () => redirect(config('stufis.terms_url')))->name('terms');
Route::get('git-repo', static fn () => redirect(config('stufis.git_url')))->name('git-repo');
Route::get('blog', static fn () => redirect(config('stufis.blog_url')))->name('blog');
Route::get('docs', static fn () => redirect(config('stufis.docs_url')))->name('docs');
