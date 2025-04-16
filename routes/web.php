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

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {

    // legacy is register later, so we cannot route(legacy.dashboard) there
    Route::redirect('/', 'menu/mygremium')->name('home');

    Route::get('konto/import/manual', \App\Livewire\TransactionImportWire::class)->name('konto.import.manual');

});

// login routes
Route::get('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('auth/callback', [\App\Http\Controllers\AuthController::class, 'callback'])->name('login.callback');
Route::get('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// guest routes
Route::get('about', static fn () => redirect(config('stufis.about_url')))->name('about');

Route::get('privacy', static fn () => redirect(config('stufis.privacy_url')))->name('privacy');

Route::get('terms', static fn () => redirect(config('stufis.terms_url')))->name('terms');

Route::get('git-repo', static fn () => redirect(config('stufis.git_url')))->name('git-repo');

Route::get('blog', static fn () => redirect(config('stufis.blog_url')))->name('blog');

Route::get('docs', static fn () => redirect(config('stufis.docs_url')))->name('docs');
