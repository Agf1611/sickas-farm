<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Support\SickasFormatter;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class GrowthAdgChartWidget extends ChartWidget
{
    protected ?string $heading = 'Rata-rata ADG Batch Aktif';

    protected string $color = 'success';

    protected ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    public function getDescription(): ?string
    {
        $summary = app(DashboardService::class)->growthSummary();

        return 'Rata-rata saat ini: '.SickasFormatter::adg($summary['average_adg'])
            .' | Total kenaikan: '.SickasFormatter::kg($summary['total_weight_gain']);
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $data = app(DashboardService::class)->growthChartData();

        return [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => $data['has_data'] ? 'ADG' : 'Data timbang belum cukup',
                    'data' => $data['visual_adg'],
                    'actualData' => $data['adg'],
                    'hasMonthData' => $data['has_month_data'],
                    'borderColor' => $data['has_data'] ? '#34d399' : '#64748b',
                    'backgroundColor' => $data['has_data'] ? 'rgba(52, 211, 153, 0.16)' : 'rgba(100, 116, 139, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                    'spanGaps' => true,
                    'pointRadius' => $data['has_data']
                        ? array_map(fn (bool $hasMonthData): int => $hasMonthData ? 4 : 0, $data['has_month_data'])
                        : 0,
                    'pointHoverRadius' => 5,
                    'pointBackgroundColor' => array_map(
                        fn (bool $hasMonthData): string => $hasMonthData ? '#34d399' : 'rgba(52, 211, 153, 0)',
                        $data['has_month_data'],
                    ),
                    'pointBorderColor' => array_map(
                        fn (bool $hasMonthData): string => $hasMonthData ? '#34d399' : 'rgba(52, 211, 153, 0)',
                        $data['has_month_data'],
                    ),
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
            {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#64748b',
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const hasMonthData = context.dataset.hasMonthData?.[context.dataIndex] ?? false;
                                const value = context.dataset.actualData?.[context.dataIndex] ?? null;

                                return hasMonthData
                                    ? `ADG: ${Number(value).toLocaleString('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} kg/hari`
                                    : 'Belum ada data timbang';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(100, 116, 139, 0.16)' },
                        ticks: { color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(100, 116, 139, 0.16)' },
                        ticks: {
                            color: '#64748b',
                            callback: (value) => `${Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg/hari`
                        }
                    }
                }
            }
        JS);
    }
}
