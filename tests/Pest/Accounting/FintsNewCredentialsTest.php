<?php

namespace Tests\Pest\Accounting;

use App\Models\FintsInstitute;
use App\Models\Legacy\BankAccountCredential;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// The institute list is written straight into the shared testing database, so roll the whole
// thing back afterwards.
uses(DatabaseTransactions::class);

const NEW_TEST_BLZ = '10010425';

/**
 * An institute that is offered by the bank dropdown, i.e. one that speaks PIN/TAN.
 */
function pinTanInstitute(string $name = 'Zweitbank'): FintsInstitute
{
    return FintsInstitute::query()->updateOrCreate(
        ['blz' => NEW_TEST_BLZ],
        [
            'name' => $name,
            'pin_tan_address' => 'https://fints.example.de/servlet',
            'synced_at' => now(),
        ],
    );
}

beforeEach(function (): void {
    $this->actingAs(cashOfficer());
    resetLegacySingletons();
});

it('offers the bank list without preselecting a bank', function (): void {
    pinTanInstitute();

    $response = $this->get('/konto/credentials/new')
        ->assertOk()
        ->assertSee('Lege neue Zugangsdaten an')
        ->assertSee('Zweitbank');

    $form = legacyHtml($response);

    // The selectpicker turns the select's title into a placeholder option with an empty value,
    // which is what keeps the first bank of the list from being posted unnoticed. Attribute
    // values are run through htmlentities(), so the umlaut arrives as an entity.
    expect($form)->toContain("title='Bank ausw&auml;hlen'")
        // And no option may carry `selected`: the form starts on that placeholder.
        ->and($form)->not->toContain('selected');
});

it('refuses to create a bank access when no bank was chosen', function (): void {
    pinTanInstitute();

    legacyPost($this, '/konto/credentials/new', [
        'nonce' => LEGACY_NONCE,
        'name' => 'Zugang ohne Bank',
        'blz' => '',
        'bank-username' => 'testuser',
    ])->assertRedirect(route('legacy.konto.credentials.new'));

    expect(BankAccountCredential::query()->where('name', 'Zugang ohne Bank')->exists())->toBeFalse();
});

it('creates the bank access for the chosen bank', function (): void {
    pinTanInstitute();

    legacyPost($this, '/konto/credentials/new', [
        'nonce' => LEGACY_NONCE,
        'name' => 'Neuer Zugang',
        'blz' => NEW_TEST_BLZ,
        'bank-username' => 'testuser',
    ])->assertRedirect(route('legacy.konto.credentials'));

    $credential = BankAccountCredential::query()->where('name', 'Neuer Zugang')->first();

    expect($credential)->not->toBeNull()
        ->and($credential->blz)->toBe(NEW_TEST_BLZ)
        ->and($credential->bank_username)->toBe('testuser')
        ->and($credential->owner_id)->toBe(cashOfficer()->id);
});

it('refuses a bank that does not speak PIN/TAN', function (): void {
    FintsInstitute::query()->updateOrCreate(
        ['blz' => NEW_TEST_BLZ],
        ['name' => 'Nur HBCI', 'pin_tan_address' => null, 'synced_at' => now()],
    );

    legacyPost($this, '/konto/credentials/new', [
        'nonce' => LEGACY_NONCE,
        'name' => 'Zugang ohne PIN/TAN',
        'blz' => NEW_TEST_BLZ,
        'bank-username' => 'testuser',
    ])->assertRedirect(route('legacy.konto.credentials.new'));

    expect(BankAccountCredential::query()->where('name', 'Zugang ohne PIN/TAN')->exists())->toBeFalse();
});
