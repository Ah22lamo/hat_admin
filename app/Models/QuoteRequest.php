<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuoteRequest extends Model
{
    protected $fillable = [
        'reference',
        'customer_id',
        'status',
        'assigned_to',
        'requested_items',
        'approved_amount',
        'notes',
    ];

    protected $casts = [
        'requested_items' => 'array',
        'approved_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }
}