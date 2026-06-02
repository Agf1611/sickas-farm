<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FatteningBatch;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\Supplier;
use App\Models\WeighingRecord;
use Illuminate\Database\Seeder;

class SickasFarmDemoSeeder extends Seeder
{
    /**
     * Seed demo data for checking SICKAS FARM workflows.
     */
    public function run(): void
    {
        $this->clearExistingDemoData();

        $pens = $this->seedPens();
        $suppliers = $this->seedSuppliers();
        $buyers = $this->seedBuyers();
        $categories = $this->seedExpenseCategories();
        $batches = $this->seedBatches($pens, $suppliers);

        $this->seedPurchases($batches, $pens, $suppliers);
        $batches = $this->seedBatches($pens, $suppliers);
        $sheep = $this->seedSheep($batches, $pens);
        $this->seedWeighingRecords($batches, $sheep);
        $this->seedExpenses($batches, $pens, $categories);
        $this->seedIncidents($batches, $sheep);
        $this->seedSales($batches, $buyers, $sheep);
    }

    private function clearExistingDemoData(): void
    {
        $batchIds = FatteningBatch::query()
            ->whereIn('batch_code', ['LOT-2026-901', 'LOT-2026-902', 'LOT-2026-903', 'LOT-2026-904'])
            ->pluck('id');

        $sheepIds = Sheep::query()
            ->whereIn('tag_number', [
                'DMB-2026-9001',
                'DMB-2026-9002',
                'DMB-2026-9003',
                'DMB-2026-9004',
                'DMB-2026-9005',
                'DMB-2026-9006',
            ])
            ->pluck('id');

        $saleIds = Sale::query()
            ->whereIn('sale_number', ['INV-JUAL-2026-901', 'INV-JUAL-2026-902'])
            ->pluck('id');

        SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->orWhereIn('sheep_id', $sheepIds)
            ->delete();

        Sale::query()->whereIn('id', $saleIds)->delete();

        SheepIncidentRecord::query()
            ->whereIn('fattening_batch_id', $batchIds)
            ->orWhereIn('sheep_id', $sheepIds)
            ->delete();

        WeighingRecord::query()
            ->whereIn('fattening_batch_id', $batchIds)
            ->orWhereIn('sheep_id', $sheepIds)
            ->delete();

        Expense::query()->whereIn('fattening_batch_id', $batchIds)->delete();
        SheepPurchase::query()->whereIn('fattening_batch_id', $batchIds)->delete();
        Sheep::query()->whereIn('id', $sheepIds)->delete();
        FatteningBatch::query()->whereIn('id', $batchIds)->delete();
    }

    private function seedPens(): array
    {
        return [
            'A' => Pen::query()->updateOrCreate(
                ['code' => 'KDG-DEMO-A'],
                [
                    'name' => 'Kandang Demo A',
                    'capacity' => 40,
                    'location' => 'Blok Utara',
                    'description' => 'Kandang contoh untuk batch pertumbuhan bagus.',
                    'is_active' => true,
                ],
            ),
            'B' => Pen::query()->updateOrCreate(
                ['code' => 'KDG-DEMO-B'],
                [
                    'name' => 'Kandang Demo B',
                    'capacity' => 35,
                    'location' => 'Blok Timur',
                    'description' => 'Kandang contoh untuk batch perlu timbang ulang.',
                    'is_active' => true,
                ],
            ),
            'C' => Pen::query()->updateOrCreate(
                ['code' => 'KDG-DEMO-C'],
                [
                    'name' => 'Kandang Demo C',
                    'capacity' => 25,
                    'location' => 'Blok Selatan',
                    'description' => 'Kandang contoh untuk batch belum ditimbang.',
                    'is_active' => true,
                ],
            ),
        ];
    }

    private function seedSuppliers(): array
    {
        return [
            'maju' => Supplier::query()->updateOrCreate(
                ['code' => 'SUP-DEMO-01'],
                [
                    'name' => 'CV Ternak Maju Demo',
                    'phone' => '0812-0000-9001',
                    'address' => 'Pasar Hewan Demo',
                    'notes' => 'Supplier dummy untuk pembelian bibit.',
                    'is_active' => true,
                ],
            ),
            'barokah' => Supplier::query()->updateOrCreate(
                ['code' => 'SUP-DEMO-02'],
                [
                    'name' => 'Barokah Domba Demo',
                    'phone' => '0812-0000-9002',
                    'address' => 'Desa Ketapang Demo',
                    'notes' => 'Supplier dummy kedua.',
                    'is_active' => true,
                ],
            ),
        ];
    }

