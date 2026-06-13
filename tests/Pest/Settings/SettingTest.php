<?php

use App\Models\Setting;
use App\Support\SettingsBag;

// The suite shares a persisted DB, so only ever touch a throwaway key here.
afterEach(fn () => Setting::drop('test.scratch'));

it('falls back to the hard-coded default for a parent key with no stored row', function (): void {
    // No bare `project` row exists (only dotted children), so this resolves from defaults().
    $project = Setting::get('project');

    expect($project)->toBeInstanceOf(SettingsBag::class)
        ->and($project->description->min_length)->toBe(50)
        ->and($project->description->max_length)->toBe(99999);
});

it('prefers a caller-supplied default over the hard-coded default and null', function (): void {
    expect(Setting::get('does.not.exist', 'fallback'))->toBe('fallback')
        ->and(Setting::get('does.not.exist'))->toBeNull();
});

it('stores and reads back a scalar value', function (): void {
    expect(Setting::get('test.scratch'))->toBeNull();

    Setting::set('test.scratch', 'hello');

    expect(Setting::get('test.scratch'))->toBe('hello');
});

it('wraps stored associative arrays in a SettingsBag, but returns plain lists as-is', function (): void {
    Setting::set('test.scratch', ['a' => ['b' => 2]]);
    expect(Setting::get('test.scratch'))->toBeInstanceOf(SettingsBag::class)
        ->and(Setting::get('test.scratch')->a->b)->toBe(2);

    Setting::set('test.scratch', ['x', 'y', 'z']);
    expect(Setting::get('test.scratch'))->toBe(['x', 'y', 'z']);
});

it('resolves a parent key from the undotted, merged tree — not just the exact leaf', function (): void {
    // `tax.active` lives in defaults() as a flat, dotted key. Fetching the *parent*
    // `tax` must still return a bag built from the undotted tree (the exact bug from
    // tinker: get('tax.active') worked but get('tax') was null). Value-agnostic so it
    // is safe against the shared, persisted DB.
    expect(Setting::get('tax'))->toBeInstanceOf(SettingsBag::class)
        ->and(Setting::get('tax')->active)->toBeBool();

    // A *stored* dotted child must surface when reading its parent, via the throwaway key.
    Setting::set('test.scratch.active', true);

    expect(Setting::get('test.scratch.active'))->toBeTrue()
        ->and(Setting::get('test.scratch'))->toBeInstanceOf(SettingsBag::class)
        ->and(Setting::get('test.scratch')->active)->toBeTrue();

    Setting::drop('test.scratch.active');
});

it('drops a stored key, reverting to the default', function (): void {
    Setting::set('test.scratch', 'temp');
    expect(Setting::get('test.scratch'))->toBe('temp');

    Setting::drop('test.scratch');
    expect(Setting::get('test.scratch'))->toBeNull();
});

it('exposes a read-only SettingsBag with dot access, has(), and isset()', function (): void {
    $bag = Setting::get('project');

    expect($bag->get('description.max_length'))->toBe(99999)
        ->and($bag->get('description.missing', 'd'))->toBe('d')
        ->and($bag->has('description'))->toBeTrue()
        ->and($bag->has('nope'))->toBeFalse()
        ->and(isset($bag->description))->toBeTrue();

    // Writes are silently ignored.
    $bag['description'] = 'overwritten';
    expect($bag->description->min_length)->toBe(50);
});
