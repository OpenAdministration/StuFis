<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Login/callback/logout are browser flows and need the full "web" stack
| (session, cookies, CSRF). Back-Channel Logout is a server-to-server POST
| from the IdP with no browser session or CSRF token, so it is registered
| outside that group.
|
*/

Route::middleware('web')->group(function (): void {
    Route::get('auth/login', [AuthController::class, 'login'])->name('login');
    Route::get('auth/callback', [AuthController::class, 'callback'])->name('login.callback');
    Route::get('auth/logout', [AuthController::class, 'logout'])->name('logout');
});

// OIDC Back-Channel Logout receiver. Stateless server-to-server POST with a
// signed logout_token - no session, cookies, or CSRF token involved, and the
// controller returns plain responses so no HTML error view is rendered. Register
// "<APP_URL>/auth/backchannel-logout" as the client's back_channel_logout_uri in
// the IdP (StuMV: OIDC client edit screen).
Route::post('auth/backchannel-logout', [AuthController::class, 'backChannelLogout'])
    ->name('logout.backchannel');
