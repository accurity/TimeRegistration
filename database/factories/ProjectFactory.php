<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->words(3, true),
            'rate' => fake()->randomFloat(2, 40, 150),
            'status' => 'active',
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'archived']);
    }
}
