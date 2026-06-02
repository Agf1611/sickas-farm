<?php

namespace Tests\Feature;

use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\SheepPurchase;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SheepPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_purchase_creates_active_fattening_batch(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'Supplier Test',
        ]);

        $pen = Pen::create([
            'code' => 'KDG-001',
            'name' => 'Kandang Test',
        ]);

        $purchase = SheepPurchase::create([
            'purchase_date' => '2026-05-31',
            'supplier_id' => $supplier->id,
            'pen_id' => $pen->id,
            'purchase_type' => 'bulk',
            'head_count' => 25,
            'total_weight_kg' => 750,
            'total_purchase_price' => 50000000,
            'transport_cost' => 1500000,
            'other_cost' => 500000,
            'notes' => 'Pembelian borongan',
        ]);

        $purchase->refresh();

        $this->assertNotNull($purchase->fattening_batch_id);
        $this->assertDatabaseHas('fattening_batches', [
            'id' => $purchase->fattening_batch_id,
            'batch_code' => 'LOT-2026-001',
            'pen_id' => $pen->id,
            'supplier_id' => $supplier->id,
            'initial_head_count' => 25,
            'current_head_count' => 25,
            'initial_total_weight_kg' => 750,
            'purchase_capital' => 52000000,
            'status' => 'active',
        ]);
    }

    public function test_purchase_can_update_an_existing_batch(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-002',
            'name' => 'Supplier Dua',
        ]);

        $pen = Pen::create([
            'code' => 'KDG-002',
            'name' => 'Kandang Dua',
        ]);

        $batch = FatteningBatch::create([
            'start_date' => '2026-05-31',
            'initial_head_count' => 0,
            'current_head_count' => 0,
            'status' => 'active',
        ]);

        SheepPurchase::create([
            'purchase_date' => '2026-05-31',
            'supplier_id' => $supplier->id,
            'pen_id' => $pen->id,
            'fattening_batch_id' => $batch->id,
            'purchase_type' => 'bulk',
            'head_count' => 12,
            'total_purchase_price' => 24000000,
            'transport_cost' => 1000000,
            'other_cost' => 0,
        ]);

        $batch->refresh();

        $this->assertSame($supplier->id, $batch->supplier_id);
        $this->assertSame($pen->id, $batch->pen_id);
        $this->assertSame(12, $batch->initial_head_count);
        $this->assertSame(12, $batch->current_head_count);
        $this->assertSame('25000000.00', $batch->purchase_capital);
    }
}
