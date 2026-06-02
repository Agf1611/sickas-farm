<?php

namespace Tests\Feature;

use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Models\WeighingRecord;
use App\Services\GrowthMonitoringService;
use App\Services\IndividualWeighingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndividualWeighingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_weighing_updates_sheep_weights_and_creates_batch_summary(): void
    {
        $batch = FatteningBatch::create([
            'start_date' => '2026-05-01',
            'initial_head_count' => 2,
            'current_head_count' => 2,
            'initial_total_weight_kg' => 40,
            'status' => 'active',
        ]);

        $firstSheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 20,
            'status' => 'active',
        ]);

        $secondSheep = Sheep::create([
            'fattening_batch_id' => $batch->id,
            'initial_weight_kg' => 20,
            'status' => 'active',
        ]);

        $summary = app(IndividualWeighingService::class)->record($batch, '2026-05-11', [
            ['sheep_id' => $firstSheep->id, 'weight_kg' => 22, 'notes' => 'Naik bagus'],
            ['sheep_id' => $secondSheep->id, 'weight_kg' => 19.5, 'notes' => 'Turun'],
        ]);

        $this->assertSame(2, $summary['qty']);
        $this->assertSame(41.5, $summary['total_weight_kg']);
        $this->assertSame(22.0, $firstSheep->fresh()->current_weight_kg);
        $this->assertSame(19.5, $secondSheep->fresh()->current_weight_kg);

        $this->assertSame(2, WeighingRecord::query()->where('weight_type', 'per_ekor')->count());

        $batchSummary = WeighingRecord::query()
            ->where('weight_type', 'batch')
            ->where('source', 'actual_individual')
            ->first();

        $this->assertNotNull($batchSummary);
        $this->assertSame('41.50', $batchSummary->total_weight_kg);
        $this->assertSame('20.75', $batchSummary->average_weight_kg);

        $growth = app(GrowthMonitoringService::class)->calculateSheepGrowth($secondSheep->fresh());

        $this->assertSame('Turun', $growth['status']);
        $this->assertSame(19.5, $growth['latest_weight']);
    }
}
