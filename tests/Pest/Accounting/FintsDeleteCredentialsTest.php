<?php

namespace Tests\Pest\Accounting;

use App\Models\FintsInstitute;
use App\Models\Legacy\BankAccountCredential;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// Bank accesses and the institute they point at are written straight into the shared
// testing database, so roll the whole thing back afterwards.
uses(DatabaseTransactions::class);

const DELETE_TEST_BLZ = '10010424';

/**
 * A bank access owned by the given user, pointing at an institute that exists in the list.
 */
function bankAccess(int $ownerId, string $pinTanAddress = 'https://fints.example.de/servlet'): BankAccountCredential
{
    FintsInstitute::query()->updateOrCreate(
        ['blz' => DELETE_TEST_BLZ],
        [
            'name' => 'Testbank',
            'pin_tan_address' => $pinTanAddress,
            'synced_at' => now(),
        ],
    );

    return BankAccountCredential::create([
        'blz' => DELETE_TEST_BLZ,
        'owner_id' => $ownerId,
        'name' => 'Testzugang',
        'bank_username' => 'testuser',
    ]);
}

beforeEach(function (): void {
    $this->actingAs(cashOfficer());
    resetLegacySingletons();
});

it('asks before deleting and leaves the bank access alone on the way there', function (): void {
    $access = bankAccess(cashOfficer()->id);

    $this->get("/konto/credentials/$access->id/delete")
        ->assertOk()
        ->assertSee('Wirklich löschen?')
        ->assertSee('Testzugang')
        // The accounts and their bookings are the reassuring half of the message.
        ->assertSee('bleiben erhalten');

    expect(BankAccountCredential::find($access->id))->not->toBeNull();
});

it('deletes the bank access when the confirmation is posted', function (): void {
    $access = bankAccess(cashOfficer()->id);

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => LEGACY_NONCE])
        ->assertRedirect(route('legacy.konto.credentials'));

    expect(BankAccountCredential::find($access->id))->toBeNull();
});

it('refuses a delete that carries no valid nonce', function (): void {
    $access = bankAccess(cashOfficer()->id);

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => 'not-the-token'])
        ->assertRedirect(route('legacy.konto.credentials'));

    expect(BankAccountCredential::find($access->id))->not->toBeNull();
});

it('never deletes a bank access belonging to somebody else', function (): void {
    $access = bankAccess(budgetManager()->id);

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => LEGACY_NONCE])
        ->assertRedirect(route('legacy.konto.credentials'));

    expect(BankAccountCredential::find($access->id))->not->toBeNull();
});

it('offers deleting even while nobody is logged in at the bank', function (): void {
    $access = bankAccess(cashOfficer()->id);

    // The overview renders no active session here, which used to hide the action - and the
    // usual reason to delete an access is that logging in with it does not work.
    $this->get('/konto/credentials')
        ->assertOk()
        ->assertSee("konto/credentials/$access->id/delete", false);
});

it('deletes a bank access whose FinTS address is not usable at all', function (): void {
    // The endpoint guard refuses to build a connection for this one, and building it is what
    // the controller does for every other action. Deleting must not depend on it.
    $access = bankAccess(cashOfficer()->id, 'http://fints.example.de/servlet');

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => LEGACY_NONCE])
        ->assertRedirect(route('legacy.konto.credentials'));

    expect(BankAccountCredential::find($access->id))->toBeNull();
});
