<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'code',
        'name',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function fatteningBatches(): HasMany
    {
        return $this->hasMany(FatteningBatch::class);
    }

    public function sheepPurchases(): HasMany
    {
        return $this->hasMany(SheepPurchase::class);
    }
}
