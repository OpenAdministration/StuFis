<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class IbanRule implements ValidationRule
{
    public function __construct(public Collection $ibans) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->ibans as $iban) {
            if (empty($iban)) {
                continue;
            }
            if (! verify_iban($iban)) {
                $fail(__('konto.csv-verify-iban-error'));
            }
        }
    }
}
