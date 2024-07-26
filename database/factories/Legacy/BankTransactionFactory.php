<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        $date = $this->faker->date();
        return [
            'date' => $date,
            'valuta' => $date,
            'type' => $this->faker->word(),
            'empf_iban' => $this->faker->iban('de'),
            'empf_bic' => $this->faker->swiftBicNumber(),
            'empf_name' => $this->faker->name(),
            'primanota' => $this->faker->randomDigit(),
            'zweck' => $this->faker->word(),
            'comment' => $this->faker->word(),
            'customer_ref' => $this->faker->word(),
            'konto_id' => BankAccount::factory(),

            'value' => ($this->faker->boolean() ? "" : "-" ) . $this->faker->randomFloat(2),
            'saldo' => $this->faker->randomFloat(2),
        ];
    }

    public function continuous(int $amount, Carbon $minDate, null|string|float $startValue = null,): self
    {
        $lastSaldo = $startValue ?? $this->faker->randomFloat(2);
        $lastDate = $minDate;
        $seq = [];
        $id = 1;

        for ($i = 0; $i < $amount; $i++) {
            $val = ($this->faker->boolean() ? "" : "-" ) . $this->faker->randomFloat(2, 100, 100000);
            $lastSaldo = bcadd($lastSaldo, $val,2);
            $minutes = $this->faker->randomNumber(4);
            $lastDate = $lastDate->addMinutes($minutes);
            $seq[] = [
                'id' => $id++,
                'date' => $lastDate->format('Y-m-d'),
                'valuta' => $lastDate->format('Y-m-d'),
                'value' => $val,
                'saldo' => $lastSaldo,
            ];
        }
        return $this->sequence(... $seq)->count($amount);
    }
}
