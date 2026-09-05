<?php

namespace Tests\Pest\Accounting;

use booking\konto\FintsConnectionHandler;
use Fhp\BaseAction;
use Fhp\CurlException;
use Fhp\FinTs;
use Fhp\Model\TanMode;
use Illuminate\Http\Request;
use LogicException;
use Mockery;
use Monolog\Logger;
use ReflectionClass;
use ReflectionProperty;

/**
 * Same reasoning as FintsStatementResumeTest: these tests call FintsConnectionHandler directly
 * (no controller, no HTTP round trip), so the session it reads/writes through request()
 * has to be wired onto the request by hand, and legacy/lib/inc.all.php has to be pulled in once
 * so HTMLPageRenderer::addFlash() finds its DEV constant.
 */
beforeEach(function (): void {
    $this->actingAs(cashOfficer());
    resetLegacySingletons();

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
 * A FintsConnectionHandler whose private $finTs is the given mock, built without running the
 * constructor - see FintsStatementResumeTest for why.
 */
function handlerForDecoupledTest(FinTs $finTs, int $credentialId): FintsConnectionHandler
{
    $handler = new ReflectionClass(FintsConnectionHandler::class)->newInstanceWithoutConstructor();
    new ReflectionProperty(FintsConnectionHandler::class, 'finTs')->setValue($handler, $finTs);
    new ReflectionProperty(FintsConnectionHandler::class, 'credentialId')->setValue($handler, $credentialId);
    $handler->logger = new Logger('test');

    return $handler;
}

/**
 * A decoupled TanMode mock with sensible defaults for the methods confirmDecoupledTan() may
 * touch. Individual tests override getMaxDecoupledChecks()/getPeriodicDecoupledCheckDelaySeconds()
 * where the scenario cares about them.
 */
function decoupledTanModeMock(int $maxChecks = 0): TanMode
{
    $tanMode = Mockery::mock(TanMode::class);
    $tanMode->shouldReceive('isDecoupled')->andReturn(true);
    $tanMode->shouldReceive('getMaxDecoupledChecks')->andReturn($maxChecks);
    $tanMode->shouldReceive('getFirstDecoupledCheckDelaySeconds')->andReturn(60);
    $tanMode->shouldReceive('getPeriodicDecoupledCheckDelaySeconds')->andReturn(30);

    return $tanMode;
}

/**
 * The most recently flashed message, as plain text (the alert renders itself as HTML via
 * AbstractHtmlTag::__toString()).
 */
function lastDecoupledFlashText(): string
{
    $flashes = request()->session()->get('flash', []);
    expect($flashes)->not->toBeEmpty();

    // body() escapes for HTML output, so an umlaut comes back as "&auml;" rather than "ä" -
    // decode it the same way legacyHtml() in tests/Pest.php does for the same reason.
    return html_entity_decode((string) end($flashes), ENT_QUOTES);
}

it('completes the action once the bank confirms the approval', function (): void {
    $credentialId = 601;
    $action = Mockery::mock(BaseAction::class);
    $action->shouldReceive('needsTan')->andReturn(false);
    $action->shouldReceive('isDone')->andReturn(true);

    $tanMode = decoupledTanModeMock(maxChecks: 3);

    $finTs = Mockery::mock(FinTs::class);
    $finTs->shouldReceive('getSelectedTanMode')->andReturn($tanMode);
    $finTs->shouldReceive('checkDecoupledSubmission')->once()->with($action)->andReturn(true);
    $finTs->shouldReceive('persist')->once()->andReturn('persisted-after-confirm');

    $handler = handlerForDecoupledTest($finTs, $credentialId);
    request()->session()->put("fints.$credentialId.action", $action);
    request()->session()->put("fints.$credentialId.decoupled-checks", 0);
    request()->session()->put("fints.$credentialId.decoupled-next-check", time() - 1);

    expect($handler->confirmDecoupledTan())->toBeTrue();
    // saveAction() re-persisted the now-completed FinTs state and dropped the finished action -
    // there is nothing left to confirm again.
    expect(request()->session()->get("fints.$credentialId.action"))->toBeNull();
    expect(request()->session()->get("fints.$credentialId.persist"))->toBe('persisted-after-confirm');
});

it('reports that the bank has not seen the approval yet', function (): void {
    $credentialId = 602;
    $action = Mockery::mock(BaseAction::class);
    $action->shouldReceive('needsTan')->andReturn(true);
    $action->shouldReceive('isDone')->andReturn(false);

    $tanMode = decoupledTanModeMock(maxChecks: 3);

    $finTs = Mockery::mock(FinTs::class);
    $finTs->shouldReceive('getSelectedTanMode')->andReturn($tanMode);
    $finTs->shouldReceive('checkDecoupledSubmission')->once()->with($action)->andReturn(false);
    $finTs->shouldReceive('persist')->andReturn('persisted-still-waiting');

    $handler = handlerForDecoupledTest($finTs, $credentialId);
    request()->session()->put("fints.$credentialId.action", $action);
    request()->session()->put("fints.$credentialId.decoupled-checks", 0);
    request()->session()->put("fints.$credentialId.decoupled-next-check", time() - 1);

    expect($handler->confirmDecoupledTan())->toBeFalse();
    expect(lastDecoupledFlashText())->toContain('noch nicht gesehen');
    // The action is still pending, so it has to stay resumable, and the used-check counter
    // moves on so the attempt limit is eventually reached even if the bank never confirms.
    expect(request()->session()->get("fints.$credentialId.action"))->toBe($action);
    expect(request()->session()->get("fints.$credentialId.decoupled-checks"))->toBe(1);
});

it('refuses to ask the bank again before the earliest allowed check', function (): void {
    $credentialId = 603;
    $action = Mockery::mock(BaseAction::class);

    $tanMode = decoupledTanModeMock(maxChecks: 3);

    $finTs = Mockery::mock(FinTs::class);
    $finTs->shouldReceive('getSelectedTanMode')->andReturn($tanMode);
    // The whole point of the pacing guard: no request may go out before the bank's earliest
    // allowed check, no matter how often the user clicks the button.
    $finTs->shouldReceive('checkDecoupledSubmission')->never()->andReturnUsing(function (): void {
        throw new LogicException('checkDecoupledSubmission() must not run before the earliest allowed check');
    });
    $finTs->shouldNotReceive('persist');

    $handler = handlerForDecoupledTest($finTs, $credentialId);
    request()->session()->put("fints.$credentialId.action", $action);
    request()->session()->put("fints.$credentialId.decoupled-checks", 0);
    request()->session()->put("fints.$credentialId.decoupled-next-check", time() + 120);

    expect($handler->confirmDecoupledTan())->toBeFalse();
    expect(lastDecoupledFlashText())->toContain('Sekunden');
});

it('drops the pending action once the allowed number of checks is used up', function (): void {
    $credentialId = 604;
    $action = Mockery::mock(BaseAction::class);
    // Hit only if the (missing) attempt-limit guard lets the drop fall through to saveAction()
    // with the action still attached instead of null.
    $action->shouldReceive('needsTan')->andReturn(true)->byDefault();
    $action->shouldReceive('isDone')->andReturn(false)->byDefault();

    $tanMode = decoupledTanModeMock(maxChecks: 3);

    $finTs = Mockery::mock(FinTs::class);
    $finTs->shouldReceive('getSelectedTanMode')->andReturn($tanMode);
    $finTs->shouldReceive('checkDecoupledSubmission')->never()->andReturnUsing(function (): void {
        throw new LogicException('checkDecoupledSubmission() must not run once the attempt limit is reached');
    });
    $finTs->shouldReceive('persist')->once()->andReturn('persisted-after-drop');

    $handler = handlerForDecoupledTest($finTs, $credentialId);
    request()->session()->put("fints.$credentialId.action", $action);
    request()->session()->put("fints.$credentialId.decoupled-checks", 3);
    request()->session()->put("fints.$credentialId.decoupled-next-check", time() - 1);

    expect($handler->confirmDecoupledTan())->toBeFalse();
    expect(lastDecoupledFlashText())->toContain('nicht rechtzeitig bestätigt');
    expect(request()->session()->get("fints.$credentialId.action"))->toBeNull();
});

it('reports a connection failure instead of letting the exception escape', function (): void {
    $credentialId = 605;
    $action = Mockery::mock(BaseAction::class);

    $tanMode = decoupledTanModeMock(maxChecks: 0);

    $finTs = Mockery::mock(FinTs::class);
    $finTs->shouldReceive('getSelectedTanMode')->andReturn($tanMode);
    $finTs->shouldReceive('checkDecoupledSubmission')
        ->once()
        ->with($action)
        ->andThrow(new CurlException('Verbindung fehlgeschlagen', null));
    $finTs->shouldNotReceive('persist');

    $handler = handlerForDecoupledTest($finTs, $credentialId);
    request()->session()->put("fints.$credentialId.action", $action);
    request()->session()->put("fints.$credentialId.decoupled-next-check", time() - 1);

    expect($handler->confirmDecoupledTan())->toBeFalse();
    expect(lastDecoupledFlashText())->toContain('Konnte keine Verbindung zum Server aufbauen');
});
