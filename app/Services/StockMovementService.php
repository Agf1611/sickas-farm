<?php

namespace App\Services;

use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    private const STOCK_OUT_INCIDENT_TYPES = ['dead', 'lost', 'culled'];

    public function validatePurchase(SheepPurchase $purchase): void
    {
        if (! $purchase->fattening_batch_id) {
            return;
        }

        $batch = FatteningBatch::find($purchase->fattening_batch_id);

        if ($batch) {
            $this->ensureBatchAcceptsNewMovement($batch, $purchase, 'fattening_batch_id');
        }
    }

    public function validateSale(Sale $sale): void
    {
        if (! $sale->fattening_batch_id) {
            return;
        }

        $batch = FatteningBatch::find($sale->fattening_batch_id);

        if (! $batch) {
            return;
        }

        $this->ensureBatchAcceptsNewMovement($batch, $sale, 'fattening_batch_id');

        if ((int) $sale->head_count < 0) {
            $this->fail('Jumlah ternak yang dijual tidak boleh minus.');
        }

        $available = $this->availableHeadCountForSale($batch, $sale->exists ? $sale->id : null);

        if ((int) $sale->head_count > $available) {
            $this->fail("Jumlah ternak dijual melebihi stok batch. Stok tersedia: {$available} ekor.");
        }
    }

    public function validateIncident(SheepIncidentRecord $incident): void
    {
        if ($incident->sheep_id) {
            $this->ensureSheepCanReceiveIncident($incident);
        }

        $batch = $this->resolveIncidentBatch($incident);

        if (! $batch) {
            return;
        }

        $this->ensureBatchAcceptsNewMovement($batch, $incident, 'fattening_batch_id');

        if ((int) $incident->head_count < 1) {
            $this->fail('Jumlah ternak pada kejadian minimal 1 ekor.');
        }

        if (! $this->incidentAffectsStock($incident->incident_type)) {
            return;
        }

        $available = $this->availableHeadCountForIncident($batch, $incident->exists ? $incident->id : null);

        if ((int) $incident->head_count > $available) {
            $this->fail("Jumlah ternak pada kejadian melebihi stok batch. Stok tersedia: {$available} ekor.");
        }
    }

    public function validateSaleItem(SaleItem $saleItem): void
    {
        if (! $saleItem->sheep_id) {
            return;
        }

        $sheep = Sheep::find($saleItem->sheep_id);

        if (! $sheep) {
            return;
        }

        $alreadySold = SaleItem::query()
            ->where('sheep_id', $sheep->id)
            ->when($saleItem->exists, fn ($query): mixed => $query->whereKeyNot($saleItem->id))
            ->exists();

        if ($alreadySold) {
            $this->fail('Ternak ini sudah tercatat pada penjualan lain.');
        }

        $isCurrentSaleItemSheep = $saleItem->exists && (int) $saleItem->getOriginal('sheep_id') === (int) $sheep->id;

        if ($sheep->status !== 'active' && ! ($sheep->status === 'sold' && $isCurrentSaleItemSheep)) {
            $this->fail('Ternak yang sudah terjual, mati, hilang, afkir, atau sakit tidak bisa dijual.');
        }
    }

    public function recalculateBatch(FatteningBatch|int|null $batch): void
    {
        $batch = $batch instanceof FatteningBatch ? $batch : FatteningBatch::find($batch);

        if (! $batch) {
            return;
        }

        $purchaseQuery = SheepPurchase::query()->where('fattening_batch_id', $batch->id);
        $hasPurchases = $purchaseQuery->exists();

        $initialHeadCount = $hasPurchases
            ? (int) (clone $purchaseQuery)->sum('head_count')
            : (int) $batch->initial_head_count;

        $soldHeadCount = (int) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('head_count');

        $incidentStockOut = (int) SheepIncidentRecord::query()
            ->where('fattening_batch_id', $batch->id)
            ->whereIn('incident_type', self::STOCK_OUT_INCIDENT_TYPES)
            ->sum('head_count');

        $currentHeadCount = max(0, $initialHeadCount - $soldHeadCount - $incidentStockOut);
        $data = [
            'initial_head_count' => $initialHeadCount,
            'current_head_count' => $currentHeadCount,
        ];

        if ($hasPurchases) {
            $data['initial_total_weight_kg'] = (float) (clone $purchaseQuery)->sum('total_weight_kg');
            $data['purchase_capital'] = (float) (clone $purchaseQuery)->get()->sum(fn (SheepPurchase $purchase): float => $purchase->totalCapital());
            $data['start_date'] = (clone $purchaseQuery)->min('purchase_date');
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

        app(BatchSyncService::class)->sync($batch->id);
    }

    public function syncSheepStatus(Sheep|int|null $sheep): void
    {
        $sheep = $sheep instanceof Sheep ? $sheep : Sheep::find($sheep);

        if (! $sheep) {
            return;
        }

        $status = match (true) {
            $sheep->saleItems()->exists() => 'sold',
            $sheep->sheepIncidentRecords()->where('incident_type', 'dead')->exists() => 'dead',
            $sheep->sheepIncidentRecords()->where('incident_type', 'lost')->exists() => 'lost',
            $sheep->sheepIncidentRecords()->where('incident_type', 'culled')->exists() => 'culled',
            $sheep->sheepIncidentRecords()->where('incident_type', 'sick')->exists() => 'sick',
            default => 'active',
        };

        if ($sheep->status !== $status) {
            $sheep->forceFill(['status' => $status])->saveQuietly();
        }
    }

    public function recordPurchaseMovement(SheepPurchase $purchase): void
    {
        if (! $purchase->fattening_batch_id || (int) $purchase->head_count < 1) {
            return;
        }

        $batch = $purchase->fatteningBatch ?: FatteningBatch::find($purchase->fattening_batch_id);

        StockMovement::query()->updateOrCreate(
            $this->referenceKey($purchase),
            [
                'movement_date' => $purchase->purchase_date,
                'movement_type' => 'purchase',
                'fattening_batch_id' => $purchase->fattening_batch_id,
                'livestock_type_id' => $purchase->livestock_type_id ?: $batch?->livestock_type_id,
                'pen_id' => $purchase->pen_id ?: $batch?->pen_id,
                'quantity_in' => (int) $purchase->head_count,
                'quantity_out' => 0,
                'balance_after' => $batch?->current_head_count,
                'notes' => $purchase->purchase_number
                    ? "Pembelian {$purchase->purchase_number}"
                    : 'Pembelian ternak',
            ],
        );
    }

    public function recordSaleMovement(Sale $sale): void
    {
        if (! $sale->fattening_batch_id || (int) $sale->head_count < 1) {
            return;
        }

        $batch = $sale->fatteningBatch ?: FatteningBatch::find($sale->fattening_batch_id);

        StockMovement::query()->updateOrCreate(
            $this->referenceKey($sale),
            [
                'movement_date' => $sale->sale_date,
                'movement_type' => 'sale',
                'fattening_batch_id' => $sale->fattening_batch_id,
                'livestock_type_id' => $batch?->livestock_type_id,
                'pen_id' => $batch?->pen_id,
                'quantity_in' => 0,
                'quantity_out' => (int) $sale->head_count,
                'balance_after' => $batch?->current_head_count,
                'notes' => "Penjualan {$sale->sale_number}",
            ],
        );
    }

    public function recordIncidentMovement(SheepIncidentRecord $incident): void
    {
        if (! $this->incidentAffectsStock($incident->incident_type) || (int) $incident->head_count < 1) {
            $this->deleteMovementFor($incident);

            return;
        }

        $batch = $this->resolveIncidentBatch($incident);

        if (! $batch) {
            return;
        }

        StockMovement::query()->updateOrCreate(
            $this->referenceKey($incident),
            [
                'movement_date' => $incident->incident_date,
                'movement_type' => $incident->incident_type === 'dead' ? 'death' : $incident->incident_type,
                'fattening_batch_id' => $batch->id,
                'sheep_id' => $incident->sheep_id,
                'livestock_type_id' => $incident->sheep?->livestock_type_id ?: $batch->livestock_type_id,
                'pen_id' => $incident->sheep?->pen_id ?: $batch->pen_id,
                'quantity_in' => 0,
                'quantity_out' => (int) $incident->head_count,
                'balance_after' => $batch->current_head_count,
                'notes' => $incident->reason ?: $incident->notes ?: 'Kejadian ternak',
            ],
        );
    }

    public function deleteMovementFor(Model $reference): void
    {
        StockMovement::query()
            ->where($this->referenceKey($reference))
            ->delete();
    }

    private function availableHeadCountForSale(FatteningBatch $batch, ?int $ignoredSaleId = null): int
    {
        $initialHeadCount = $this->initialHeadCount($batch);
        $soldHeadCount = (int) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->when($ignoredSaleId, fn ($query): mixed => $query->whereKeyNot($ignoredSaleId))
            ->sum('head_count');
        $incidentStockOut = $this->incidentStockOutCount($batch);

        return max(0, $initialHeadCount - $soldHeadCount - $incidentStockOut);
    }

    private function availableHeadCountForIncident(FatteningBatch $batch, ?int $ignoredIncidentId = null): int
    {
        $initialHeadCount = $this->initialHeadCount($batch);
        $soldHeadCount = (int) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('head_count');
        $incidentStockOut = $this->incidentStockOutCount($batch, $ignoredIncidentId);

        return max(0, $initialHeadCount - $soldHeadCount - $incidentStockOut);
    }

    private function initialHeadCount(FatteningBatch $batch): int
    {
        $purchaseHeadCount = (int) SheepPurchase::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('head_count');

        return $purchaseHeadCount > 0 ? $purchaseHeadCount : (int) $batch->initial_head_count;
    }

    private function incidentStockOutCount(FatteningBatch $batch, ?int $ignoredIncidentId = null): int
    {
        return (int) SheepIncidentRecord::query()
            ->where('fattening_batch_id', $batch->id)
            ->whereIn('incident_type', self::STOCK_OUT_INCIDENT_TYPES)
            ->when($ignoredIncidentId, fn ($query): mixed => $query->whereKeyNot($ignoredIncidentId))
            ->sum('head_count');
    }

    private function resolveIncidentBatch(SheepIncidentRecord $incident): ?FatteningBatch
    {
        if ($incident->fattening_batch_id) {
            return FatteningBatch::find($incident->fattening_batch_id);
        }

        if ($incident->sheep_id) {
            return Sheep::find($incident->sheep_id)?->fatteningBatch;
        }

        return null;
    }

    private function ensureBatchAcceptsNewMovement(FatteningBatch $batch, Model $movement, string $batchKey): void
    {
        $usesSameBatch = $movement->exists && (int) $movement->getOriginal($batchKey) === (int) $batch->id;

        if ($usesSameBatch) {
            return;
        }

        if ($batch->status !== 'active') {
            $this->fail('Batch sudah selesai atau tidak aktif. Aktifkan ulang batch sebelum membuat transaksi baru.');
        }
    }

    private function ensureSheepCanReceiveIncident(SheepIncidentRecord $incident): void
    {
        $sheep = Sheep::find($incident->sheep_id);

        if (! $sheep) {
            return;
        }

        if ($sheep->saleItems()->exists()) {
            $this->fail('Ternak yang sudah terjual tidak bisa dicatat mati, hilang, afkir, atau sakit.');
        }

        $hasOtherTerminalIncident = $sheep->sheepIncidentRecords()
            ->when($incident->exists, fn ($query): mixed => $query->whereKeyNot($incident->id))
            ->whereIn('incident_type', self::STOCK_OUT_INCIDENT_TYPES)
            ->exists();

        if ($hasOtherTerminalIncident) {
            $this->fail('Ternak yang sudah mati, hilang, atau afkir tidak bisa dicatat ulang pada kejadian baru.');
        }
    }

    private function incidentAffectsStock(?string $incidentType): bool
    {
        return in_array($incidentType, self::STOCK_OUT_INCIDENT_TYPES, true);
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages([
            'stock' => $message,
        ]);
    }

    /**
     * @return array{reference_type: class-string<Model>, reference_id: int|null}
     */
    private function referenceKey(Model $reference): array
    {
        return [
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
        ];
    }
}
