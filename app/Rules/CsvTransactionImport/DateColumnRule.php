<?php

namespace App\Rules\CsvTransactionImport;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class DateColumnRule implements ValidationRule
{
    public function __construct(private readonly Collection $column) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $first = $this->column->first();
        $last = $this->column->last();

        if (! is_string($first) || ! is_string($last)) {
            $fail(__('konto.csv-verify-date-error'));

            return;
        }

        try {
            $firstDate = guessDate($first, 'Y-m-d');
            $lastDate = guessDate($last, 'Y-m-d');
            // ISO date strings compare correctly with >
            if ($firstDate > $lastDate) {
                $fail(__('konto.csv-verify-date-order-error'));
            }
        } catch (InvalidFormatException | \TypeError) {
            $fail(__('konto.csv-verify-date-error'));
        }
    }
}
