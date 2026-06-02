<?php

namespace App\Filament\Pages;

use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Pen;
use App\Services\ReportExportService;
use App\Support\SickasFormatter;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class SheepPopulationReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Populasi Ternak';

    protected static ?string $title = 'Laporan Populasi Ternak';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.sheep-population-report';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.operational.view') ?? false;
    }

    public ?string $penId = null;

    public ?string $batchId = null;

    public ?string $livestockTypeId = null;

    public ?string $sheepStatus = null;

    public ?string $purchaseDateFrom = null;

    public ?string $purchaseDateUntil = null;

    public function resetFilters(): void
    {
        $this->penId = null;
        $this->batchId = null;
        $this->livestockTypeId = null;
        $this->sheepStatus = null;
        $this->purchaseDateFrom = null;
        $this->purchaseDateUntil = null;
    }

    public function exportExcel()
    {
        return app(ReportExportService::class)->downloadPopulationExcel($this->exportFilters());
    }

    public function exportPdf()
    {
        return app(ReportExportService::class)->downloadPopulationPdf($this->exportFilters());
    }

    public function exportExcelUrl(): string
    {
        return $this->exportUrl('excel');
    }

    public function exportPdfUrl(): string
    {
        return $this->exportUrl('pdf');
    }

    public function getPenOptions(): array
    {
        return Pen::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getBatchOptions(): array
    {
        return FatteningBatch::query()
            ->orderByDesc('start_date')
            ->orderBy('batch_code')
            ->pluck('batch_code', 'id')
            ->all();
    }

    public function getLivestockTypeOptions(): array
    {
        return LivestockType::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getSheepStatusOptions(): array
    {
        return [
            'active' => 'Aktif',
            'dead' => 'Mati',
            'culled' => 'Afkir',
            'sold' => 'Terjual',
            'lost' => 'Hilang',
            'sick' => 'Sakit',
        ];
    }

    public function getRows(): Collection
    {
        return FatteningBatch::query()
            ->with(['pen', 'supplier', 'livestockType', 'sheepPurchases', 'sales', 'sheepIncidentRecords'])
            ->when($this->penId, fn ($query): mixed => $query->where('pen_id', $this->penId))
            ->when($this->batchId, fn ($query): mixed => $query->whereKey($this->batchId))
            ->when($this->livestockTypeId, fn ($query): mixed => $query->where('livestock_type_id', $this->livestockTypeId))
            ->orderByDesc('start_date')
            ->limit(150)
            ->get()
            ->map(fn (FatteningBatch $batch): array => $this->makeBatchRow($batch))
            ->filter(fn (array $row): bool => $this->matchesPurchaseDateFilter($row['purchase_date']))
            ->filter(fn (array $row): bool => $this->matchesSheepStatusFilter($row))
            ->values();
    }

    public function getSummary(): array
    {
        $rows = $this->getRows();

        return [
            'initial' => $rows->sum('initial_head_count'),
            'active' => $rows->sum('active_head_count'),
            'dead' => $rows->sum('dead_head_count'),
            'culled' => $rows->sum('culled_head_count'),
            'sold' => $rows->sum('sold_head_count'),
        ];
    }

    public function formatDate(CarbonInterface|string|null $value): string
    {
        if (! $value) {
            return '-';
        }

        return SickasFormatter::date($value);
    }

    public function formatNumber(int|float $value): string
    {
        return SickasFormatter::number($value);
    }

    public function batchStatusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'closed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    public function batchStatusColor(?string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'closed' => 'gray',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    private function makeBatchRow(FatteningBatch $batch): array
    {
        $purchaseDate = $batch->sheepPurchases->min('purchase_date') ?? $batch->start_date;
        $deadCount = (int) $batch->sheepIncidentRecords->where('incident_type', 'dead')->sum('head_count');
        $culledCount = (int) $batch->sheepIncidentRecords->where('incident_type', 'culled')->sum('head_count');
        $lostCount = (int) $batch->sheepIncidentRecords->where('incident_type', 'lost')->sum('head_count');
        $sickCount = (int) $batch->sheepIncidentRecords->where('incident_type', 'sick')->sum('head_count');
        $soldCount = (int) $batch->sales->sum('head_count');

        return [
            'batch_code' => $batch->batch_code,
            'livestock_type_name' => $batch->livestockType?->name ?? 'Domba',
            'pen_name' => $batch->pen?->name ?? 'Tanpa kandang',
            'supplier_name' => $batch->supplier?->name ?? '-',
            'purchase_date' => $purchaseDate,
            'source_label' => $batch->is_historical ? 'Histori Manual' : 'Normal',
            'source_color' => $batch->is_historical ? 'warning' : 'gray',
            'initial_head_count' => (int) $batch->initial_head_count,
            'active_head_count' => (int) $batch->current_head_count,
            'dead_head_count' => $deadCount,
            'culled_head_count' => $culledCount,
            'lost_head_count' => $lostCount,
            'sick_head_count' => $sickCount,
            'sold_head_count' => $soldCount,
            'batch_status' => $batch->status,
        ];
    }

    private function matchesPurchaseDateFilter(CarbonInterface|string|null $purchaseDate): bool
    {
        if (! $this->purchaseDateFrom && ! $this->purchaseDateUntil) {
            return true;
        }

        if (! $purchaseDate) {
            return false;
        }

        $date = CarbonImmutable::parse($purchaseDate)->toDateString();

        if ($this->purchaseDateFrom && $date < $this->purchaseDateFrom) {
            return false;
        }

        if ($this->purchaseDateUntil && $date > $this->purchaseDateUntil) {
            return false;
        }

        return true;
    }

    private function matchesSheepStatusFilter(array $row): bool
    {
        if (! $this->sheepStatus) {
            return true;
        }

        return match ($this->sheepStatus) {
            'active' => $row['active_head_count'] > 0,
            'dead' => $row['dead_head_count'] > 0,
            'culled' => $row['culled_head_count'] > 0,
            'sold' => $row['sold_head_count'] > 0,
            'lost' => $row['lost_head_count'] > 0,
            'sick' => $row['sick_head_count'] > 0,
            default => true,
        };
    }

    private function exportFilters(): array
    {
        return [
            'pen_id' => $this->penId,
            'batch_id' => $this->batchId,
            'livestock_type_id' => $this->livestockTypeId,
            'sheep_status' => $this->sheepStatus,
            'from' => $this->purchaseDateFrom,
            'until' => $this->purchaseDateUntil,
        ];
    }

    private function exportUrl(string $format): string
    {
        return route('sickas-farm.reports.export.population', [
            'format' => $format,
            ...array_filter($this->exportFilters(), fn ($value): bool => filled($value)),
        ]);
    }
}
