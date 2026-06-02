<?php

namespace Tests\Feature;

use App\Filament\Widgets\SickasFarmStats;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SheepIncidentRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_use_expected_monitoring_sources(): void
    {
        FatteningBatch::create([
            'start_date' => '2026-05-31',
            'initial_head_count' => 20,
            'current_head_count' => 18,
            'purchase_capital' => 40000000,
            'status' => 'active',
        ]);

        FatteningBatch::create([
            'start_date' => '2026-05-31',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'purchase_capital' => 20000000,
            'status' => 'closed',
        ]);

        SheepIncidentRecord::create([
            'incident_date' => '2026-05-31',
            'incident_type' => 'dead',
            'head_count' => 2,
        ]);

        $category = ExpenseCategory::create([
            'code' => 'PAKAN',
            'name' => 'Pakan',
        ]);

        Expense::create([
            'expense_date' => '2026-05-31',
            'expense_category_id' => $category->id,
            'description' => 'Pakan',
            'amount' => 5000000,
        ]);

        Sale::create([
            'sale_date' => '2026-05-31',
            'sale_type' => 'bulk',
            'head_count' => 4,
            'total_amount' => 15000000,
        ]);

        $widget = app(SickasFarmStats::class);
        $method = new ReflectionMethod($widget, 'getStats');
        $stats = $method->invoke($widget);
        $values = collect($stats)->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()]);

        $this->assertSame('18 ekor', $values['Total Ternak Aktif']);
        $this->assertSame('1 batch', $values['Total Batch Aktif']);
        $this->assertSame('2 ekor', $values['Total Ternak Mati / Afkir']);
        $this->assertSame('4 ekor', $values['Total Ternak Terjual']);
        $this->assertSame('Rp 60.000.000', $values['Total Modal Pembelian']);
        $this->assertSame('Rp 5.000.000', $values['Total Pengeluaran']);
        $this->assertSame('Rp 15.000.000', $values['Total Penjualan']);
        $this->assertSame('Rp -50.000.000', $values['Estimasi Laba / Rugi']);
        $this->assertSame('1 batch', $values['Batch Belum Ditimbang']);
        $this->assertSame('0 batch', $values['Batch Perlu Timbang Ulang']);
        $this->assertSame('0 ekor', $values['Ternak Belum Ditimbang']);
        $this->assertSame('0 ekor', $values['Ternak Perlu Timbang Ulang']);
    }
}
