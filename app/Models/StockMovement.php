<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'movement_date',
        'movement_type',
        'fattening_batch_id',
        'sheep_id',
        'livestock_type_id',
        'pen_id',
        'reference_type',
        'reference_id',
        'quantity_in',
        'quantity_out',
        'balance_after',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
        ];
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function sheep(): BelongsTo
    {
        return $this->belongsTo(Sheep::class);
    }

    public function livestockType(): BelongsTo
    {
        return $this->belongsTo(LivestockType::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }
}
