<?php

namespace App\Filament\Pages;

use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Pen;
use App\Models\Sheep;
use App\Services\GrowthMonitoringService;
use App\Support\SickasFormatter;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class GrowthMonitoring extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Ternak';

    protected static ?string $navigationLabel = 'Monitoring Pertumbuhan';

    protected static ?string $title = 'Monitoring Pertumbuhan Ternak';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.growth-monitoring';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('reports.operational.view') || $user?->can('batches.view'));
    }

    public ?string $batchId = null;

    public string $monitoringMode = 'batch';

    public ?string $penId = null;

    public ?string $livestockTypeId = null;

    public ?string $growthStatus = null;

    public ?string $startDateFrom = null;

    public ?string $startDateUntil = null;

    public function resetFilters(): void
    {
        $this->batchId = null;
        $this->monitoringMode = 'batch';
        $this->penId = null;
        $this->livestockTypeId = null;
        $this->growthStatus = null;
        $this->startDateFrom = null;
        $this->startDateUntil = null;
    }

    public function getBatchOptions(): array
    {
        return FatteningBatch::query()
            ->orderByDesc('start_date')
            ->orderBy('batch_code')
            ->pluck('batch_code', 'id')
            ->all();
    }

    public function getPenOptions(): array
    {
        return Pen::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getLivestockTypeOptions(): array
    {
        return LivestockType::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getGrowthStatusOptions(): array
    {
        return [
            'Bagus' => 'Bagus',
            'Lambat' => 'Lambat',
            'Stagnan' => 'Stagnan',
            'Turun' => 'Turun',
            'Belum Ditimbang' => 'Belum Ditimbang',
        ];
    }

    public function formatKg(?float $value): string
    {
        return SickasFormatter::kg($value);
    }

    public function formatAdg(?float $value): string
    {
        return SickasFormatter::adg($value);
    }

    public function formatDate(?CarbonInterface $value): string
    {
        return SickasFormatter::date($value);
    }

    public function statusColor(string $status): string
    {
        return app(GrowthMonitoringService::class)->colorForStatus($status);
    }

    public function weighingAlertColor(string $status): string
    {
        return app(GrowthMonitoringService::class)->colorForWeighingAlert($status);
    }

    public function sellingIndicatorColor(string $status): string
    {
        return app(GrowthMonitoringService::class)->colorForSellingIndicator($status);
    }

    public function getRows(): Collection
    {
        if ($this->monitoringMode === 'individual') {
            return $this->getIndividualRows();
        }

        $service = app(GrowthMonitoringService::class);

        return FatteningBatch::query()
            ->with(['pen', 'supplier', 'livestockType'])
            ->when($this->batchId, fn ($query): mixed => $query->whereKey($this->batchId))
            ->when($this->penId, fn ($query): mixed => $query->where('pen_id', $this->penId))
            ->when($this->livestockTypeId, fn ($query): mixed => $query->where('livestock_type_id', $this->livestockTypeId))
            ->when($this->startDateFrom, fn ($query): mixed => $query->whereDate('start_date', '>=', $this->startDateFrom))
            ->when($this->startDateUntil, fn ($query): mixed => $query->whereDate('start_date', '<=', $this->startDateUntil))
            ->orderByDesc('start_date')
            ->limit(50)
            ->get()
            ->map(fn (FatteningBatch $batch): array => $this->makeBatchRow($batch, $service))
            ->when(
                $this->growthStatus,
                fn (Collection $rows): Collection => $rows->where('status', $this->growthStatus)->values(),
            );
    }

    public function getSummary(): array
    {
        $rows = $this->getRows();

        return [
            'total_batches' => $rows->count(),
            'average_adg' => $rows->whereNotNull('adg')->avg('adg'),
            'needs_reweighing' => $rows->where('weighing_alert_status', 'Perlu Timbang Ulang')->count(),
            'not_weighed' => $rows->where('weighing_alert_status', 'Belum Ditimbang')->count(),
            'good' => $rows->where('status', 'Bagus')->count(),
            'down' => $rows->where('status', 'Turun')->count(),
        ];
    }

    private function makeBatchRow(FatteningBatch $batch, GrowthMonitoringService $service): array
    {
        return [
            'batch_code' => $batch->batch_code,
            'livestock_type_name' => $batch->livestockType?->name ?? 'Domba',
            'start_date' => $batch->start_date,
            'pen_name' => $batch->pen?->name ?? 'Tanpa kandang',
            'initial_head_count' => $batch->initial_head_count,
            'current_head_count' => $batch->current_head_count,
            ...$service->calculateBatchGrowth($batch),
        ];
    }

    private function getIndividualRows(): Collection
    {
        $service = app(GrowthMonitoringService::class);

        return Sheep::query()
            ->with(['fatteningBatch.pen', 'livestockType', 'pen'])
            ->whereIn('status', ['active', 'sick'])
            ->when($this->batchId, fn ($query): mixed => $query->where('fattening_batch_id', $this->batchId))
            ->when($this->penId, fn ($query): mixed => $query->where('pen_id', $this->penId))
            ->when($this->livestockTypeId, fn ($query): mixed => $query->where('livestock_type_id', $this->livestockTypeId))
            ->when($this->startDateFrom, fn ($query): mixed => $query->whereHas('fatteningBatch', fn ($query): mixed => $query->whereDate('start_date', '>=', $this->startDateFrom)))
            ->when($this->startDateUntil, fn ($query): mixed => $query->whereHas('fatteningBatch', fn ($query): mixed => $query->whereDate('start_date', '<=', $this->startDateUntil)))
            ->orderBy('tag_number')
            ->limit(80)
            ->get()
            ->map(function (Sheep $sheep) use ($service): array {
                $growth = $service->calculateSheepGrowth($sheep);
                $batch = $sheep->fatteningBatch;

                return [
                    'batch_code' => $sheep->tag_number,
                    'livestock_type_name' => $sheep->livestockType?->name ?? 'Domba',
                    'start_date' => $batch?->start_date,
                    'pen_name' => $sheep->pen?->name ?? $batch?->pen?->name ?? 'Tanpa kandang',
                    'initial_head_count' => 1,
                    'current_head_count' => 1,
                    'initial_average_weight' => $growth['initial_weight'],
                    'latest_average_weight' => $growth['latest_weight'],
                    'average_weight_gain' => $growth['weight_gain'],
                    'target_sale_average_weight' => null,
                    'selling_indicator' => 'Evaluasi Pakan dan Perawatan',
                    'selling_indicator_description' => 'Keputusan jual tetap dilihat dari batch dan kondisi ternak.',
                    ...$growth,
                ];
            })
            ->when(
                $this->growthStatus,
                fn (Collection $rows): Collection => $rows->where('status', $this->growthStatus)->values(),
            );
    }
}
