<?php

namespace Tests\Pest\Settings;

use App\Models\Setting;
use Livewire\Livewire;

// Keys the settings page persists. Snapshot them all so the shared, persisted
// DB is left exactly as it was found.
const SETTINGS_KEYS = [
    'mail_domain',
    'project.description.min_length',
    'project.description.max_length',
    'project.protocol_url.active',
    'project.protocol_url.label',
    'user.committees.mode',
    'user.committees.data',
    'tax.active',
    'datev',
];

beforeEach(function (): void {
    $this->snapshot = collect(SETTINGS_KEYS)
        ->mapWithKeys(fn (string $key): array => [$key => Setting::find($key)?->value])
        ->all();

    // Known-good baseline for the required text field so unrelated validation tests
    // aren't tripped up by whatever the shared DB happens to hold. Restored afterwards.
    Setting::set('mail_domain', 'open-administration.de');
});

afterEach(function (): void {
    foreach ($this->snapshot as $key => $value) {
        $value === null ? Setting::drop($key) : Setting::set($key, $value);
    }
});

it('forbids non-admins from opening the settings page', function (): void {
    Livewire::actingAs(user())
        ->test('pages::settings')
        ->assertForbidden();
});

it('forbids a non-admin finance officer from saving settings', function (): void {
    Livewire::actingAs(budgetManager())
        ->test('pages::settings')
        ->assertForbidden();
});

it('lets an admin open the settings page', function (): void {
    $this->actingAs(adminUser());

    Livewire::test('pages::settings')->assertStatus(200);
});

it('persists changed settings on save', function (): void {
    $this->actingAs(adminUser());

    Livewire::test('pages::settings')
        ->set('mailDomain', 'example.org')
        ->set('descMin', 10)
        ->set('descMax', -1)
        ->set('taxActive', true)
        ->set('committeesData', "Gremium A\nGremium B")
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('mail_domain'))->toBe('example.org')
        ->and(Setting::get('project.description.min_length'))->toBe(10)
        ->and(Setting::get('project.description.max_length'))->toBe(-1)
        ->and(Setting::get('tax.active'))->toBeTrue()
        ->and(Setting::get('user.committees.data'))->toBe(['Gremium A', 'Gremium B']);
});

it('trims and drops blank lines from the committee list', function (): void {
    $this->actingAs(adminUser());

    Livewire::test('pages::settings')
        ->set('committeesData', "  Gremium A  \n\n   \n Gremium B \n")
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('user.committees.data'))->toBe(['Gremium A', 'Gremium B']);
});

it('rejects a maximum length below the minimum (unless -1)', function (): void {
    $this->actingAs(adminUser());

    Livewire::test('pages::settings')
        ->set('descMin', 100)
        ->set('descMax', 50)
        ->call('save')
        ->assertHasErrors('descMax');

    // -1 is always allowed, even with a high minimum.
    Livewire::test('pages::settings')
        ->set('descMin', 100)
        ->set('descMax', -1)
        ->call('save')
        ->assertHasNoErrors();
});

it('rejects an empty mail domain', function (): void {
    $this->actingAs(adminUser());

    Livewire::test('pages::settings')
        ->set('mailDomain', '')
        ->call('save')
        ->assertHasErrors('mailDomain');
});
