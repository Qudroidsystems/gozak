<?php

namespace App\Models;

use App\Models\User;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    use HasFactory;

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGE = 'damage';

    const REFERENCE_PURCHASE = 'purchase';
    const REFERENCE_SALE = 'sale';
    const REFERENCE_RETURN = 'return';
    const REFERENCE_ADJUSTMENT = 'adjustment';
    const REFERENCE_TRANSFER = 'transfer';
    const REFERENCE_DAMAGE = 'damage';
    const REFERENCE_OTHER = 'other';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'stock_location_id',
        'destination_location_id',
        'user_id',
        'type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'unit_cost',
        'total_cost',
        'reference_number',
        'reference_type',
        'adjustment_reason',
        'notes',
        'expiry_date',
        'batch_number',
        'serial_number',
        'metadata',
        'transaction_date'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'expiry_date' => 'date',
        'metadata' => 'array',
        'transaction_date' => 'datetime'
    ];

    protected $appends = [
        'type_label',
        'type_color',
        'formatted_quantity',
        'formatted_cost'
    ];

    /**
     * Get the product that owns the stock transaction.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variant_id');
    }

    /**
     * Get the user who made the stock transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stock location.
     */
    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    /**
     * Get the destination location for transfers.
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'destination_location_id');
    }

    /**
     * Get the stock movement record.
     */
    public function movement(): HasOne
    {
        return $this->hasOne(StockMovement::class);
    }

    /**
     * Scope for incoming stock.
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('type', [self::TYPE_IN, self::TYPE_TRANSFER, self::TYPE_RETURN]);
    }

    /**
     * Scope for outgoing stock.
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('type', [self::TYPE_OUT, self::TYPE_DAMAGE]);
    }

    /**
     * Scope for adjustments.
     */
    public function scopeAdjustments($query)
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    /**
     * Scope for transfers.
     */
    public function scopeTransfers($query)
    {
        return $query->where('type', self::TYPE_TRANSFER);
    }

    /**
     * Scope for returns.
     */
    public function scopeReturns($query)
    {
        return $query->where('type', self::TYPE_RETURN);
    }

    /**
     * Scope for damages.
     */
    public function scopeDamages($query)
    {
        return $query->where('type', self::TYPE_DAMAGE);
    }

    /**
     * Scope for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for a specific location.
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('stock_location_id', $locationId);
    }

    /**
     * Scope for a specific reference type.
     */
    public function scopeForReference($query, $referenceType, $referenceNumber = null)
    {
        $query = $query->where('reference_type', $referenceType);
        if ($referenceNumber) {
            $query->where('reference_number', $referenceNumber);
        }
        return $query;
    }

    /**
     * Scope for date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute()
    {
        $labels = [
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_TRANSFER => 'Transfer',
            self::TYPE_RETURN => 'Return',
            self::TYPE_DAMAGE => 'Damage/Loss',
        ];
        
        return $labels[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get the type color for badges.
     */
    public function getTypeColorAttribute()
    {
        $colors = [
            self::TYPE_IN => 'success',
            self::TYPE_OUT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            self::TYPE_TRANSFER => 'info',
            self::TYPE_RETURN => 'primary',
            self::TYPE_DAMAGE => 'dark',
        ];
        
        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Get the formatted quantity with sign.
     */
    public function getFormattedQuantityAttribute()
    {
        $sign = in_array($this->type, [self::TYPE_IN, self::TYPE_ADJUSTMENT, self::TYPE_TRANSFER, self::TYPE_RETURN]) ? '+' : '-';
        return $sign . abs($this->quantity);
    }

    /**
     * Get the formatted cost.
     */
    public function getFormattedCostAttribute()
    {
        if ($this->total_cost) {
            return '$' . number_format($this->total_cost, 2);
        }
        return null;
    }

    /**
     * Get the reference label.
     */
    public function getReferenceLabelAttribute()
    {
        $labels = [
            self::REFERENCE_PURCHASE => 'Purchase',
            self::REFERENCE_SALE => 'Sale',
            self::REFERENCE_RETURN => 'Return',
            self::REFERENCE_ADJUSTMENT => 'Adjustment',
            self::REFERENCE_TRANSFER => 'Transfer',
            self::REFERENCE_DAMAGE => 'Damage',
            self::REFERENCE_OTHER => 'Other',
        ];
        
        return $labels[$this->reference_type] ?? ucfirst($this->reference_type);
    }

    /**
     * Check if this is an incoming transaction.
     */
    public function isIncoming()
    {
        return in_array($this->type, [self::TYPE_IN, self::TYPE_TRANSFER, self::TYPE_RETURN]);
    }

    /**
     * Check if this is an outgoing transaction.
     */
    public function isOutgoing()
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_DAMAGE]);
    }

    /**
     * Get the total quantity change.
     */
    public function getQuantityChange()
    {
        return $this->isIncoming() ? $this->quantity : -$this->quantity;
    }

    /**
     * Create a stock movement record.
     */
    public function createMovement()
    {
        $movementType = $this->type === self::TYPE_TRANSFER ? 'transfer_out' : $this->type;
        if ($this->type === self::TYPE_TRANSFER && $this->destination_location_id) {
            // Create movement for destination location
            StockMovement::create([
                'stock_id' => $this->id,
                'product_id' => $this->product_id,
                'stock_location_id' => $this->destination_location_id,
                'movement_type' => 'transfer_in',
                'quantity' => $this->quantity,
                'balance' => $this->destinationLocation->getProductStock($this->product_id, $this->product_variant_id),
                'reference' => $this->reference_number,
                'description' => "Transfer from {$this->stockLocation->name}",
                'user_id' => $this->user_id,
            ]);
        }
        
        return StockMovement::create([
            'stock_id' => $this->id,
            'product_id' => $this->product_id,
            'stock_location_id' => $this->stock_location_id,
            'movement_type' => $movementType,
            'quantity' => $this->quantity,
            'balance' => $this->new_quantity,
            'reference' => $this->reference_number,
            'description' => $this->notes ?? $this->adjustment_reason ?? $this->type_label,
            'user_id' => $this->user_id,
        ]);
    }
}