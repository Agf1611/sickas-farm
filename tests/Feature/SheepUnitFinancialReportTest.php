<?php

namespace Tests\Feature;

use App\Filament\Pages\SheepUnitFinancialReport;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SheepUnitFinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_report_summarizes_filtered_unit_totals(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-KEU-1',
            'name' => 'Kandang Keuangan',
        ]);

        $otherPen = Pen::create([
            'code' => 'KDG-KEU-2',
            'name' => 'Kandang Lain',
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-KEU-1',
            'name' => 'Supplier Keuangan',
        ]);

        $batch = FatteningBatch::create([
            'batch_code' => 'LOT-KEU-001',
            'pen_id' => $pen->id,
            'supplier_id' => $supplier->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'status' => 'active',
        ]);

        $otherBatch = FatteningBatch::create([
            'batch_code' => 'LOT-KEU-002',
            'pen_id' => $otherPen->id,
            'supplier_id' => $supplier->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 5,
            'current_head_count' => 5,
            'status' => 'active',
        ]);

        SheepPurchase::create([
            'purchase_date' => '2026-05-01',
            'supplier_id' => $supplier->id,
            'pen_id' => $pen->id,
            'fattening_batch_id' => $batch->id,
            'purchase_type' => 'bulk',
            'head_count' => 10,
            'total_purchase_price' => 10000000,
            'transport_cost' => 500000,
            'other_cost' => 250000,
        ]);

        SheepPurchase::create([
            'purchase_date' => '2026-05-01',
            'supplier_id' => $supplier->id,
            'pen_id' => $otherPen->id,
            'fattening_batch_id' => $otherBatch->id,
            'purchase_type' => 'bulk',
            'head_count' => 5,
            'total_purchase_price' => 5000000,
            'transport_cost' => 0,
            'other_cost' => 0,
        ]);

        $categories = [
            'feed' => ExpenseCategory::create(['code' => 'PAKAN', 'name' => 'Pakan']),
            'medicine' => ExpenseCategory::create(['code' => 'OBAT', 'name' => 'Obat / Vitamin']),
            'wage' => ExpenseCategory::create(['code' => 'UPAH', 'name' => 'Upah Pengurus']),
            'transport' => ExpenseCategory::create(['code' => 'TRANSPORT', 'name' => 'Transportasi']),
            'pen' => ExpenseCategory::create(['code' => 'KANDANG', 'name' => 'Perbaikan Kandang']),
            'other' => ExpenseCategory::create(['code' => 'LAIN', 'name' => 'Biaya Lain-lain']),
        ];

        foreach ([
            ['feed', 1000000],
            ['medicine', 200000],
            ['wage', 300000],
            ['transport', 400000],
            ['pen', 500000],
            ['other', 600000],
        ] as [$categoryKey, $amount]) {
            Expense::create([
                'expense_date' => '2026-05-10',
                'expense_category_id' => $categories[$categoryKey]->id,
                'fattening_batch_id' => $batch->id,
                'pen_id' => $pen->id,
                'description' => 'Biaya '.$categoryKey,
                'amount' => $amount,
            ]);
        }

        Expense::create([
            'expense_date' => '2026-06-10',
            'expense_category_id' => $categories['feed']->id,
            'fattening_batch_id' => $batch->id,
            'pen_id' => $pen->id,
            'description' => 'Pakan di luar periode',
            'amount' => 999000,
        ]);

        Sale::create([
            'sale_date' => '2026-05-20',
            'sale_type' => 'bulk',
            'fattening_batch_id' => $batch->id,
            'head_count' => 2,
            'total_amount' => 15000000,
        ]);

        Sale::create([
            'sale_date' => '2026-05-20',
            'sale_type' => 'bulk',
            'fattening_batch_id' => $otherBatch->id,
            'head_count' => 1,
            'total_amount' => 3000000,
        ]);

        $page = app(SheepUnitFinancialReport::class);
        $page->periodFrom = '2026-05-01';
        $page->periodUntil = '2026-05-31';
        $page->penId = (string) $pen->id;
        $page->batchId = (string) $batch->id;

        $data = $page->getReportData();

        $this->assertSame(10750000.0, $data['purchase_capital']);
        $this->assertSame(1000000.0, $data['feed_expenses']);
        $this->assertSame(200000.0, $data['medicine_expenses']);
        $this->assertSame(300000.0, $data['wage_expenses']);
        $this->assertSame(400000.0, $data['transport_expenses']);
        $this->assertSame(500000.0, $data['pen_equipment_expenses']);
        $this->assertSame(600000.0, $data['other_expenses']);
        $this->assertSame(3000000.0, $data['total_expenses']);
        $this->assertSame(15000000.0, $data['total_sales']);
        $this->assertSame(1250000.0, $data['net_profit_loss']);
    }

    public function test_financial_report_includes_historical_batch_capital(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-HIS-1',
            'name' => 'Kandang Histori',
        ]);

        $batch = FatteningBatch::create([
            'batch_code' => 'LOT-HIS-001',
            'pen_id' => $pen->id,
            'start_date' => '2025-12-15',
            'initial_head_count' => 12,
            'current_head_count' => 9,
            'initial_total_weight_kg' => 300,
            'purchase_capital' => 24000000,
            'is_historical' => true,
            'historical_notes' => 'Data dari jurnal BUMDes 2025',
            'status' => 'active',
        ]);

        $page = app(SheepUnitFinancialReport::class);
        $page->periodFrom = '2025-12-01';
        $page->periodUntil = '2025-12-31';
        $page->penId = (string) $pen->id;

        $data = $page->getReportData();

        $this->assertSame(24000000.0, $data['purchase_capital']);
        $this->assertSame(-24000000.0, $data['net_profit_loss']);
        $this->assertTrue($batch->fresh()->is_historical);
    }
}
