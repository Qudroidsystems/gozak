<?php

namespace App\Models\Dispatch;

use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryRider extends Model
{
    public const VEHICLES = [
        'motorbike' => 'Motorbike',
        'tricycle'  => 'Tricycle (keke)',
        'car'       => 'Car',
        'van'       => 'Van',
        'bicycle'   => 'Bicycle',
        'on_foot'   => 'On foot',
    ];

    protected $fillable = ['user_id', 'stock_location_id', 'name', 'phone', 'vehicle_type', 'vehicle_plate', 'capacity', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function batches()
    {
        return $this->hasMany(DispatchBatch::class, 'rider_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function vehicleLabel(): string
    {
        $v = self::VEHICLES[$this->vehicle_type] ?? ucfirst((string) $this->vehicle_type);
        return $this->vehicle_plate ? "{$v} · {$this->vehicle_plate}" : $v;
    }

    /** Is the rider currently out on a run? */
    public function isOnRun(): bool
    {
        return $this->batches()->whereIn('status', ['loading', 'dispatched'])->exists();
    }
}
