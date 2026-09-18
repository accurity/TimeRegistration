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
        abort_unless($request->user()->client_id === $invoice->client_id, 404);
        abort_unless(in_array($invoice->status, ['final', 'cancelled'], true), 404);
        abort_unless($invoice->pdf_path && Storage::disk('invoices')->exists($invoice->pdf_path), 404);

        return Storage::disk('invoices')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }
}
