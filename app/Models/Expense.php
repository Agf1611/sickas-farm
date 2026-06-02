<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_date',
        'expense_category_id',
        'fattening_batch_id',
        'pen_id',
        'description',
        'amount',
        'notes',
        'receipt_photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'receipt_photo_paths' => 'array',
        ];
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function fatteningBatch(): BelongsTo
    {
        return $this->belongsTo(FatteningBatch::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }
}
