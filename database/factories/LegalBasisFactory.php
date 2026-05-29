<?php

namespace Database\Factories;

use App\Models\LegalBasis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalBasis>
 */
class LegalBasisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->unique()->word(),
            'has_details' => fake()->boolean(),
            'active' => fake()->boolean(),
        ];
    }
}
