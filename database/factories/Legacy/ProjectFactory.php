<?php

namespace Database\Factories\Legacy;

use App\Models\Legacy\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Model>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'createdat' => $this->faker->dateTime(),
            'lastupdated' => $this->faker->dateTime(),
            'version' => 1,
            'state' => 'draft',
            'name' => $this->faker->text(30),
            'responsible' => $this->faker->userName(),
            'org' => $this->faker->company(),
            'org-mail' => $this->faker->companyEmail(),
            'protokoll' => $this->faker->url(),
            'recht' => 'stura',
            'recht-additional' => $this->faker->text(10),
            'date-start' => $this->faker->date(),
            'date-end' => $this->faker->date(),
            'beschreibung' => $this->faker->text(500),
        ];
    }

    public function by(User $user): ProjectFactory|Factory
    {
        return $this->state(fn (array $attributes) => [
            'creator_id' => $user->id,
            'stateCreator_id' => $user->id,
        ]);
    }

    public function projectState(string $state): ProjectFactory|Factory
    {
        return $this->state(fn (array $attributes) => [
            'state' => $state,
        ]);
    }
}
