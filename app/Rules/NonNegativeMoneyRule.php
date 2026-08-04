<?php

namespace App\Rules;

use App\Support\Money\MoneyInput;
use Cknow\Money\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects negative money amounts, e.g. a Posten's Einnahme/Ausgabe entered as
 * "-50 €", which would otherwise silently flip the project's totals.
 *
 * Only a value that is no money value at all (null, an array) is skipped; an
 * unparsable string reads as 0 and is reported by the `money` rule on the same
 * field, so failing here as well would only add a second, redundant message.
 *
 * The message names the row via :position, which the validator fills in from the
 * attribute — it therefore expects an array attribute (`posts.*.ausgaben`). Bind
 * a per-field message in validation.php if the rule is ever used on a plain field.
 */
class NonNegativeMoneyRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $money = MoneyInput::parse($value);

        if (! $money instanceof Money) {
            return;
        }

        if ($money->isNegative()) {
            $fail(__('errors.negative-money'));
        }
    }
}
