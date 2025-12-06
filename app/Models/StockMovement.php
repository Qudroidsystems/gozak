<?php

namespace App\Models;

use App\Models\User;
use App\Models\Stock;
use App\Models\Product;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'product_id',
        'stock_location_id',
        'movement_type',
        'quantity',
        'balance',
        'reference',
        'description',
        'user_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'balance' => 'integer'
    ];

    protected $appends = ['formatted_quantity', 'movement_label'];

    /**
     * Get the stock transaction.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the stock location.
     */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted quantity with sign.
     */
    public function getFormattedQuantityAttribute()
    {
        $sign = in_array($this->movement_type, ['in', 'adjustment', 'transfer_in']) ? '+' : '-';
        return $sign . abs($this->quantity);
    }

    /**
     * Get movement label.
     */
    public function getMovementLabelAttribute()
    {
        $labels = [
            'in' => 'Stock In',
            'out' => 'Stock Out',
            'adjustment' => 'Adjustment',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
        ];
        
        return $labels[$this->movement_type] ?? ucfirst($this->movement_type);
    }

    /**
     * Get movement color.
     */
    public function getMovementColorAttribute()
    {
        $colors = [
            'in' => 'success',
            'out' => 'danger',
            'adjustment' => 'warning',
            'transfer_in' => 'info',
            'transfer_out' => 'primary',
        ];
        
        return $colors[$this->movement_type] ?? 'secondary';
    }

    /**
     * Scope for incoming movements.
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('movement_type', ['in', 'transfer_in']);
    }

    /**
     * Scope for outgoing movements.
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('movement_type', ['out', 'transfer_out']);
    }
}