<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\ExpensesReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpensesReceiptFactory extends Factory
{
    protected $model = ExpensesReceipt::class;

    public function definition(): array
    {
        return [
            'short' => fake()->unique()->numberBetween(1, 10),
            'datum' => fake()->date(),
            'beschreibung' => fake()->text(),
        ];
    }
}
