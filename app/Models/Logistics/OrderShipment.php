<?php

namespace App\Models\Logistics;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

/** The marketplace courier chosen for an order, and where its delivery is. */
class OrderShipment extends Model
{
    public const LABELS = [
        'selected'        => 'Chosen at checkout',
        'booking'         => 'Booking…',
        'booked'          => 'Booked — waiting for a rider',
        'assigned'        => 'Rider assigned',
        'picked_up'       => 'Picked up — on the way',
        'delivered'       => 'Delivered',
        'failed'          => 'Booking failed',
        'cancelled'       => 'Cancelled',
        'delivery_failed' => 'Delivery attempt failed',
        'returned'        => 'Returned',
    ];

    /** Still moving: worth polling the marketplace for. */
    public const ACTIVE = ['booked', 'assigned', 'picked_up', 'delivery_failed'];

    protected $fillable = [
        'order_id', 'carrier', 'option_id', 'service_name', 'provider_id', 'provider_name', 'provider_logo', 'provider_rating',
        'marketplace_name', 'eta_label', 'quoted_price', 'price', 'fee_payer', 'status', 'external_status', 'agreement',
        'external_order_number', 'tracking_code', 'tracking_url', 'payment_url', 'booked_at', 'picked_up_at', 'delivered_at',
        'synced_at', 'attempts', 'last_error', 'payload', 'mode',
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'price'        => 'decimal:2',
        'payload'      => 'array',
        'booked_at'    => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'synced_at'    => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function badge(): string
    {
        return match ($this->status) {
            'delivered'                               => 'success',
            'picked_up', 'assigned'                   => 'primary',
            'booked'                                  => 'info',
            'failed', 'delivery_failed', 'cancelled'  => 'danger',
            'returned'                                => 'warning',
            default                                   => 'secondary',
        };
    }

    /** Booked and not finished: there is something to follow on the map. */
    public function isLive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    public function isOwn(): bool
    {
        return $this->mode === 'own';
    }

    public function canBook(): bool
    {
        return in_array($this->status, ['selected', 'failed'], true);
    }

    /** For the customer app. */
    public function toCustomer(): array
    {
        return [
            'provider'      => $this->provider_name,
            'provider_logo' => $this->provider_logo,
            'rating'        => $this->provider_rating !== null ? (float) $this->provider_rating : null,
            'marketplace'   => $this->marketplace_name,
            'service'       => $this->service_name,
            'eta'           => $this->eta_label,
            'fee'           => (float) ($this->price ?? $this->quoted_price),
            'fee_payer'     => $this->fee_payer,
            'pay_rider'     => $this->fee_payer === 'receiver' ? (float) ($this->price ?? $this->quoted_price) : 0,
            'status'        => $this->status,
            'status_label'  => $this->label(),
            'tracking_code' => $this->tracking_code,
            'tracking_url'  => $this->tracking_url,
            // own = GozakMart Delivery run by GozakMart's team on the marketplace
            'mode'          => $this->mode ?: 'courier',
            // The app can show the rider live (GET /api/orders/{id}/live-tracking) while this is true.
            'live'          => $this->isLive(),
        ];
    }
}
