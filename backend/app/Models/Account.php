<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['user_id', 'name', 'currency', 'color', 'icon'];

    protected $appends = ['balance'];

    public function getBalanceAttribute(): float
    {
        return (float) $this->transactions()
            ->where('type', 'income')
            ->sum('amount')
            - (float) $this->transactions()
            ->where('type', 'expense')
            ->sum('amount');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}