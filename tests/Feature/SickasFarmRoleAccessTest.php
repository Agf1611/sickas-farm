<?php

namespace Tests\Feature;

use App\Filament\Pages\BatchProfitLossReport;
use App\Filament\Pages\FatteningPerformanceReport;
use App\Filament\Pages\GrowthMonitoring;
use App\Filament\Pages\SheepUnitFinancialReport;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SheepIncidentRecord;
use App\Models\User;
use App\Models\WeighingRecord;
use App\Support\SickasFarmPermissions;
use Database\Seeders\SickasFarmRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SickasFarmRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_creates_roles_and_keeps_existing_admin(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@sickas.local',
        ]);

        $this->seed(SickasFarmRoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => SickasFarmPermissions::SUPER_ADMIN]);
        $this->assertDatabaseHas('roles', ['name' => SickasFarmPermissions::PETUGAS_KANDANG]);
        $this->assertTrue($admin->fresh()->hasRole(SickasFarmPermissions::SUPER_ADMIN));
        $this->assertTrue($admin->fresh()->can('viewAny', User::class));
        $this->assertTrue($admin->fresh()->can('create', User::class));
    }

    public function test_bendahara_can_manage_financial_records_but_cannot_delete(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(SickasFarmPermissions::BENDAHARA);

        $this->assertTrue($user->can('viewAny', Expense::class));
        $this->assertTrue($user->can('create', Expense::class));
        $this->assertTrue($user->can('update', Expense::class));
        $this->assertFalse($user->can('delete', Expense::class));
        $this->assertTrue($user->can('viewAny', Sale::class));
        $this->assertTrue($user->can('create', Sale::class));
        $this->assertFalse($user->can('delete', Sale::class));

        $this->actingAs($user);
        $this->assertTrue(SheepUnitFinancialReport::canAccess());
        $this->assertTrue(BatchProfitLossReport::canAccess());
        $this->assertFalse(FatteningPerformanceReport::canAccess());
    }

    public function test_petugas_kandang_can_input_operational_records_without_financial_report_access(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(SickasFarmPermissions::PETUGAS_KANDANG);

        $this->assertTrue($user->can('viewAny', WeighingRecord::class));
        $this->assertTrue($user->can('create', WeighingRecord::class));
        $this->assertTrue($user->can('viewAny', SheepIncidentRecord::class));
        $this->assertTrue($user->can('create', SheepIncidentRecord::class));
        $this->assertFalse($user->can('delete', WeighingRecord::class));
        $this->assertFalse($user->can('viewAny', Expense::class));
        $this->assertFalse($user->can('viewAny', Sale::class));

        $this->actingAs($user);
        $this->assertFalse(SheepUnitFinancialReport::canAccess());
        $this->assertFalse(FatteningPerformanceReport::canAccess());
        $this->assertTrue(GrowthMonitoring::canAccess());
    }

    public function test_pengawas_can_only_access_reports_without_mutating_data(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(SickasFarmPermissions::PENGAWAS);

        $this->assertFalse($user->can('viewAny', Expense::class));
        $this->assertFalse($user->can('create', Expense::class));
        $this->assertFalse($user->can('viewAny', Sale::class));

        $this->actingAs($user);
        $this->assertTrue(SheepUnitFinancialReport::canAccess());
        $this->assertTrue(BatchProfitLossReport::canAccess());
        $this->assertTrue(FatteningPerformanceReport::canAccess());
    }
}
