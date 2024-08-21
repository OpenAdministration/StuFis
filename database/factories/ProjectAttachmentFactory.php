<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ProjectAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAttachment>
 */
class ProjectAttachmentFactory extends Factory
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
            'name' => $this->faker->name(),
            'path' => $this->faker->filePath(),
            'mime_type' => $this->faker->mimeType(),
        ];
    }
}
