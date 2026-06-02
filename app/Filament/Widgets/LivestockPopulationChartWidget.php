<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class LivestockPopulationChartWidget extends ChartWidget
{
    protected ?string $heading = 'Populasi Ternak';

    protected ?string $description = 'Komposisi ternak aktif, terjual, mati, dan afkir.';

    protected string $color = 'success';

    protected ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $data = app(DashboardService::class)->populationChartData();

        if ($data['total'] === 0) {
            return [
                'labels' => ['Belum Ada Data'],
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#334155'],
                        'borderColor' => ['#1f2937'],
                    ],
                ],
            ];
        }

        return [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'data' => $data['data'],
                    'backgroundColor' => ['#22c55e', '#38bdf8', '#fb7185', '#fbbf24'],
                    'borderColor' => ['#052e16', '#082f49', '#450a0a', '#451a03'],
                    'borderWidth' => 2,
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
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#64748b',
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${Number(context.parsed ?? 0).toLocaleString('id-ID')} ekor`
                        }
                    }
                }
            }
        JS);
    }
}
