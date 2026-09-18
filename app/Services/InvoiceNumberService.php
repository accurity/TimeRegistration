<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceNumberService
{
    /**
     * Determine the next sequence number for a client, resetting per calendar year of the invoice date.
     */
    public function nextSequenceNumber(int $clientId, Carbon|string $invoiceDate, ?int $excludingInvoiceId = null): int
    {
        $date = Carbon::parse($invoiceDate);

        $lastSequenceNumber = Invoice::query()
            ->where('client_id', $clientId)
            ->whereYear('invoice_date', $date->year)
            ->when($excludingInvoiceId, fn ($query) => $query->where('id', '!=', $excludingInvoiceId))
            ->max('sequence_number');

        return $lastSequenceNumber ? $lastSequenceNumber + 1 : 101;
    }

    /**
     * @return array{sequence_number: int, invoice_number: string}
     */
    public function generate(Client $client, Carbon|string $invoiceDate): array
    {
        $date = Carbon::parse($invoiceDate);
        $sequenceNumber = $this->nextSequenceNumber($client->id, $date);

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
