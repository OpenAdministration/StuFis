<?php

namespace Tests\Pest\Settings;

use App\Models\Setting;
use App\Rules\DescriptionLengthRule;

// The suite shares a persisted DB, so snapshot the two length settings and
// restore them exactly afterwards (drop if there was no row to begin with).
beforeEach(function (): void {
    $this->origMin = Setting::find('project.description.min_length')?->value;
    $this->origMax = Setting::find('project.description.max_length')?->value;
});

afterEach(function (): void {
    restoreLengthSetting('project.description.min_length', $this->origMin);
    restoreLengthSetting('project.description.max_length', $this->origMax);
});

function restoreLengthSetting(string $key, mixed $original): void
{
    $original === null ? Setting::drop($key) : Setting::set($key, $original);
}

/**
 * Run the rule and return the collected failure messages.
 *
 * @return list<string>
 */
function descriptionErrors(string $value): array
{
    $errors = [];
    (new DescriptionLengthRule)->validate(
        'beschreibung',
        $value,
        function (string $message) use (&$errors): void {
            $errors[] = $message;
        }
    );

    return $errors;
}

it('counts only the visible characters, ignoring the surrounding HTML', function (): void {
    Setting::set('project.description.min_length', 50);
    Setting::set('project.description.max_length', -1);

    // 50 visible chars wrapped in markup passes; 49 is one short.
    expect(descriptionErrors('<p><strong>'.str_repeat('a', 50).'</strong></p>'))->toBeEmpty()
        ->and(descriptionErrors('<p>'.str_repeat('a', 49).'</p>'))->not->toBeEmpty();
});

it('treats an empty editor (<p></p>) as zero characters', function (): void {
    Setting::set('project.description.min_length', 50);
    Setting::set('project.description.max_length', -1);

    expect(descriptionErrors('<p></p>'))->not->toBeEmpty()
        ->and(descriptionErrors('<p>&nbsp;&nbsp;</p>'))->not->toBeEmpty();
});

it('collapses runs of whitespace when counting', function (): void {
    Setting::set('project.description.min_length', 4);
    Setting::set('project.description.max_length', -1);

    // "a   b" -> "a b" -> 3 visible chars, below the minimum of 4.
    expect(descriptionErrors('<p>a   b</p>'))->not->toBeEmpty();

    Setting::set('project.description.min_length', 3);
    expect(descriptionErrors('<p>a   b</p>'))->toBeEmpty();
});

it('enforces the maximum, ignoring markup characters', function (): void {
    Setting::set('project.description.min_length', 0);
    Setting::set('project.description.max_length', 10);

    expect(descriptionErrors('<p>'.str_repeat('a', 10).'</p>'))->toBeEmpty()
        ->and(descriptionErrors('<p>'.str_repeat('a', 11).'</p>'))->not->toBeEmpty();
});

it('disables the upper bound when the maximum is -1', function (): void {
    Setting::set('project.description.min_length', 0);
    Setting::set('project.description.max_length', -1);

    expect(descriptionErrors(str_repeat('a', 10_000)))->toBeEmpty();
});

it('disables the lower bound when the minimum is 0', function (): void {
    Setting::set('project.description.min_length', 0);
    Setting::set('project.description.max_length', -1);

    expect(descriptionErrors('<p></p>'))->toBeEmpty();
});
