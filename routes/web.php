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

use App\Http\Controllers\AdminConfigPage;
use App\Http\Controllers\ViewChangelog;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {

    Route::get('/', function () {
        $sub = Auth::user()->getCommittees()->isEmpty() ? 'allgremium' : 'mygremium';
        $latestPlan = LegacyBudgetPlan::latest();

        return to_route('legacy.dashboard', ['sub' => $sub, 'hhp_id' => $latestPlan->id]);
    })->name('home');

    Route::get('config', [AdminConfigPage::class, 'render'] )->name('config');

    Route::livewire('bank-account/new', 'pages::new-banking-account')->name('bank-account.new');
    Route::livewire('bank-account/import/manual', 'pages::bank.csv-import')->name('bank-account.import.csv');
    Route::get('bank-account/{account_id}/transaction/{transaction_id}', [\App\Http\Controllers\Legacy\TransactionView::class, 'view'])->name('bank-account.transaction');

    Route::get('profile', static fn () => redirect(config('stufis.profile_url')))->name('profile');

    Route::livewire('datev/export', 'pages::datev-export')->name('datev.export');

    Route::livewire('project/create', 'pages::project.edit-project')->name('project.create');
    Route::livewire('project/{project_id}', 'pages::project.show-project')->name('project.show');
    Route::livewire('project/{project_id}/history', 'pages::project.show-project')->name('project.history');
    Route::livewire('project/{project_id}/edit', 'pages::project.edit-project')->name('project.edit');
    Route::get('project/attachment/{attachment}/{fileName}', [\App\Http\Controllers\ProjectController::class, 'showAttachment'])->name('project.attachment');

    Route::permanentRedirect('projekt/create', '/project/create');
    Route::permanentRedirect('projekt/{project_id}', '/project/{project_id}');
    Route::permanentRedirect('projekt/{project_id}/edit', '/project/{project_id}/edit');
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
