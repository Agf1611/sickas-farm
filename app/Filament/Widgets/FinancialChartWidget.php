<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class FinancialChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Keuangan';

    protected ?string $description = 'Modal, pengeluaran, penjualan, dan laba/rugi dalam 6 bulan terakhir.';

    protected string $color = 'primary';

    protected ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.financial.view') ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $data = app(DashboardService::class)->financialChartData();

        return [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Modal Pembelian',
                    'data' => $data['purchase'],
                    'backgroundColor' => '#38bdf8',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $data['expenses'],
                    'backgroundColor' => '#fbbf24',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Penjualan',
                    'data' => $data['sales'],
                    'backgroundColor' => '#22c55e',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Laba / Rugi',
                    'type' => 'line',
                    'data' => $data['profit'],
                    'borderColor' => '#fb7185',
                    'backgroundColor' => '#fb7185',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 5,
                    'tension' => 0.35,
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
                                const value = Number(context.parsed.y ?? 0);
                                return `${context.dataset.label}: Rp ${value.toLocaleString('id-ID')}`;
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
                        grid: { color: 'rgba(100, 116, 139, 0.16)' },
                        ticks: {
                            color: '#64748b',
                            callback: (value) => `Rp ${Number(value).toLocaleString('id-ID')}`
                        }
                    }
                }
            }
        JS);
    }
}
