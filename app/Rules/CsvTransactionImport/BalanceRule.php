<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class BalanceRule implements ValidationRule
{

    public function __construct(private Collection $differences, private Collection $balances,private string $initalBalance){}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currentBalance = $this->initalBalance;
        foreach ($this->differences as $id => $difference){
            $currentValue = str($difference)->replace(',','.')->toString();
            try {
                $currentBalance = bcadd($currentBalance, $currentValue, 2);
            }catch (\ValueError $error){
                $fail(__('konto.csv-verify-balance-error-wrong-datatype'));
            }
            $givenBalance = str($this->balances->get($id))->replace(',','.');
            if(!is_null($currentBalance) && $currentBalance !== $givenBalance){
                $fail(__('konto.csv-verify-balance-error'));
            }
        }
    }
}
