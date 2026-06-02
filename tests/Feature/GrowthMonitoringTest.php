<?php

namespace Tests\Feature;

use App\Filament\Pages\GrowthMonitoring;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\Sheep;
use App\Models\WeighingRecord;
use App\Services\GrowthMonitoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_batch_growth_uses_initial_batch_weight_and_latest_batch_weighing(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $batch->id,
            'head_count' => 10,
            'total_weight_kg' => 102,
        ]);

        $growth = app(GrowthMonitoringService::class)->calculateBatchGrowth($batch);

        $this->assertSame(100.0, $growth['initial_weight']);
        $this->assertSame(102.0, $growth['latest_weight']);
        $this->assertSame(2.0, $growth['weight_gain']);
        $this->assertSame(10.0, $growth['initial_average_weight']);
        $this->assertSame(10.2, $growth['latest_average_weight']);
        $this->assertEqualsWithDelta(0.2, $growth['average_weight_gain'], 0.000001);
        $this->assertSame(10, $growth['days']);
        $this->assertSame(0.2, $growth['adg']);
        $this->assertSame(10.2, $growth['average_weight']);
        $this->assertSame('Bagus', $growth['status']);
        $this->assertSame(30.0, $growth['target_sale_average_weight']);
        $this->assertSame('Lanjutkan Penggemukan', $growth['selling_indicator']);
    }

    public function test_individual_growth_uses_initial_sheep_weight_and_latest_individual_weighing(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 1,
            'current_head_count' => 1,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 20,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'individual',
            'fattening_batch_id' => $batch->id,
            'sheep_id' => $sheep->id,
            'weight_kg' => 20.6,
        ]);

        $growth = app(GrowthMonitoringService::class)->calculateSheepGrowth($sheep);

        $this->assertSame(20.0, $growth['initial_weight']);
        $this->assertSame(20.6, $growth['latest_weight']);
        $this->assertEqualsWithDelta(0.6, $growth['weight_gain'], 0.000001);
        $this->assertSame(10, $growth['days']);
        $this->assertEqualsWithDelta(0.06, $growth['adg'], 0.000001);
        $this->assertSame('Lambat', $growth['status']);
    }

    public function test_growth_without_weighing_is_marked_as_not_weighed_yet(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        $growth = app(GrowthMonitoringService::class)->calculateBatchGrowth($batch);

        $this->assertNull($growth['latest_weight']);
        $this->assertNull($growth['weight_gain']);
        $this->assertNull($growth['days']);
        $this->assertNull($growth['adg']);
        $this->assertSame('Belum Ditimbang', $growth['status']);
        $this->assertSame('Segera lakukan timbang agar pertumbuhan bisa dipantau.', $growth['recommendation']);
    }

    public function test_negative_individual_growth_is_marked_as_down(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 1,
            'current_head_count' => 1,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 20,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'individual',
            'fattening_batch_id' => $batch->id,
            'sheep_id' => $sheep->id,
            'weight_kg' => 19.5,
        ]);

        $growth = app(GrowthMonitoringService::class)->calculateSheepGrowth($sheep);

        $this->assertSame('Turun', $growth['status']);
        $this->assertSame('Berat turun. Segera periksa kesehatan ternak dan pisahkan jika perlu.', $growth['recommendation']);
    }

    public function test_growth_monitoring_page_filters_batch_rows(): void
    {
        $firstPen = Pen::create([
            'code' => 'KDG-001',
            'name' => 'Kandang Satu',
        ]);

        $secondPen = Pen::create([
            'code' => 'KDG-002',
            'name' => 'Kandang Dua',
        ]);

        $goodBatch = FatteningBatch::create([
            'pen_id' => $firstPen->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        $slowBatch = FatteningBatch::create([
            'pen_id' => $secondPen->id,
            'start_date' => '2026-05-10',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $goodBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 102,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-20',
            'record_type' => 'batch',
            'fattening_batch_id' => $slowBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 100.6,
        ]);

        $page = app(GrowthMonitoring::class);
        $page->penId = (string) $firstPen->id;
        $page->growthStatus = 'Bagus';
        $page->startDateFrom = '2026-05-01';
        $page->startDateUntil = '2026-05-31';

        $rows = $page->getRows();

        $this->assertCount(1, $rows);
        $this->assertSame($goodBatch->batch_code, $rows->first()['batch_code']);
        $this->assertSame('Kandang Satu', $rows->first()['pen_name']);
        $this->assertSame('Bagus', $rows->first()['status']);
    }

    public function test_weighing_alert_marks_active_batch_and_sheep_that_need_attention(): void
    {
        Carbon::setTestNow('2026-05-31 08:00:00');

        $service = app(GrowthMonitoringService::class);

        $neverWeighedBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        $oldWeighedBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        $recentWeighedBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-16',
            'record_type' => 'batch',
            'fattening_batch_id' => $oldWeighedBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 101,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-17',
            'record_type' => 'batch',
            'fattening_batch_id' => $recentWeighedBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 101,
        ]);

        $neverWeighedSheep = Sheep::create([
            'status' => 'active',
            'initial_weight_kg' => 20,
        ]);

        $oldWeighedSheep = Sheep::create([
            'status' => 'active',
            'initial_weight_kg' => 20,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-16',
            'record_type' => 'individual',
            'sheep_id' => $oldWeighedSheep->id,
            'weight_kg' => 20.5,
        ]);

        $this->assertSame('Belum Ditimbang', $service->calculateBatchGrowth($neverWeighedBatch)['weighing_alert_status']);
        $this->assertSame('Perlu Timbang Ulang', $service->calculateBatchGrowth($oldWeighedBatch)['weighing_alert_status']);
        $this->assertSame('Timbang Terkini', $service->calculateBatchGrowth($recentWeighedBatch)['weighing_alert_status']);
        $this->assertSame('Belum Ditimbang', $service->calculateSheepGrowth($neverWeighedSheep)['weighing_alert_status']);
        $this->assertSame('Perlu Timbang Ulang', $service->calculateSheepGrowth($oldWeighedSheep)['weighing_alert_status']);

        $this->assertSame(1, $service->countBatchNeverWeighed());
        $this->assertSame(1, $service->countBatchNeedsReweighing());
        $this->assertSame(1, $service->countSheepNeverWeighed());
        $this->assertSame(1, $service->countSheepNeedsReweighing());
    }

    public function test_selling_indicator_uses_adg_and_target_average_weight(): void
    {
        $readyBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 280,
            'target_sale_average_weight_kg' => 30,
            'status' => 'active',
        ]);

        $continueBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 200,
            'target_sale_average_weight_kg' => 30,
            'status' => 'active',
        ]);

        $slowBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 200,
            'target_sale_average_weight_kg' => 30,
            'status' => 'active',
        ]);

        $downBatch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 200,
            'target_sale_average_weight_kg' => 30,
            'status' => 'active',
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $readyBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 302,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $continueBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 202,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $slowBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 200.6,
        ]);

        WeighingRecord::create([
            'weighed_at' => '2026-05-11',
            'record_type' => 'batch',
            'fattening_batch_id' => $downBatch->id,
            'head_count' => 10,
            'total_weight_kg' => 199,
        ]);

        $service = app(GrowthMonitoringService::class);

        $this->assertSame('Siap Dipertimbangkan untuk Dijual', $service->calculateBatchGrowth($readyBatch)['selling_indicator']);
        $this->assertSame('Lanjutkan Penggemukan', $service->calculateBatchGrowth($continueBatch)['selling_indicator']);
        $this->assertSame('Evaluasi Pakan dan Perawatan', $service->calculateBatchGrowth($slowBatch)['selling_indicator']);
        $this->assertSame('Jangan Dijual Normal, Periksa Kesehatan', $service->calculateBatchGrowth($downBatch)['selling_indicator']);
    }
}
