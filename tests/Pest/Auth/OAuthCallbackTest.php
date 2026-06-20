<?php

namespace Tests\Pest\Auth;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * A stale/lost OAuth state on the Socialite callback throws InvalidStateException. It is a
 * user-flow condition (expired login, back button, reused callback URL, dropped session
 * cookie), not a server error — the handler in bootstrap/app.php must turn it into a
 * redirect back to login (fresh state) instead of a 500, and must not log it as an error.
 */
it('renders an OAuth InvalidStateException as a redirect to login', function (): void {
    $handler = resolve(ExceptionHandler::class);

    $response = $handler->render(request(), new InvalidStateException);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toBe(route('login'));
});

it('does not report an OAuth InvalidStateException', function (): void {
    $handler = resolve(ExceptionHandler::class);

    expect($handler->shouldReport(new InvalidStateException))->toBeFalse();
});
