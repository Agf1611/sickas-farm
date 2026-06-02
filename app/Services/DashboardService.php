<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\WeighingRecord;
use App\Support\SickasFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly GrowthMonitoringService $growthMonitoring,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        $profile = BusinessProfile::main();
        $identity = BusinessProfile::reportIdentity();

        return [
            ...$identity,
            'logo_url' => $profile?->logoUrl(),
            'banner_url' => $profile?->bannerUrl() ?: url('/images/sickas-farm/default-farm-banner.svg'),
            'date_label' => now()->translatedFormat('d F Y'),
            'day_time_label' => now()->translatedFormat('l, H:i').' WIB',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mainStats(bool $includeFinancial = true): array
    {
        $activeLivestock = (int) FatteningBatch::query()
            ->where('status', 'active')
            ->sum('current_head_count');

        $activeBatches = FatteningBatch::query()
            ->where('status', 'active')
            ->count();

        $deadOrCulled = (int) SheepIncidentRecord::query()
            ->whereIn('incident_type', ['dead', 'culled'])
            ->sum('head_count');

        $soldLivestock = (int) Sale::query()->sum('head_count');
        $purchaseCapital = (float) FatteningBatch::query()->sum('purchase_capital');
        $expenses = (float) Expense::query()->sum('amount');
        $sales = (float) Sale::query()->sum('total_amount');
        $profit = $sales - $purchaseCapital - $expenses;

        $stats = [
            [
                'label' => 'Ternak Aktif',
                'value' => SickasFormatter::number($activeLivestock).' ekor',
                'description' => 'Total dari batch aktif',
                'tone' => 'success',
                'icon' => 'identification',
            ],
            [
                'label' => 'Batch Aktif',
                'value' => SickasFormatter::number($activeBatches).' batch',
                'description' => 'Batch penggemukan berjalan',
                'tone' => 'info',
                'icon' => 'layers',
            ],
            [
                'label' => 'Mati / Afkir',
                'value' => SickasFormatter::number($deadOrCulled).' ekor',
                'description' => 'Catatan kematian dan afkir',
                'tone' => 'danger',
                'icon' => 'warning',
            ],
            [
                'label' => 'Ternak Terjual',
                'value' => SickasFormatter::number($soldLivestock).' ekor',
                'description' => 'Akumulasi penjualan',
                'tone' => 'purple',
                'icon' => 'cart',
            ],
        ];

        if (! $includeFinancial) {
            return [
                'items' => $stats,
                'profit' => $profit,
            ];
        }

        return [
            'items' => [
                ...$stats,
                [
                    'label' => 'Modal Pembelian',
                    'value' => SickasFormatter::rupiah($purchaseCapital),
                    'description' => 'Total modal batch',
                    'tone' => 'amber',
                    'icon' => 'money',
                ],
                [
                    'label' => 'Total Pengeluaran',
                    'value' => SickasFormatter::rupiah($expenses),
                    'description' => 'Biaya penggemukan tercatat',
                    'tone' => 'orange',
                    'icon' => 'cash',
                ],
                [
                    'label' => 'Total Penjualan',
                    'value' => SickasFormatter::rupiah($sales),
                    'description' => 'Nilai transaksi penjualan',
                    'tone' => 'success',
                    'icon' => 'receipt',
                ],
                [
                    'label' => 'Laba / Rugi',
                    'value' => SickasFormatter::rupiah($profit),
                    'description' => $profit >= 0 ? 'Estimasi laba bersih' : 'Estimasi rugi bersih',
                    'tone' => $profit >= 0 ? 'success' : 'danger',
                    'icon' => $profit >= 0 ? 'trend-up' : 'trend-down',
                ],
            ],
            'profit' => $profit,
        ];
    }

    /**
     * @return array{labels: array<int, string>, purchase: array<int, float>, expenses: array<int, float>, sales: array<int, float>, profit: array<int, float>}
     */
    public function financialChartData(): array
    {
        $months = $this->lastSixMonths();
        $start = $months->first()['start'];
        $end = $months->last()['end'];

        $purchaseByMonth = FatteningBatch::query()
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->get(['start_date', 'purchase_capital'])
            ->groupBy(fn (FatteningBatch $batch): string => $batch->start_date->format('Y-m'))
            ->map(fn (Collection $rows): float => (float) $rows->sum('purchase_capital'));

        $expensesByMonth = Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->get(['expense_date', 'amount'])
            ->groupBy(fn (Expense $expense): string => $expense->expense_date->format('Y-m'))
            ->map(fn (Collection $rows): float => (float) $rows->sum('amount'));

        $salesByMonth = Sale::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->get(['sale_date', 'total_amount'])
            ->groupBy(fn (Sale $sale): string => $sale->sale_date->format('Y-m'))
            ->map(fn (Collection $rows): float => (float) $rows->sum('total_amount'));

        $labels = [];
        $purchase = [];
        $expenses = [];
        $sales = [];
        $profit = [];

        foreach ($months as $month) {
            $key = $month['key'];
            $monthlyPurchase = (float) ($purchaseByMonth[$key] ?? 0);
            $monthlyExpenses = (float) ($expensesByMonth[$key] ?? 0);
            $monthlySales = (float) ($salesByMonth[$key] ?? 0);

            $labels[] = $month['label'];
            $purchase[] = $monthlyPurchase;
            $expenses[] = $monthlyExpenses;
            $sales[] = $monthlySales;
            $profit[] = $monthlySales - $monthlyPurchase - $monthlyExpenses;
        }

        return compact('labels', 'purchase', 'expenses', 'sales', 'profit');
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>, total: int}
     */
    public function populationChartData(): array
    {
        $active = (int) FatteningBatch::query()
            ->where('status', 'active')
            ->sum('current_head_count');
        $sold = (int) Sale::query()->sum('head_count');
        $dead = (int) SheepIncidentRecord::query()->where('incident_type', 'dead')->sum('head_count');
        $culled = (int) SheepIncidentRecord::query()->where('incident_type', 'culled')->sum('head_count');

        return [
            'labels' => ['Aktif', 'Terjual', 'Mati', 'Afkir'],
            'data' => [$active, $sold, $dead, $culled],
            'total' => $active + $sold + $dead + $culled,
        ];
    }

    /**
     * @return array{labels: array<int, string>, adg: array<int, float|null>, visual_adg: array<int, float>, has_month_data: array<int, bool>, has_data: bool}
     */
    public function growthChartData(): array
    {
        $months = $this->lastSixMonths();
        $start = $months->first()['start'];
        $end = $months->last()['end'];

        $records = WeighingRecord::query()
            ->with('fatteningBatch')
            ->where('record_type', 'batch')
            ->whereNotNull('total_weight_kg')
            ->whereBetween('weighed_at', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->filter(fn (WeighingRecord $record): bool => $record->fatteningBatch !== null
                && $record->fatteningBatch->initial_total_weight_kg !== null
                && $record->fatteningBatch->start_date !== null);

        $adgByMonth = $records
            ->map(function (WeighingRecord $record): array {
                $batch = $record->fatteningBatch;
                $days = max(1, (int) CarbonImmutable::parse($batch->start_date->toDateString())
                    ->diffInDays(CarbonImmutable::parse($record->weighed_at->toDateString()), absolute: false));
                $gain = (float) $record->total_weight_kg - (float) $batch->initial_total_weight_kg;

                return [
                    'month' => $record->weighed_at->format('Y-m'),
                    'adg' => $gain / $days,
                ];
            })
            ->groupBy('month')
            ->map(fn (Collection $rows): float => (float) $rows->avg('adg'));

        $labels = [];
        $adg = [];
        $visualAdg = [];
        $hasMonthData = [];

        foreach ($months as $month) {
            $hasDataForMonth = array_key_exists($month['key'], $adgByMonth->all());
            $value = $hasDataForMonth ? round((float) $adgByMonth[$month['key']], 3) : null;

            $labels[] = $month['label'];
            $adg[] = $value;
            $visualAdg[] = $value ?? 0.0;
            $hasMonthData[] = $hasDataForMonth;
        }

        return [
            'labels' => $labels,
            'adg' => $adg,
            'visual_adg' => $visualAdg,
            'has_month_data' => $hasMonthData,
            'has_data' => $records->isNotEmpty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function growthSummary(): array
    {
        return $this->growthMonitoring->activeBatchGrowthSummary();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function warningItems(): array
    {
        $summary = $this->growthSummary();
        $deadOrCulled = (int) SheepIncidentRecord::query()
            ->whereIn('incident_type', ['dead', 'culled'])
            ->sum('head_count');

        return [
            [
                'label' => 'Batch belum ditimbang',
                'value' => $this->growthMonitoring->countBatchNeverWeighed().' batch',
                'description' => 'Batch aktif tanpa data timbang',
                'tone' => 'warning',
            ],
            [
                'label' => 'Batch perlu timbang ulang',
                'value' => $this->growthMonitoring->countBatchNeedsReweighing().' batch',
                'description' => 'Timbang terakhir lebih dari 14 hari',
                'tone' => 'orange',
            ],
            [
                'label' => 'Batch ADG lambat',
                'value' => $summary['slow_count'].' batch',
                'description' => 'ADG di bawah target bagus',
                'tone' => 'warning',
            ],
            [
                'label' => 'Batch berat turun',
                'value' => $summary['down_count'].' batch',
                'description' => 'Perlu pemeriksaan kesehatan',
                'tone' => 'danger',
            ],
            [
                'label' => 'Ternak mati/afkir tercatat',
                'value' => $deadOrCulled.' ekor',
                'description' => 'Akumulasi insiden penting',
                'tone' => 'danger',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function attentionBatches(int $limit = 6): array
    {
        return $this->growthMonitoring
            ->activeBatchGrowthRows()
            ->map(function (array $row): array {
                $priority = match (true) {
                    $row['status'] === 'Turun' => 1,
                    $row['status'] === 'Lambat' => 2,
                    $row['status'] === 'Stagnan' => 3,
                    $row['weighing_alert_status'] !== 'Timbang Terkini' => 4,
                    default => 5,
                };

                $batch = $row['batch'];

                return [
                    ...$row,
                    'priority' => $priority,
                    'livestock_type_name' => $batch->livestockType?->name ?? 'Domba',
                    'recommendation_short' => $this->shortRecommendation($row['recommendation']),
                ];
            })
            ->sortBy([
                ['priority', 'asc'],
                ['adg', 'asc'],
            ])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function recentActivities(int $limit = 5): array
    {
        $activities = collect();

        SheepPurchase::query()
            ->with('fatteningBatch')
            ->latest('purchase_date')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(fn (SheepPurchase $purchase) => $activities->push([
                'type' => 'Pembelian',
                'title' => 'Pembelian ternak',
                'description' => ($purchase->fatteningBatch?->batch_code ?? 'Tanpa batch').' - '.$purchase->head_count.' ekor',
                'date' => $purchase->purchase_date,
                'tone' => 'success',
            ]));

        Expense::query()
            ->with('expenseCategory')
            ->latest('expense_date')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(fn (Expense $expense) => $activities->push([
                'type' => 'Pengeluaran',
                'title' => $expense->expenseCategory?->name ?? 'Pengeluaran',
                'description' => SickasFormatter::rupiah($expense->amount),
                'date' => $expense->expense_date,
                'tone' => 'info',
            ]));

        Sale::query()
            ->latest('sale_date')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(fn (Sale $sale) => $activities->push([
                'type' => 'Penjualan',
                'title' => $sale->sale_number,
                'description' => $sale->head_count.' ekor - '.SickasFormatter::rupiah($sale->total_amount),
                'date' => $sale->sale_date,
                'tone' => 'purple',
            ]));

        WeighingRecord::query()
            ->with('fatteningBatch')
            ->latest('weighed_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(fn (WeighingRecord $record) => $activities->push([
                'type' => 'Timbang',
                'title' => $record->fatteningBatch?->batch_code ?? 'Timbang ternak',
                'description' => $record->total_weight_kg !== null
                    ? SickasFormatter::kg($record->total_weight_kg)
                    : SickasFormatter::kg($record->weight_kg),
                'date' => $record->weighed_at,
                'tone' => 'warning',
            ]));

        SheepIncidentRecord::query()
            ->with('fatteningBatch')
            ->latest('incident_date')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(fn (SheepIncidentRecord $incident) => $activities->push([
                'type' => 'Kematian / Afkir',
                'title' => ucfirst($incident->incident_type),
                'description' => ($incident->fatteningBatch?->batch_code ?? 'Tanpa batch').' - '.$incident->head_count.' ekor',
                'date' => $incident->incident_date,
                'tone' => 'danger',
            ]));

        return $activities
            ->sortByDesc('date')
            ->take($limit)
            ->map(fn (array $activity): array => [
                ...$activity,
                'date_label' => SickasFormatter::date($activity['date']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function lastSixMonths(): Collection
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        return collect(range(0, 5))->map(function (int $offset) use ($start): array {
            $month = $start->addMonths($offset);

            return [
                'key' => $month->format('Y-m'),
                'label' => $month->translatedFormat('M Y'),
                'start' => $month->startOfMonth(),
                'end' => $month->endOfMonth(),
            ];
        });
    }

    private function shortRecommendation(string $recommendation): string
    {
        return str($recommendation)
            ->before('.')
            ->limit(46)
            ->toString();
    }
}
