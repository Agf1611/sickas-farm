<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\SheepPurchases\SheepPurchaseResource;
use App\Filament\Resources\WeightRecords\WeightRecordResource;
use App\Services\DashboardService;
use Filament\Widgets\Widget;

class ModernDashboardHeader extends Widget
{
    protected string $view = 'filament.widgets.modern-dashboard-header';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        return app(DashboardService::class)->profile();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function quickActions(): array
    {
        $user = auth()->user();

        return collect([
            [
                'label' => 'Pembelian',
                'url' => SheepPurchaseResource::getUrl('index'),
                'tone' => 'success',
                'permission' => 'purchases.manage',
            ],
            [
                'label' => '+ Pengeluaran',
                'url' => ExpenseResource::getUrl('index'),
                'tone' => 'info',
                'permission' => 'expenses.manage',
            ],
            [
                'label' => '+ Penjualan',
                'url' => SaleResource::getUrl('index'),
                'tone' => 'purple',
                'permission' => 'sales.manage',
            ],
            [
                'label' => 'Timbang',
                'url' => WeightRecordResource::getUrl('index'),
                'tone' => 'warning',
                'permission' => 'weighing.manage',
            ],
        ])
            ->filter(fn (array $action): bool => $user?->can($action['permission']) ?? false)
            ->map(fn (array $action): array => [
                'label' => $action['label'],
                'url' => $action['url'],
                'tone' => $action['tone'],
            ])
            ->values()
            ->all();
    }
}
