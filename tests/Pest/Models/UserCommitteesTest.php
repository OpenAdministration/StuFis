<?php

use App\Models\Setting;

// These tests mutate the shared committees settings; capture and restore them so the
// rest of the suite (which relies on TestSeeder's 'raw' mode) is unaffected.
beforeEach(function (): void {
    $this->origMode = Setting::find('user.committees.mode')?->value;
    $this->origData = Setting::find('user.committees.data')?->value;
});

afterEach(function (): void {
    Setting::set('user.committees.mode', $this->origMode);
    Setting::set('user.committees.data', $this->origData);
});

it('raw mode returns the auth provider committees verbatim', function (): void {
    Setting::set('user.committees.mode', 'raw');
    $this->actingAs(user()); // LocalAuthService maps 'user' -> ['Students Council']

    expect(user()->getCommittees()->all())->toBe(['Students Council']);
});

it('filter mode keeps only provider committees present in the configured superset', function (): void {
    Setting::set('user.committees.mode', 'filter');
    Setting::set('user.committees.data', ['Students Council', 'Senat']);
    $this->actingAs(user());

    expect(user()->getCommittees()->values()->all())->toBe(['Students Council']);
});

it('filter mode drops provider committees missing from the superset', function (): void {
    Setting::set('user.committees.mode', 'filter');
    Setting::set('user.committees.data', ['Senat']); // 'Students Council' not included
    $this->actingAs(user());

    expect(user()->getCommittees())->toBeEmpty();
});

it('all mode returns the configured superset regardless of the provider', function (): void {
    Setting::set('user.committees.mode', 'all');
    Setting::set('user.committees.data', ['Senat', 'Studenten']);
    $this->actingAs(user());

    expect(user()->getCommittees()->all())->toBe(['Senat', 'Studenten']);
});
