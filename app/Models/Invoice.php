<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'project_id',
    'period_start',
    'period_end',
    'line_description',
    'sequence_number',
    'invoice_number',
    'invoice_date',
    'due_date',
    'subtotal',
    'vat_percentage',
    'vat_amount',
    'total',
    'status',
    'payment_status',
    'paid_at',
    'pdf_path',
    'timesheet_pdf_path',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'vat_percentage' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * The single status an admin manages: combines the invoice status and the payment status.
     *
     * @return 'draft'|'open'|'paid'|'cancelled'
     */
    public function managedStatus(): string
    {
        if (! $this->isFinal()) {
            return $this->status;
        }

        return $this->isPaid() ? 'paid' : 'open';
    }

    /**
     * The invoiced months, e.g. "september 2026", "januari – maart 2026" or "december 2025 – februari 2026".
     */
    public function periodLabel(string $monthFormat = 'F'): string
    {
        return static::periodLabelFor($this->period_start, $this->period_end, $monthFormat);
    }

    /**
     * The text of the invoice line, falling back to the default for invoices that have none stored.
     */
    public function lineDescription(): string
    {
        return $this->line_description ?? static::defaultLineDescriptionFor($this->project, $this->period_start, $this->period_end);
    }

    public static function periodLabelFor(CarbonInterface $start, CarbonInterface $end, string $monthFormat = 'F'): string
    {
        if ($start->isSameMonth($end)) {
            return $end->translatedFormat("{$monthFormat} Y");
        }

        $startFormat = $start->isSameYear($end) ? $monthFormat : "{$monthFormat} Y";

        return $start->translatedFormat($startFormat).' – '.$end->translatedFormat("{$monthFormat} Y");
    }

    public static function defaultLineDescriptionFor(Project $project, CarbonInterface $start, CarbonInterface $end): string
    {
        return "{$project->name} — werkzaamheden ".static::periodLabelFor($start, $end);
    }
}