    private function seedBuyers(): array
    {
        return [
            'aqiqah' => Buyer::query()->updateOrCreate(
                ['code' => 'BYR-DEMO-01'],
                [
                    'name' => 'Aqiqah Sejahtera Demo',
                    'phone' => '0813-0000-9001',
                    'address' => 'Kota Demo',
                    'notes' => 'Pembeli dummy untuk cek penjualan.',
                    'is_active' => true,
                ],
            ),
            'pedagang' => Buyer::query()->updateOrCreate(
                ['code' => 'BYR-DEMO-02'],
                [
                    'name' => 'Pedagang Domba Demo',
                    'phone' => '0813-0000-9002',
                    'address' => 'Pasar Demo',
                    'notes' => 'Pembeli dummy borongan.',
                    'is_active' => true,
                ],
            ),
        ];
    }

    private function seedExpenseCategories(): array
    {
        return [
            'feed' => ExpenseCategory::query()->updateOrCreate(
                ['code' => 'PAKAN-DEMO'],
                [
                    'name' => 'Pakan Demo',
                    'description' => 'Pakan hijauan dan konsentrat.',
                    'is_active' => true,
                ],
            ),
            'medicine' => ExpenseCategory::query()->updateOrCreate(
                ['code' => 'OBAT-DEMO'],
                [
                    'name' => 'Obat / Vitamin Demo',
                    'description' => 'Obat, vitamin, dan perawatan kesehatan.',
                    'is_active' => true,
                ],
            ),
            'wage' => ExpenseCategory::query()->updateOrCreate(
                ['code' => 'UPAH-DEMO'],
                [
                    'name' => 'Upah Pengurus Demo',
                    'description' => 'Upah tenaga kandang.',
                    'is_active' => true,
                ],
            ),
        ];
    }

    private function seedBatches(array $pens, array $suppliers): array
    {
        return [
            'good' => FatteningBatch::query()->updateOrCreate(
                ['batch_code' => 'LOT-2026-901'],
                [
                    'pen_id' => $pens['A']->id,
                    'supplier_id' => $suppliers['maju']->id,
                    'start_date' => '2026-05-01',
                    'end_date' => null,
                    'initial_head_count' => 30,
                    'current_head_count' => 30,
                    'initial_total_weight_kg' => 750,
                    'target_sale_average_weight_kg' => 30,
                    'purchase_capital' => 57000000,
                    'status' => 'active',
                    'notes' => 'Demo: pertumbuhan bagus dan timbang masih baru.',
                ],
            ),
            'reweigh' => FatteningBatch::query()->updateOrCreate(
                ['batch_code' => 'LOT-2026-902'],
                [
                    'pen_id' => $pens['B']->id,
                    'supplier_id' => $suppliers['barokah']->id,
                    'start_date' => '2026-04-28',
                    'end_date' => null,
                    'initial_head_count' => 25,
                    'current_head_count' => 24,
                    'initial_total_weight_kg' => 600,
                    'target_sale_average_weight_kg' => 30,
                    'purchase_capital' => 45500000,
                    'status' => 'active',
                    'notes' => 'Demo: timbang terakhir lebih dari 14 hari.',
                ],
            ),
            'never' => FatteningBatch::query()->updateOrCreate(
                ['batch_code' => 'LOT-2026-903'],
                [
                    'pen_id' => $pens['C']->id,
                    'supplier_id' => $suppliers['maju']->id,
                    'start_date' => '2026-05-20',
                    'end_date' => null,
                    'initial_head_count' => 18,
                    'current_head_count' => 18,
                    'initial_total_weight_kg' => 405,
                    'target_sale_average_weight_kg' => 28,
                    'purchase_capital' => 31500000,
                    'status' => 'active',
                    'notes' => 'Demo: belum ada data timbang batch.',
                ],
            ),
            'sold' => FatteningBatch::query()->updateOrCreate(
                ['batch_code' => 'LOT-2026-904'],
                [
                    'pen_id' => $pens['A']->id,
                    'supplier_id' => $suppliers['barokah']->id,
                    'start_date' => '2026-03-01',
                    'end_date' => '2026-05-25',
                    'initial_head_count' => 20,
                    'current_head_count' => 0,
                    'initial_total_weight_kg' => 460,
                    'target_sale_average_weight_kg' => 28,
                    'purchase_capital' => 36000000,
                    'status' => 'closed',
                    'notes' => 'Demo: batch sudah dijual untuk cek laporan laba rugi.',
                ],
            ),
        ];
    }

