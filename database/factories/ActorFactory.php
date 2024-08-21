<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Actor>
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
            'name' => $this->faker->name(),
            'address' => $this->faker->address(),
            'iban' => $this->faker->iban(),
            'bic' => $this->faker->swiftBicNumber(),
        ];
    }

    public function asOrganisation() : static
    {
        return $this->state([
            'is_organisation' => true,
            'website' => $this->faker->url(),
            'register_number' => $this->faker->creditCardNumber(), // next best to fake
        ]);
    }
}
