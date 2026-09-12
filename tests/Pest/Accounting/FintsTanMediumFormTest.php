<?php

namespace Tests\Pest\Accounting;

use booking\konto\FintsConnectionHandler;
use booking\konto\FintsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Renders the TAN-medium picker without a live bank dialog behind it: the controller is built
 * without its constructor and handed a stubbed connection handler, the route info the action
 * reads, and an empty POST request so it renders the form instead of saving a pick.
 */
function tanMediumMarkup(array $tanMedia = ['Handy' => '[Handy] +49 123']): string
{
    if (! defined('DEV')) {
        require base_path('legacy/lib/inc.all.php');
    }

    // renderNonce() writes csrf_token(), which needs a started session.
    Session::start();

    $handler = Mockery::mock(FintsConnectionHandler::class);
    $handler->shouldReceive('getTanMedias')->andReturn($tanMedia);

    $controller = new ReflectionClass(FintsController::class)->newInstanceWithoutConstructor();
    new ReflectionProperty(FintsController::class, 'routeInfo')->setValue($controller, ['tan-mode-id' => '921']);
    new ReflectionProperty(FintsController::class, 'credentialId')->setValue($controller, 7);
    new ReflectionProperty(FintsController::class, 'fintsHandler')->setValue($controller, $handler);
    new ReflectionProperty(FintsController::class, 'request')->setValue($controller, new Request);

    ob_start();
    try {
        new ReflectionMethod(FintsController::class, 'actionPickTanMedium')->invoke($controller);

        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

/**
 * Legacy pages are handed to the browser inside an iframe's srcdoc, and such a document has no
 * URL of its own: an empty or relative form action resolves against "about:srcdoc", so the
 * submit navigates there instead of reaching the application. Every legacy form therefore needs
 * an absolute action.
 */
it('posts the picked TAN medium back to its own absolute url', function (): void {
    expect(tanMediumMarkup())
        ->toContain("action='".URIBASE."konto/credentials/7/tan-mode/921/medium'")
        ->not->toContain("action=''");
});

it('offers the media the bank reported', function (): void {
    expect(tanMediumMarkup(['Handy' => '[Handy] +49 123', 'TAN-Karte' => '[TAN-Karte] keine Telefon-Nr. hinterlegt']))
        ->toContain("name='tan-medium-name'")
        ->toContain('[Handy] +49 123')
        ->toContain('[TAN-Karte] keine Telefon-Nr. hinterlegt');
});

it('carries a nonce so the post survives the legacy csrf check', function (): void {
    expect(tanMediumMarkup())->toContain('name="nonce"');
});

it('posts to a url the application accepts', function (): void {
    // There is no dedicated route for the medium picker - it is served by the legacy catch-all.
    // Worth pinning: a POST that only matched a GET route would come back as 405 rather than
    // reaching actionPickTanMedium.
    $route = Route::getRoutes()->match(Request::create('/konto/credentials/7/tan-mode/921/medium', 'POST'));

    expect($route->getActionName())->toContain('LegacyController');
});
