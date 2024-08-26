<?php

namespace Database\Factories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actor>
 */
class ActorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_organisation' => false,
            'name' => fake()->name(),
            'address' => fake()->address(),
            'iban' => fake()->iban(),
            'bic' => fake()->swiftBicNumber(),
        ];
    }

    public function asOrganisation() : static
    {
        return $this->state([
            'is_organisation' => true,
            'website' => fake()->url(),
            'register_number' => fake()->creditCardNumber(), // next best to fake
        ]);
    }
}
