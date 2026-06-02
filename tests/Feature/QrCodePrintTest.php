<?php

namespace Tests\Feature;

use App\Filament\Resources\Sheep\SheepResource;
use App\Filament\Resources\SheepBatches\SheepBatchResource;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\Sheep;
use App\Models\User;
use App\Services\QrCodeService;
use App\Support\SickasFarmPermissions;
use Database\Seeders\SickasFarmRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_and_sheep_qr_codes_point_to_admin_detail_pages(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-QR',
            'name' => 'Kandang QR',
        ]);

        $batch = FatteningBatch::create([
            'batch_code' => 'LOT-2026-QR1',
            'pen_id' => $pen->id,
            'start_date' => '2026-06-01',
            'initial_head_count' => 10,
            'current_head_count' => 10,
            'purchase_capital' => 20000000,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'tag_number' => 'DMB-2026-QR01',
            'fattening_batch_id' => $batch->id,
            'pen_id' => $pen->id,
            'status' => 'active',
        ]);

        $service = app(QrCodeService::class);

        $this->assertSame(SheepBatchResource::getUrl('view', ['record' => $batch]), $service->batchDetailUrl($batch));
        $this->assertSame(SheepResource::getUrl('view', ['record' => $sheep]), $service->sheepDetailUrl($sheep));
        $this->assertStringContainsString('<svg', $service->svgForUrl($service->batchDetailUrl($batch)));
    }

    public function test_print_pages_render_qr_cards_for_authorized_admin_user(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(SickasFarmPermissions::SUPER_ADMIN);

        $batch = FatteningBatch::create([
            'batch_code' => 'LOT-2026-QR2',
            'start_date' => '2026-06-01',
            'initial_head_count' => 8,
            'current_head_count' => 8,
            'purchase_capital' => 16000000,
            'status' => 'active',
        ]);

        $sheep = Sheep::create([
            'tag_number' => 'DMB-2026-QR02',
            'fattening_batch_id' => $batch->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $this->get(route('sickas-farm.qr.batch.print', $batch))
            ->assertOk()
            ->assertSee('QR Code Batch Penggemukan')
            ->assertSee('LOT-2026-QR2')
            ->assertSee('Cetak QR');

        $this->get(route('sickas-farm.qr.sheep.print', $sheep))
            ->assertOk()
            ->assertSee('QR Code Ternak')
            ->assertSee('DMB-2026-QR02')
            ->assertSee('Cetak QR');
    }
}
