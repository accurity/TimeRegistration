<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceNumberService
{
    /**
     * Determine the next sequence number, continuing across all clients and resetting per calendar year of the invoice date.
     */
    public function nextSequenceNumber(Carbon|string $invoiceDate, ?int $excludingInvoiceId = null): int
    {
        $date = Carbon::parse($invoiceDate);

        $lastSequenceNumber = Invoice::query()
            ->whereYear('invoice_date', $date->year)
            ->when($excludingInvoiceId, fn ($query) => $query->where('id', '!=', $excludingInvoiceId))
            ->max('sequence_number');

        return $lastSequenceNumber ? $lastSequenceNumber + 1 : 101;
    }

    /**
     * Take the sequence number from the first three digits of an (optionally hand-edited) invoice number, so the counter follows it.
     */
    public function sequenceNumberFor(string $invoiceNumber, Carbon|string $invoiceDate, ?int $excludingInvoiceId = null): int
    {
        if (preg_match('/^(\d{3})/', $invoiceNumber, $matches)) {
            return (int) $matches[1];
        }

        return $this->nextSequenceNumber($invoiceDate, $excludingInvoiceId);
    }

    /**
     * @return array{sequence_number: int, invoice_number: string}
     */
    public function generate(Client $client, Carbon|string $invoiceDate): array
    {
        $date = Carbon::parse($invoiceDate);
        $sequenceNumber = $this->nextSequenceNumber($date);

        return [
            'sequence_number' => $sequenceNumber,
            'invoice_number' => sprintf(
                '%03d%s%s-%s',
                $sequenceNumber,
                $date->format('y'),
                substr((string) $date->year, 0, 2),
                $client->invoice_abbreviation,
            ),
        ];
    }
}
