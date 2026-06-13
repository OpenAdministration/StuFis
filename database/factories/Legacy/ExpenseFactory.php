<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            // words(true) returns a string; the plain array form cannot be inserted
            // into these string columns and breaks ->create().
            'name_suffix' => fake()->words(4, true),
            'state' => 'draft',
            'zahlung_iban' => fake()->iban(),
            'zahlung_name' => fake()->name(),
            'zahlung_vwzk' => fake()->words(7, true),
            'address' => fake()->address(),
            'etag' => Str::random(32), // NOT NULL, no DB default
            'version' => 1,
        ];
    }
}
