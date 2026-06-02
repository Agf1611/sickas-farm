<?php

namespace App\Models;

use App\Services\CodeGeneratorService;
use App\Services\MarketValueEstimationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SaleProposal extends Model
{
    protected $fillable = [
        'proposal_number',
        'fattening_batch_id',
        'livestock_type_id',
        'proposed_date',
        'proposal_type',
        'head_count',
        'estimated_total_weight_kg',
        'livestock_market_price_id',
        'estimated_unit_price',
        'estimated_total_amount',
        'estimated_profit_loss',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'sale_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'proposed_date' => 'date',
            'estimated_total_weight_kg' => 'decimal:2',
            'estimated_unit_price' => 'decimal:2',
            'estimated_total_amount' => 'decimal:2',
            'estimated_profit_loss' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SaleProposal $proposal): void {
            if (blank($proposal->proposal_number)) {
                $proposal->proposal_number = app(CodeGeneratorService::class)->generateSaleProposalNumber(
                    $proposal->proposed_date?->year,
                );
            }

            if (blank($proposal->requested_by) && Auth::id()) {
                $proposal->requested_by = Auth::id();
            }
        });

        static::saving(function (SaleProposal $proposal): void {
            if (blank($proposal->livestock_type_id) && filled($proposal->fattening_batch_id)) {
                $proposal->livestock_type_id = FatteningBatch::query()
                    ->whereKey($proposal->fattening_batch_id)
                    ->value('livestock_type_id');
            }

            if ($proposal->fattening_batch_id) {
                app(MarketValueEstimationService::class)->fillProposalEstimates($proposal);
            }
        });
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function livestockType(): BelongsTo
    {
        return $this->belongsTo(LivestockType::class);
    }

    public function livestockMarketPrice(): BelongsTo
    {
        return $this->belongsTo(LivestockMarketPrice::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleProposalItem::class);
    }
}
