<?php

namespace App\Models;

use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SheepIncidentRecord extends Model
{
    protected $fillable = [
        'incident_date',
        'incident_type',
        'fattening_batch_id',
        'sheep_id',
        'head_count',
        'reason',
        'notes',
        'photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'photo_paths' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SheepIncidentRecord $incident): void {
            if ($incident->sheep_id) {
                $sheep = Sheep::find($incident->sheep_id);

                $incident->fattening_batch_id ??= $sheep?->fattening_batch_id;
                $incident->head_count = 1;
            }

            app(StockMovementService::class)->validateIncident($incident);
        });

        static::saved(function (SheepIncidentRecord $incident): void {
            $stock = app(StockMovementService::class);

            if ($incident->wasChanged('fattening_batch_id')) {
                $stock->recalculateBatch($incident->getOriginal('fattening_batch_id'));
            }

            if ($incident->wasChanged('sheep_id')) {
                $stock->syncSheepStatus($incident->getOriginal('sheep_id'));
            }

            $stock->recalculateBatch($incident->fattening_batch_id);
            $stock->syncSheepStatus($incident->sheep_id);
            $stock->recordIncidentMovement($incident->fresh(['fatteningBatch', 'sheep']));
        });

        static::deleted(function (SheepIncidentRecord $incident): void {
            $stock = app(StockMovementService::class);

            $stock->deleteMovementFor($incident);
            $stock->recalculateBatch($incident->fattening_batch_id);
            $stock->syncSheepStatus($incident->sheep_id);
        });
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
