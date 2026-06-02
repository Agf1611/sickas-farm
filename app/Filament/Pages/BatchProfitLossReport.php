<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Sale;
use App\Services\ReportExportService;
use App\Support\SickasFormatter;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class BatchProfitLossReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laba Rugi Per Batch';

    protected static ?string $title = 'Laporan Laba Rugi Per Batch';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.batch-profit-loss-report';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.financial.view') ?? false;
    }

    public ?string $batchId = null;

    public ?string $livestockTypeId = null;

    public ?string $periodFrom = null;

    public ?string $periodUntil = null;

    public array $tableFilters = [];

    public function resetFilters(): void
    {
        $this->batchId = null;
        $this->livestockTypeId = null;
        $this->periodFrom = null;
        $this->periodUntil = null;
        $this->tableFilters = [];
    }

    public function exportExcel()
    {
        return app(ReportExportService::class)->downloadProfitLossExcel($this->exportFilters());
    }

    public function exportPdf()
    {
        return app(ReportExportService::class)->downloadProfitLossPdf($this->exportFilters());
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

    public function getLivestockTypeOptions(): array
    {
        return LivestockType::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getRows(): Collection
    {
        return FatteningBatch::query()
            ->with(['pen', 'supplier', 'livestockType'])
            ->when($this->batchId, fn (Builder $query, string $batchId): Builder => $query->whereKey($batchId))
            ->when($this->livestockTypeId, fn (Builder $query, string $livestockTypeId): Builder => $query->where('livestock_type_id', $livestockTypeId))
            ->when($this->getPeriodFrom() || $this->getPeriodUntil(), fn (Builder $query): Builder => $this->applyPeriodFilter($query, [
                'from' => $this->getPeriodFrom(),
                'until' => $this->getPeriodUntil(),
            ]))
            ->orderByDesc('start_date')
            ->limit(100)
            ->get()
            ->map(fn (FatteningBatch $batch): array => $this->makeBatchRow($batch));
    }

    public function getSummary(): array
    {
        $rows = $this->getRows();

        return [
            'purchase_capital' => $rows->sum('purchase_capital'),
            'total_expenses' => $rows->sum('total_expenses'),
            'total_sales' => $rows->sum('total_sales'),
            'profit_loss' => $rows->sum('profit_loss'),
        ];
    }

    public function formatRupiah(float|int|string|null $amount): string
    {
        return SickasFormatter::rupiah($amount);
    }

    public function formatNumber(float|int|string|null $value): string
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

    public function profitColor(float|int $amount): string
    {
        return (float) $amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    }

    public function profitBadgeColor(float|int $amount): string
    {
        return (float) $amount >= 0 ? 'success' : 'danger';
    }

    private function getBatchExpenses(FatteningBatch $batch): float
    {
        return (float) Expense::query()
            ->where('fattening_batch_id', $batch->id)
            ->when($this->getPeriodFrom(), fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
            ->when($this->getPeriodUntil(), fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date))
            ->sum('amount');
    }

    private function getBatchSales(FatteningBatch $batch): float
    {
        return (float) Sale::query()
            ->where('fattening_batch_id', $batch->id)
            ->when($this->getPeriodFrom(), fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
            ->when($this->getPeriodUntil(), fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date))
            ->sum('total_amount');
    }

    private function getBatchProfitLoss(FatteningBatch $batch): float
    {
        return $this->getBatchSales($batch) - (float) $batch->purchase_capital - $this->getBatchExpenses($batch);
    }

    private function makeBatchRow(FatteningBatch $batch): array
    {
        $totalExpenses = $this->getBatchExpenses($batch);
        $totalSales = $this->getBatchSales($batch);
        $profitLoss = $totalSales - (float) $batch->purchase_capital - $totalExpenses;

        return [
            'batch_code' => $batch->batch_code,
            'livestock_type_name' => $batch->livestockType?->name ?? 'Domba',
            'source_label' => $batch->is_historical ? 'Histori Manual' : 'Normal',
            'source_color' => $batch->is_historical ? 'warning' : 'gray',
            'pen_name' => $batch->pen?->name ?? 'Tanpa kandang',
            'supplier_name' => $batch->supplier?->name ?? '-',
            'initial_head_count' => (int) $batch->initial_head_count,
            'current_head_count' => (int) $batch->current_head_count,
            'purchase_capital' => (float) $batch->purchase_capital,
            'total_expenses' => $totalExpenses,
            'total_sales' => $totalSales,
            'profit_loss' => $profitLoss,
            'status' => $batch->status,
        ];
    }

    private function applyPeriodFilter(Builder $query, array $data): Builder
    {
        $from = $data['from'] ?? null;
        $until = $data['until'] ?? null;

        if (! $from && ! $until) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($from, $until): void {
            $query
                ->where(function (Builder $query) use ($from, $until): void {
                    $query
                        ->when($from, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
                        ->when($until, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date));
                })
                ->orWhereHas('expenses', fn (Builder $query): Builder => $this->applyExpensePeriod($query))
                ->orWhereHas('sales', fn (Builder $query): Builder => $this->applySalePeriod($query));
        });
    }

    private function applyExpensePeriod(Builder $query): Builder
    {
        return $query
            ->when($this->getPeriodFrom(), fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
            ->when($this->getPeriodUntil(), fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date));
    }

    private function applySalePeriod(Builder $query): Builder
    {
        return $query
            ->when($this->getPeriodFrom(), fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
            ->when($this->getPeriodUntil(), fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date));
    }

    private function getPeriodFrom(): ?string
    {
        return $this->periodFrom ?: ($this->tableFilters['period']['from'] ?? null);
    }

    private function getPeriodUntil(): ?string
    {
        return $this->periodUntil ?: ($this->tableFilters['period']['until'] ?? null);
    }

    private function exportFilters(): array
    {
        return [
            'batch_id' => $this->batchId,
            'livestock_type_id' => $this->livestockTypeId,
            'from' => $this->getPeriodFrom(),
            'until' => $this->getPeriodUntil(),
        ];
    }

    private function exportUrl(string $format): string
    {
        return route('sickas-farm.reports.export.profit-loss', [
            'format' => $format,
            ...array_filter($this->exportFilters(), fn ($value): bool => filled($value)),
        ]);
    }
}
