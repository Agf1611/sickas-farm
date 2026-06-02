<?php

namespace Tests\Feature;

use App\Models\FatteningBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockMovementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_sale_and_incident_keep_batch_stock_consistent(): void
    {
        $purchase = SheepPurchase::create([
            'purchase_date' => '2026-05-01',
            'purchase_type' => 'bulk',
            'head_count' => 10,
            'total_weight_kg' => 250,
            'total_purchase_price' => 20000000,
            'transport_cost' => 1000000,
            'other_cost' => 0,
        ]);

        $batch = $purchase->fatteningBatch()->first();

        $this->assertSame(10, $batch->initial_head_count);
        $this->assertSame(10, $batch->current_head_count);

        Sale::create([
            'fattening_batch_id' => $batch->id,
            'sale_date' => '2026-05-10',
            'sale_type' => 'bulk',
            'head_count' => 3,
            'total_amount' => 9000000,
        ]);

        $batch->refresh();
        $this->assertSame(7, $batch->current_head_count);
        $this->assertSame('active', $batch->status);

        SheepIncidentRecord::create([
            'fattening_batch_id' => $batch->id,
            'incident_date' => '2026-05-11',
            'incident_type' => 'dead',
            'head_count' => 2,
        ]);

        $batch->refresh();
        $this->assertSame(5, $batch->current_head_count);

        SheepPurchase::create([
            'purchase_date' => '2026-05-12',
            'fattening_batch_id' => $batch->id,
            'purchase_type' => 'bulk',
            'head_count' => 5,
            'total_weight_kg' => 125,
            'total_purchase_price' => 10000000,
            'transport_cost' => 0,
            'other_cost' => 0,
        ]);

        $batch->refresh();
        $this->assertSame(15, $batch->initial_head_count);
        $this->assertSame(10, $batch->current_head_count);
        $this->assertSame('375.00', $batch->initial_total_weight_kg);
        $this->assertSame('2026-05-01', $batch->start_date->toDateString());
    }

    public function test_stock_cannot_go_minus_and_batch_closes_when_empty(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 5,
            'current_head_count' => 5,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        Sale::create([
            'fattening_batch_id' => $batch->id,
            'sale_date' => '2026-05-10',
            'sale_type' => 'bulk',
            'head_count' => 6,
            'total_amount' => 12000000,
        ]);
    }

    public function test_batch_is_closed_after_all_stock_is_out_and_rejects_new_transactions(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 5,
            'current_head_count' => 5,
            'status' => 'active',
        ]);

        Sale::create([
            'fattening_batch_id' => $batch->id,
            'sale_date' => '2026-05-10',
            'sale_type' => 'bulk',
            'head_count' => 5,
            'total_amount' => 12000000,
        ]);

        $batch->refresh();
        $this->assertSame(0, $batch->current_head_count);
        $this->assertSame('closed', $batch->status);

        $this->expectException(ValidationException::class);

        SheepIncidentRecord::create([
            'fattening_batch_id' => $batch->id,
            'incident_date' => '2026-05-11',
            'incident_type' => 'dead',
            'head_count' => 1,
        ]);
    }

    public function test_sale_item_marks_sheep_sold_and_prevents_reselling_or_selling_dead_sheep(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 3,
            'current_head_count' => 3,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'fattening_batch_id' => $batch->id,
            'sale_date' => '2026-05-10',
            'sale_type' => 'per_head',
            'head_count' => 1,
            'total_amount' => 2500000,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'sheep_id' => $sheep->id,
            'price' => 2500000,
        ]);

        $sheep->refresh();
        $this->assertSame('sold', $sheep->status);

        $secondSale = Sale::create([
            'fattening_batch_id' => $batch->id,
            'sale_date' => '2026-05-11',
            'sale_type' => 'per_head',
            'head_count' => 1,
            'total_amount' => 2500000,
        ]);

        try {
            SaleItem::create([
                'sale_id' => $secondSale->id,
                'sheep_id' => $sheep->id,
                'price' => 2500000,
            ]);

            $this->fail('Ternak yang sudah terjual seharusnya tidak bisa dijual lagi.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $deadSheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'status' => 'active',
        ]);

        SheepIncidentRecord::create([
            'sheep_id' => $deadSheep->id,
            'incident_date' => '2026-05-12',
            'incident_type' => 'dead',
            'head_count' => 1,
        ]);

        $this->expectException(ValidationException::class);

        SaleItem::create([
            'sale_id' => $secondSale->id,
            'sheep_id' => $deadSheep->id,
            'price' => 2500000,
        ]);
    }

    public function test_sick_incident_does_not_reduce_stock_but_culled_incident_does(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 3,
            'current_head_count' => 3,
            'status' => 'active',
        ]);

        SheepIncidentRecord::create([
            'fattening_batch_id' => $batch->id,
            'incident_date' => '2026-05-10',
            'incident_type' => 'sick',
            'head_count' => 1,
        ]);

        $batch->refresh();
        $this->assertSame(3, $batch->current_head_count);

        $sheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'status' => 'active',
        ]);

        SheepIncidentRecord::create([
            'sheep_id' => $sheep->id,
            'incident_date' => '2026-05-11',
            'incident_type' => 'culled',
            'head_count' => 1,
        ]);

        $batch->refresh();
        $sheep->refresh();

        $this->assertSame(2, $batch->current_head_count);
        $this->assertSame('culled', $sheep->status);
    }
}
