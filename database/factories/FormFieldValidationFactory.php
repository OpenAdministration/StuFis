<?php

namespace Database\Factories;

use App\Models\FormField;
use App\Models\FormFieldValidation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormFieldValidation>
 */
class FormFieldValidationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_field_id' => FormField::factory(),
            'validation_rule' => fake()->randomElement(['max', 'min']),
            'validation_parameter' => fake()->numberBetween(10, 255),
        ];
    }
}
