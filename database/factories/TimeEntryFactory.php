<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'description' => fake()->sentence(),
            'rate' => fake()->randomFloat(2, 40, 150),
        ];
    }
}
