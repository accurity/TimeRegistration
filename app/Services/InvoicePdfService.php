<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\TimeEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Render the invoice PDF and store it, returning the storage path.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['client', 'project']);

        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'settings' => Setting::current(),
            'totalHours' => $this->timeEntries($invoice)->sum('hours'),
        ])->setPaper('a4', 'portrait');

        return $this->store($this->storagePath($invoice), $pdf->output());
    }

    /**
     * Render the timesheet PDF, with the hours grouped per month, and store it, returning the storage path.
     */
    public function generateTimesheet(Invoice $invoice): string
    {
        $invoice->loadMissing(['client', 'project']);

        $timeEntries = $this->timeEntries($invoice);

        $pdf = Pdf::loadView('pdfs.timesheet', [
            'invoice' => $invoice,
            'settings' => Setting::current(),
            'timeEntriesPerMonth' => $timeEntries->groupBy(fn (TimeEntry $entry) => $entry->date->format('Y-m')),
            'totalHours' => $timeEntries->sum('hours'),
        ])->setPaper('a4', 'portrait');

        return $this->store($this->timesheetStoragePath($invoice), $pdf->output());
    }

    public function storagePath(Invoice $invoice): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $invoice->invoice_number);

        return "{$invoice->invoice_date->year}/{$safeNumber}.pdf";
    }

    public function timesheetStoragePath(Invoice $invoice): string
    {
        return preg_replace('/\.pdf$/', '-urenspecificatie.pdf', $this->storagePath($invoice));
    }

    /**
     * @return Collection<int, TimeEntry>
     */
    private function timeEntries(Invoice $invoice): Collection
    {
        return TimeEntry::query()
            ->where('invoice_id', $invoice->id)
            ->orderBy('date')
            ->get();
    }

    private function store(string $path, string $contents): string
    {
        Storage::disk('invoices')->put($path, $contents);

        return $path;
    }
}
