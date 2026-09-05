<?php

namespace Tests\Pest\Accounting;

use App\Models\Legacy\BankAccount;
use Livewire\Livewire;

// The testing DB is shared and persisted, so anything created here is removed again.
const TEST_IBAN = 'DE02120300000000202051';
const TEST_SHORT = 'QX';

afterEach(function (): void {
    BankAccount::whereIn('iban', [TEST_IBAN])->orWhere('short', TEST_SHORT)->delete();
});

it('prefills the iban handed over by a FinTS bank access', function (): void {
    Livewire::withQueryParams(['iban' => TEST_IBAN])
        ->test('pages::new-banking-account')
        ->assertSet('iban', TEST_IBAN);
});

it('drops the Kasse wording when a bank access hands the account over', function (): void {
    Livewire::withQueryParams(['iban' => TEST_IBAN, 'bankSynced' => 1])
        ->test('pages::new-banking-account')
        ->assertSee('Neues Konto anlegen')
        ->assertDontSee('Kasse')
        // The save button says where it leads, because it hands the user back to the
        // bank access to set the retrieval up.
        ->assertSee('Speichern und weiter');
});

it('keeps the Kasse wording when no bank access is involved', function (): void {
    Livewire::test('pages::new-banking-account')
        ->assertSee('Neues Konto bzw. neue Kasse anlegen')
        ->assertSee('Speichern')
        ->assertDontSee('weiter zum automatischen Abruf');
});

it('stores an account that a bank access handed over', function (): void {
    Livewire::withQueryParams(['iban' => TEST_IBAN])
        ->test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->call('store')
        ->assertHasNoErrors();

    $account = BankAccount::where('iban', TEST_IBAN)->sole();
    expect($account->short)->toBe(TEST_SHORT)
        ->and($account->name)->toBe('FinTS Testkonto')
        // FinTS-synced accounts must not be hand-editable; the column defaults to false,
        // which the legacy insert this replaced never set at all.
        ->and((bool) $account->manually_enterable)->toBeFalse();
});

it('rejects an invalid iban instead of storing an unusable account', function (): void {
    Livewire::withQueryParams(['iban' => 'DE00120300000000202051'])
        ->test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->call('store')
        ->assertHasErrors('iban');

    expect(BankAccount::where('short', TEST_SHORT)->count())->toBe(0);
});

it('returns to the bank access it was handed over from', function (): void {
    $returnTo = '/konto/credentials/7/sepa';

    Livewire::withQueryParams(['iban' => TEST_IBAN, 'bankSynced' => 1, 'returnTo' => $returnTo])
        ->test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->call('store')
        ->assertHasNoErrors()
        ->assertRedirect($returnTo);
});

it('falls back to the konto page when no return path was given', function (): void {
    Livewire::withQueryParams(['iban' => TEST_IBAN])
        ->test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->call('store')
        ->assertRedirect(route('legacy.konto'));
});

it('refuses to be turned into an open redirect', function (string $hostile): void {
    Livewire::withQueryParams(['iban' => TEST_IBAN, 'returnTo' => $hostile])
        ->test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->call('store')
        ->assertRedirect(route('legacy.konto'));
})->with([
    'absolute url' => ['https://evil.example/phish'],
    'protocol relative' => ['//evil.example/phish'],
    'scheme only' => ['javascript:alert(1)'],
]);

it('locks manual entry off for an account handed over by a bank access', function (): void {
    // The switch is disabled in the form; this proves a tampered request cannot flip it,
    // because manual entry would rule out the synchronisation the account exists for.
    Livewire::withQueryParams(['iban' => TEST_IBAN, 'bankSynced' => 1])
        ->test('pages::new-banking-account')
        // The form says why, rather than hiding the switch.
        ->assertSee(__('konto.new.manual-locked-sub'))
        ->assertSee(__('konto.new.iban-locked-sub'))
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->set('manually_enterable', true)
        ->call('store')
        ->assertHasNoErrors();

    expect((bool) BankAccount::where('iban', TEST_IBAN)->sole()->manually_enterable)->toBeFalse();
});

it('still allows a manually entered account when no bank access is involved', function (): void {
    Livewire::test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'Barkasse Test')
        ->set('sync_from', '2026-01-01')
        ->set('manually_enterable', true)
        ->call('store')
        ->assertHasNoErrors();

    expect((bool) BankAccount::where('short', TEST_SHORT)->sole()->manually_enterable)->toBeTrue();
});

it('rejects a short that is already taken', function (): void {
    $taken = BankAccount::query()->whereNotNull('short')->where('short', '!=', '')->first();

    Livewire::test('pages::new-banking-account')
        ->set('iban', TEST_IBAN)
        ->set('short', $taken->short)
        ->set('name', 'FinTS Testkonto')
        ->set('sync_from', '2026-01-01')
        ->call('store')
        ->assertHasErrors('short');
});

it('requires a start date for the synchronisation', function (): void {
    Livewire::withQueryParams(['iban' => TEST_IBAN])
        ->test('pages::new-banking-account')
        ->set('short', TEST_SHORT)
        ->set('name', 'FinTS Testkonto')
        ->call('store')
        ->assertHasErrors('sync_from');
});
