<?php

// in this file all routes which should be visible in debug=true should be listed

Route::middleware(['auth'])->group(function (): void {
    // Debugging
    Route::get('dev/groups', [\App\Http\Controllers\Dev::class, 'groups']);
});

Route::get('/session/destroy', function (): void {
    Session::flush();
    echo 'Session destroyed';
});