    private function seedPurchases(array $batches, array $pens, array $suppliers): void
    {
        $purchaseData = [
            ['good', '2026-05-01', 'maju', 'A', 30, 750, 54000000, 2000000, 1000000],
            ['reweigh', '2026-04-28', 'barokah', 'B', 25, 600, 43000000, 1500000, 1000000],
            ['never', '2026-05-20', 'maju', 'C', 18, 405, 30000000, 1000000, 500000],
            ['sold', '2026-03-01', 'barokah', 'A', 20, 460, 34000000, 1500000, 500000],
        ];

        foreach ($purchaseData as [$key, $date, $supplierKey, $penKey, $headCount, $weight, $price, $transport, $other]) {
            SheepPurchase::query()->updateOrCreate(
                ['fattening_batch_id' => $batches[$key]->id],
                [
                    'purchase_date' => $date,
                    'supplier_id' => $suppliers[$supplierKey]->id,
                    'pen_id' => $pens[$penKey]->id,
                    'purchase_type' => 'bulk',
                    'head_count' => $headCount,
                    'total_weight_kg' => $weight,
                    'total_purchase_price' => $price,
                    'transport_cost' => $transport,
                    'other_cost' => $other,
                    'notes' => 'Pembelian borongan dummy untuk '.$batches[$key]->batch_code,
                ],
            );
        }
    }

    private function seedSheep(array $batches, array $pens): array
    {
        $items = [
            'active_recent' => ['DMB-2026-9001', 'good', 'A', 'male', 8, 25.00, 1900000, 'active'],
            'active_good' => ['DMB-2026-9002', 'good', 'A', 'male', 9, 24.50, 1850000, 'active'],
            'active_never' => ['DMB-2026-9003', 'never', 'C', 'female', 7, 22.50, 1750000, 'active'],
            'active_reweigh' => ['DMB-2026-9004', 'reweigh', 'B', 'male', 10, 24.00, 1800000, 'active'],
            'sick' => ['DMB-2026-9005', 'reweigh', 'B', 'female', 8, 23.00, 1700000, 'sick'],
            'sold' => ['DMB-2026-9006', 'sold', 'A', 'male', 11, 23.00, 1800000, 'sold'],
        ];

        $sheep = [];

        foreach ($items as $key => [$tag, $batchKey, $penKey, $sex, $age, $weight, $price, $status]) {
            $sheep[$key] = Sheep::query()->updateOrCreate(
                ['tag_number' => $tag],
                [
                    'fattening_batch_id' => $batches[$batchKey]->id,
                    'pen_id' => $pens[$penKey]->id,
                    'sex' => $sex,
                    'estimated_age_months' => $age,
                    'initial_weight_kg' => $weight,
                    'purchase_price' => $price,
                    'status' => $status,
                    'notes' => 'Domba dummy untuk cek monitoring per ekor.',
                ],
            );
        }

        return $sheep;
    }

    private function seedWeighingRecords(array $batches, array $sheep): void
    {
        $batchWeights = [
            ['2026-05-10', 'good', 30, 780],
            ['2026-05-28', 'good', 30, 875],
            ['2026-05-10', 'reweigh', 24, 624],
            ['2026-04-01', 'sold', 20, 500],
            ['2026-05-20', 'sold', 20, 560],
        ];

        foreach ($batchWeights as [$date, $batchKey, $headCount, $weight]) {
            WeighingRecord::query()->updateOrCreate(
                [
                    'weighed_at' => $date,
                    'record_type' => 'batch',
                    'fattening_batch_id' => $batches[$batchKey]->id,
                    'sheep_id' => null,
                ],
                [
                    'head_count' => $headCount,
                    'total_weight_kg' => $weight,
                    'weight_kg' => null,
                    'notes' => 'Timbang batch dummy.',
                ],
            );
        }

        $individualWeights = [
            ['2026-05-28', 'active_recent', 29.20],
            ['2026-05-28', 'active_good', 28.50],
            ['2026-05-10', 'active_reweigh', 24.80],
            ['2026-05-10', 'sick', 22.90],
            ['2026-05-20', 'sold', 28.00],
        ];

        foreach ($individualWeights as [$date, $sheepKey, $weight]) {
            WeighingRecord::query()->updateOrCreate(
                [
                    'weighed_at' => $date,
                    'record_type' => 'individual',
                    'sheep_id' => $sheep[$sheepKey]->id,
                ],
                [
                    'fattening_batch_id' => $sheep[$sheepKey]->fattening_batch_id,
                    'head_count' => null,
                    'total_weight_kg' => null,
                    'weight_kg' => $weight,
                    'notes' => 'Timbang per ekor dummy.',
                ],
            );
        }
    }

