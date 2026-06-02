<?php

namespace Tests\Feature;

use App\Filament\Pages\FatteningPerformanceReport;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\WeighingRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FatteningPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_performance_report_filters_and_displays_growth_metrics(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-REPORT-1',
            'name' => 'Kandang Laporan',
        ]);

        $otherPen = Pen::create([
            'code' => 'KDG-REPORT-2',
            'name' => 'Kandang Lain',
        ]);

        $goodBatch = FatteningBatch::create([
            'batch_code' => 'LOT-REPORT-001',
            'pen_id' => $pen->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'initial_total_weight_kg' => 100,
            'target_sale_average_weight_kg' => 10,
            'status' => 'active',
        ]);

        $slowBatch = FatteningBatch::create([
            'batch_code' => 'LOT-REPORT-002',
            'pen_id' => $otherPen->id,
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

        $page = app(FatteningPerformanceReport::class);
        $page->penId = (string) $pen->id;
        $page->growthStatus = 'Bagus';
        $page->startDateFrom = '2026-05-01';
        $page->startDateUntil = '2026-05-31';

        $rows = $page->getRows();

        $this->assertCount(1, $rows);
        $this->assertSame('LOT-REPORT-001', $rows->first()['batch_code']);
        $this->assertSame('Kandang Laporan', $rows->first()['pen_name']);
        $this->assertSame(100.0, $rows->first()['initial_weight']);
        $this->assertSame(102.0, $rows->first()['latest_weight']);
        $this->assertSame(2.0, $rows->first()['weight_gain']);
        $this->assertSame(10.0, $rows->first()['initial_average_weight']);
        $this->assertSame(10.2, $rows->first()['latest_average_weight']);
        $this->assertEqualsWithDelta(0.2, $rows->first()['average_weight_gain'], 0.000001);
        $this->assertSame(0.2, $rows->first()['adg']);
        $this->assertSame('Bagus', $rows->first()['status']);
        $this->assertSame(10.0, $rows->first()['target_sale_average_weight']);
        $this->assertSame('Siap Dipertimbangkan untuk Dijual', $rows->first()['selling_indicator']);
    }
}
