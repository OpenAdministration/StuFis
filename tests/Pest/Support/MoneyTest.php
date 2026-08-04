<?php

use App\Rules\ExactlyOneZeroMoneyRule;
use App\Rules\NonNegativeMoneyRule;
use App\Support\Money\DefaultMoneyFormater;
use Cknow\Money\Money as CknowMoney;
use Illuminate\Support\Facades\Validator;
use Money\Currency;
use Money\Money;

// ── DefaultMoneyFormater ────────────────────────────────────────────────────

it('formats cents as a German-style euro string', function (): void {
    $f = new DefaultMoneyFormater;

    expect($f->format(new Money(12345, new Currency('EUR'))))->toBe('123,45 €')
        ->and($f->format(new Money(100000, new Currency('EUR'))))->toBe('1.000,00 €')
        ->and($f->format(new Money(0, new Currency('EUR'))))->toBe('0,00 €')
        ->and($f->format(new Money(-4250, new Currency('EUR'))))->toBe('-42,50 €');
});

it('parses a formatted euro string back into Money (inverse)', function (): void {
    $f = new DefaultMoneyFormater;

    expect($f->inverse('1.234,56 €')->getAmount())->toBe('123456')
        ->and($f->inverse('0,00 €')->getAmount())->toBe('0')
        ->and($f->inverse('42,50')->getAmount())->toBe('4250');
});

it('round-trips format and inverse', function (): void {
    $f = new DefaultMoneyFormater;
    $money = new Money(98765, new Currency('EUR'));

    expect($f->inverse($f->format($money))->getAmount())->toBe('98765');
});

// ── ExactlyOneZeroMoneyRule ─────────────────────────────────────────────────

/**
 * Runs the rule for `posts.0.einnahmen` against a sibling `posts.*.ausgaben`
 * field and reports whether validation failed.
 */
function oneZeroRuleFails(mixed $einnahmen, mixed $ausgaben): bool
{
    $rule = new ExactlyOneZeroMoneyRule('posts.*.ausgaben');
    $rule->setData(['posts' => [0 => ['einnahmen' => $einnahmen, 'ausgaben' => $ausgaben]]]);

    $failed = false;
    $rule->validate('posts.0.einnahmen', $einnahmen, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

/**
 * Validates a whole posts array the way the project form does, and returns the message
 * for one row's field. Goes through a real Validator on purpose: both money rules leave
 * the `:position` placeholder in their message for the validator to fill from the row
 * index, so only this path shows what the user actually reads.
 *
 * @param  array<int, array{einnahmen: mixed, ausgaben: mixed}>  $posts
 */
function postsMoneyMessage(array $posts, string $attribute): ?string
{
    $validator = Validator::make(['posts' => $posts], [
        'posts.*.einnahmen' => [new NonNegativeMoneyRule],
        'posts.*.ausgaben' => [new NonNegativeMoneyRule, new ExactlyOneZeroMoneyRule('posts.*.einnahmen')],
    ]);

    return $validator->errors()->first($attribute) ?: null;
}

it('passes when exactly one of the paired money fields is zero', function (): void {
    expect(oneZeroRuleFails(CknowMoney::EUR(0), CknowMoney::EUR(10000)))->toBeFalse()
        ->and(oneZeroRuleFails(CknowMoney::EUR(5000), CknowMoney::EUR(0)))->toBeFalse();
});

it('fails when both fields are zero or both are non-zero', function (): void {
    expect(oneZeroRuleFails(CknowMoney::EUR(0), CknowMoney::EUR(0)))->toBeTrue()
        ->and(oneZeroRuleFails(CknowMoney::EUR(5000), CknowMoney::EUR(10000)))->toBeTrue();
});

/**
 * Regression guard for the production "Call to a member function getAmount() on string":
 * a consolidated Livewire update delivers the money leaves as their raw input strings,
 * and the rule used to call Money methods on them straight away.
 */
it('judges raw money input strings instead of erroring on them', function (): void {
    expect(oneZeroRuleFails('0,00 €', '100,00 €'))->toBeFalse()
        ->and(oneZeroRuleFails('50,00 €', CknowMoney::EUR(0)))->toBeFalse()
        ->and(oneZeroRuleFails('50,00 €', '100,00 €'))->toBeTrue()
        ->and(oneZeroRuleFails('', '0,00 €'))->toBeTrue();
});

it('leaves values that are no money at all to the money rule', function (): void {
    // null (missing/typed-away input) must neither error nor add a second message.
    expect(oneZeroRuleFails(null, CknowMoney::EUR(0)))->toBeFalse();
});

it('names the offending row in both money messages', function (): void {
    // Neither message is much use without the row: the budget table can hold a dozen
    // Posten and the errors are reported for the whole form at once.
    $posts = [
        0 => ['einnahmen' => CknowMoney::EUR(0), 'ausgaben' => CknowMoney::EUR(5000)],
        1 => ['einnahmen' => CknowMoney::EUR(5000), 'ausgaben' => CknowMoney::EUR(10000)],
        2 => ['einnahmen' => CknowMoney::EUR(0), 'ausgaben' => CknowMoney::EUR(-5000)],
    ];

    expect(postsMoneyMessage($posts, 'posts.0.ausgaben'))->toBeNull()
        ->and(postsMoneyMessage($posts, 'posts.1.ausgaben'))->toContain('Posten 2')
        ->and(postsMoneyMessage($posts, 'posts.2.ausgaben'))->toContain('Posten 3');
});

// ── NonNegativeMoneyRule ─────────────────────────────────────────────────────

/**
 * Runs the rule directly against a single value and reports whether it failed.
 */
function nonNegativeRuleFails(mixed $value): bool
{
    $rule = new NonNegativeMoneyRule;

    $failed = false;
    $rule->validate('posts.0.ausgaben', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('fails a negative amount', function (): void {
    // "-50 €" used to pass every rule and silently flip a Posten's expense into income.
    expect(nonNegativeRuleFails(CknowMoney::EUR(-5000)))->toBeTrue()
        ->and(nonNegativeRuleFails('-50,00 €'))->toBeTrue();
});

it('passes positive and zero amounts', function (): void {
    expect(nonNegativeRuleFails(CknowMoney::EUR(5000)))->toBeFalse()
        ->and(nonNegativeRuleFails(CknowMoney::EUR(0)))->toBeFalse();
});

it('leaves a value that is no money at all to the money rule', function (): void {
    // Skipped, not failed — the `money:EUR` rule on the same field already reports this case,
    // and failing here too would just add a second, redundant message.
    expect(nonNegativeRuleFails(null))->toBeFalse();
});
