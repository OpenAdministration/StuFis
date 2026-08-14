<?php

namespace Tests\Pest\Accounting;

use App\Models\FintsInstitute;
use App\Models\Legacy\BankAccountCredential;
use framework\Singleton;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use ReflectionProperty;
use Tests\TestCase;

// Bank accesses and the institute they point at are written straight into the shared
// testing database, so roll the whole thing back afterwards.
uses(DatabaseTransactions::class);

const DELETE_TEST_BLZ = '10010424';

// The legacy group runs without Laravel's CSRF middleware; FintsController compares the
// posted `nonce` against csrf_token() itself, so the session token has to be pinned to a
// known value rather than read before the request.
const VALID_NONCE = 'valid-test-nonce';

/**
 * Posts to a legacy page.
 *
 * `Renderer` builds its own request with `Request::createFromGlobals()` rather than taking
 * Laravel's - deliberately, because the global middleware trims strings and a trimmed bank
 * PIN is indistinguishable from a wrong one. The upshot for a test is that the superglobals
 * have to carry the request too, or the legacy side sees the CLI's GET with an empty body.
 */
function legacyPost(TestCase $test, string $uri, array $data): TestResponse
{
    // Reached through $GLOBALS rather than the superglobals directly: Rector rewrites a plain
    // `$_POST` read into the Request facade, which is the very thing the legacy side does not
    // look at.
    $serverBefore = $GLOBALS['_SERVER'];
    $postBefore = $GLOBALS['_POST'] ?? [];

    $GLOBALS['_SERVER']['REQUEST_METHOD'] = 'POST';
    $GLOBALS['_SERVER']['REQUEST_URI'] = $uri;
    $GLOBALS['_POST'] = $data;

    try {
        return $test->withSession(['_token' => VALID_NONCE])->post($uri, $data);
    } finally {
        $GLOBALS['_SERVER'] = $serverBefore;
        $GLOBALS['_POST'] = $postBefore;
    }
}

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

/**
 * Drops the legacy singletons, DBConnector above all.
 *
 * It grabs `DB::getPdo()` once and keeps it for the life of the process. Rolling back a test
 * makes Laravel reconnect, so that cached handle goes stale and the legacy side stops seeing
 * anything the test wrote - which shows up as "this bank access does not exist" from the
 * second test onwards. A real request never notices: one process, one request, one handle.
 */
function resetLegacySingletons(): void
{
    if (! class_exists(Singleton::class, false)) {
        // inc.all.php has not been pulled in yet, so there is nothing to reset.
        return;
    }

    new ReflectionProperty(Singleton::class, 'instances')->setValue(null, []);
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

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => VALID_NONCE])
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

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => VALID_NONCE])
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

    legacyPost($this, "/konto/credentials/$access->id/delete", ['nonce' => VALID_NONCE])
        ->assertRedirect(route('legacy.konto.credentials'));

    expect(BankAccountCredential::find($access->id))->toBeNull();
});
