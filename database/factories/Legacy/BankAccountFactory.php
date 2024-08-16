<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'short' => $this->faker->randomLetter(),
            //'sync_from' => $this->faker->date(),
            //'sync_until' => $this->faker->date(),
            'iban' => $this->faker->iban('de'),
            //'last_sync' => $this->faker->date(),
        ];
    }
}
