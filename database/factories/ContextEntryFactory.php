<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContextEntryType;
use App\Models\ContextEntry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContextEntry>
 */
final class ContextEntryFactory extends Factory
{
    protected $model = ContextEntry::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(ContextEntryType::values()),
            'title' => fake()->unique()->sentence(3),
            'content' => fake()->paragraph(),
            'tags' => [],
            'source_agent' => null,
            'importance' => fake()->numberBetween(1, 5),
        ];
    }
}
