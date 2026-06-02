<?php

namespace App\Filament\Pages;

use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Pen;
use App\Services\GrowthMonitoringService;
use App\Services\ReportExportService;
use App\Support\SickasFormatter;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class FatteningPerformanceReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Performa Penggemukan';

    protected static ?string $title = 'Laporan Performa Penggemukan';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.fattening-performance-report';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.operational.view') ?? false;
    }

    public ?string $batchId = null;

    public ?string $penId = null;

    public ?string $livestockTypeId = null;

    public ?string $growthStatus = null;

    public ?string $startDateFrom = null;

    public ?string $startDateUntil = null;

    public function resetFilters(): void
    {
        $this->batchId = null;
        $this->penId = null;
        $this->livestockTypeId = null;
        $this->growthStatus = null;
        $this->startDateFrom = null;
        $this->startDateUntil = null;
    }

    public function exportExcel()
    {
        return app(ReportExportService::class)->downloadPerformanceExcel($this->exportFilters());
    }

    public function exportPdf()
    {
        return app(ReportExportService::class)->downloadPerformancePdf($this->exportFilters());
    }

    public function exportExcelUrl(): string
    {
        return $this->exportUrl('excel');
    }

    public function exportPdfUrl(): string
    {
        return $this->exportUrl('pdf');
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

    public function getRows(): Collection
    {
        $service = app(GrowthMonitoringService::class);

        return FatteningBatch::query()
            ->with(['pen', 'livestockType'])
            ->when($this->batchId, fn ($query): mixed => $query->whereKey($this->batchId))
            ->when($this->penId, fn ($query): mixed => $query->where('pen_id', $this->penId))
            ->when($this->livestockTypeId, fn ($query): mixed => $query->where('livestock_type_id', $this->livestockTypeId))
            ->when($this->startDateFrom, fn ($query): mixed => $query->whereDate('start_date', '>=', $this->startDateFrom))
            ->when($this->startDateUntil, fn ($query): mixed => $query->whereDate('start_date', '<=', $this->startDateUntil))
            ->orderByDesc('start_date')
            ->limit(100)
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
            'ready_to_review' => $rows->where('selling_indicator', 'Siap Dipertimbangkan untuk Dijual')->count(),
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

    public function sellingIndicatorColor(string $status): string
    {
        return app(GrowthMonitoringService::class)->colorForSellingIndicator($status);
    }

    private function makeBatchRow(FatteningBatch $batch, GrowthMonitoringService $service): array
    {
        return [
            'batch_code' => $batch->batch_code,
            'livestock_type_name' => $batch->livestockType?->name ?? 'Domba',
            'pen_name' => $batch->pen?->name ?? 'Tanpa kandang',
            'start_date' => $batch->start_date,
            'source_label' => $batch->is_historical ? 'Histori Manual' : 'Normal',
            'source_color' => $batch->is_historical ? 'warning' : 'gray',
            'initial_head_count' => $batch->initial_head_count,
            'current_head_count' => $batch->current_head_count,
            ...$service->calculateBatchGrowth($batch),
        ];
    }

    private function exportFilters(): array
    {
        return [
            'batch_id' => $this->batchId,
            'pen_id' => $this->penId,
            'livestock_type_id' => $this->livestockTypeId,
            'growth_status' => $this->growthStatus,
            'from' => $this->startDateFrom,
            'until' => $this->startDateUntil,
        ];
    }

    private function exportUrl(string $format): string
    {
        return route('sickas-farm.reports.export.performance', [
            'format' => $format,
            ...array_filter($this->exportFilters(), fn ($value): bool => filled($value)),
        ]);
    }
}
