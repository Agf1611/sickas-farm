<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Services\GrowthMonitoringService;
use App\Support\SickasFormatter;
use Filament\Widgets\Widget;

class DashboardOperationsPanel extends Widget
{
    protected string $view = 'filament.widgets.dashboard-operations-panel';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function warnings(): array
    {
        return app(DashboardService::class)->warningItems();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function attentionBatches(): array
    {
        return app(DashboardService::class)->attentionBatches();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentActivities(): array
    {
        return app(DashboardService::class)->recentActivities();
    }

    public function formatAdg(?float $value): string
    {
        return SickasFormatter::adg($value);
    }

    public function statusColor(string $status): string
    {
        return app(GrowthMonitoringService::class)->colorForStatus($status);
    }
}
