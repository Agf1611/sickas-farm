<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class WeighingRecord extends Model
{
    protected $fillable = [
        'weighed_at',
        'record_type',
        'weight_type',
        'source',
        'fattening_batch_id',
        'sheep_id',
        'qty',
        'head_count',
        'total_weight_kg',
        'average_weight_kg',
        'weight_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weighed_at' => 'date',
            'total_weight_kg' => 'decimal:2',
            'average_weight_kg' => 'decimal:2',
            'weight_kg' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WeighingRecord $record): void {
            $record->syncLegacyAndNewFields();
            $record->validateWeighing();
        });

        static::saved(function (WeighingRecord $record): void {
            if ($record->isIndividualWeighing() && $record->sheep_id) {
                Sheep::query()
                    ->whereKey($record->sheep_id)
                    ->update(['current_weight_kg' => $record->weight_kg]);
            }
        });
    }

    public function syncLegacyAndNewFields(): void
    {
        if (blank($this->weight_type)) {
            $this->weight_type = $this->record_type === 'individual' ? 'per_ekor' : 'batch';
        }

        if (blank($this->record_type)) {
            $this->record_type = $this->weight_type === 'per_ekor' ? 'individual' : 'batch';
        }

        $this->record_type = $this->weight_type === 'per_ekor' ? 'individual' : 'batch';

        if ($this->qty !== null) {
            $this->head_count = $this->qty;
        }

        if ($this->head_count !== null && $this->qty === null) {
            $this->qty = $this->head_count;
        }

        if ($this->isIndividualWeighing() && ($this->source === 'actual_batch' || blank($this->source))) {
            $this->source = 'actual_individual';
        }

        if (blank($this->source)) {
            $this->source = $this->isIndividualWeighing() ? 'actual_individual' : 'actual_batch';
        }

        if ($this->isBatchWeighing()) {
            $qty = (int) ($this->qty ?: $this->head_count ?: 0);

            $this->sheep_id = null;
            $this->weight_kg = null;

            if ($qty > 0 && $this->total_weight_kg !== null) {
                $this->average_weight_kg = round((float) $this->total_weight_kg / $qty, 2);
            }
        }

        if ($this->isIndividualWeighing()) {
            if ($this->sheep_id && blank($this->fattening_batch_id)) {
                $this->fattening_batch_id = Sheep::query()
                    ->whereKey($this->sheep_id)
                    ->value('fattening_batch_id');
            }

            $this->qty = 1;
            $this->head_count = 1;
            $this->total_weight_kg = null;
            $this->average_weight_kg = null;
        }
    }

    public function isBatchWeighing(): bool
    {
        return $this->weight_type === 'batch' || $this->record_type === 'batch';
    }

    public function isIndividualWeighing(): bool
    {
        return $this->weight_type === 'per_ekor' || $this->record_type === 'individual';
    }

    private function validateWeighing(): void
    {
        if ($this->isBatchWeighing() && (float) $this->total_weight_kg < 0) {
            $this->fail('Total berat tidak boleh minus.');
        }

        if ($this->isIndividualWeighing() && (float) $this->weight_kg < 0) {
            $this->fail('Berat ternak tidak boleh minus.');
        }

        if ($this->isBatchWeighing() && (int) ($this->qty ?: $this->head_count) < 1) {
            $this->fail('Jumlah ternak saat timbang batch minimal 1 ekor.');
        }

        if ($this->isIndividualWeighing() && ! $this->sheep_id) {
            $this->fail('Ternak wajib dipilih untuk timbang per ekor.');
        }

        $batch = $this->fatteningBatch ?: $this->sheep?->fatteningBatch;

        if (! $batch?->start_date || ! $this->weighed_at) {
            return;
        }

        if ($this->weighed_at->lt($batch->start_date)) {
            $this->fail('Tanggal timbang tidak boleh sebelum tanggal mulai pembelian/batch.');
        }
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages([
            'weighing' => $message,
        ]);
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function sheep(): BelongsTo
    {
        return $this->belongsTo(Sheep::class);
    }
}
