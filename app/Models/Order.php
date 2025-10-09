<?php

namespace App\Models;

use App\Models\User;
use App\Models\Address;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'status',
        'total_amount',
        'shipping_cost',
        'tax_cost',
        'order_date',
        'payment_method',
        'shipping_address_id',
        'billing_address_id',
        'delivery_date',
        'billing_address_same_as_shipping',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_cost' => 'decimal:2',
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'billing_address_same_as_shipping' => 'boolean',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    
    /**
     * Get the user that placed the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the shipping address.
     */
    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Get the billing address.
     */
    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
 * Get the transactions for the order
 */
public function transactions()
{
    return $this->hasMany(Transaction::class);
}

/**
 * Get the successful transaction for this order
 */
public function successfulTransaction()
{
    return $this->hasOne(Transaction::class)->where('status', 'success');
}

/**
 * Check if order is paid
 */
public function isPaid()
{
    return $this->payment_status === 'paid';
}
}