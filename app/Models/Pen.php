<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pen extends Model
{
    protected $fillable = [
        'code',
        'name',
        'capacity',
        'location',
        'description',
        'condition_photo_paths',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'condition_photo_paths' => 'array',
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

    public function sheep(): HasMany
    {
        return $this->hasMany(Sheep::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
