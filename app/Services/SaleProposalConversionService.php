<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleProposalConversionService
{
    public function approve(SaleProposal $proposal, ?User $user = null): SaleProposal
    {
        if ($proposal->status === 'converted_to_sale') {
            $this->fail('Ajuan ini sudah menjadi transaksi penjualan.');
        }

        $proposal->forceFill([
            'status' => 'approved',
            'approved_by' => $user?->id ?: $proposal->approved_by,
            'approved_at' => now(),
        ])->save();

        return $proposal->fresh();
    }

    /**
     * @param  array{sale_date?: string|null, buyer_id?: int|string|null, notes?: string|null}  $data
     */
    public function convertToSale(SaleProposal $proposal, array $data = []): Sale
    {
        $proposal->loadMissing(['items.sheep', 'fatteningBatch']);

        if ($proposal->status !== 'approved') {
            $this->fail('Ajuan harus berstatus Disetujui sebelum dibuat menjadi penjualan.');
        }

        if ($proposal->sale_id || $proposal->status === 'converted_to_sale') {
            $this->fail('Ajuan ini sudah pernah dibuat menjadi penjualan.');
        }

        return DB::transaction(function () use ($proposal, $data): Sale {
            $sale = Sale::create([
                'buyer_id' => $data['buyer_id'] ?? null,
                'fattening_batch_id' => $proposal->fattening_batch_id,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'sale_type' => $this->saleType($proposal),
                'head_count' => $this->headCount($proposal),
                'total_weight_kg' => $this->totalWeight($proposal),
                'unit_price' => $proposal->estimated_unit_price,
                'total_amount' => $this->totalAmount($proposal),
                'notes' => $this->saleNotes($proposal, $data['notes'] ?? null),
            ]);

            if ($proposal->proposal_type === 'selected_livestock') {
                foreach ($proposal->items as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'sheep_id' => $item->sheep_id,
                        'weight_kg' => $item->latest_weight_kg,
                        'price' => $item->estimated_price,
                        'notes' => $item->notes,
                    ]);
                }
            }

            $proposal->forceFill([
                'status' => 'converted_to_sale',
                'sale_id' => $sale->id,
            ])->save();

            return $sale->fresh(['saleItems']);
        });
    }

    private function saleType(SaleProposal $proposal): string
    {
        if ($proposal->proposal_type === 'selected_livestock') {
            return 'per_head';
        }

        return $proposal->livestockMarketPrice?->price_type === 'per_kg' ? 'per_kg' : 'bulk';
    }

    private function headCount(SaleProposal $proposal): int
    {
        if ($proposal->proposal_type === 'selected_livestock') {
            $count = $proposal->items->count();

            if ($count < 1) {
                $this->fail('Ajuan ternak terpilih wajib memiliki minimal satu ternak.');
            }

            return $count;
        }

        return max(1, (int) $proposal->head_count);
    }

    private function totalAmount(SaleProposal $proposal): float
    {
        if ($proposal->proposal_type === 'selected_livestock' && $proposal->items->isNotEmpty()) {
            return round((float) $proposal->items->sum('estimated_price'), 2);
        }

        return (float) $proposal->estimated_total_amount;
    }

    private function totalWeight(SaleProposal $proposal): ?float
    {
        if ($proposal->proposal_type === 'selected_livestock' && $proposal->items->isNotEmpty()) {
            $weight = $proposal->items
                ->filter(fn ($item): bool => $item->latest_weight_kg !== null)
                ->sum('latest_weight_kg');

            return $weight > 0 ? round((float) $weight, 2) : null;
        }

        return $proposal->estimated_total_weight_kg !== null ? (float) $proposal->estimated_total_weight_kg : null;
    }

    private function saleNotes(SaleProposal $proposal, ?string $extraNotes): string
    {
        $parts = collect([
            "Dibuat dari ajuan penjualan {$proposal->proposal_number}.",
            $proposal->notes,
            $extraNotes,
        ])->filter(fn (?string $value): bool => filled($value));

        return $parts->implode("\n");
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages([
            'sale_proposal' => $message,
        ]);
    }
}
