<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\FormDefinition;
use App\Models\LegalBasis;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'state' => 'draft',
            'form_name' => FormDefinition::factory(),
            'form_version' => FormDefinition::factory(),
            'version' => $this->faker->numberBetween(1, 10),
            'legal_basis' => LegalBasis::factory(),
            'legal_basis_details' => $this->faker->text(),
            'constraints' => $this->faker->text(),
            'funding_total' => $this->faker->randomFloat(2, 1, 1000),
            'extra_fields' => "{}"
        ];
    }
}
