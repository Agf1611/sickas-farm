<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoUploadPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_paths_are_stored_as_arrays_on_documented_records(): void
    {
        $pen = Pen::create([
            'code' => 'KDG-FOTO',
            'name' => 'Kandang Foto',
            'condition_photo_paths' => ['sickas-farm/kandang/kandang-1.jpg'],
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-FOTO',
            'name' => 'Supplier Foto',
        ]);

        $purchase = SheepPurchase::create([
            'purchase_date' => '2026-06-01',
            'supplier_id' => $supplier->id,
            'pen_id' => $pen->id,
            'purchase_type' => 'bulk',
            'head_count' => 5,
            'total_weight_kg' => 125,
            'total_purchase_price' => 10000000,
            'transport_cost' => 250000,
            'other_cost' => 0,
            'proof_photo_paths' => ['sickas-farm/bukti-pembelian/nota-beli.webp'],
        ]);

        $sheep = Sheep::create([
            'fattening_batch_id' => $purchase->fattening_batch_id,
            'pen_id' => $pen->id,
            'status' => 'active',
            'photo_paths' => ['sickas-farm/domba/domba-1.png'],
        ]);

        $category = ExpenseCategory::create([
            'code' => 'PAKAN-FOTO',
            'name' => 'Pakan Foto',
        ]);

        $expense = Expense::create([
            'expense_date' => '2026-06-02',
            'expense_category_id' => $category->id,
            'fattening_batch_id' => $purchase->fattening_batch_id,
            'pen_id' => $pen->id,
            'description' => 'Pakan tambahan',
            'amount' => 500000,
            'receipt_photo_paths' => ['sickas-farm/nota-pengeluaran/nota-pakan.jpg'],
        ]);

        $sale = Sale::create([
            'fattening_batch_id' => $purchase->fattening_batch_id,
            'sale_date' => '2026-06-10',
            'sale_type' => 'bulk',
            'head_count' => 1,
            'total_amount' => 3000000,
            'proof_photo_paths' => ['sickas-farm/bukti-penjualan/invoice.jpg'],
        ]);

        $incident = SheepIncidentRecord::create([
            'incident_date' => '2026-06-11',
            'incident_type' => 'sick',
            'fattening_batch_id' => $purchase->fattening_batch_id,
            'sheep_id' => $sheep->id,
            'head_count' => 1,
            'photo_paths' => ['sickas-farm/kematian-afkir/kondisi.jpg'],
        ]);

        $this->assertSame(['sickas-farm/kandang/kandang-1.jpg'], $pen->refresh()->condition_photo_paths);
        $this->assertSame(['sickas-farm/bukti-pembelian/nota-beli.webp'], $purchase->refresh()->proof_photo_paths);
        $this->assertSame(['sickas-farm/domba/domba-1.png'], $sheep->refresh()->photo_paths);
        $this->assertSame(['sickas-farm/nota-pengeluaran/nota-pakan.jpg'], $expense->refresh()->receipt_photo_paths);
        $this->assertSame(['sickas-farm/bukti-penjualan/invoice.jpg'], $sale->refresh()->proof_photo_paths);
        $this->assertSame(['sickas-farm/kematian-afkir/kondisi.jpg'], $incident->refresh()->photo_paths);
    }
}
