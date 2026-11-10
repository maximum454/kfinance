<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringPayment extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'category_id', 'amount', 'type',
        'frequency', 'next_date', 'active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'next_date' => 'date',
            'active' => 'boolean',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}