<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\ExpenseReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseReceiptFactory extends Factory
{
    protected $model = ExpenseReceipt::class;

    public function definition(): array
    {
        return [
            'short' => fake()->unique()->numberBetween(1, 10),
            'datum' => fake()->date(),
            'beschreibung' => fake()->text(),
        ];
    }
}
