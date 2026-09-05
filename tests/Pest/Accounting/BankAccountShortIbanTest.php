<?php

namespace Tests\Pest\Accounting;

use App\Models\Legacy\BankAccount;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// Accounts are written into the shared testing database, so roll the whole thing back.
uses(DatabaseTransactions::class);

it('finds the account behind a shortened IBAN', function (): void {
    $account = BankAccount::factory()->create(['iban' => 'DE51200411330641363700', 'name' => 'Comdirekt']);

    expect(BankAccount::findByShortIban('DE513700')?->id)->toBe($account->id);
});

it('has no account for a shortened IBAN nobody registered', function (): void {
    BankAccount::factory()->create(['iban' => 'DE51200411330641363700']);

    expect(BankAccount::findByShortIban('DE999999'))->toBeNull();
});

it('does not let a wildcard in the URL match an account', function (): void {
    BankAccount::factory()->create(['iban' => 'DE51200411330641363700']);

    // Reaches the model straight from a route parameter, so a `%` must not be able to widen the
    // LIKE into "any account at all".
    expect(BankAccount::findByShortIban('DE%13700'))->toBeNull()
        ->and(BankAccount::findByShortIban('%'))->toBeNull();
});

it('names the account being imported in the breadcrumb', function (): void {
    BankAccount::factory()->create(['iban' => 'DE51200411330641363700', 'name' => 'Comdirekt']);

    // The TAN prompt runs under this route as well, and its own page says nothing about the
    // account - the breadcrumb is where that has to be visible.
    $trail = Breadcrumbs::generate('legacy.konto.credentials.import-transactions', 4, 'DE513700');

    expect($trail->pluck('title'))->toContain('Comdirekt');
});

it('falls back to the shortened IBAN for an unregistered account', function (): void {
    $trail = Breadcrumbs::generate('legacy.konto.credentials.import-transactions', 4, 'DE999999');

    expect($trail->pluck('title'))->toContain('DE999999');
});
