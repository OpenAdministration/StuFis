<?php

use App\Rules\ExactlyOneZeroMoneyRule;
use App\Support\Money\DefaultMoneyFormater;
use Cknow\Money\Money as CknowMoney;
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
function oneZeroRuleFails(CknowMoney $einnahmen, CknowMoney $ausgaben): bool
{
    $rule = new ExactlyOneZeroMoneyRule('posts.*.ausgaben');
    $rule->setData(['posts' => [0 => ['einnahmen' => $einnahmen, 'ausgaben' => $ausgaben]]]);

    $failed = false;
    $rule->validate('posts.0.einnahmen', $einnahmen, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('passes when exactly one of the paired money fields is zero', function (): void {
    expect(oneZeroRuleFails(CknowMoney::EUR(0), CknowMoney::EUR(10000)))->toBeFalse()
        ->and(oneZeroRuleFails(CknowMoney::EUR(5000), CknowMoney::EUR(0)))->toBeFalse();
});

it('fails when both fields are zero or both are non-zero', function (): void {
    expect(oneZeroRuleFails(CknowMoney::EUR(0), CknowMoney::EUR(0)))->toBeTrue()
        ->and(oneZeroRuleFails(CknowMoney::EUR(5000), CknowMoney::EUR(10000)))->toBeTrue();
});
