<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'reference',
        'amount',
        'status',
        'payment_method',
        'payment_data',
        'paid_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'payment_data' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'order_id' => 'string', // Important: Cast order_id as string since orders use string IDs
    ];

    /**
     * Get the order that owns the transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to get pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful()
    {
        return $this->status === 'success';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }
}
