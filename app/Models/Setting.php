<?php

namespace App\Models;

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
    'default_payment_term_days',
    'default_vat_percentage',
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
        ]);
    }

    protected function casts(): array
    {
        return [
            'default_payment_term_days' => 'integer',
            'default_vat_percentage' => 'decimal:2',
        ];
    }
}
