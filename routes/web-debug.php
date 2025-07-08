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

Route::get('/font-weight', function () {
    return view('debug.font-weight', ['text' => 'Sphinx of black quartz, judge my vow.']);
});
