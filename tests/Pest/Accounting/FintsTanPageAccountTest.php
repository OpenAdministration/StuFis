<?php

namespace Tests\Pest\Accounting;

use App\Models\Legacy\BankAccount;
use booking\konto\FintsController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

// Accounts are written into the shared testing database, so roll the whole thing back.
uses(DatabaseTransactions::class);

/**
 * Runs the private renderRequestedAccount() for the given route parameters and returns what it
 * echoed. The TAN pages themselves can only be reached with a live bank dialog behind them, so
 * the controller is built without its constructor and handed nothing but the route info that
 * method reads.
 */
function requestedAccountMarkup(array $routeInfo): string
{
    if (! defined('DEV')) {
        require base_path('legacy/lib/inc.all.php');
    }

    $controller = new ReflectionClass(FintsController::class)->newInstanceWithoutConstructor();
    new ReflectionProperty(FintsController::class, 'routeInfo')->setValue($controller, $routeInfo);

    ob_start();
    try {
        new ReflectionMethod(FintsController::class, 'renderRequestedAccount')->invoke($controller);

        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

it('names the account a TAN is being asked for', function (): void {
    BankAccount::factory()->create(['iban' => 'DE51200411330641363700', 'name' => 'Comdirekt']);

    expect(requestedAccountMarkup(['short-iban' => 'DE513700']))
        ->toContain('Comdirekt')
        ->toContain('DE51200411330641363700');
});

it('falls back to the shortened IBAN for an account it does not know', function (): void {
    expect(requestedAccountMarkup(['short-iban' => 'DE999999']))->toContain('DE999999');
});

it('says nothing when the TAN belongs to the bank access rather than an account', function (): void {
    // The login and TAN-mode routes carry no account, and inventing one there would be a lie.
    expect(requestedAccountMarkup([]))->toBe('');
});
