<?php

namespace Tests\Feature;

use App\Filament\Pages\SheepPopulationReport;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SheepPopulationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_population_report_displays_summary_and_filters_rows(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-POP-1',
            'name' => 'Kandang Populasi',
        ]);

        $otherPen = Pen::create([
            'code' => 'KDG-POP-2',
            'name' => 'Kandang Lain',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-POP-1',
            'name' => 'Supplier Populasi',
        ]);

        $batch = FatteningBatch::create([
            'batch_code' => 'LOT-POP-001',
            'pen_id' => $pen->id,
            'supplier_id' => $supplier->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 5,
            'status' => 'active',
        ]);

        $otherBatch = FatteningBatch::create([
            'batch_code' => 'LOT-POP-002',
            'pen_id' => $otherPen->id,
            'supplier_id' => $supplier->id,
            'start_date' => '2026-05-20',
            'initial_head_count' => 4,
            'current_head_count' => 4,
            'status' => 'active',
        ]);

        SheepPurchase::create([
            'purchase_date' => '2026-05-01',
            'fattening_batch_id' => $batch->id,
            'supplier_id' => $supplier->id,
            'pen_id' => $pen->id,
            'purchase_type' => 'bulk',
            'head_count' => 10,
            'total_purchase_price' => 20000000,
            'transport_cost' => 0,
            'other_cost' => 0,
        ]);

        SheepPurchase::create([
            'purchase_date' => '2026-05-20',
            'fattening_batch_id' => $otherBatch->id,
            'supplier_id' => $supplier->id,
            'pen_id' => $otherPen->id,
            'purchase_type' => 'bulk',
            'head_count' => 4,
            'total_purchase_price' => 8000000,
            'transport_cost' => 0,
            'other_cost' => 0,
        ]);

        Sale::create([
            'sale_date' => '2026-05-10',
            'fattening_batch_id' => $batch->id,
            'sale_type' => 'bulk',
            'head_count' => 2,
            'total_amount' => 6000000,
        ]);

        SheepIncidentRecord::create([
            'incident_date' => '2026-05-11',
            'fattening_batch_id' => $batch->id,
            'incident_type' => 'dead',
            'head_count' => 1,
        ]);

        SheepIncidentRecord::create([
            'incident_date' => '2026-05-12',
            'fattening_batch_id' => $batch->id,
            'incident_type' => 'culled',
            'head_count' => 2,
        ]);

        $batch->refresh();
        $this->assertSame(5, $batch->current_head_count);

        $page = app(SheepPopulationReport::class);
        $page->penId = (string) $pen->id;
        $page->sheepStatus = 'culled';
        $page->purchaseDateFrom = '2026-05-01';
        $page->purchaseDateUntil = '2026-05-15';

        $rows = $page->getRows();
        $summary = $page->getSummary();

        $this->assertCount(1, $rows);
        $this->assertSame('LOT-POP-001', $rows->first()['batch_code']);
        $this->assertSame('Kandang Populasi', $rows->first()['pen_name']);
        $this->assertSame('Supplier Populasi', $rows->first()['supplier_name']);
        $this->assertSame(10, $rows->first()['initial_head_count']);
        $this->assertSame(5, $rows->first()['active_head_count']);
        $this->assertSame(1, $rows->first()['dead_head_count']);
        $this->assertSame(2, $rows->first()['culled_head_count']);
        $this->assertSame(2, $rows->first()['sold_head_count']);
        $this->assertSame('active', $rows->first()['batch_status']);

        $this->assertSame(10, $summary['initial']);
        $this->assertSame(5, $summary['active']);
        $this->assertSame(1, $summary['dead']);
        $this->assertSame(2, $summary['culled']);
        $this->assertSame(2, $summary['sold']);
    }
}
