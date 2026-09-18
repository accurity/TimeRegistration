<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 5000);
        $vatPercentage = 21.00;
        $vatAmount = round($subtotal * $vatPercentage / 100, 2);

        return [
            'client_id' => Client::factory(),
            'project_id' => Project::factory(),
            'period_year' => now()->year,
            'period_month' => now()->month,
            'sequence_number' => fake()->unique()->numberBetween(101, 999),
            'invoice_number' => fake()->unique()->numerify('###2620-ABC'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => $subtotal,
            'vat_percentage' => $vatPercentage,
            'vat_amount' => $vatAmount,
            'total' => $subtotal + $vatAmount,
            'status' => 'draft',
            'payment_status' => 'open',
        ];
    }

    public function final(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'final']);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'cancelled']);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['payment_status' => 'paid', 'paid_at' => now()]);
    }
}
