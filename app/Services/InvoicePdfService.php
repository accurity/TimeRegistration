<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\TimeEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Render the invoice PDF and store it, returning the storage path.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['client', 'project']);

        $timeEntries = TimeEntry::query()
            ->where('invoice_id', $invoice->id)
            ->orderBy('date')
            ->get();

        $approval = $invoice->project->monthlyApprovals()
            ->where('year', $invoice->period_year)
            ->where('month', $invoice->period_month)
            ->first();

        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'settings' => Setting::current(),
            'timeEntries' => $timeEntries,
            'totalHours' => $timeEntries->sum('hours'),
            'approval' => $approval,
        ])->setPaper('a4', 'portrait');

        $path = $this->storagePath($invoice);

        Storage::disk('invoices')->put($path, $pdf->output());

        return $path;
    }

    public function storagePath(Invoice $invoice): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $invoice->invoice_number);

        return "{$invoice->invoice_date->year}/{$safeNumber}.pdf";
    }
}
