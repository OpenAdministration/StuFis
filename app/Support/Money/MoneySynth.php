<?php

namespace App\Support\Money;

use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;
use Money\Currency;
use Cknow\Money\Money;

/**
 *  Synthesizes a Money object for Livewire usage
 *  Default serialization: {"amount":"15205","currency":"EUR","formatted":"152,05&nbsp;\u20ac"}
 */
class MoneySynth extends Synth
{
    public static $key = 'money';

    #[\Override]
    static function match($target): bool
    {
        return $target instanceof Money || $target instanceof \Money\Money;
    }

    public function dehydrate(Money $target): array
    {
        return [$target->format(), []];
    }

    public function hydrate($value): Money
    {
        return Money::fromMoney((new DefaultMoneyFormater())->inverse($value));
    }

}
