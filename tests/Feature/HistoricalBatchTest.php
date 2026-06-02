<?php

namespace Tests\Feature;

use App\Filament\Pages\SheepPopulationReport;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_batch_can_exist_without_purchase_invoice_and_appears_in_reports(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-HIST-REPORT',
            'name' => 'Kandang Histori Report',
        ]);

        FatteningBatch::create([
            'batch_code' => 'LOT-HIST-REPORT',
            'pen_id' => $pen->id,
            'start_date' => '2025-08-10',
            'initial_head_count' => 20,
            'current_head_count' => 14,
            'initial_total_weight_kg' => 480,
            'purchase_capital' => 40000000,
            'is_historical' => true,
            'historical_notes' => 'Data dari jurnal BUMDes 2025',
            'status' => 'active',
        ]);

        $page = app(SheepPopulationReport::class);
        $page->penId = (string) $pen->id;
        $page->purchaseDateFrom = '2025-08-01';
        $page->purchaseDateUntil = '2025-08-31';

        $row = $page->getRows()->first();
        $summary = $page->getSummary();
        $export = app(ReportExportService::class)->buildPopulationReport([
            'pen_id' => (string) $pen->id,
            'from' => '2025-08-01',
            'until' => '2025-08-31',
        ]);

        $this->assertSame('LOT-HIST-REPORT', $row['batch_code']);
        $this->assertSame('Histori Manual', $row['source_label']);
        $this->assertSame(20, $summary['initial']);
        $this->assertSame(14, $summary['active']);
        $this->assertSame('Histori Manual', $export['rows'][0]['Sumber']);
    }
}
