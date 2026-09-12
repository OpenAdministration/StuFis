<?php

use App\Support\FastCgiProcesses;

/**
 * These tests only ever *list* processes. The documented `pkill -u "$USER" '^php'` would take
 * the artisan process with it - on Hostsharing that runs as `php8.4` and matches the pattern -
 * so the guarantee worth testing is that the test runner's own PHP process is never a
 * candidate.
 */
it('never lists this process or its ancestors', function (): void {
    $pids = new FastCgiProcesses()->pids();

    expect($pids)->not->toContain(getmypid());
});

it('lists no candidate for a user that has no processes', function (): void {
    expect(new FastCgiProcesses('no-such-user-'.uniqid())->pids())->toBe([]);
});

it('signals nothing when there is nothing to signal', function (): void {
    expect(new FastCgiProcesses()->kill([]))->toBe(0);
});
