<?php

namespace Tests\Feature;

use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\Sale;
use App\Models\Sheep;
use App\Services\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_batch_sheep_and_sale_codes_when_records_are_created(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-31',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'status' => 'active',
        ]);

        $sale = Sale::create([
            'sale_date' => '2026-05-31',
            'sale_type' => 'bulk',
            'head_count' => 1,
            'total_amount' => 2500000,
        ]);

        $this->assertSame('LOT-2026-001', $batch->batch_code);
        $this->assertSame('DMB-2026-0001', $sheep->tag_number);
        $this->assertSame('INV-JUAL-2026-001', $sale->sale_number);
    }

    public function test_generator_uses_next_available_number_for_the_requested_year(): void
    {
        FatteningBatch::create([
            'batch_code' => 'LOT-2026-001',
            'start_date' => '2026-05-31',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'status' => 'active',
        ]);

        FatteningBatch::create([
            'batch_code' => 'LOT-2026-002',
            'start_date' => '2026-05-31',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'status' => 'active',
        ]);

        $this->assertSame('LOT-2026-003', app(CodeGeneratorService::class)->generateBatchCode(2026));
    }

    public function test_sheep_code_uses_livestock_type_prefix_and_batch_default(): void
    {
        $kambing = LivestockType::query()->firstOrCreate(
            ['code' => 'KMB'],
            [
                'name' => 'Kambing',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
            ],
        );

        $batch = FatteningBatch::create([
            'livestock_type_id' => $kambing->id,
            'start_date' => '2026-05-31',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'status' => 'active',
        ]);

        $this->assertSame($kambing->id, $sheep->livestock_type_id);
        $this->assertSame('KMB-2026-0001', $sheep->tag_number);
    }
}
