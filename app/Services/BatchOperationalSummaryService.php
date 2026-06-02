<?php

namespace App\Services;

use App\Filament\Pages\IndividualWeighing;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\MortalityRecords\MortalityRecordResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Sheep\SheepResource;
use App\Filament\Resources\WeightRecords\WeightRecordResource;
use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\WeighingRecord;
use Illuminate\Support\Collection;

class BatchOperationalSummaryService
{
    public function __construct(
        private readonly GrowthMonitoringService $growthMonitoring,
        private readonly QrCodeService $qrCode,
        private readonly MarketValueEstimationService $marketValueEstimation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summarize(FatteningBatch $batch): array
    {
        $batch->loadMissing(['pen', 'supplier', 'livestockType']);

        $purchases = SheepPurchase::query()
            ->with('supplier')
            ->where('fattening_batch_id', $batch->id)
            ->get();

        $sheep = Sheep::query()
            ->with(['livestockType', 'pen'])
            ->where('fattening_batch_id', $batch->id)
            ->orderBy('tag_number')
            ->get();

        $growth = $this->growthMonitoring->calculateBatchGrowth($batch);
        $latestActualBatchWeighing = $this->latestBatchWeighing($batch, 'actual_batch');
        $latestIndividualSummary = $this->latestBatchWeighing($batch, 'actual_individual');
        $expenses = (float) Expense::query()->where('fattening_batch_id', $batch->id)->sum('amount');
        $sales = (float) Sale::query()->where('fattening_batch_id', $batch->id)->sum('total_amount');
        $transportCost = (float) $purchases->sum('transport_cost');
        $otherCost = (float) $purchases->sum('other_cost');
        $purchaseCapital = (float) $batch->purchase_capital;
        $sheepRows = $this->sheepRows($sheep);
        $marketEstimate = $this->marketValueEstimation->estimateBatch($batch);

        return [
            'header' => [
                'batch_code' => $batch->batch_code,
                'livestock_type' => $batch->livestockType?->name ?? 'Domba',
                'pen' => $batch->pen?->name ?? 'Tanpa kandang',
                'supplier' => $batch->supplier?->name ?? $purchases->first()?->supplier?->name ?? 'Tanpa supplier',
                'purchase_date' => $purchases->min('purchase_date') ?: $batch->start_date,
                'status' => $this->batchStatusLabel($batch->status),
                'status_color' => $this->batchStatusColor($batch->status),
                'detail_status' => $batch->detail_status === 'complete' ? 'Detail Lengkap' : 'Detail Belum Lengkap',
                'detail_color' => $batch->detail_status === 'complete' ? 'success' : 'warning',
                'data_quality' => $this->dataQuality($sheep),
                'data_quality_color' => $this->dataQualityColor($sheep),
            ],
            'population' => $this->populationSummary($batch),
            'weight' => [
                'initial_total' => (float) $batch->initial_total_weight_kg,
                'initial_average' => $batch->average_initial_weight_kg,
                'latest_batch_total' => $latestActualBatchWeighing?->total_weight_kg !== null ? (float) $latestActualBatchWeighing->total_weight_kg : null,
                'latest_batch_average' => $latestActualBatchWeighing?->average_weight_kg !== null ? (float) $latestActualBatchWeighing->average_weight_kg : null,
                'latest_individual_total' => $latestIndividualSummary?->total_weight_kg !== null ? (float) $latestIndividualSummary->total_weight_kg : null,
                'latest_individual_average' => $latestIndividualSummary?->average_weight_kg !== null ? (float) $latestIndividualSummary->average_weight_kg : null,
                'weight_gain' => $growth['weight_gain'],
                'adg' => $growth['adg'],
                'status' => $growth['status'],
                'status_color' => $this->growthMonitoring->colorForStatus($growth['status']),
                'weighing_alert' => $growth['weighing_alert_status'],
                'weighing_alert_color' => $this->growthMonitoring->colorForWeighingAlert($growth['weighing_alert_status']),
            ],
            'finance' => [
                'purchase_capital' => $purchaseCapital,
                'transport_cost' => $transportCost,
                'other_cost' => $otherCost,
                'expenses' => $expenses,
                'sales' => $sales,
                'profit' => $sales - $purchaseCapital - $expenses,
                'estimated_market_value' => $marketEstimate['estimated_value'],
                'estimated_profit_loss_today' => $marketEstimate['estimated_profit_loss'],
                'market_unit_price' => $marketEstimate['unit_price'],
                'market_price_type' => $marketEstimate['price_type'],
            ],
            'warnings' => $this->warnings($batch, $sheep, $sheepRows, $growth),
            'sheep_rows' => $sheepRows,
            'actions' => $this->actions($batch),
        ];
    }

    private function latestBatchWeighing(FatteningBatch $batch, string $source): ?WeighingRecord
    {
        return WeighingRecord::query()
            ->where('fattening_batch_id', $batch->id)
            ->where(function ($query): void {
                $query->where('weight_type', 'batch')->orWhere('record_type', 'batch');
            })
            ->where('source', $source)
            ->whereNotNull('total_weight_kg')
            ->latest('weighed_at')
            ->latest('id')
            ->first();
    }

    private function populationSummary(FatteningBatch $batch): array
    {
        $dead = (int) SheepIncidentRecord::query()
            ->where('fattening_batch_id', $batch->id)
            ->where('incident_type', 'dead')
            ->sum('head_count');
        $culled = (int) SheepIncidentRecord::query()
            ->where('fattening_batch_id', $batch->id)
            ->where('incident_type', 'culled')
            ->sum('head_count');
        $sold = (int) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->sum('head_count');

        return [
            'initial' => (int) $batch->initial_head_count,
            'active' => (int) Sheep::query()->where('fattening_batch_id', $batch->id)->where('status', 'active')->count(),
            'dead' => $dead,
            'culled' => $culled,
            'sold' => $sold,
            'current' => (int) $batch->current_head_count,
        ];
    }

    private function warnings(FatteningBatch $batch, Collection $sheep, Collection $sheepRows, array $growth): array
    {
        $warnings = [];

        if ($batch->detail_status !== 'complete' || $sheep->where('is_estimated', true)->isNotEmpty()) {
            $warnings[] = ['label' => 'Detail ternak belum lengkap', 'description' => 'Masih ada data rata-rata/estimasi yang perlu dilengkapi.', 'tone' => 'warning'];
        }

        $missingPhotos = $sheep->filter(fn (Sheep $item): bool => blank($item->photo_paths))->count();
        if ($missingPhotos > 0) {
            $warnings[] = ['label' => 'Foto ternak belum lengkap', 'description' => $missingPhotos.' ekor belum memiliki foto.', 'tone' => 'warning'];
        }

        $neverWeighed = $sheep->filter(fn (Sheep $item): bool => ! $item->weighingRecords()
            ->where(function ($query): void {
                $query->where('weight_type', 'per_ekor')->orWhere('record_type', 'individual');
            })
            ->whereNotNull('weight_kg')
            ->exists())->count();
        if ($neverWeighed > 0) {
            $warnings[] = ['label' => 'Ternak belum ditimbang per ekor', 'description' => $neverWeighed.' ekor belum punya timbang aktual per ekor.', 'tone' => 'danger'];
        }

        $down = $sheepRows->where('growth_status', 'Turun')->count();
        if ($down > 0) {
            $warnings[] = ['label' => 'Ada ternak berat turun', 'description' => $down.' ekor perlu dicek kesehatan/pakan.', 'tone' => 'danger'];
        }

        if ($growth['weighing_alert_status'] === 'Perlu Timbang Ulang') {
            $warnings[] = ['label' => 'Batch perlu timbang ulang', 'description' => 'Timbang terakhir sudah lebih dari 14 hari.', 'tone' => 'orange'];
        }

        $sick = $sheep->where('status', 'sick')->count();
        if ($sick > 0) {
            $warnings[] = ['label' => 'Ada ternak sakit', 'description' => $sick.' ekor sedang berstatus sakit.', 'tone' => 'danger'];
        }

        return $warnings;
    }

    private function sheepRows(Collection $sheep): Collection
    {
        return $sheep->map(function (Sheep $item): array {
            $growth = $this->growthMonitoring->calculateSheepGrowth($item);
            $marketEstimate = $this->marketValueEstimation->estimateSheep($item);
            $hasPhoto = filled($item->photo_paths);
            $hasIndividualWeight = $growth['latest_weight'] !== null;

            return [
                'id' => $item->id,
                'photo_paths' => $item->photo_paths,
                'tag_number' => $item->tag_number,
                'livestock_type' => $item->livestockType?->name ?? 'Domba',
                'initial_weight' => $item->initial_weight_kg !== null ? (float) $item->initial_weight_kg : null,
                'latest_weight' => $growth['latest_weight'],
                'weight_gain' => $growth['weight_gain'],
                'adg' => $growth['adg'],
                'purchase_price' => $item->purchase_price !== null ? (float) $item->purchase_price : null,
                'estimated_market_value' => $marketEstimate['estimated_value'],
                'estimated_profit_loss' => $marketEstimate['estimated_profit_loss'],
                'status' => $this->sheepStatusLabel($item->status),
                'status_color' => $this->sheepStatusColor($item->status),
                'growth_status' => $growth['status'],
                'growth_color' => $this->growthMonitoring->colorForStatus($growth['status']),
                'data_status' => $item->is_estimated ? 'Rata-rata' : 'Aktual',
                'data_color' => $item->is_estimated ? 'warning' : 'success',
                'photo_status' => $hasPhoto ? 'Foto Ada' : 'Foto Belum Ada',
                'photo_color' => $hasPhoto ? 'success' : 'warning',
                'weighing_status' => $hasIndividualWeight ? 'Sudah Ditimbang' : 'Belum Ditimbang',
                'weighing_color' => $hasIndividualWeight ? 'success' : 'danger',
                'detail_url' => SheepResource::getUrl('view', ['record' => $item]),
            ];
        });
    }

    private function actions(FatteningBatch $batch): array
    {
        return [
            ['label' => 'Timbang Batch', 'url' => WeightRecordResource::getUrl('index'), 'tone' => 'info'],
            ['label' => 'Timbang Per Ekor', 'url' => IndividualWeighing::getUrl(['batchId' => $batch->id]), 'tone' => 'success'],
            ['label' => 'Lengkapi Data Ternak', 'url' => SheepResource::getUrl('index'), 'tone' => 'warning'],
            ['label' => 'Catat Pengeluaran', 'url' => ExpenseResource::getUrl('index'), 'tone' => 'orange'],
            ['label' => 'Catat Sakit / Obat', 'url' => MortalityRecordResource::getUrl('index'), 'tone' => 'warning'],
            ['label' => 'Catat Mati / Afkir', 'url' => MortalityRecordResource::getUrl('index'), 'tone' => 'danger'],
            ['label' => 'Jual Ternak', 'url' => SaleResource::getUrl('index'), 'tone' => 'purple'],
            ['label' => 'Cetak QR Batch', 'url' => $this->qrCode->batchPrintUrl($batch), 'tone' => 'gray', 'new_tab' => true],
        ];
    }

    private function dataQuality(Collection $sheep): string
    {
        if ($sheep->isEmpty()) {
            return 'Belum Ada Data Ternak';
        }

        return $sheep->where('is_estimated', true)->isNotEmpty() ? 'Data Rata-rata' : 'Data Aktual';
    }

    private function dataQualityColor(Collection $sheep): string
    {
        if ($sheep->isEmpty()) {
            return 'gray';
        }

        return $sheep->where('is_estimated', true)->isNotEmpty() ? 'warning' : 'success';
    }

    private function batchStatusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'closed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    private function batchStatusColor(?string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'closed' => 'gray',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    private function sheepStatusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'sold' => 'Terjual',
            'dead' => 'Mati',
            'lost' => 'Hilang',
            'culled' => 'Afkir',
            'sick' => 'Sakit',
            default => '-',
        };
    }

    private function sheepStatusColor(?string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'sold' => 'info',
            'dead' => 'danger',
            'lost', 'sick' => 'warning',
            'culled' => 'gray',
            default => 'gray',
        };
    }
}
