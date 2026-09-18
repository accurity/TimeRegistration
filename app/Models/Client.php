<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'invoice_abbreviation',
    'address',
    'postal_code',
    'city',
    'kvk_number',
    'btw_number',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * Contact persons (client-role users) linked to this client.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
