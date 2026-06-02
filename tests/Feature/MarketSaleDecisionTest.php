<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\FatteningBatch;
use App\Models\LivestockMarketPrice;
use App\Models\LivestockType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sheep;
use App\Models\SheepPurchase;
use App\Models\SaleProposal;
use App\Models\SaleProposalItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MarketValueEstimationService;
use App\Services\SaleProposalConversionService;
use App\Support\SickasFarmPermissions;
use Database\Seeders\SickasFarmRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketSaleDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_price_estimates_batch_and_sheep_sale_value(): void
    {
        $livestockType = LivestockType::query()->firstOrCreate(
            ['code' => 'DMB'],
            [
                'name' => 'Domba',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
            ],
        );

        $batch = FatteningBatch::create([
            'livestock_type_id' => $livestockType->id,
            'start_date' => '2026-05-01',
            'initial_head_count' => 2,
            'current_head_count' => 2,
            'initial_total_weight_kg' => 50,
            'purchase_capital' => 3000000,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'livestock_type_id' => $livestockType->id,
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 25,
            'current_weight_kg' => 28,
            'purchase_price' => 1500000,
            'status' => 'active',
        ]);

        Sheep::create([
            'livestock_type_id' => $livestockType->id,
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 25,
            'current_weight_kg' => 27,
            'purchase_price' => 1500000,
            'status' => 'active',
        ]);

        LivestockMarketPrice::create([
            'livestock_type_id' => $livestockType->id,
            'effective_date' => '2026-06-01',
            'price_type' => 'per_kg',
            'price_per_kg' => 70000,
            'is_active' => true,
        ]);

        $service = app(MarketValueEstimationService::class);

        $batchEstimate = $service->estimateBatch($batch->fresh());
        $sheepEstimate = $service->estimateSheep($sheep->fresh());

        $this->assertSame(3850000.0, $batchEstimate['estimated_value']);
        $this->assertSame(850000.0, $batchEstimate['estimated_profit_loss']);
        $this->assertSame(1960000.0, $sheepEstimate['estimated_value']);
        $this->assertSame(460000.0, $sheepEstimate['estimated_profit_loss']);
    }

    public function test_purchase_and_sale_create_invoice_numbers_and_stock_movements(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier Uji']);
        $buyer = Buyer::create(['name' => 'Pembeli Uji']);

        $purchase = SheepPurchase::create([
            'purchase_date' => '2026-06-01',
            'supplier_id' => $supplier->id,
            'purchase_type' => 'bulk',
            'head_count' => 3,
            'total_purchase_price' => 6000000,
        ]);

        $this->assertSame('INV-BELI-2026-001', $purchase->purchase_number);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => SheepPurchase::class,
            'reference_id' => $purchase->id,
            'movement_type' => 'purchase',
            'quantity_in' => 3,
            'quantity_out' => 0,
        ]);

        $sale = Sale::create([
            'buyer_id' => $buyer->id,
            'fattening_batch_id' => $purchase->fattening_batch_id,
            'sale_date' => '2026-06-02',
            'sale_type' => 'bulk',
            'head_count' => 1,
            'total_amount' => 2500000,
        ]);

        $this->assertSame('INV-JUAL-2026-001', $sale->sale_number);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'movement_type' => 'sale',
            'quantity_in' => 0,
            'quantity_out' => 1,
        ]);
    }

    public function test_invoice_pdf_routes_are_downloadable_for_super_admin(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(SickasFarmPermissions::SUPER_ADMIN);

        $purchase = SheepPurchase::create([
            'purchase_date' => '2026-06-01',
            'purchase_type' => 'bulk',
            'head_count' => 1,
            'total_purchase_price' => 1500000,
        ]);

        $sale = Sale::create([
            'fattening_batch_id' => $purchase->fattening_batch_id,
            'sale_date' => '2026-06-02',
            'sale_type' => 'bulk',
            'head_count' => 1,
            'total_amount' => 2000000,
        ]);

        $this->actingAs($admin)
            ->get(route('sickas-farm.invoices.purchase.pdf', $purchase))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('sickas-farm.invoices.sale.pdf', $sale))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_approved_batch_sale_proposal_can_be_converted_to_real_sale(): void
    {
        $livestockType = LivestockType::query()->firstOrCreate(
            ['code' => 'DMB'],
            [
                'name' => 'Domba',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
            ],
        );

        $batch = FatteningBatch::create([
            'livestock_type_id' => $livestockType->id,
            'start_date' => '2026-06-01',
            'initial_head_count' => 3,
            'current_head_count' => 3,
            'initial_total_weight_kg' => 90,
            'purchase_capital' => 6000000,
            'status' => 'active',
        ]);

        LivestockMarketPrice::create([
            'livestock_type_id' => $livestockType->id,
            'effective_date' => '2026-06-01',
            'price_type' => 'per_kg',
            'price_per_kg' => 80000,
            'is_active' => true,
        ]);

        $proposal = SaleProposal::create([
            'fattening_batch_id' => $batch->id,
            'proposed_date' => '2026-06-02',
            'proposal_type' => 'batch',
            'status' => 'submitted',
        ]);

        $service = app(SaleProposalConversionService::class);
        $service->approve($proposal);

        $sale = $service->convertToSale($proposal->fresh(), [
            'sale_date' => '2026-06-03',
            'notes' => 'Disetujui rapat unit',
        ]);

        $this->assertSame('converted_to_sale', $proposal->fresh()->status);
        $this->assertSame($sale->id, $proposal->fresh()->sale_id);
        $this->assertSame('per_kg', $sale->sale_type);
        $this->assertSame(3, $sale->head_count);
        $this->assertSame('7200000.00', $sale->total_amount);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'movement_type' => 'sale',
            'quantity_out' => 3,
        ]);

        $this->expectException(ValidationException::class);
        $service->convertToSale($proposal->fresh(), ['sale_date' => '2026-06-04']);
    }

    public function test_selected_livestock_sale_proposal_creates_sale_items_and_marks_sheep_sold(): void
    {
        $livestockType = LivestockType::query()->firstOrCreate(
            ['code' => 'DMB'],
            [
                'name' => 'Domba',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
            ],
        );

        $batch = FatteningBatch::create([
            'livestock_type_id' => $livestockType->id,
            'start_date' => '2026-06-01',
            'initial_head_count' => 2,
            'current_head_count' => 2,
            'initial_total_weight_kg' => 60,
            'purchase_capital' => 4000000,
            'status' => 'active',
        ]);

        $first = Sheep::create([
            'livestock_type_id' => $livestockType->id,
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 30,
            'current_weight_kg' => 32,
            'purchase_price' => 2000000,
            'status' => 'active',
        ]);

        Sheep::create([
            'livestock_type_id' => $livestockType->id,
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 30,
            'current_weight_kg' => 31,
            'purchase_price' => 2000000,
            'status' => 'active',
        ]);

        LivestockMarketPrice::create([
            'livestock_type_id' => $livestockType->id,
            'effective_date' => '2026-06-01',
            'price_type' => 'per_kg',
            'price_per_kg' => 85000,
            'is_active' => true,
        ]);

        $proposal = SaleProposal::create([
            'fattening_batch_id' => $batch->id,
            'proposed_date' => '2026-06-02',
            'proposal_type' => 'selected_livestock',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        SaleProposalItem::create([
            'sale_proposal_id' => $proposal->id,
            'sheep_id' => $first->id,
        ]);

        $sale = app(SaleProposalConversionService::class)->convertToSale($proposal->fresh(['items.sheep']), [
            'sale_date' => '2026-06-03',
        ]);

        $this->assertSame('per_head', $sale->sale_type);
        $this->assertSame(1, $sale->head_count);
        $this->assertSame('32.00', $sale->total_weight_kg);
        $this->assertSame('2720000.00', $sale->total_amount);
        $this->assertSame(1, SaleItem::query()->where('sale_id', $sale->id)->count());
        $this->assertSame('sold', $first->fresh()->status);
    }
}
