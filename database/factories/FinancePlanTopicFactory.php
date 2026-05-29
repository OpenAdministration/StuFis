<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\FinancePlanTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancePlanTopic>
 */
class FinancePlanTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'name' => fake()->name(),
            'is_active' => fake()->boolean(),
        ];
    }
}
