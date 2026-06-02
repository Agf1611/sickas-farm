<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivestockType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'quantity_unit',
        'weight_unit',
        'uses_weight_monitoring',
        'default_sale_target_weight',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'uses_weight_monitoring' => 'boolean',
            'default_sale_target_weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
