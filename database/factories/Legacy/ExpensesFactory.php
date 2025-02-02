<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\Expenses;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpensesFactory extends Factory
{
    protected $model = Expenses::class;

    public function definition(): array
    {
        return [
            'name_suffix' => fake()->words(4),
            'state' => 'draft',
            'zahlung_iban' => fake()->iban(),
            'zahlung_name' => fake()->name(),
            'zahlung_vwzk' => fake()->words(7),
            'address' => fake()->address(),
            'version' => 1,
        ];
    }
}
