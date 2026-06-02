<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Support\SickasFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfService
{
    public function downloadPurchase(SheepPurchase $purchase): Response
    {
        $purchase->loadMissing(['supplier', 'pen', 'fatteningBatch', 'livestockType']);

        return Pdf::loadView('invoices.sickas-invoice-pdf', [
            'identity' => BusinessProfile::reportIdentity(),
            'type' => 'purchase',
            'title' => 'Invoice Pembelian Ternak',
            'number' => $purchase->purchase_number ?: 'INV-BELI-'.$purchase->id,
            'date' => SickasFormatter::date($purchase->purchase_date),
            'party_label' => 'Supplier',
            'party_name' => $purchase->supplier?->name ?? '-',
            'meta' => [
                'Batch' => $purchase->fatteningBatch?->batch_code ?? '-',
                'Kandang' => $purchase->pen?->name ?? '-',
                'Jenis Ternak' => $purchase->livestockType?->name ?? 'Domba',
                'Tipe Pembelian' => $this->purchaseTypeLabel($purchase->purchase_type),
            ],
            'rows' => [
                [
                    'description' => 'Pembelian ternak '.$this->purchaseTypeLabel($purchase->purchase_type),
                    'qty' => SickasFormatter::number($purchase->head_count).' ekor',
                    'weight' => SickasFormatter::kg($purchase->total_weight_kg),
                    'amount' => SickasFormatter::rupiah($purchase->total_purchase_price),
                ],
                [
                    'description' => 'Biaya transport',
                    'qty' => '-',
                    'weight' => '-',
                    'amount' => SickasFormatter::rupiah($purchase->transport_cost),
                ],
                [
                    'description' => 'Biaya lain-lain',
                    'qty' => '-',
                    'weight' => '-',
                    'amount' => SickasFormatter::rupiah($purchase->other_cost),
                ],
            ],
            'total_label' => 'Total Modal Pembelian',
            'total' => SickasFormatter::rupiah($purchase->totalCapital()),
            'notes' => $purchase->notes,
        ])
            ->setPaper('a4')
            ->download($this->filename('invoice-pembelian', $purchase->purchase_number ?: (string) $purchase->id));
    }

    public function downloadSale(Sale $sale): Response
    {
        $sale->loadMissing(['buyer', 'fatteningBatch', 'saleItems.sheep']);

        $rows = $sale->saleItems->isNotEmpty()
            ? $sale->saleItems->map(fn ($item): array => [
                'description' => $item->sheep?->tag_number ?: 'Ternak',
                'qty' => '1 ekor',
                'weight' => SickasFormatter::kg($item->weight_kg),
                'amount' => SickasFormatter::rupiah($item->price),
            ])->all()
            : [[
                'description' => 'Penjualan ternak '.$this->saleTypeLabel($sale->sale_type),
                'qty' => SickasFormatter::number($sale->head_count).' ekor',
                'weight' => SickasFormatter::kg($sale->total_weight_kg),
                'amount' => SickasFormatter::rupiah($sale->total_amount),
            ]];

        return Pdf::loadView('invoices.sickas-invoice-pdf', [
            'identity' => BusinessProfile::reportIdentity(),
            'type' => 'sale',
            'title' => 'Invoice Penjualan Ternak',
            'number' => $sale->sale_number,
            'date' => SickasFormatter::date($sale->sale_date),
            'party_label' => 'Pembeli',
            'party_name' => $sale->buyer?->name ?? '-',
            'meta' => [
                'Batch' => $sale->fatteningBatch?->batch_code ?? '-',
                'Jenis Penjualan' => $this->saleTypeLabel($sale->sale_type),
                'Harga Satuan' => SickasFormatter::rupiah($sale->unit_price),
            ],
            'rows' => $rows,
            'total_label' => 'Total Penjualan',
            'total' => SickasFormatter::rupiah($sale->total_amount),
            'notes' => $sale->notes,
        ])
            ->setPaper('a4')
            ->download($this->filename('invoice-penjualan', $sale->sale_number));
    }

    private function filename(string $prefix, string $number): string
    {
        $safeNumber = str($number)->slug('-')->toString();

        return "{$prefix}-sickas-farm-{$safeNumber}.pdf";
    }

    private function purchaseTypeLabel(?string $type): string
    {
        return match ($type) {
            'per_head' => 'Per Ekor',
            'per_kg' => 'Per Kg',
            default => 'Borongan',
        };
    }

    private function saleTypeLabel(?string $type): string
    {
        return match ($type) {
            'per_head' => 'Per Ekor',
            'per_kg' => 'Per Kg',
            default => 'Borongan',
        };
    }
}
