<?php

// in this file all routes which should be visible with env:debug=true should be listed

use App\Http\Controllers\Dev;

Route::middleware(['auth'])->group(function (): void {
    // Debugging - renders regular layout -> login needed
    Route::get('/dev/groups', [Dev::class, 'groups']);
    Route::get('/dev/markdown', [Dev::class, 'markdown']);
    Route::get('/font-weight', [Dev::class, 'fontWeight']);
    Route::get('/middleware', [Dev::class, 'showMiddleware']);
});

Route::get('/session/destroy', static function (): void {
    Session::flush();
    echo 'Session destroyed';
});
