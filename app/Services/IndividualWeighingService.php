<?php

namespace App\Services;

use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Models\WeighingRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IndividualWeighingService
{
    /**
     * @param  array<int, array{sheep_id: int, weight_kg: mixed, notes?: ?string}>  $items
     */
    public function record(FatteningBatch $batch, string $weighedAt, array $items, ?string $notes = null): array
    {
        if ($batch->start_date && $weighedAt < $batch->start_date->toDateString()) {
            throw ValidationException::withMessages([
                'weighed_at' => 'Tanggal timbang tidak boleh sebelum tanggal mulai pembelian/batch.',
            ]);
        }

        $validItems = collect($items)
            ->filter(fn (array $item): bool => filled($item['weight_kg'] ?? null))
            ->map(function (array $item): array {
                $weight = (float) $item['weight_kg'];

                if ($weight < 0) {
                    throw ValidationException::withMessages([
                        'weights' => 'Berat ternak tidak boleh minus.',
                    ]);
                }

                return [
                    'sheep_id' => (int) $item['sheep_id'],
                    'weight_kg' => $weight,
                    'notes' => $item['notes'] ?? null,
                ];
            })
            ->values();

        if ($validItems->isEmpty()) {
            throw ValidationException::withMessages([
                'weights' => 'Isi minimal satu berat ternak.',
            ]);
        }

        $activeSheepIds = Sheep::query()
            ->where('fattening_batch_id', $batch->id)
            ->where('status', 'active')
            ->whereIn('id', $validItems->pluck('sheep_id'))
            ->pluck('id')
            ->all();

        if (count($activeSheepIds) !== $validItems->count()) {
            throw ValidationException::withMessages([
                'weights' => 'Hanya ternak aktif dalam batch ini yang bisa ditimbang per ekor.',
            ]);
        }

        return DB::transaction(function () use ($batch, $weighedAt, $validItems, $notes): array {
            $totalWeight = 0.0;

            foreach ($validItems as $item) {
                WeighingRecord::create([
                    'weighed_at' => $weighedAt,
                    'weight_type' => 'per_ekor',
                    'source' => 'actual_individual',
                    'fattening_batch_id' => $batch->id,
                    'sheep_id' => $item['sheep_id'],
                    'weight_kg' => $item['weight_kg'],
                    'notes' => $item['notes'],
                ]);

                $totalWeight += $item['weight_kg'];
            }

            $qty = $validItems->count();

            WeighingRecord::create([
                'weighed_at' => $weighedAt,
                'weight_type' => 'batch',
                'source' => 'actual_individual',
                'fattening_batch_id' => $batch->id,
                'qty' => $qty,
                'head_count' => $qty,
                'total_weight_kg' => $totalWeight,
                'average_weight_kg' => round($totalWeight / $qty, 2),
                'notes' => $notes ?: 'Ringkasan otomatis dari timbang per ekor.',
            ]);

            return [
                'qty' => $qty,
                'total_weight_kg' => $totalWeight,
                'average_weight_kg' => round($totalWeight / $qty, 2),
            ];
        });
    }
}
