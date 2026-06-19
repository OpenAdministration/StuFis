<?php

namespace App\Rules\CamtImport;

use Cknow\Money\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A CAMT statement is internally consistent only if its opening balance plus the sum of all
 * booked entries equals its closing balance. A mismatch means entries are missing from the
 * file, so importing it would create a broken saldo sequence.
 */
class BalanceConsistencyRule implements ValidationRule
{
    public function __construct(
        private readonly Collection $values,
        private readonly ?string $openingBalance,
        private readonly ?string $closingBalance,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->openingBalance === null || $this->closingBalance === null) {
            return;
        }

        $computed = $this->values->reduce(
            fn ($carry, $v) => bcadd((string) $carry, (string) str($v)->replace(',', '.'), 2),
            (string) $this->openingBalance
        );

        if (bccomp($computed, $this->closingBalance, 2) !== 0) {
            $fail(__('konto.camt-balance-mismatch', ['calc' => Money::EUR($computed), 'statement' => Money::EUR($this->closingBalance)]));
        }
    }
}
