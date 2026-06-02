<?php

namespace App\Services;

use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;

class BatchSyncService
{
    private const STOCK_OUT_INCIDENT_TYPES = ['dead', 'lost', 'culled'];

    public function sync(FatteningBatch|int|null $batch): void
    {
        $batch = $batch instanceof FatteningBatch ? $batch : FatteningBatch::find($batch);

        if (! $batch) {
            return;
        }

        $purchaseQuery = SheepPurchase::query()->where('fattening_batch_id', $batch->id);
        $sheepQuery = Sheep::query()->where('fattening_batch_id', $batch->id);

        $purchaseCount = (int) (clone $purchaseQuery)->sum('head_count');
        $sheepCount = (int) (clone $sheepQuery)->count();
        $hasPurchases = $purchaseCount > 0;

        $initialHeadCount = $hasPurchases
            ? $purchaseCount
            : max((int) $batch->initial_head_count, $sheepCount);

        $soldHeadCount = (int) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('head_count');

        $incidentStockOut = (int) SheepIncidentRecord::query()
            ->where('fattening_batch_id', $batch->id)
            ->whereIn('incident_type', self::STOCK_OUT_INCIDENT_TYPES)
            ->sum('head_count');

        $currentHeadCount = max(0, $initialHeadCount - $soldHeadCount - $incidentStockOut);
        $sheepInitialWeight = (float) (clone $sheepQuery)->sum('initial_weight_kg');
        $sheepPurchasePrice = (float) (clone $sheepQuery)->sum('purchase_price');

        $data = [
            'initial_head_count' => $initialHeadCount,
            'current_head_count' => $currentHeadCount,
            'detail_status' => $this->detailStatus($batch, $initialHeadCount),
        ];

        if ($hasPurchases) {
            $data['start_date'] = (clone $purchaseQuery)->min('purchase_date');
            $data['purchase_capital'] = (float) (clone $purchaseQuery)
                ->get()
                ->sum(fn (SheepPurchase $purchase): float => $purchase->totalCapital());
        } elseif ($sheepPurchasePrice > 0) {
            $data['purchase_capital'] = $sheepPurchasePrice;
        }

        if ($sheepCount >= $initialHeadCount && $sheepInitialWeight > 0) {
            $data['initial_total_weight_kg'] = $sheepInitialWeight;
        } elseif ($hasPurchases) {
            $data['initial_total_weight_kg'] = (float) (clone $purchaseQuery)->sum('total_weight_kg');
        }

        if ($currentHeadCount === 0 && $batch->status === 'active') {
            $data['status'] = 'closed';
            $data['end_date'] = now()->toDateString();
        }

        if ($currentHeadCount > 0 && $batch->status === 'closed') {
            $data['status'] = 'active';
            $data['end_date'] = null;
        }

        $batch->forceFill($data)->saveQuietly();
    }

    public function averageInitialWeight(FatteningBatch $batch): ?float
    {
        if ((int) $batch->initial_head_count < 1 || blank($batch->initial_total_weight_kg)) {
            return null;
        }

        return round((float) $batch->initial_total_weight_kg / (int) $batch->initial_head_count, 2);
    }

    private function detailStatus(FatteningBatch $batch, int $initialHeadCount): string
    {
        if ($initialHeadCount < 1) {
            return 'incomplete';
        }

        $sheepQuery = Sheep::query()->where('fattening_batch_id', $batch->id);

        if ((clone $sheepQuery)->count() < $initialHeadCount) {
            return 'incomplete';
        }

        $hasEstimatedData = (clone $sheepQuery)
            ->where('is_estimated', true)
            ->exists();

        $hasIncompleteData = (clone $sheepQuery)
            ->where(function ($query): void {
                $query
                    ->whereNull('initial_weight_kg')
                    ->orWhereNull('purchase_price');
            })
            ->exists();

        return $hasEstimatedData || $hasIncompleteData ? 'incomplete' : 'complete';
    }
}
