<?php

namespace App\Services;

use App\Exports\ArrayReportExport;
use App\Models\BusinessProfile;
use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Support\SickasFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportExportService
{
    public function __construct(
        private readonly GrowthMonitoringService $growthMonitoring,
    ) {}

    public function downloadPopulationExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildPopulationReport($filters));
    }

    public function downloadPopulationPdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildPopulationReport($filters));
    }

    public function downloadPurchasesExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildPurchasesReport($filters));
    }

    public function downloadPurchasesPdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildPurchasesReport($filters));
    }

    public function downloadExpensesExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildExpensesReport($filters));
    }

    public function downloadExpensesPdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildExpensesReport($filters));
    }

    public function downloadSalesExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildSalesReport($filters));
    }

    public function downloadSalesPdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildSalesReport($filters));
    }

    public function downloadProfitLossExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildProfitLossReport($filters));
    }

    public function downloadProfitLossPdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildProfitLossReport($filters));
    }

    public function downloadPerformanceExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildPerformanceReport($filters));
    }

    public function downloadPerformancePdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildPerformanceReport($filters));
    }

    public function downloadUnitFinancialExcel(array $filters = []): BinaryFileResponse
    {
        return $this->downloadExcel($this->buildUnitFinancialReport($filters));
    }

    public function downloadUnitFinancialPdf(array $filters = []): Response
    {
        return $this->downloadPdf($this->buildUnitFinancialReport($filters));
    }

    public function buildPopulationReport(array $filters = []): array
    {
        $rows = FatteningBatch::query()
            ->with(['pen', 'supplier', 'livestockType', 'sheepPurchases', 'sales', 'sheepIncidentRecords'])
            ->when($filters['pen_id'] ?? null, fn (Builder $query, string $penId): Builder => $query->where('pen_id', $penId))
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->whereKey($batchId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->where('livestock_type_id', $livestockTypeId))
            ->orderByDesc('start_date')
            ->get()
            ->map(function (FatteningBatch $batch): array {
                $purchaseDate = $batch->sheepPurchases->min('purchase_date') ?? $batch->start_date;

                return [
                    'Kode Batch' => $batch->batch_code,
                    'Jenis Ternak' => $batch->livestockType?->name ?? 'Domba',
                    'Kandang' => $batch->pen?->name ?? 'Tanpa kandang',
                    'Supplier' => $batch->supplier?->name ?? '-',
                    'Tanggal Beli' => $this->formatDate($purchaseDate),
                    'Jumlah Awal' => (int) $batch->initial_head_count,
                    'Jumlah Aktif' => (int) $batch->current_head_count,
                    'Jumlah Mati' => (int) $batch->sheepIncidentRecords->where('incident_type', 'dead')->sum('head_count'),
                    'Jumlah Afkir' => (int) $batch->sheepIncidentRecords->where('incident_type', 'culled')->sum('head_count'),
                    'Jumlah Terjual' => (int) $batch->sales->sum('head_count'),
                    'Status Batch' => $this->batchStatusLabel($batch->status),
                    'Sumber' => $batch->is_historical ? 'Histori Manual' : 'Normal',
                    '_purchase_date' => $purchaseDate ? CarbonImmutable::parse($purchaseDate)->toDateString() : null,
                    '_lost' => (int) $batch->sheepIncidentRecords->where('incident_type', 'lost')->sum('head_count'),
                    '_sick' => (int) $batch->sheepIncidentRecords->where('incident_type', 'sick')->sum('head_count'),
                ];
            })
            ->filter(fn (array $row): bool => $this->matchesDateRange($row['_purchase_date'], $filters['from'] ?? null, $filters['until'] ?? null))
            ->filter(fn (array $row): bool => $this->matchesPopulationStatus($row, $filters['sheep_status'] ?? null))
            ->map(fn (array $row): array => collect($row)->except(['_purchase_date', '_lost', '_sick'])->all())
            ->values();

        return $this->makeReport(
            title: 'Laporan Populasi Ternak',
            slug: 'laporan-populasi-ternak',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Kode Batch', 'Jenis Ternak', 'Kandang', 'Supplier', 'Tanggal Beli', 'Jumlah Awal', 'Jumlah Aktif', 'Jumlah Mati', 'Jumlah Afkir', 'Jumlah Terjual', 'Status Batch', 'Sumber'],
            rows: $rows,
            summary: [
                'Total Ternak Awal' => $this->formatNumber($rows->sum('Jumlah Awal')).' ekor',
                'Total Ternak Aktif' => $this->formatNumber($rows->sum('Jumlah Aktif')).' ekor',
                'Total Ternak Mati' => $this->formatNumber($rows->sum('Jumlah Mati')).' ekor',
                'Total Ternak Afkir' => $this->formatNumber($rows->sum('Jumlah Afkir')).' ekor',
                'Total Ternak Terjual' => $this->formatNumber($rows->sum('Jumlah Terjual')).' ekor',
            ],
        );
    }

    public function buildPurchasesReport(array $filters = []): array
    {
        $rows = SheepPurchase::query()
            ->with(['supplier', 'pen', 'fatteningBatch'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '<=', $date))
            ->orderByDesc('purchase_date')
            ->get()
            ->map(fn (SheepPurchase $purchase): array => [
                'Tanggal' => $this->formatDate($purchase->purchase_date),
                'Tipe' => $this->purchaseTypeLabel($purchase->purchase_type),
                'Supplier' => $purchase->supplier?->name ?? '-',
                'Kandang' => $purchase->pen?->name ?? '-',
                'Batch' => $purchase->fatteningBatch?->batch_code ?? '-',
                'Jumlah Ekor' => (int) $purchase->head_count,
                'Total Berat' => $this->formatKg((float) $purchase->total_weight_kg),
                'Harga Beli' => $this->formatRupiah((float) $purchase->total_purchase_price),
                'Transport' => $this->formatRupiah((float) $purchase->transport_cost),
                'Biaya Lain' => $this->formatRupiah((float) $purchase->other_cost),
                'Total Modal' => $this->formatRupiah($purchase->totalCapital()),
            ]);

        $source = SheepPurchase::query()
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '<=', $date));

        return $this->makeReport(
            title: 'Laporan Pembelian Ternak',
            slug: 'laporan-pembelian-ternak',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Tanggal', 'Tipe', 'Supplier', 'Kandang', 'Batch', 'Jumlah Ekor', 'Total Berat', 'Harga Beli', 'Transport', 'Biaya Lain', 'Total Modal'],
            rows: $rows,
            summary: [
                'Total Transaksi' => $this->formatNumber($source->count()).' transaksi',
                'Total Ekor' => $this->formatNumber((int) (clone $source)->sum('head_count')).' ekor',
                'Total Modal Pembelian' => $this->formatRupiah((float) (clone $source)->selectRaw('COALESCE(SUM(total_purchase_price + transport_cost + other_cost), 0) as aggregate')->value('aggregate')),
            ],
        );
    }

    public function buildExpensesReport(array $filters = []): array
    {
        $query = Expense::query()
            ->with(['expenseCategory', 'fatteningBatch', 'pen'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date));

        $expenses = (clone $query)->orderByDesc('expense_date')->get();
        $rows = $expenses->map(fn (Expense $expense): array => [
            'Tanggal' => $this->formatDate($expense->expense_date),
            'Kategori' => $expense->expenseCategory?->name ?? '-',
            'Keterangan' => $expense->description,
            'Batch' => $expense->fatteningBatch?->batch_code ?? '-',
            'Kandang' => $expense->pen?->name ?? '-',
            'Nominal' => $this->formatRupiah((float) $expense->amount),
        ]);

        return $this->makeReport(
            title: 'Laporan Pengeluaran',
            slug: 'laporan-pengeluaran',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Tanggal', 'Kategori', 'Keterangan', 'Batch', 'Kandang', 'Nominal'],
            rows: $rows,
            summary: [
                'Total Transaksi' => $this->formatNumber($expenses->count()).' transaksi',
                'Total Pengeluaran' => $this->formatRupiah((float) $expenses->sum('amount')),
            ],
        );
    }

    public function buildSalesReport(array $filters = []): array
    {
        $query = Sale::query()
            ->with(['buyer', 'fatteningBatch'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date));

        $sales = (clone $query)->orderByDesc('sale_date')->get();
        $rows = $sales->map(fn (Sale $sale): array => [
            'Nomor Invoice' => $sale->sale_number,
            'Tanggal' => $this->formatDate($sale->sale_date),
            'Pembeli' => $sale->buyer?->name ?? '-',
            'Batch' => $sale->fatteningBatch?->batch_code ?? '-',
            'Jenis' => $this->saleTypeLabel($sale->sale_type),
            'Jumlah Ekor' => (int) $sale->head_count,
            'Total Bobot' => $this->formatKg((float) $sale->total_weight_kg),
            'Total Penjualan' => $this->formatRupiah((float) $sale->total_amount),
        ]);

        return $this->makeReport(
            title: 'Laporan Penjualan',
            slug: 'laporan-penjualan',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Nomor Invoice', 'Tanggal', 'Pembeli', 'Batch', 'Jenis', 'Jumlah Ekor', 'Total Bobot', 'Total Penjualan'],
            rows: $rows,
            summary: [
                'Total Transaksi' => $this->formatNumber($sales->count()).' transaksi',
                'Total Ekor Terjual' => $this->formatNumber((int) $sales->sum('head_count')).' ekor',
                'Total Penjualan' => $this->formatRupiah((float) $sales->sum('total_amount')),
            ],
        );
    }

    public function buildProfitLossReport(array $filters = []): array
    {
        $query = FatteningBatch::query()
            ->with(['pen', 'supplier', 'livestockType'])
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->whereKey($batchId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->where('livestock_type_id', $livestockTypeId));

        if (($filters['from'] ?? null) || ($filters['until'] ?? null)) {
            $query->where(function (Builder $query) use ($filters): void {
                $query
                    ->where(fn (Builder $query): Builder => $this->applyDateRange($query, 'start_date', $filters))
                    ->orWhereHas('expenses', fn (Builder $query): Builder => $this->applyDateRange($query, 'expense_date', $filters))
                    ->orWhereHas('sales', fn (Builder $query): Builder => $this->applyDateRange($query, 'sale_date', $filters));
            });
        }

        $rows = $query->orderByDesc('start_date')->get()->map(function (FatteningBatch $batch) use ($filters): array {
            $expenses = (float) Expense::query()
                ->where('fattening_batch_id', $batch->id)
                ->tap(fn (Builder $query): Builder => $this->applyDateRange($query, 'expense_date', $filters))
                ->sum('amount');
            $sales = (float) Sale::query()
                ->where('fattening_batch_id', $batch->id)
                ->tap(fn (Builder $query): Builder => $this->applyDateRange($query, 'sale_date', $filters))
                ->sum('total_amount');
            $profit = $sales - (float) $batch->purchase_capital - $expenses;

            return [
                'Kode Batch' => $batch->batch_code,
                'Jenis Ternak' => $batch->livestockType?->name ?? 'Domba',
                'Jumlah Awal' => (int) $batch->initial_head_count,
                'Jumlah Saat Ini' => (int) $batch->current_head_count,
                'Modal Pembelian' => $this->formatRupiah((float) $batch->purchase_capital),
                'Total Pengeluaran' => $this->formatRupiah($expenses),
                'Total Penjualan' => $this->formatRupiah($sales),
                'Laba/Rugi' => $this->formatRupiah($profit),
                'Status Batch' => $this->batchStatusLabel($batch->status),
                'Sumber' => $batch->is_historical ? 'Histori Manual' : 'Normal',
                '_purchase_capital' => (float) $batch->purchase_capital,
                '_expenses' => $expenses,
                '_sales' => $sales,
                '_profit' => $profit,
            ];
        });

        return $this->makeReport(
            title: 'Laporan Laba Rugi Per Batch',
            slug: 'laporan-laba-rugi',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Kode Batch', 'Jenis Ternak', 'Jumlah Awal', 'Jumlah Saat Ini', 'Modal Pembelian', 'Total Pengeluaran', 'Total Penjualan', 'Laba/Rugi', 'Status Batch', 'Sumber'],
            rows: $rows->map(fn (array $row): array => collect($row)->except(['_purchase_capital', '_expenses', '_sales', '_profit'])->all())->values(),
            summary: [
                'Total Modal Pembelian' => $this->formatRupiah((float) $rows->sum('_purchase_capital')),
                'Total Pengeluaran' => $this->formatRupiah((float) $rows->sum('_expenses')),
                'Total Penjualan' => $this->formatRupiah((float) $rows->sum('_sales')),
                'Laba/Rugi Bersih' => $this->formatRupiah((float) $rows->sum('_profit')),
            ],
        );
    }

    public function buildPerformanceReport(array $filters = []): array
    {
        $rows = FatteningBatch::query()
            ->with(['pen', 'livestockType'])
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->whereKey($batchId))
            ->when($filters['pen_id'] ?? null, fn (Builder $query, string $penId): Builder => $query->where('pen_id', $penId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->where('livestock_type_id', $livestockTypeId))
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date))
            ->orderByDesc('start_date')
            ->get()
            ->map(function (FatteningBatch $batch): array {
                $growth = $this->growthMonitoring->calculateBatchGrowth($batch);

                return [
                    'Kode Batch' => $batch->batch_code,
                    'Jenis Ternak' => $batch->livestockType?->name ?? 'Domba',
                    'Sumber' => $batch->is_historical ? 'Histori Manual' : 'Normal',
                    'Kandang' => $batch->pen?->name ?? 'Tanpa kandang',
                    'Tanggal Mulai' => $this->formatDate($batch->start_date),
                    'Jumlah Hari' => $growth['days'].' hari',
                    'Jumlah Awal' => (int) $batch->initial_head_count,
                    'Jumlah Saat Ini' => (int) $batch->current_head_count,
                    'Berat Awal Total' => $this->formatKg($growth['initial_weight']),
                    'Berat Terakhir Total' => $this->formatKg($growth['latest_weight']),
                    'Kenaikan Berat Total' => $this->formatKg($growth['weight_gain']),
                    'Berat Awal Rata-rata' => $this->formatKg($growth['initial_average_weight']),
                    'Berat Terakhir Rata-rata' => $this->formatKg($growth['latest_average_weight']),
                    'Kenaikan Berat Rata-rata' => $this->formatKg($growth['average_weight_gain']),
                    'ADG' => $this->formatAdg($growth['adg']),
                    'Status Pertumbuhan' => $growth['status'],
                    'Rekomendasi' => $growth['recommendation'],
                    '_status' => $growth['status'],
                ];
            })
            ->when($filters['growth_status'] ?? null, fn (Collection $rows, string $status): Collection => $rows->where('_status', $status)->values())
            ->map(fn (array $row): array => collect($row)->except('_status')->all())
            ->values();

        return $this->makeReport(
            title: 'Laporan Performa Penggemukan',
            slug: 'laporan-performa-penggemukan',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Kode Batch', 'Jenis Ternak', 'Sumber', 'Kandang', 'Tanggal Mulai', 'Jumlah Hari', 'Jumlah Awal', 'Jumlah Saat Ini', 'Berat Awal Total', 'Berat Terakhir Total', 'Kenaikan Berat Total', 'Berat Awal Rata-rata', 'Berat Terakhir Rata-rata', 'Kenaikan Berat Rata-rata', 'ADG', 'Status Pertumbuhan', 'Rekomendasi'],
            rows: $rows,
            summary: [
                'Total Batch' => $this->formatNumber($rows->count()).' batch',
                'Batch Pertumbuhan Bagus' => $this->formatNumber($rows->where('Status Pertumbuhan', 'Bagus')->count()).' batch',
                'Batch Perlu Evaluasi' => $this->formatNumber($rows->whereIn('Status Pertumbuhan', ['Lambat', 'Stagnan', 'Turun'])->count()).' batch',
            ],
        );
    }

    public function buildUnitFinancialReport(array $filters = []): array
    {
        $purchases = SheepPurchase::query()
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('purchase_date', '<=', $date))
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->where('fattening_batch_id', $batchId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('livestock_type_id', $livestockTypeId)))
            ->when($filters['pen_id'] ?? null, fn (Builder $query, string $penId): Builder => $query->where(fn (Builder $query): Builder => $query->where('pen_id', $penId)->orWhereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('pen_id', $penId))));

        $expenses = Expense::query()
            ->with('expenseCategory')
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date))
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->where('fattening_batch_id', $batchId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('livestock_type_id', $livestockTypeId)))
            ->when($filters['pen_id'] ?? null, fn (Builder $query, string $penId): Builder => $query->where(fn (Builder $query): Builder => $query->where('pen_id', $penId)->orWhereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('pen_id', $penId))))
            ->get();

        $sales = Sale::query()
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('sale_date', '<=', $date))
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->where('fattening_batch_id', $batchId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('livestock_type_id', $livestockTypeId)))
            ->when($filters['pen_id'] ?? null, fn (Builder $query, string $penId): Builder => $query->whereHas('fatteningBatch', fn (Builder $query): Builder => $query->where('pen_id', $penId)));

        $purchaseCapital = (float) (clone $purchases)->selectRaw('COALESCE(SUM(total_purchase_price + transport_cost + other_cost), 0) as aggregate')->value('aggregate')
            + $this->historicalPurchaseCapital($filters);
        $expenseBreakdown = $this->expenseBreakdown($expenses);
        $totalExpenses = array_sum($expenseBreakdown);
        $totalSales = (float) $sales->sum('total_amount');
        $profit = $totalSales - $purchaseCapital - $totalExpenses;

        $rows = collect([
            ['Komponen' => 'Total Modal Pembelian', 'Nominal' => $this->formatRupiah($purchaseCapital)],
            ['Komponen' => 'Total Biaya Pakan', 'Nominal' => $this->formatRupiah($expenseBreakdown['feed'])],
            ['Komponen' => 'Total Biaya Obat/Vitamin', 'Nominal' => $this->formatRupiah($expenseBreakdown['medicine'])],
            ['Komponen' => 'Total Upah', 'Nominal' => $this->formatRupiah($expenseBreakdown['wage'])],
            ['Komponen' => 'Total Transportasi', 'Nominal' => $this->formatRupiah($expenseBreakdown['transport'])],
            ['Komponen' => 'Total Biaya Kandang/Peralatan', 'Nominal' => $this->formatRupiah($expenseBreakdown['pen_equipment'])],
            ['Komponen' => 'Total Biaya Lain-lain', 'Nominal' => $this->formatRupiah($expenseBreakdown['other'])],
            ['Komponen' => 'Total Semua Pengeluaran', 'Nominal' => $this->formatRupiah($totalExpenses)],
            ['Komponen' => 'Total Penjualan', 'Nominal' => $this->formatRupiah($totalSales)],
            ['Komponen' => 'Laba/Rugi Bersih', 'Nominal' => $this->formatRupiah($profit)],
        ]);

        return $this->makeReport(
            title: 'Laporan Keuangan Unit Ternak',
            slug: 'laporan-keuangan-unit-ternak',
            period: $this->periodLabel($filters['from'] ?? null, $filters['until'] ?? null),
            periodFrom: $filters['from'] ?? null,
            columns: ['Komponen', 'Nominal'],
            rows: $rows,
            summary: [
                'Total Modal Pembelian' => $this->formatRupiah($purchaseCapital),
                'Total Semua Pengeluaran' => $this->formatRupiah($totalExpenses),
                'Total Penjualan' => $this->formatRupiah($totalSales),
                'Laba/Rugi Bersih' => $this->formatRupiah($profit),
            ],
        );
    }

    private function downloadExcel(array $report): BinaryFileResponse
    {
        return Excel::download(
            new ArrayReportExport($this->excelRows($report), $report['title']),
            $this->filename($report['slug'], 'xlsx', $report['period_from'] ?? null),
        );
    }

    private function downloadPdf(array $report): Response
    {
        return Pdf::loadView('exports.sickas-report-pdf', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->download($this->filename($report['slug'], 'pdf', $report['period_from'] ?? null));
    }

    private function makeReport(string $title, string $slug, string $period, ?string $periodFrom, array $columns, Collection $rows, array $summary): array
    {
        $identity = BusinessProfile::reportIdentity();

        return [
            'title' => $title,
            'slug' => $slug,
            ...$identity,
            'period' => $period,
            'period_from' => $periodFrom,
            'printed_at' => SickasFormatter::dateTime(now()),
            'columns' => $columns,
            'rows' => $rows->values()->all(),
            'summary' => $summary,
        ];
    }

    private function excelRows(array $report): array
    {
        $rows = [
            [$report['app_name'] ?? 'SICKAS FARM'],
            [$report['business_title']],
            ['BUMDes', $report['bumdes_name'] ?? 'BUMDes Ketapang Ternak Domba'],
            ['Unit', $report['unit_name']],
            ['Judul', $report['title']],
            ['Periode', $report['period']],
            ['Tanggal Cetak', $report['printed_at']],
            [],
            $report['columns'],
        ];

        foreach ($report['rows'] as $row) {
            $rows[] = collect($report['columns'])
                ->map(fn (string $column): mixed => $row[$column] ?? null)
                ->all();
        }

        $rows[] = [];
        $rows[] = ['Ringkasan'];

        foreach ($report['summary'] as $label => $value) {
            $rows[] = [$label, $value];
        }

        return $rows;
    }

    private function applyDateRange(Builder $query, string $column, array $filters): Builder
    {
        return $query
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '<=', $date));
    }

    private function matchesDateRange(?string $date, ?string $from, ?string $until): bool
    {
        if (! $from && ! $until) {
            return true;
        }

        if (! $date) {
            return false;
        }

        return (! $from || $date >= $from) && (! $until || $date <= $until);
    }

    private function matchesPopulationStatus(array $row, ?string $status): bool
    {
        return match ($status) {
            'active' => $row['Jumlah Aktif'] > 0,
            'dead' => $row['Jumlah Mati'] > 0,
            'culled' => $row['Jumlah Afkir'] > 0,
            'sold' => $row['Jumlah Terjual'] > 0,
            'lost' => $row['_lost'] > 0,
            'sick' => $row['_sick'] > 0,
            default => true,
        };
    }

    /**
     * @return array{feed: float, medicine: float, wage: float, transport: float, pen_equipment: float, other: float}
     */
    private function expenseBreakdown(Collection $expenses): array
    {
        $breakdown = [
            'feed' => 0.0,
            'medicine' => 0.0,
            'wage' => 0.0,
            'transport' => 0.0,
            'pen_equipment' => 0.0,
            'other' => 0.0,
        ];

        $expenses->each(function (Expense $expense) use (&$breakdown): void {
            $breakdown[$this->classifyExpense($expense)] += (float) $expense->amount;
        });

        return $breakdown;
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

    private function historicalPurchaseCapital(array $filters): float
    {
        return (float) FatteningBatch::query()
            ->where('is_historical', true)
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date))
            ->when($filters['batch_id'] ?? null, fn (Builder $query, string $batchId): Builder => $query->whereKey($batchId))
            ->when($filters['livestock_type_id'] ?? null, fn (Builder $query, string $livestockTypeId): Builder => $query->where('livestock_type_id', $livestockTypeId))
            ->when($filters['pen_id'] ?? null, fn (Builder $query, string $penId): Builder => $query->where('pen_id', $penId))
            ->sum('purchase_capital');
    }

    private function filename(string $slug, string $extension, ?string $periodFrom = null): string
    {
        $date = $periodFrom
            ? CarbonImmutable::parse($periodFrom)->format('Y-m-d')
            : now()->format('Y-m-d');

        return "{$slug}-sickas-farm-{$date}.{$extension}";
    }

    private function periodLabel(?string $from, ?string $until): string
    {
        if (! $from && ! $until) {
            return 'Semua periode';
        }

        return ($from ? $this->formatDate($from) : 'Awal').' sampai '.($until ? $this->formatDate($until) : 'akhir');
    }

    private function formatRupiah(float|int|null $amount): string
    {
        return SickasFormatter::rupiah($amount);
    }

    private function formatKg(float|int|null $amount): string
    {
        if (! $amount) {
            return '-';
        }

        return SickasFormatter::kg($amount);
    }

    private function formatAdg(?float $amount): string
    {
        return SickasFormatter::adg($amount);
    }

    private function formatNumber(float|int $number): string
    {
        return SickasFormatter::number($number);
    }

    private function formatDate(CarbonInterface|string|null $date): string
    {
        if (! $date) {
            return '-';
        }

        return SickasFormatter::date($date);
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

    private function purchaseTypeLabel(?string $type): string
    {
        return match ($type) {
            'bulk' => 'Borongan',
            'per_head' => 'Per Ekor',
            'per_kg' => 'Per Kg',
            default => '-',
        };
    }

    private function saleTypeLabel(?string $type): string
    {
        return match ($type) {
            'bulk' => 'Borongan',
            'per_head' => 'Per Ekor',
            'per_kg' => 'Per Kg',
            default => '-',
        };
    }
}
