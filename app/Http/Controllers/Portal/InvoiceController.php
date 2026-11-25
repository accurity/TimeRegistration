<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->where('client_id', $request->user()->client_id)
            ->whereIn('status', ['final', 'cancelled'])
            ->with('project')
            ->orderByDesc('invoice_date')
            ->orderByDesc('sequence_number')
            ->get();

        return view('portal.invoices.index', [
            'invoices' => $invoices,
        ]);
    }

    public function download(Request $request, Invoice $invoice): StreamedResponse
    {
        return $this->downloadFile($request, $invoice, $invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }

    public function downloadTimesheet(Request $request, Invoice $invoice): StreamedResponse
    {
        return $this->downloadFile($request, $invoice, $invoice->timesheet_pdf_path, "{$invoice->invoice_number}-urenspecificatie.pdf");
    }

    private function downloadFile(Request $request, Invoice $invoice, ?string $path, string $fileName): StreamedResponse
    {
        abort_unless($request->user()->client_id === $invoice->client_id, 404);
        abort_unless(in_array($invoice->status, ['final', 'cancelled'], true), 404);
        abort_unless($path && Storage::disk('invoices')->exists($path), 404);

        return Storage::disk('invoices')->download($path, $fileName);
    }
}
