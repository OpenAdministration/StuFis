<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class BicRule implements ValidationRule
{
    public function __construct(public Collection $bics){}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->bics as $bic){
            if(empty($bic)) continue;
            if(!verify_iban($bic)){
                $fail( __('konto.csv-verify-iban-error'));
            }
        }
    }
}
