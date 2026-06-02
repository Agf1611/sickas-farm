<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Sale;
use App\Models\SheepPurchase;
use App\Support\SickasFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfService
{
    public function previewPurchase(SheepPurchase $purchase): View
    {
        return view('invoices.sickas-invoice-preview', $this->purchaseData($purchase));
    }

    public function downloadPurchase(SheepPurchase $purchase): Response
    {
        $data = $this->purchaseData($purchase);

        return Pdf::loadView('invoices.sickas-invoice-pdf', $data)
            ->setPaper('a4')
            ->download($this->filename('invoice-pembelian', $data['number']));
    }

    public function previewSale(Sale $sale): View
    {
        return view('invoices.sickas-invoice-preview', $this->saleData($sale));
    }

    public function downloadSale(Sale $sale): Response
    {
        $data = $this->saleData($sale);

        return Pdf::loadView('invoices.sickas-invoice-pdf', $data)
            ->setPaper('a4')
            ->download($this->filename('invoice-penjualan', $data['number']));
    }

    private function purchaseData(SheepPurchase $purchase): array
    {
        $purchase->loadMissing(['supplier', 'pen', 'fatteningBatch', 'livestockType']);

        return [
            'identity' => BusinessProfile::reportIdentity(),
            'type' => 'purchase',
            'title' => 'Invoice Pembelian Ternak',
            'number' => $purchase->purchase_number ?: 'INV-BELI-'.$purchase->id,
            'pdf_url' => route('sickas-farm.invoices.purchase.pdf', $purchase),
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
        ];
    }

    private function saleData(Sale $sale): array
    {
        $sale->loadMissing([
            'buyer',
            'fatteningBatch.livestockType',
            'fatteningBatch.pen',
            'saleItems.sheep.livestockType',
            'saleItems.sheep.fatteningBatch',
            'saleItems.sheep.pen',
        ]);

        $rows = $sale->saleItems->isNotEmpty()
            ? $sale->saleItems->map(fn ($item): array => [
                'description' => $item->sheep?->tag_number ?: 'Ternak',
                'livestock_code' => $item->sheep?->tag_number ?: '-',
                'livestock_type' => $item->sheep?->livestockType?->name ?? '-',
                'batch_code' => $item->sheep?->fatteningBatch?->batch_code ?? '-',
                'pen_name' => $item->sheep?->pen?->name ?? '-',
                'detail' => collect([
                    $item->sheep?->livestockType?->name,
                    $item->sheep?->fatteningBatch?->batch_code,
                    $item->sheep?->pen?->name,
                    filled($item->notes) ? 'Catatan: '.$item->notes : null,
                ])->filter()->implode(' | '),
                'qty' => '1 ekor',
                'weight' => SickasFormatter::kg($item->weight_kg),
                'unit_price' => SickasFormatter::rupiah($item->price),
                'amount' => SickasFormatter::rupiah($item->price),
            ])->all()
            : [[
                'description' => 'Penjualan ternak '.$this->saleTypeLabel($sale->sale_type),
                'livestock_code' => 'Borongan',
                'livestock_type' => $sale->fatteningBatch?->livestockType?->name ?? '-',
                'batch_code' => $sale->fatteningBatch?->batch_code ?? '-',
                'pen_name' => $sale->fatteningBatch?->pen?->name ?? '-',
                'detail' => $sale->fatteningBatch?->batch_code ? 'Batch '.$sale->fatteningBatch->batch_code : null,
                'qty' => SickasFormatter::number($sale->head_count).' ekor',
                'weight' => SickasFormatter::kg($sale->total_weight_kg),
                'unit_price' => SickasFormatter::rupiah($sale->unit_price),
                'amount' => SickasFormatter::rupiah($sale->total_amount),
            ]];

        if ($sale->saleItems->isNotEmpty()) {
            $detailedHeadCount = $sale->saleItems->count();
            $remainingHeadCount = max(0, (int) $sale->head_count - $detailedHeadCount);
            $remainingAmount = max(0, (float) $sale->total_amount - (float) $sale->saleItems->sum('price'));

            if ($remainingHeadCount > 0 || $remainingAmount > 0) {
                $rows[] = [
                    'description' => 'Sisa ternak belum dirinci',
                    'livestock_code' => 'Belum dirinci',
                    'livestock_type' => $sale->fatteningBatch?->livestockType?->name ?? '-',
                    'batch_code' => $sale->fatteningBatch?->batch_code ?? '-',
                    'pen_name' => $sale->fatteningBatch?->pen?->name ?? '-',
                    'detail' => 'Lengkapi detail ternak terjual agar invoice mencatat semua ekor secara spesifik.',
                    'qty' => SickasFormatter::number($remainingHeadCount).' ekor',
                    'weight' => '-',
                    'unit_price' => $remainingHeadCount > 0
                        ? SickasFormatter::rupiah($remainingAmount / $remainingHeadCount)
                        : '-',
                    'amount' => SickasFormatter::rupiah($remainingAmount),
                    'is_warning' => true,
                ];
            }
        }

        $detailTotal = $sale->saleItems->isNotEmpty()
            ? (float) $sale->saleItems->sum('price')
            : (float) $sale->total_amount;

        return [
            'identity' => BusinessProfile::reportIdentity(),
            'type' => 'sale',
            'title' => 'Invoice Penjualan Ternak',
            'number' => $sale->sale_number,
            'pdf_url' => route('sickas-farm.invoices.sale.pdf', $sale),
            'date' => SickasFormatter::date($sale->sale_date),
            'party_label' => 'Pembeli',
            'party_name' => $sale->buyer?->name ?? '-',
            'meta' => [
                'Batch' => $sale->fatteningBatch?->batch_code ?? '-',
                'Jenis Penjualan' => $this->saleTypeLabel($sale->sale_type),
                'Harga Satuan' => $sale->unit_price !== null
                    ? SickasFormatter::rupiah($sale->unit_price)
                    : ($sale->saleItems->isNotEmpty() ? 'Lihat detail per ternak' : '-'),
            ],
            'rows' => $rows,
            'detail_summary' => $sale->saleItems->isNotEmpty() ? [
                'head_count' => (int) $sale->head_count,
                'detailed_head_count' => $sale->saleItems->count(),
                'undetailed_head_count' => max(0, (int) $sale->head_count - $sale->saleItems->count()),
                'detail_total' => SickasFormatter::rupiah($detailTotal),
                'header_total' => SickasFormatter::rupiah($sale->total_amount),
                'difference' => SickasFormatter::rupiah(max(0, (float) $sale->total_amount - $detailTotal)),
                'is_complete' => $sale->saleItems->count() >= (int) $sale->head_count
                    && abs((float) $sale->total_amount - $detailTotal) < 1,
            ] : null,
            'total_label' => 'Total Penjualan',
            'total' => SickasFormatter::rupiah($sale->total_amount),
            'notes' => $sale->notes,
        ];
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
