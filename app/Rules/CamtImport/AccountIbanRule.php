<?php

namespace App\Rules\CamtImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Sanity check that the uploaded CAMT statement actually belongs to the selected account: the
 * statement's own account IBAN must match the BankAccount's IBAN. Guards against importing a
 * statement into the wrong konto. Skipped when either IBAN is missing (e.g. cash accounts).
 */
class AccountIbanRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $accountIban,
        private readonly ?string $statementIban,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $account = $this->normalize($this->accountIban);
        $statement = $this->normalize($this->statementIban);

        if ($account === '' || $statement === '') {
            return;
        }

        if ($account !== $statement) {
            $fail(__('konto.camt-account-iban-mismatch', [
                'account' => $this->accountIban,
                'statement' => $this->statementIban,
            ]));
        }
    }

    private function normalize(?string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $iban));
    }
}
