<?php

namespace App\Filament\Widgets;

use App\Services\GrowthMonitoringService;
use Filament\Widgets\Widget;

class GrowthWarningList extends Widget
{
    protected string $view = 'filament.widgets.growth-warning-list';

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getWarnings(): array
    {
        return app(GrowthMonitoringService::class)->activeBatchGrowthWarnings();
    }

    public function formatKg(?float $value): string
    {
        return $value === null ? '-' : number_format($value, 2, ',', '.').' kg';
    }

    public function formatAdg(?float $value): string
    {
        return $value === null ? '-' : number_format($value, 3, ',', '.').' kg/hari';
    }
}