    private function seedExpenses(array $batches, array $pens, array $categories): void
    {
        $expenses = [
            ['2026-05-05', 'feed', 'good', 'A', 'Pakan konsentrat dan hijauan batch 901', 3500000],
            ['2026-05-15', 'medicine', 'good', 'A', 'Vitamin batch 901', 650000],
            ['2026-05-25', 'wage', 'good', 'A', 'Upah pengurus batch 901', 1800000],
            ['2026-05-02', 'feed', 'reweigh', 'B', 'Pakan batch 902', 2800000],
            ['2026-05-09', 'medicine', 'reweigh', 'B', 'Obat domba sakit batch 902', 900000],
            ['2026-05-21', 'feed', 'never', 'C', 'Pakan awal batch 903', 1200000],
            ['2026-04-10', 'feed', 'sold', 'A', 'Pakan batch 904', 4200000],
        ];

        foreach ($expenses as [$date, $categoryKey, $batchKey, $penKey, $description, $amount]) {
            Expense::query()->updateOrCreate(
                [
                    'expense_date' => $date,
                    'fattening_batch_id' => $batches[$batchKey]->id,
                    'description' => $description,
                ],
                [
                    'expense_category_id' => $categories[$categoryKey]->id,
                    'pen_id' => $pens[$penKey]->id,
                    'amount' => $amount,
                    'notes' => 'Biaya dummy.',
                ],
            );
        }
    }

    private function seedIncidents(array $batches, array $sheep): void
    {
        SheepIncidentRecord::query()->updateOrCreate(
            [
                'incident_date' => '2026-05-12',
                'fattening_batch_id' => $batches['reweigh']->id,
                'incident_type' => 'sick',
                'sheep_id' => $sheep['sick']->id,
            ],
            [
                'head_count' => 1,
                'reason' => 'Nafsu makan turun',
                'notes' => 'Dummy catatan kesehatan.',
            ],
        );

        SheepIncidentRecord::query()->updateOrCreate(
            [
                'incident_date' => '2026-05-15',
                'fattening_batch_id' => $batches['reweigh']->id,
                'incident_type' => 'dead',
                'sheep_id' => null,
            ],
            [
                'head_count' => 1,
                'reason' => 'Kematian contoh untuk dashboard',
                'notes' => 'Dummy catatan kematian.',
            ],
        );
    }

    private function seedSales(array $batches, array $buyers, array $sheep): void
    {
        $bulkSale = Sale::query()->updateOrCreate(
            ['sale_number' => 'INV-JUAL-2026-901'],
            [
                'buyer_id' => $buyers['pedagang']->id,
                'fattening_batch_id' => $batches['sold']->id,
                'sale_date' => '2026-05-25',
                'sale_type' => 'bulk',
                'head_count' => 20,
                'total_weight_kg' => 560,
                'unit_price' => null,
                'total_amount' => 56000000,
                'notes' => 'Penjualan borongan dummy batch selesai.',
            ],
        );

        SaleItem::query()->updateOrCreate(
            [
                'sale_id' => $bulkSale->id,
                'sheep_id' => $sheep['sold']->id,
            ],
            [
                'weight_kg' => 28,
                'price' => 2800000,
                'notes' => 'Contoh detail domba terjual.',
            ],
        );

        Sale::query()->updateOrCreate(
            ['sale_number' => 'INV-JUAL-2026-902'],
            [
                'buyer_id' => $buyers['aqiqah']->id,
                'fattening_batch_id' => $batches['good']->id,
                'sale_date' => '2026-05-29',
                'sale_type' => 'per_kg',
                'head_count' => 2,
                'total_weight_kg' => 60,
                'unit_price' => 105000,
                'total_amount' => 6300000,
                'notes' => 'Penjualan sebagian dummy untuk batch aktif.',
            ],
        );
    }
}
