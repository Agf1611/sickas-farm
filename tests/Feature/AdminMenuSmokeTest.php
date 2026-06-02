<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SickasFarmPermissions;
use Database\Seeders\SickasFarmRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_main_admin_menus_without_server_errors(): void
    {
        $this->seed(SickasFarmRoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(SickasFarmPermissions::SUPER_ADMIN);

        $paths = [
            '/admin',
            '/admin/business-profiles',
            '/admin/livestock-types',
            '/admin/livestock-market-prices',
            '/admin/pens',
            '/admin/suppliers',
            '/admin/customers',
            '/admin/expense-categories',
            '/admin/sheep-purchases',
            '/admin/sheep-batches',
            '/admin/sheep',
            '/admin/weight-records',
            '/admin/mortality-records',
            '/admin/expenses',
            '/admin/sales',
            '/admin/sale-proposals',
            '/admin/stock-movements',
            '/admin/growth-monitoring',
            '/admin/batch-profit-loss-report',
            '/admin/fattening-performance-report',
            '/admin/sheep-population-report',
            '/admin/sheep-unit-financial-report',
            '/admin/users',
        ];

        foreach ($paths as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }
}
