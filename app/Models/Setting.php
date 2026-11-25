<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_name',
    'address',
    'postal_code',
    'city',
    'kvk_number',
    'btw_number',
    'iban',
    'phone',
    'default_payment_term_days',
    'default_vat_percentage',
    'vat_period',
])]
class Setting extends Model
{
    /**
     * Get the single settings row, creating it with empty defaults if it doesn't exist yet.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'company_name' => '',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'default_payment_term_days' => 14,
            'default_vat_percentage' => 21.00,
        ]);
    }

    protected function casts(): array
    {
        return [
            'default_payment_term_days' => 'integer',
            'default_vat_percentage' => 'decimal:2',
        ];
    }

    /**
     * Whether the months from start to end fall in more than one VAT return period.
     */
    public function spansMultipleVatPeriods(CarbonInterface $start, CarbonInterface $end): bool
    {
        return match ($this->vat_period) {
            'month' => ! $start->isSameMonth($end),
            'year' => ! $start->isSameYear($end),
            default => ! $start->isSameQuarter($end),
        };
    }
}
