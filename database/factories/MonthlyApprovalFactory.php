<?php

namespace Database\Factories;

use App\Models\MonthlyApproval;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyApproval>
 */
class MonthlyApprovalFactory extends Factory
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
            'year' => now()->year,
            'month' => now()->month,
            'status' => 'pending',
            'submitted_at' => now(),
        ];
    }
}
