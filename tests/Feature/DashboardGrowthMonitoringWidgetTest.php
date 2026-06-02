<?php

namespace Tests\Feature;

use App\Filament\Widgets\GrowthMonitoringStats;
use App\Filament\Widgets\GrowthWarningList;
use App\Models\FatteningBatch;
use App\Models\WeighingRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardGrowthMonitoringWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_growth_monitoring_stats_summarize_active_batches(): void
    {
        Carbon::setTestNow('2026-05-31 08:00:00');

        $this->createBatchWithWeighing('LOT-TEST-GOOD', '2026-05-15', '2026-05-25', 100, 102);
        $this->createBatchWithWeighing('LOT-TEST-SLOW', '2026-05-01', '2026-05-11', 100, 100.6);
        $this->createBatchWithWeighing('LOT-TEST-STAGNAN', '2026-05-15', '2026-05-25', 100, 100.2);
        $this->createBatchWithWeighing('LOT-TEST-DOWN', '2026-05-15', '2026-05-25', 100, 99);

        FatteningBatch::create([
            'batch_code' => 'LOT-TEST-NEVER',
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        $widget = app(GrowthMonitoringStats::class);
        $method = new ReflectionMethod($widget, 'getStats');
        $stats = $method->invoke($widget);
        $values = collect($stats)->mapWithKeys(fn ($stat): array => [$stat->getLabel() => $stat->getValue()]);

        $this->assertSame('0,045 kg/hari', $values['Rata-rata ADG Batch Aktif']);
        $this->assertSame('1,80 kg', $values['Total Kenaikan Berat']);
        $this->assertSame('1 batch', $values['Batch Pertumbuhan Bagus']);
        $this->assertSame('1 batch', $values['Batch Pertumbuhan Lambat']);
        $this->assertSame('1 batch', $values['Batch Stagnan']);
        $this->assertSame('1 batch', $values['Batch Berat Turun']);
        $this->assertSame('1 batch', $values['Batch Perlu Timbang Ulang']);
    }

    public function test_growth_warning_list_groups_problem_batches(): void
    {
        Carbon::setTestNow('2026-05-31 08:00:00');

        $slow = $this->createBatchWithWeighing('LOT-WARN-SLOW', '2026-05-01', '2026-05-11', 100, 100.6);
        $down = $this->createBatchWithWeighing('LOT-WARN-DOWN', '2026-05-15', '2026-05-25', 100, 99);
        $overdue = FatteningBatch::create([
            'batch_code' => 'LOT-WARN-NEVER',
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        $warnings = app(GrowthWarningList::class)->getWarnings();

        $this->assertSame($down->batch_code, $warnings['down'][0]['batch_code']);
        $this->assertSame($slow->batch_code, $warnings['slow'][0]['batch_code']);
        $this->assertSame($overdue->batch_code, $warnings['not_weighed_overdue'][0]['batch_code']);
    }

    private function createBatchWithWeighing(string $batchCode, string $startDate, string $weighedAt, float $initialWeight, float $latestWeight): FatteningBatch
    {
        $batch = FatteningBatch::create([
            'batch_code' => $batchCode,
            'start_date' => $startDate,
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => $initialWeight,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => $weighedAt,
            'record_type' => 'batch',
            'fattening_batch_id' => $batch->id,
            'head_count' => 10,
            'total_weight_kg' => $latestWeight,
        ]);

        return $batch;
    }
}
