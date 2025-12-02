<?php

namespace App\Support\Money;

use Money\Money;

class DefaultMoneyFormater implements \Money\MoneyFormatter
{
    #[\Override]
    public function format(Money $money): string
    {
        $amount = $money->getAmount() / 100;
        $currency = '€'; // fixed currency symbol for now

        // assemble the formatted string with 2 decimal places and a space after the comma
        return number_format($amount, 2, ',', '.').' '.$currency;
    }

    /**
     * Converts a formatted money string into a Money object.
     *
     * This method is used by Livewire Synthesizer to reverse the formatting process.
     *
     * @param  string  $formatted  The input money string with currency symbol and formatting.
     * @return Money The resulting Money object with amount in cents and EUR currency.
     */
    public function inverse(string $formatted): Money
    {
        // Remove currency symbol and trim
        $formatted = str_replace('€', '', trim($formatted));

        // Convert remove the thousand separators, comma to decimal point and remove spaces
        $amount = (float) str_replace(['.', ',', ' '], ['', '.', ''], $formatted);

        // Convert to cents
        $cents = (int) round($amount * 100);

        // Create new Money object with EUR currency
        return new Money($cents, new \Money\Currency('EUR'));
    }
}
