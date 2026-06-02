<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FatteningBatch extends Model
{
    protected $fillable = [
        'batch_code',
        'livestock_type_id',
        'pen_id',
        'supplier_id',
        'start_date',
        'end_date',
        'initial_head_count',
        'current_head_count',
        'initial_total_weight_kg',
        'target_sale_average_weight_kg',
        'purchase_capital',
        'detail_status',
        'is_historical',
        'historical_notes',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'initial_total_weight_kg' => 'decimal:2',
            'target_sale_average_weight_kg' => 'decimal:2',
            'purchase_capital' => 'decimal:2',
            'is_historical' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FatteningBatch $batch): void {
            if (blank($batch->livestock_type_id)) {
                $batch->livestock_type_id = LivestockType::query()
                    ->where('code', 'DMB')
                    ->value('id');
            }

            if (blank($batch->batch_code)) {
                $batch->batch_code = app(CodeGeneratorService::class)->generateBatchCode(
                    $batch->start_date?->year,
                );
            }
        });
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function livestockType(): BelongsTo
    {
        return $this->belongsTo(LivestockType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sheepPurchases(): HasMany
    {
        return $this->hasMany(SheepPurchase::class);
    }

    public function sheep(): HasMany
    {
        return $this->hasMany(Sheep::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function weighingRecords(): HasMany
    {
        return $this->hasMany(WeighingRecord::class);
    }

    public function sheepIncidentRecords(): HasMany
    {
        return $this->hasMany(SheepIncidentRecord::class);
    }

    public function saleProposals(): HasMany
    {
        return $this->hasMany(SaleProposal::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getAverageInitialWeightKgAttribute(): ?float
    {
        if ((int) $this->initial_head_count < 1 || blank($this->initial_total_weight_kg)) {
            return null;
        }

        return round((float) $this->initial_total_weight_kg / (int) $this->initial_head_count, 2);
    }
}
