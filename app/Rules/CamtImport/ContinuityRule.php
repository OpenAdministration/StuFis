<?php

namespace App\Rules\CamtImport;

use Cknow\Money\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * When the account already has transactions, a CAMT statement's opening balance must match the
 * last stored saldo — otherwise this statement does not directly follow the existing history (a
 * gap), and importing it would desync the saldo.
 */
class ContinuityRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $latestSaldo,
        private readonly ?string $openingBalance,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->openingBalance === null || $this->latestSaldo === null) {
            return;
        }

        if (bccomp((string) $this->latestSaldo, (string) $this->openingBalance, 2) !== 0) {
            $fail(__('konto.camt-continuity-mismatch', ['db' => Money::EUR($this->latestSaldo), 'statement' => Money::EUR($this->openingBalance)]));
        }
    }
}
