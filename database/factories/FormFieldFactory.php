<?php

namespace Database\Factories;

use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_definition_id' => FormDefinition::factory(),
            'name' => $this->faker->unique()->word(),
            'label' => $this->faker->unique()->word(),
            'type' => $this->faker->randomElement(['date', 'textarea', 'number']),
            'default_value' => '',
            'position' => $this->faker->unique()->numberBetween(1,100),
            'view_key' => '',
        ];
    }
}
