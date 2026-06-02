<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\LivestockMarketPrice;
use App\Models\Sale;
use App\Models\SaleProposal;
use App\Models\Sheep;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MarketValueEstimationService
{
    public function __construct(
        private readonly GrowthMonitoringService $growthMonitoring,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function estimateBatch(FatteningBatch $batch): array
    {
        $batch->loadMissing(['livestockType', 'sheep' => fn ($query): mixed => $query->where('status', 'active')]);

        $price = $this->latestMarketPrice($batch->livestock_type_id);
        $activeSheep = $batch->sheep;
        $weightFromIndividuals = $this->activeIndividualWeight($activeSheep);
        $growth = $this->growthMonitoring->calculateBatchGrowth($batch);
        $latestWeight = $weightFromIndividuals
            ?: ($growth['latest_weight'] ?? null)
            ?: ($batch->initial_total_weight_kg ? (float) $batch->initial_total_weight_kg : null);
        $headCount = (int) $batch->current_head_count;
        $estimatedValue = $this->estimateValue($price, $latestWeight, $headCount);
        $existingSales = (float) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('total_amount');
        $expenses = (float) Expense::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('amount');
        $purchaseCapital = (float) $batch->purchase_capital;

        return [
            'market_price' => $price,
            'price_type' => $price?->price_type,
            'unit_price' => $price?->unit_price,
            'head_count' => $headCount,
            'latest_weight' => $latestWeight,
            'weight_source' => $weightFromIndividuals ? 'individual' : 'batch',
            'estimated_value' => $estimatedValue,
            'existing_sales' => $existingSales,
            'purchase_capital' => $purchaseCapital,
            'expenses' => $expenses,
            'estimated_profit_loss' => $existingSales + $estimatedValue - $purchaseCapital - $expenses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function estimateSheep(?Sheep $sheep): array
    {
        if (! $sheep) {
            return [
                'market_price' => null,
                'price_type' => null,
                'unit_price' => null,
                'latest_weight' => null,
                'estimated_value' => 0.0,
                'purchase_price' => 0.0,
                'estimated_profit_loss' => 0.0,
            ];
        }

        $price = $this->latestMarketPrice($sheep->livestock_type_id);
        $latestWeight = $sheep->current_weight_kg !== null ? (float) $sheep->current_weight_kg : null;
        $estimatedValue = $this->estimateValue($price, $latestWeight, 1);
        $purchasePrice = $sheep->purchase_price !== null ? (float) $sheep->purchase_price : 0.0;

        return [
            'market_price' => $price,
            'price_type' => $price?->price_type,
            'unit_price' => $price?->unit_price,
            'latest_weight' => $latestWeight,
            'estimated_value' => $estimatedValue,
            'purchase_price' => $purchasePrice,
            'estimated_profit_loss' => $estimatedValue - $purchasePrice,
        ];
    }

    public function fillProposalEstimates(SaleProposal $proposal): void
    {
        $batch = FatteningBatch::query()
            ->with('sheep')
            ->find($proposal->fattening_batch_id);

        if (! $batch) {
            return;
        }

        $estimate = $this->estimateBatch($batch);
        $price = $estimate['market_price'];

        $proposal->livestock_type_id = $proposal->livestock_type_id ?: $batch->livestock_type_id;
        $proposal->livestock_market_price_id = $proposal->livestock_market_price_id ?: $price?->id;
        $proposal->estimated_unit_price = $proposal->estimated_unit_price ?: $estimate['unit_price'];
        $proposal->head_count = $proposal->head_count ?: $estimate['head_count'];
        $proposal->estimated_total_weight_kg = $proposal->estimated_total_weight_kg ?: $estimate['latest_weight'];
        $proposal->estimated_total_amount = $proposal->estimated_total_amount ?: $estimate['estimated_value'];
        $proposal->estimated_profit_loss = $proposal->estimated_profit_loss ?: $estimate['estimated_profit_loss'];
    }

    private function latestMarketPrice(?int $livestockTypeId): ?LivestockMarketPrice
    {
        if (! $livestockTypeId) {
            return null;
        }

        return LivestockMarketPrice::query()
            ->where('livestock_type_id', $livestockTypeId)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();
    }

    private function estimateValue(?LivestockMarketPrice $price, ?float $weight, int $headCount): float
    {
        if (! $price) {
            return 0.0;
        }

        if ($price->price_type === 'per_head') {
            return round(($price->price_per_head ? (float) $price->price_per_head : 0.0) * $headCount, 2);
        }

        return round(($price->price_per_kg ? (float) $price->price_per_kg : 0.0) * ($weight ?: 0.0), 2);
    }

    /**
     * @param  EloquentCollection<int, Sheep>  $sheep
     */
    private function activeIndividualWeight(EloquentCollection $sheep): ?float
    {
        $weights = $sheep
            ->filter(fn (Sheep $item): bool => $item->status === 'active' && $item->current_weight_kg !== null)
            ->map(fn (Sheep $item): float => (float) $item->current_weight_kg);

        return $weights->isNotEmpty() ? round((float) $weights->sum(), 2) : null;
    }
}
