<?php

namespace App\Models\Dispatch;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Append-only log of what happened on the road. */
class DispatchEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'dispatch_batch_id', 'rider_id', 'user_id', 'event', 'note', 'lat', 'lng', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public static function log(string $event, ?Order $order = null, ?DispatchBatch $batch = null, array $extra = []): void
    {
        try {
            static::create(array_merge([
                'event'             => $event,
                'order_id'          => $order?->id,
                'dispatch_batch_id' => $batch?->id ?? $order?->dispatch_batch_id,
                'rider_id'          => $batch?->rider_id,
                'user_id'           => auth()->id(),
            ], array_filter($extra, fn ($v) => $v !== null && $v !== '')));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Dispatch event not logged: ' . $e->getMessage());
        }
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function rider()
    {
        return $this->belongsTo(DeliveryRider::class, 'rider_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
