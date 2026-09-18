<?php

use booking\konto\FintsConnectionHandler;
use Monolog\Logger;

/**
 * submitTan() takes the pending action out of the session and hands it to the bank. The
 * controller has no way of knowing whether one is still there - it simply calls submitTan()
 * whenever a 'tan' field was posted - so the check belongs here.
 *
 * The handler cannot be constructed without a FinTS session, and the guard returns before it
 * touches $finTs, so an uninitialised instance with just its two used properties is enough.
 */
function handlerWithoutSession(): FintsConnectionHandler
{
    // The guard flashes through HTMLPageRenderer, which reads the legacy DEV constant.
    if (! defined('DEV')) {
        require base_path('legacy/lib/inc.all.php');
    }

    $handler = new ReflectionClass(FintsConnectionHandler::class)->newInstanceWithoutConstructor();

    // The session is where the pending action would live; the handler reads it through
    // request()->session(), which throws outright when no store is attached.
    request()->setLaravelSession(resolve('session.store'));

    $logger = new ReflectionProperty(FintsConnectionHandler::class, 'logger');
    $logger->setValue($handler, new Logger('fints-test'));
    $credentialId = new ReflectionProperty(FintsConnectionHandler::class, 'credentialId');
    $credentialId->setValue($handler, 1);

    return $handler;
}

it('reports a TAN submitted without a pending action instead of failing with a TypeError', function (): void {
    // Regression: saveAction() drops the cached action as soon as it completes, so submitting
    // the TAN form twice - a reload of the POST, a second tab, a double click - or letting the
    // session expire on an open TAN page passed null to FinTs::submitTan(), which answered
    // with "Argument #1 ($action) must be of type Fhp\BaseAction, null given" and an error
    // page. What is pinned here is that the bank is never reached at all: the handler has no
    // FinTS session, so any path past the guard would throw rather than return false.
    expect(handlerWithoutSession()->submitTan('123456'))->toBeFalse();
});

it('guards the TAN submission the same way the decoupled confirmation always has', function (): void {
    // The two are siblings: both pull the pending action out of the session and both are
    // reachable with nothing pending. Only confirmDecoupledTan() used to say so.
    expect(handlerWithoutSession()->confirmDecoupledTan())->toBeFalse();
});
