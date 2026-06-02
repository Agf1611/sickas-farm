<?php

namespace App\Services;

use App\Models\LivestockType;
use App\Models\Sheep;
use App\Models\SheepPurchase;
use Illuminate\Support\Facades\DB;

class PurchaseSheepGenerationService
{
    public function generateForPurchase(SheepPurchase $purchase): void
    {
        if (! $purchase->fattening_batch_id || (int) $purchase->head_count < 1) {
            return;
        }

        if ($purchase->purchase_type !== 'bulk') {
            return;
        }

        if ($purchase->sheep()->exists() || $purchase->fatteningBatch?->sheep()->exists()) {
            app(BatchSyncService::class)->sync($purchase->fattening_batch_id);

            return;
        }

        DB::transaction(function () use ($purchase): void {
            $livestockTypeId = $this->resolveLivestockTypeId($purchase);
            $headCount = max(1, (int) $purchase->head_count);
            $averageWeight = filled($purchase->total_weight_kg)
                ? round((float) $purchase->total_weight_kg / $headCount, 2)
                : null;
            $averagePurchasePrice = round((float) $purchase->total_purchase_price / $headCount, 2);

            for ($index = 0; $index < $headCount; $index++) {
                Sheep::create([
                    'sheep_purchase_id' => $purchase->id,
                    'livestock_type_id' => $livestockTypeId,
                    'fattening_batch_id' => $purchase->fattening_batch_id,
                    'pen_id' => $purchase->pen_id,
                    'initial_weight_kg' => $averageWeight,
                    'purchase_price' => $averagePurchasePrice,
                    'is_estimated' => true,
                    'status' => 'active',
                    'notes' => 'Data otomatis dari pembelian borongan. Silakan lengkapi detail ternak.',
                ]);
            }

            app(BatchSyncService::class)->sync($purchase->fattening_batch_id);
        });
    }

    private function resolveLivestockTypeId(SheepPurchase $purchase): ?int
    {
        if ($purchase->livestock_type_id) {
            return $purchase->livestock_type_id;
        }

        if ($purchase->fatteningBatch?->livestock_type_id) {
            return $purchase->fatteningBatch->livestock_type_id;
        }

        return LivestockType::query()
            ->where('code', 'DMB')
            ->value('id');
    }
}
