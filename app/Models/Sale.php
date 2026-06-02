<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'sale_number',
        'buyer_id',
        'fattening_batch_id',
        'sale_date',
        'sale_type',
        'head_count',
        'total_weight_kg',
        'unit_price',
        'total_amount',
        'notes',
        'proof_photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_weight_kg' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'proof_photo_paths' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (blank($sale->sale_number)) {
                $sale->sale_number = app(CodeGeneratorService::class)->generateSaleInvoiceNumber(
                    $sale->sale_date?->year,
                );
            }
        });

        static::saving(function (Sale $sale): void {
            app(StockMovementService::class)->validateSale($sale);
        });

        static::saved(function (Sale $sale): void {
            $stock = app(StockMovementService::class);

            if ($sale->wasChanged('fattening_batch_id')) {
                $stock->recalculateBatch($sale->getOriginal('fattening_batch_id'));
            }

            $stock->recalculateBatch($sale->fattening_batch_id);
            $stock->recordSaleMovement($sale->fresh(['fatteningBatch']));
        });

        static::deleting(function (Sale $sale): void {
            $sale->saleItems()->get()->each->delete();
        });

        static::deleted(function (Sale $sale): void {
            app(StockMovementService::class)->deleteMovementFor($sale);
            app(StockMovementService::class)->recalculateBatch($sale->fattening_batch_id);
        });
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
