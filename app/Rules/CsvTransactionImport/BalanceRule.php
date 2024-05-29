<?php

namespace App\Rules\CsvTransactionImport;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class BalanceRule implements ValidationRule
{

    private readonly Collection $differences;
    private readonly Collection $balances;
    private readonly ?string $initalBalance;

    public function __construct(Collection $differences,Collection $balances,?string $initalBalance){
        // make sure all given Money strings have the same format: XXXX.XX (2 decimals, with . as separator)
        $this->differences = $differences->map(function ($item){
            return number_format((float) str_replace(',','.', $item ?? ''), 2, '.', '');
        });
        $this->balances = $balances->map(function ($item){
            return number_format((float) str_replace(',','.', $item ?? ''), 2, '.', '');
        });
        $this->initalBalance = is_null($initalBalance) ? null : number_format((float) str_replace(',','.', $initalBalance), 2, '.', '');
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            // if there is no inital balance (no prior transaction) then make sure4 the first csv entry as correct
            $currentBalance = $this->initalBalance ?? bcsub($this->balances[0], $this->differences[0],2);
            foreach ($this->differences as $id => $currentValue){
                $currentBalance = bcadd($currentBalance, $currentValue, 2);
                $csvBalance = $this->balances->get($id);
                if($currentBalance !== $csvBalance){
                    $fail(__('konto.csv-verify-balance-error'));
                }
            }
        }catch (\ValueError $error){
            // thrown by bcsub and bc add
            $fail(__('konto.csv-verify-balance-error-wrong-datatype'));
        }
    }
}
