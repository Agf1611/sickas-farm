<?php

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use Filament\Widgets\Widget;

class ModernDashboardStats extends Widget
{
    protected string $view = 'filament.widgets.modern-dashboard-stats';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function stats(): array
    {
        return app(DashboardService::class)
            ->mainStats(auth()->user()?->can('dashboard.financial.view') ?? false)['items'];
    }
}
