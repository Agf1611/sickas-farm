<?php

namespace App\Filament\Widgets;

use App\Services\GrowthMonitoringService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GrowthMonitoringStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Monitoring Pertumbuhan';

    protected ?string $description = 'Ringkasan performa penggemukan batch aktif.';

    protected static ?int $sort = 2;

    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 4,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $summary = app(GrowthMonitoringService::class)->activeBatchGrowthSummary();

        return [
            Stat::make('Rata-rata ADG Batch Aktif', $this->formatAdg($summary['average_adg']))
                ->description('Rata-rata dari batch aktif yang sudah ditimbang')
                ->icon(Heroicon::OutlinedPresentationChartLine)
                ->color('info'),
            Stat::make('Total Kenaikan Berat', $this->formatKg($summary['total_weight_gain']))
                ->description('Akumulasi kenaikan berat batch aktif')
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->color($summary['total_weight_gain'] >= 0 ? 'success' : 'danger'),
            Stat::make('Batch Pertumbuhan Bagus', $this->formatBatch($summary['good_count']))
                ->description('ADG minimal 0,150 kg/hari')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('success'),
            Stat::make('Batch Pertumbuhan Lambat', $this->formatBatch($summary['slow_count']))
                ->description('ADG 0,050 sampai 0,149 kg/hari')
                ->icon(Heroicon::OutlinedClock)
                ->color($summary['slow_count'] > 0 ? 'warning' : 'success'),
            Stat::make('Batch Stagnan', $this->formatBatch($summary['stagnant_count']))
                ->description('ADG 0 sampai 0,049 kg/hari')
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->color($summary['stagnant_count'] > 0 ? 'gray' : 'success'),
            Stat::make('Batch Berat Turun', $this->formatBatch($summary['down_count']))
                ->description('ADG negatif')
                ->icon(Heroicon::OutlinedArrowTrendingDown)
                ->color($summary['down_count'] > 0 ? 'danger' : 'success'),
            Stat::make('Batch Perlu Timbang Ulang', $this->formatBatch($summary['needs_reweighing_count']))
                ->description('Timbang terakhir lebih dari 14 hari')
                ->icon(Heroicon::OutlinedScale)
                ->color($summary['needs_reweighing_count'] > 0 ? 'warning' : 'success'),
        ];
    }

    private function formatAdg(?float $value): string
    {
        return $value === null ? '-' : number_format($value, 3, ',', '.').' kg/hari';
    }

    private function formatKg(float $value): string
    {
        return number_format($value, 2, ',', '.').' kg';
    }

    private function formatBatch(int $value): string
    {
        return number_format($value, 0, ',', '.').' batch';
    }
}
