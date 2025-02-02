<?php

namespace Database\Factories\Legacy;

use App\Models\ExpensesReceiptPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpensesReceiptPostFactory extends Factory
{
    protected $model = ExpensesReceiptPost::class;

    public function definition(): array
    {
        return [
            'short' => fake()->unique()->numberBetween(10, 100),
            'projekt_posten_id' => fake()->numberBetween(1, 5),
            'ausgaben' => fake()->randomFloat(2, 0, 1000),
            'einnahmen' => 0,
        ];
    }
}
