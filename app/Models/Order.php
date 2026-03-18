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
        'barcode_path',
        'barcode_data',
        'paid_at',
        'payment_status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_cost' => 'decimal:2',
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'paid_at' => 'datetime',
        'billing_address_same_as_shipping' => 'boolean',
        'barcode_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function successfulTransaction()
    {
        return $this->hasOne(Transaction::class)->where('status', 'success');
    }

    public function isPaid()
    {
        return $this->getPaymentStatusAttribute() === 'paid';
    }

    public function getPaymentStatusAttribute()
    {
        if (isset($this->attributes['payment_status']) && $this->attributes['payment_status']) {
            return $this->attributes['payment_status'];
        }

        return $this->transactions()->where('status', 'success')->exists() ? 'paid' : 'unpaid';
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid')
                    ->orWhereHas('transactions', function($q) {
                        $q->where('status', 'success');
                    });
    }

    public function getFormattedOrderDateAttribute(): string
    {
        return $this->order_date->format('M j, Y \a\t g:i A');
    }

    public function getFormattedDeliveryDateAttribute(): ?string
    {
        return $this->delivery_date?->format('M j, Y');
    }

    public function getOrderStatusTextAttribute(): string
    {
        $statusMap = [
            'pending' => 'Order Placed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        return $statusMap[$this->status] ?? ucfirst($this->status);
    }

    public function updateBarcodeInfo(string $path, array $data): void
    {
        $this->update([
            'barcode_path' => $path,
            'barcode_data' => $data,
        ]);
    }

    public function hasBarcode(): bool
    {
        return !empty($this->barcode_path) && !empty($this->barcode_data);
    }

    public function getBarcodeUrlAttribute(): ?string
    {
        if ($this->barcode_path) {
            return \Storage::disk('public')->url($this->barcode_path);
        }
        return null;
    }

    public function markAsPaid(): bool
    {
        return $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'status' => 'processing',
        ]);
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class)->latest();
    }

    public function totalRefunded()
    {
        return $this->refunds()->where('status', 'processed')->sum('amount');
    }

    public function refundableAmount()
    {
        return $this->total_amount - $this->totalRefunded();
    }

    // In your Order model class
/**
 * Get barcode data as array
 */
public function getBarcodeDataAttribute($value)
{
    if (is_string($value)) {
        return json_decode($value, true) ?? [];
    }

    return $value ?? [];
}

/**
 * Set barcode data
 */
public function setBarcodeDataAttribute($value)
{
    if (is_array($value)) {
        $this->attributes['barcode_data'] = json_encode($value);
    } else {
        $this->attributes['barcode_data'] = $value;
    }
}


}
