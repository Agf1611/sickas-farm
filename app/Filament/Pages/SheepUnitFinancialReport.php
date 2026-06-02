<?php

namespace App\Filament\Pages;

use App\Models\BusinessProfile;
use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Services\ReportExportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class SheepUnitFinancialReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Keuangan Unit Ternak';

    protected static ?string $title = 'Laporan Keuangan Unit Ternak';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.sheep-unit-financial-report';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.financial.view') ?? false;
    }

    public ?string $periodFrom = null;

    public ?string $periodUntil = null;

    public ?string $batchId = null;

    public ?string $penId = null;

    public ?string $livestockTypeId = null;

    public function resetFilters(): void
    {
        $this->periodFrom = null;
        $this->periodUntil = null;
        $this->batchId = null;
        $this->penId = null;
        $this->livestockTypeId = null;
    }

    public function exportExcel()
    {
        return app(ReportExportService::class)->downloadUnitFinancialExcel($this->exportFilters());
    }

    public function exportPdf()
    {
        return app(ReportExportService::class)->downloadUnitFinancialPdf($this->exportFilters());
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

    public function getReportData(): array
    {
        $expenseBreakdown = $this->getExpenseBreakdown();
        $totalExpenses = array_sum($expenseBreakdown);
        $purchaseCapital = $this->getPurchaseCapital();
        $sales = $this->getSalesTotal();

        return [
            'purchase_capital' => $purchaseCapital,
            'feed_expenses' => $expenseBreakdown['feed'],
            'medicine_expenses' => $expenseBreakdown['medicine'],
            'wage_expenses' => $expenseBreakdown['wage'],
            'transport_expenses' => $expenseBreakdown['transport'],
            'pen_equipment_expenses' => $expenseBreakdown['pen_equipment'],
            'other_expenses' => $expenseBreakdown['other'],
            'total_expenses' => $totalExpenses,
            'total_sales' => $sales,
            'net_profit_loss' => $sales - $purchaseCapital - $totalExpenses,
        ];
    }

    public function formatRupiah(float|int $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    public function profitColor(float|int $amount): string
    {
        return (float) $amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    }

    public function periodLabel(): string
    {
        if (! $this->periodFrom && ! $this->periodUntil) {
            return 'Semua periode';
        }

        return trim(($this->periodFrom ?: 'Awal').' sampai '.($this->periodUntil ?: 'akhir'));
    }

    public function unitName(): string
    {
        return BusinessProfile::reportIdentity()['unit_name'] ?? 'Unit Ternak';
    }

    private function getPurchaseCapital(): float
    {
        $purchaseCapital = (float) $this->purchaseQuery()
            ->selectRaw('COALESCE(SUM(total_purchase_price + transport_cost + other_cost), 0) as aggregate')
            ->value('aggregate');

        return $purchaseCapital + $this->getHistoricalPurchaseCapital();
    }

    /**
     * @return array{feed: float, medicine: float, wage: float, transport: float, pen_equipment: float, other: float}
     */
    private function getExpenseBreakdown(): array
    {
        $breakdown = [
            'feed' => 0.0,
            'medicine' => 0.0,
            'wage' => 0.0,
            'transport' => 0.0,
            'pen_equipment' => 0.0,
            'other' => 0.0,
        ];

        $this->expenseQuery()
            ->with('expenseCategory')
            ->get(['id', 'expense_category_id', 'amount'])
            ->each(function (Expense $expense) use (&$breakdown): void {
                $breakdown[$this->classifyExpense($expense)] += (float) $expense->amount;
            });

        return $breakdown;
    }

    private function getSalesTotal(): float
    {
        return (float) $this->saleQuery()->sum('total_amount');
    }

    private function purchaseQuery(): Builder
    {
        return SheepPurchase::query()
            ->when($this->periodFrom, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '>=', $date))
            ->when($this->periodUntil, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '<=', $date))
            ->when($this->batchId, fn (Builder $query, string $batchId): Builder => $query->where('fattening_batch_id', $batchId))
            ->when($this->livestockTypeId, fn (Builder $query, string $livestockTypeId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('livestock_type_id', $livestockTypeId)))
            ->when($this->penId, fn (Builder $query, string $penId): Builder => $query->where(function (Builder $query) use ($penId): void {
                $query
                    ->where('pen_id', $penId)
                    ->orWhereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('pen_id', $penId));
            }));
    }

    private function expenseQuery(): Builder
    {
        return Expense::query()
            ->when($this->periodFrom, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
            ->when($this->periodUntil, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date))
            ->when($this->batchId, fn (Builder $query, string $batchId): Builder => $query->where('fattening_batch_id', $batchId))
            ->when($this->livestockTypeId, fn (Builder $query, string $livestockTypeId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('livestock_type_id', $livestockTypeId)))
            ->when($this->penId, fn (Builder $query, string $penId): Builder => $query->where(function (Builder $query) use ($penId): void {
                $query
                    ->where('pen_id', $penId)
                    ->orWhereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('pen_id', $penId));
            }));
    }

    private function saleQuery(): Builder
    {
        return Sale::query()
            ->when($this->periodFrom, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
            ->when($this->periodUntil, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date))
            ->when($this->batchId, fn (Builder $query, string $batchId): Builder => $query->where('fattening_batch_id', $batchId))
            ->when($this->livestockTypeId, fn (Builder $query, string $livestockTypeId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('livestock_type_id', $livestockTypeId)))
            ->when($this->penId, fn (Builder $query, string $penId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('pen_id', $penId)));
    }

    private function getHistoricalPurchaseCapital(): float
    {
        return (float) FatteningBatch::query()
            ->where('is_historical', true)
            ->when($this->periodFrom, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
            ->when($this->periodUntil, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date))
            ->when($this->batchId, fn (Builder $query, string $batchId): Builder => $query->whereKey($batchId))
            ->when($this->livestockTypeId, fn (Builder $query, string $livestockTypeId): Builder => $query->where('livestock_type_id', $livestockTypeId))
            ->when($this->penId, fn (Builder $query, string $penId): Builder => $query->where('pen_id', $penId))
            ->sum('purchase_capital');
    }

    private function classifyExpense(Expense $expense): string
    {
        $text = str($expense->expenseCategory?->code.' '.$expense->expenseCategory?->name)
            ->lower()
            ->toString();

        foreach ([
            'feed' => ['pakan', 'feed'],
            'medicine' => ['obat', 'vitamin', 'medicine', 'medis', 'kesehatan'],
            'wage' => ['upah', 'gaji', 'honor', 'pengurus', 'wage', 'salary'],
            'transport' => ['transport', 'transportasi', 'angkut', 'kirim'],
            'pen_equipment' => ['kandang', 'peralatan', 'alat', 'perbaikan', 'repair', 'equipment'],
        ] as $group => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $group;
                }
            }
        }

        return 'other';
    }

    private function exportFilters(): array
    {
        return [
            'from' => $this->periodFrom,
            'until' => $this->periodUntil,
            'batch_id' => $this->batchId,
            'pen_id' => $this->penId,
            'livestock_type_id' => $this->livestockTypeId,
        ];
    }

    private function exportUrl(string $format): string
    {
        return route('sickas-farm.reports.export.unit-financial', [
            'format' => $format,
            ...array_filter($this->exportFilters(), fn ($value): bool => filled($value)),
        ]);
    }
}
