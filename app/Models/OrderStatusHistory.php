<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_histories';

    protected $fillable = ['order_id', 'from_status', 'status', 'actor_type', 'actor_id', 'note'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actorLabel(): string
    {
        return match ($this->actor_type) {
            'customer' => 'Customer',
            'admin'    => $this->actor?->full_name ?: ($this->actor?->name ?? 'Admin'),
            default    => 'System',
        };
    }
}
