<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_invoice_for_a_client_in_a_year_starts_at_101(): void
    {
        $client = Client::factory()->create(['invoice_abbreviation' => 'ABC']);

        $result = (new InvoiceNumberService)->generate($client, '2026-10-01');

        $this->assertSame(101, $result['sequence_number']);
        $this->assertSame('1012620-ABC', $result['invoice_number']);
    }

    public function test_the_sequence_number_increments_within_the_same_year(): void
    {
        Invoice::factory()->create(['invoice_date' => '2026-03-01', 'sequence_number' => 101]);
        Invoice::factory()->create(['invoice_date' => '2026-06-01', 'sequence_number' => 102]);

        $next = (new InvoiceNumberService)->nextSequenceNumber('2026-11-01');

        $this->assertSame(103, $next);
    }

    public function test_the_sequence_number_resets_per_calendar_year_of_the_invoice_date(): void
    {
        Invoice::factory()->create(['invoice_date' => '2025-12-15', 'sequence_number' => 107]);

        $next = (new InvoiceNumberService)->nextSequenceNumber('2026-01-05');

        $this->assertSame(101, $next);
    }

    public function test_the_sequence_number_continues_across_clients(): void
    {
        $computerFutures = Client::factory()->create(['invoice_abbreviation' => 'COM']);
        $inzicht = Client::factory()->create(['invoice_abbreviation' => 'INZ']);
        Invoice::factory()->create(['client_id' => $computerFutures->id, 'invoice_date' => '2026-02-01', 'sequence_number' => 103]);

        $result = (new InvoiceNumberService)->generate($inzicht, '2026-03-01');

        $this->assertSame(104, $result['sequence_number']);
        $this->assertSame('1042620-INZ', $result['invoice_number']);
    }

    public function test_it_can_exclude_a_given_invoice_from_the_calculation(): void
    {
        $invoice = Invoice::factory()->create(['invoice_date' => '2026-05-01', 'sequence_number' => 101]);

        $next = (new InvoiceNumberService)->nextSequenceNumber('2026-05-01', $invoice->id);

        $this->assertSame(101, $next);
    }

    public function test_the_sequence_number_is_taken_from_the_first_three_digits_of_a_given_invoice_number(): void
    {
        Invoice::factory()->create(['invoice_date' => '2026-05-01', 'sequence_number' => 101]);

        $sequenceNumber = (new InvoiceNumberService)->sequenceNumberFor('1042620-INZ', '2026-05-01');

        $this->assertSame(104, $sequenceNumber);
    }

    public function test_the_next_sequence_number_is_used_when_a_given_invoice_number_does_not_start_with_three_digits(): void
    {
        Invoice::factory()->create(['invoice_date' => '2026-05-01', 'sequence_number' => 101]);

        $sequenceNumber = (new InvoiceNumberService)->sequenceNumberFor('CUSTOM-1', '2026-05-01');

        $this->assertSame(102, $sequenceNumber);
    }
}
