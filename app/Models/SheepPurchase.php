<?php

namespace App\Models;

use App\Services\StockMovementService;
use App\Services\PurchaseSheepGenerationService;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SheepPurchase extends Model
{
    protected $fillable = [
        'purchase_date',
        'purchase_number',
        'supplier_id',
        'pen_id',
        'fattening_batch_id',
        'livestock_type_id',
        'purchase_type',
        'head_count',
        'total_weight_kg',
        'total_purchase_price',
        'transport_cost',
        'other_cost',
        'notes',
        'proof_photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_weight_kg' => 'decimal:2',
            'total_purchase_price' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'proof_photo_paths' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SheepPurchase $purchase): void {
            if (blank($purchase->purchase_number)) {
                $purchase->purchase_number = app(CodeGeneratorService::class)->generatePurchaseInvoiceNumber(
                    $purchase->purchase_date?->year,
                );
            }
        });

        static::saving(function (SheepPurchase $purchase): void {
            if (blank($purchase->livestock_type_id) && filled($purchase->fattening_batch_id)) {
                $purchase->livestock_type_id = FatteningBatch::query()
                    ->whereKey($purchase->fattening_batch_id)
                    ->value('livestock_type_id');
            }

            if (blank($purchase->livestock_type_id)) {
                $purchase->livestock_type_id = LivestockType::query()
                    ->where('code', 'DMB')
                    ->value('id');
            }

            app(StockMovementService::class)->validatePurchase($purchase);
        });

        static::saved(function (SheepPurchase $purchase): void {
            $originalBatchId = $purchase->wasChanged('fattening_batch_id')
                ? $purchase->getOriginal('fattening_batch_id')
                : null;

            $purchase->syncFatteningBatch();
            app(PurchaseSheepGenerationService::class)->generateForPurchase($purchase->fresh(['fatteningBatch']));

            if ($originalBatchId && (int) $originalBatchId !== (int) $purchase->fattening_batch_id) {
                app(StockMovementService::class)->recalculateBatch($originalBatchId);
            }

            app(StockMovementService::class)->recordPurchaseMovement($purchase->fresh(['fatteningBatch']));
        });

        static::deleted(function (SheepPurchase $purchase): void {
            app(StockMovementService::class)->deleteMovementFor($purchase);
            app(StockMovementService::class)->recalculateBatch($purchase->fattening_batch_id);
        });
    }

    public function totalCapital(): float
    {
        return (float) $this->total_purchase_price
            + (float) $this->transport_cost
            + (float) $this->other_cost;
    }

    public function syncFatteningBatch(): void
    {
        $data = [
            'livestock_type_id' => $this->livestock_type_id,
            'pen_id' => $this->pen_id,
            'supplier_id' => $this->supplier_id,
            'notes' => $this->notes,
        ];

        if ($this->fattening_batch_id) {
            $this->fatteningBatch?->update($data);
            app(StockMovementService::class)->recalculateBatch($this->fattening_batch_id);

            return;
        }

        $batch = FatteningBatch::create([
            ...$data,
            'start_date' => $this->purchase_date,
            'livestock_type_id' => $this->livestock_type_id,
            'initial_head_count' => $this->head_count,
            'current_head_count' => $this->head_count,
            'initial_total_weight_kg' => $this->total_weight_kg,
            'purchase_capital' => $this->totalCapital(),
            'status' => 'active',
        ]);

        $this->forceFill([
            'fattening_batch_id' => $batch->id,
        ])->saveQuietly();

        app(StockMovementService::class)->recalculateBatch($batch);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function livestockType(): BelongsTo
    {
        return $this->belongsTo(LivestockType::class);
    }

    public function sheep(): HasMany
    {
        return $this->hasMany(Sheep::class);
    }
}
