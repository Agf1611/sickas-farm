<?php

namespace App\Models;

use App\Services\MarketValueEstimationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleProposalItem extends Model
{
    protected $fillable = [
        'sale_proposal_id',
        'sheep_id',
        'latest_weight_kg',
        'estimated_price',
        'estimated_profit_loss',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latest_weight_kg' => 'decimal:2',
            'estimated_price' => 'decimal:2',
            'estimated_profit_loss' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SaleProposalItem $item): void {
            if (! $item->sheep_id) {
                return;
            }

            $estimate = app(MarketValueEstimationService::class)->estimateSheep($item->sheep);

            $item->latest_weight_kg ??= $estimate['latest_weight'];
            $item->estimated_price = $item->estimated_price ?: $estimate['estimated_value'];
            $item->estimated_profit_loss ??= $estimate['estimated_profit_loss'];
        });
    }

    public function saleProposal(): BelongsTo
    {
        return $this->belongsTo(SaleProposal::class);
    }

    public function sheep(): BelongsTo
    {
        return $this->belongsTo(Sheep::class);
    }
}
