<?php

namespace App\Support\Money;

use Cknow\Money\Money;

/**
 * Turns whatever a wire:model bound money input hands us back into a Money object.
 *
 * Usually MoneySynth does this during hydration, but a money value nested in a plain
 * array (e.g. `posts.*.einnahmen`) loses its synth metadata whenever Livewire's JS
 * diff consolidates the update into the parent array, and then the raw display string
 * lands in the component. Anything that reads such a value has to be able to re-cast it.
 */
final class MoneyInput
{
    /**
     * @return Money|null null when the value is not a money value at all (not a formatted string)
     */
    public static function parse(mixed $value): ?Money
    {
        if ($value instanceof Money) {
            return $value;
        }

        if ($value instanceof \Money\Money) {
            return Money::fromMoney($value);
        }

        if (is_string($value)) {
            return Money::fromMoney((new DefaultMoneyFormater)->inverse($value));
        }

        return null;
    }
}
