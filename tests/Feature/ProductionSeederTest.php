<?php

namespace Tests\Feature;

use App\Models\LivestockType;
use App\Models\User;
use App\Support\SickasFarmPermissions;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeder_creates_roles_and_initial_admin_without_duplicates(): void
    {
        config()->set('sickas.admin.name', 'Admin Produksi');
        config()->set('sickas.admin.email', 'admin@bumdes.test');
        config()->set('sickas.admin.password', 'PasswordProduksiSementara123!');

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $admin = User::query()->where('email', 'admin@bumdes.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame(1, User::query()->where('email', 'admin@bumdes.test')->count());
        $this->assertTrue($admin->hasRole(SickasFarmPermissions::SUPER_ADMIN));

        foreach (array_keys(SickasFarmPermissions::rolePermissions()) as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        $this->assertSame(5, LivestockType::query()->count());
        $this->assertDatabaseHas('livestock_types', [
            'name' => 'Domba',
            'code' => 'DMB',
            'default_sale_target_weight' => 30,
        ]);
    }
}
