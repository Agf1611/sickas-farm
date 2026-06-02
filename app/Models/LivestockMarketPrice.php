<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LivestockMarketPrice extends Model
{
    protected $fillable = [
        'livestock_type_id',
        'effective_date',
        'price_type',
        'price_per_kg',
        'price_per_head',
        'source',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'price_per_kg' => 'decimal:2',
            'price_per_head' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function livestockType(): BelongsTo
    {
        return $this->belongsTo(LivestockType::class);
    }

    public function saleProposals(): HasMany
    {
        return $this->hasMany(SaleProposal::class);
    }

    public function getUnitPriceAttribute(): ?float
    {
        return $this->price_type === 'per_head'
            ? ($this->price_per_head !== null ? (float) $this->price_per_head : null)
            : ($this->price_per_kg !== null ? (float) $this->price_per_kg : null);
    }
}
