<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormDefinition>
 */
class FormDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['project', 'application']),
            'name' => $this->faker->city(),
            'version' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'active' => $this->faker->boolean(),
        ];
    }
}
