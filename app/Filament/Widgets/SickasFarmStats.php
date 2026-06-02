<?php

namespace App\Filament\Widgets;

use App\Models\BusinessProfile;
use App\Models\Expense;
use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SheepIncidentRecord;
use App\Services\GrowthMonitoringService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SickasFarmStats extends StatsOverviewWidget
{
    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 4,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.financial.view') ?? false;
    }

    protected function getHeading(): ?string
    {
        return 'Monitoring '.(BusinessProfile::reportIdentity()['app_name'] ?? 'SICKAS FARM');
    }

    protected function getDescription(): ?string
    {
        $unitName = BusinessProfile::reportIdentity()['unit_name'] ?? 'BUMDes Ketapang Ternak Domba';

        return "Ringkasan populasi, modal, pengeluaran, penjualan, dan estimasi hasil usaha {$unitName}.";
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $growthMonitoring = app(GrowthMonitoringService::class);

        $activeSheep = (int) FatteningBatch::query()
            ->where('status', 'active')
            ->sum('current_head_count');

        $activeBatches = FatteningBatch::query()
            ->where('status', 'active')
            ->count();

        $deadOrCulledSheep = (int) SheepIncidentRecord::query()
            ->whereIn('incident_type', ['dead', 'culled'])
            ->sum('head_count');

        $soldSheep = (int) Sale::query()->sum('head_count');
        $purchaseCapital = (float) FatteningBatch::query()->sum('purchase_capital');
        $expenses = (float) Expense::query()->sum('amount');
        $sales = (float) Sale::query()->sum('total_amount');
        $profit = $sales - $purchaseCapital - $expenses;
        $batchNeverWeighed = $growthMonitoring->countBatchNeverWeighed();
        $batchNeedsReweighing = $growthMonitoring->countBatchNeedsReweighing();
        $sheepNeverWeighed = $growthMonitoring->countSheepNeverWeighed();
        $sheepNeedsReweighing = $growthMonitoring->countSheepNeedsReweighing();

        return [
            Stat::make('Total Ternak Aktif', number_format($activeSheep, 0, ',', '.').' ekor')
                ->description('Dari batch aktif')
                ->icon(Heroicon::OutlinedIdentification)
                ->color('success'),
            Stat::make('Total Batch Aktif', number_format($activeBatches, 0, ',', '.').' batch')
                ->description('Status aktif')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('info'),
            Stat::make('Total Ternak Mati / Afkir', number_format($deadOrCulledSheep, 0, ',', '.').' ekor')
                ->description('Dari catatan kematian / afkir')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
            Stat::make('Total Ternak Terjual', number_format($soldSheep, 0, ',', '.').' ekor')
                ->description('Dari transaksi penjualan')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color('gray'),
            Stat::make('Total Modal Pembelian', $this->formatRupiah($purchaseCapital))
                ->description('Dari modal batch')
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('warning'),
            Stat::make('Total Pengeluaran', $this->formatRupiah($expenses))
                ->description('Dari biaya penggemukan')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('warning'),
            Stat::make('Total Penjualan', $this->formatRupiah($sales))
                ->description('Dari transaksi penjualan')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color('success'),
            Stat::make('Estimasi Laba / Rugi', $this->formatRupiah($profit))
                ->description('Penjualan - modal - pengeluaran')
                ->icon($profit >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown)
                ->color($profit >= 0 ? 'success' : 'danger'),
            Stat::make('Batch Belum Ditimbang', number_format($batchNeverWeighed, 0, ',', '.').' batch')
                ->description('Batch aktif tanpa data timbang')
                ->icon(Heroicon::OutlinedScale)
                ->color($batchNeverWeighed > 0 ? 'danger' : 'success'),
            Stat::make('Batch Perlu Timbang Ulang', number_format($batchNeedsReweighing, 0, ',', '.').' batch')
                ->description('Timbang terakhir lebih dari 14 hari')
                ->icon(Heroicon::OutlinedClock)
                ->color($batchNeedsReweighing > 0 ? 'warning' : 'success'),
            Stat::make('Ternak Belum Ditimbang', number_format($sheepNeverWeighed, 0, ',', '.').' ekor')
                ->description('Ternak aktif tanpa data timbang per ekor')
                ->icon(Heroicon::OutlinedScale)
                ->color($sheepNeverWeighed > 0 ? 'danger' : 'success'),
            Stat::make('Ternak Perlu Timbang Ulang', number_format($sheepNeedsReweighing, 0, ',', '.').' ekor')
                ->description('Timbang per ekor lebih dari 14 hari')
                ->icon(Heroicon::OutlinedClock)
                ->color($sheepNeedsReweighing > 0 ? 'warning' : 'success'),
        ];
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
