<?php

namespace App\Models\Dispatch;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderParcel extends Model
{
    public const TYPES = [
        'bag'    => 'Bag',
        'carton' => 'Carton',
        'cooler' => 'Cooler box',
        'crate'  => 'Crate / tray',
        'other'  => 'Other',
    ];

    /** Handling tags → [label printed on the parcel, short code]. */
    public const HANDLING = [
        'fragile'      => ['FRAGILE', 'FRG'],
        'keep_upright' => ['KEEP UPRIGHT', 'UP'],
        'perishable'   => ['PERISHABLE', 'PER'],
        'frozen'       => ['KEEP FROZEN', 'FRZ'],
        'chilled'      => ['KEEP COLD', 'CLD'],
        'liquid'       => ['LIQUIDS', 'LIQ'],
        'heavy'        => ['HEAVY', 'HVY'],
        'eggs'         => ['EGGS – HANDLE GENTLY', 'EGG'],
    ];

    protected $fillable = [
        'order_id', 'code', 'sequence', 'package_type', 'handling', 'weight_kg', 'contents', 'status',
        'dispatch_batch_id', 'labelled_at', 'loaded_at', 'loaded_by', 'delivered_at',
    ];

    protected $casts = [
        'handling'     => 'array',
        'weight_kg'    => 'decimal:2',
        'labelled_at'  => 'datetime',
        'loaded_at'    => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function batch()
    {
        return $this->belongsTo(DispatchBatch::class, 'dispatch_batch_id');
    }

    public function loader()
    {
        return $this->belongsTo(User::class, 'loaded_by');
    }

    public function handlingLabels(): array
    {
        return collect($this->handling ?? [])
            ->map(fn ($h) => self::HANDLING[$h][0] ?? strtoupper(str_replace('_', ' ', $h)))
            ->values()
            ->all();
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->package_type] ?? ucfirst((string) $this->package_type);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'packed'           => 'secondary',
            'loaded'           => 'info',
            'out_for_delivery' => 'primary',
            'delivered'        => 'success',
            'returned'         => 'warning',
            default            => 'light',
        };
    }
}
