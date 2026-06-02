<?php

namespace App\Models;

use App\Services\BatchSyncService;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sheep extends Model
{
    protected $fillable = [
        'tag_number',
        'livestock_type_id',
        'sheep_purchase_id',
        'fattening_batch_id',
        'pen_id',
        'sex',
        'estimated_age_months',
        'initial_weight_kg',
        'current_weight_kg',
        'purchase_price',
        'is_estimated',
        'status',
        'notes',
        'photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'initial_weight_kg' => 'decimal:2',
            'current_weight_kg' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'is_estimated' => 'boolean',
            'photo_paths' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sheep $sheep): void {
            if (blank($sheep->livestock_type_id) && filled($sheep->fattening_batch_id)) {
                $sheep->livestock_type_id = FatteningBatch::query()
                    ->whereKey($sheep->fattening_batch_id)
                    ->value('livestock_type_id');
            }

            if (blank($sheep->livestock_type_id)) {
                $sheep->livestock_type_id = LivestockType::query()
                    ->where('code', 'DMB')
                    ->value('id');
            }

            if (blank($sheep->tag_number)) {
                $prefix = LivestockType::query()
                    ->whereKey($sheep->livestock_type_id)
                    ->value('code');

                $sheep->tag_number = app(CodeGeneratorService::class)->generateSheepCode(prefix: $prefix);
            }
        });

        static::saved(function (Sheep $sheep): void {
            app(BatchSyncService::class)->sync($sheep->fattening_batch_id);

            if ($sheep->wasChanged('fattening_batch_id')) {
                app(BatchSyncService::class)->sync($sheep->getOriginal('fattening_batch_id'));
            }
        });

        static::deleted(function (Sheep $sheep): void {
            app(BatchSyncService::class)->sync($sheep->fattening_batch_id);
        });
    }

    public function livestockType(): BelongsTo
    {
        return $this->belongsTo(LivestockType::class);
    }

    public function sheepPurchase(): BelongsTo
    {
        return $this->belongsTo(SheepPurchase::class);
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function weighingRecords(): HasMany
    {
        return $this->hasMany(WeighingRecord::class);
    }

    public function sheepIncidentRecords(): HasMany
    {
        return $this->hasMany(SheepIncidentRecord::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleProposalItems(): HasMany
    {
        return $this->hasMany(SaleProposalItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getCurrentWeightKgAttribute(): ?float
    {
        $latestWeight = $this->weighingRecords()
            ->where('record_type', 'individual')
            ->latest('weighed_at')
            ->value('weight_kg');

        if ($latestWeight !== null) {
            return (float) $latestWeight;
        }

        if ($this->attributes['current_weight_kg'] ?? null) {
            return (float) $this->attributes['current_weight_kg'];
        }

        return $this->initial_weight_kg !== null ? (float) $this->initial_weight_kg : null;
    }
}
