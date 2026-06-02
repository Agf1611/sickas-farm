<?php

namespace Database\Seeders;

use App\Models\LivestockType;
use Illuminate\Database\Seeder;

class LivestockTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->types() as $type) {
            LivestockType::query()->firstOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function types(): array
    {
        return [
            [
                'name' => 'Domba',
                'code' => 'DMB',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Kambing',
                'code' => 'KMB',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Sapi',
                'code' => 'SPI',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 300,
                'is_active' => true,
            ],
            [
                'name' => 'Kerbau',
                'code' => 'KRB',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 300,
                'is_active' => true,
            ],
            [
                'name' => 'Ayam',
                'code' => 'AYM',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => false,
                'default_sale_target_weight' => null,
                'is_active' => true,
            ],
        ];
    }
}
