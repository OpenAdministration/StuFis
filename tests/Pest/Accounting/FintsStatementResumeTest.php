<?php

namespace Tests\Pest\Accounting;

use booking\konto\FintsConnectionHandler;
use DateTime;
use Fhp\Action\GetStatementOfAccount;
use Fhp\FinTs;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Illuminate\Http\Request;
use LogicException;
use Mockery;
use Monolog\Logger;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * FintsConnectionHandler reads/writes fints.* keys through request()->session(), which
 * Laravel only attaches to the request once StartSession has run on a real HTTP request.
 * These tests call the handler directly (no controller, no HTTP round trip), so a session
 * store has to be wired onto the request by hand - the same store the legacyPost() helper's
 * withSession() would otherwise attach for us.
 */
beforeEach(function (): void {
    $this->actingAs(cashOfficer());
    resetLegacySingletons();

    // HTMLPageRenderer::addFlash() (reached from the "belongs to something else" branch this
    // test exercises) reads the DEV constant, which a real request only gets because the
    // legacy dispatcher requires this file first. Nothing does that for a direct, non-HTTP
    // call into the handler, so it has to be pulled in by hand - once per process, like a real
    // request would.
    if (! defined('DEV')) {
        require base_path('legacy/lib/inc.all.php');
    }

    $session = $this->app['session']->driver();
    if (! $session->isStarted()) {
        $session->start();
    }
    $request = Request::create('/');
    $request->setLaravelSession($session);
    $this->app->instance('request', $request);
});

afterEach(function (): void {
    Mockery::close();
});

/**
 * A FintsConnectionHandler whose private $finTs is the given mock. FintsConnectionHandler::load()
 * needs a DB row and a real Fhp\FinTs connection to the bank, neither of which is available or
 * wanted here, so the handler is built without running its constructor at all.
 */
function handlerForResumeTest(FinTs $finTs, int $credentialId): FintsConnectionHandler
{
    $handler = new ReflectionClass(FintsConnectionHandler::class)->newInstanceWithoutConstructor();
    new ReflectionProperty(FintsConnectionHandler::class, 'finTs')->setValue($handler, $finTs);
    new ReflectionProperty(FintsConnectionHandler::class, 'credentialId')->setValue($handler, $credentialId);
    $handler->logger = new Logger('test');

    return $handler;
}

it('resumes a statement request that just got its TAN, instead of starting a new one', function (): void {
    // Regression for OP#608's fix (commit 927f0c18): saveAction() used to clear the
    // 'action-scope' session key the moment an action stopped needing a TAN - which also
    // covers the instant submitTan() finishes it. getStatements() then found no scope left to
    // match against, discarded the just-completed action as "belonging to something else" and
    // fired off a brand new statement request, which asked for another TAN. Against a bank
    // that requires one, a statement import could never complete.
    $credentialId = 501;
    $iban = 'DE02100100109307118603';
    $start = new DateTime('2026-01-01');
    $end = new DateTime('2026-01-31');

    $isDone = false;
    $action = Mockery::mock(GetStatementOfAccount::class);
    $action->shouldReceive('isDone')->andReturnUsing(function () use (&$isDone) {
        return $isDone;
    });
    $action->shouldReceive('needsTan')->andReturnUsing(function () use (&$isDone) {
        return ! $isDone;
    });
    $statement = new StatementOfAccount;
    $action->shouldReceive('getStatement')->andReturn($statement);

    $finTs = Mockery::mock(FinTs::class);
    $finTs->shouldReceive('persist')->andReturn('persisted-state');
    // saveAction() consults the selected TAN mode while an action is still pending, to seed
    // the decoupled-confirmation pacing state - irrelevant here, but the mock has to answer
    // something rather than the "no matching expectation" that an untouched mock would throw.
    $finTs->shouldReceive('getSelectedTanMode')->andReturn(null);
    // The whole point of the fix: no new request may be sent for an action that is already
    // resolved and merely waiting to be picked back up. andReturnUsing() (rather than plain
    // shouldNotReceive()) makes the violation the visible failure instead of whatever
    // half-initialised state a silently swallowed execute() call would leave behind.
    $finTs->shouldReceive('execute')->never()->andReturnUsing(function (): void {
        throw new LogicException('execute() must not run for an action that is already resolved');
    });
    $finTs->shouldReceive('submitTan')
        ->once()
        ->with($action, '123456')
        ->andReturnUsing(function () use (&$isDone): void {
            $isDone = true;
        });

    $handler = handlerForResumeTest($finTs, $credentialId);

    // Seed the state a first getStatements() call would have left behind: a pending action,
    // cached under the scope it was created for.
    new ReflectionMethod(FintsConnectionHandler::class, 'saveAction')->invoke($handler, $action);
    $scope = new ReflectionMethod(FintsConnectionHandler::class, 'statementScope')->invoke($handler, $iban, $start, $end);
    request()->session()->put("fints.$credentialId.action-scope", $scope);

    expect($handler->submitTan('123456'))->toBeTrue();

    expect($handler->getStatements($iban, $start, $end))->toBe($statement);
    // The completed action's own scope is consumed by its success branch inside
    // getStatements(), so nothing lingers behind for the next, unrelated statement request to
    // trip over.
    expect(request()->session()->has("fints.$credentialId.action-scope"))->toBeFalse();
});
