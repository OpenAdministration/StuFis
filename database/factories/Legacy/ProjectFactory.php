<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'stateCreator_id' => User::factory(),
            'version' => 1,
            'state' => 'draft',
            'name' => fake()->sentence(3),
            'org' => 'Students Council',
            'org_mail' => fake()->safeEmail(),
            'responsible' => fake()->safeEmail(),
            'protokoll' => '',
            'recht' => '',
            'recht_additional' => '',
            'beschreibung' => fake()->paragraph(),
            'date_start' => now()->addDays(1),
            'date_end' => now()->addDays(30),
        ];
    }

    /**
     * Set the creator of the project.
     */
    public function by(User $user): static
    {
        return $this->state(fn () => [
            'creator_id' => $user->id,
            'stateCreator_id' => $user->id,
        ]);
    }

    /**
     * Set the project state.
     */
    public function withState(string $state): static
    {
        return $this->state(fn () => [
            'state' => $state,
        ]);
    }
}
