<?php

namespace Database\Factories;

use App\Models\StudentBodyDuty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentBodyDuty>
 */
class StudentBodyDutyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'short_key' => $this->faker->word(),
            'long_key' => $this->faker->sentence(),
        ];
    }
}
