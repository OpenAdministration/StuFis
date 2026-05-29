<?php

namespace Database\Factories;

use App\Models\Actor;
use App\Models\ActorPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActorPhone>
 */
class ActorPhoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => Actor::factory(),
            'value' => fake()->phoneNumber(),
        ];
    }
}
