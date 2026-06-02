<?php

namespace App\Models;

use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'sheep_id',
        'weight_kg',
        'price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SaleItem $saleItem): void {
            app(StockMovementService::class)->validateSaleItem($saleItem);
        });

        static::saved(function (SaleItem $saleItem): void {
            $stock = app(StockMovementService::class);

            if ($saleItem->wasChanged('sheep_id')) {
                $stock->syncSheepStatus($saleItem->getOriginal('sheep_id'));
            }

            $stock->syncSheepStatus($saleItem->sheep_id);
        });

        static::deleted(function (SaleItem $saleItem): void {
            app(StockMovementService::class)->syncSheepStatus($saleItem->sheep_id);
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function sheep(): BelongsTo
    {
        return $this->belongsTo(Sheep::class);
    }
}
