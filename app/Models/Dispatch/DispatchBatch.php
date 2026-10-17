<?php

namespace App\Models\Dispatch;

use App\Models\Order;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DispatchBatch extends Model
{
    public const STATUSES = [
        'draft'      => 'Planning',
        'loading'    => 'Loading',
        'dispatched' => 'Out for delivery',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
    ];

    protected $fillable = [
        'code', 'route_code', 'stock_location_id', 'delivery_zone_id', 'rider_id', 'status',
        'planned_departure_at', 'dispatched_at', 'completed_at', 'created_by', 'dispatched_by', 'notes',
        'cash_expected', 'cash_received', 'cash_received_by', 'cash_reconciled_at', 'cash_note', 'suggested',
    ];

    protected $casts = [
        'planned_departure_at' => 'datetime',
        'dispatched_at'        => 'datetime',
        'completed_at'         => 'datetime',
        'cash_reconciled_at'   => 'datetime',
        'cash_expected'        => 'decimal:2',
        'cash_received'        => 'decimal:2',
        'suggested'            => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'dispatch_batch_id')->orderBy('stop_sequence');
    }

    public function parcels()
    {
        return $this->hasMany(OrderParcel::class, 'dispatch_batch_id');
    }

    public function rider()
    {
        return $this->belongsTo(DeliveryRider::class, 'rider_id');
    }

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function branch()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function cashReceiver()
    {
        return $this->belongsTo(User::class, 'cash_received_by');
    }

    /** Cash the rider says was collected in cash (transfers / POS go straight to the bank). */
    public function cashCollectedInHand(): float
    {
        return (float) $this->orders()->where('cash_method', 'cash')->sum('cash_collected');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'loading'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'draft'      => 'secondary',
            'loading'    => 'warning',
            'dispatched' => 'primary',
            'completed'  => 'success',
            'cancelled'  => 'danger',
            default      => 'light',
        };
    }
}
