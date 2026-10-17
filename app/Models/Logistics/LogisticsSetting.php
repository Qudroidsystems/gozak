<?php

namespace App\Models\Logistics;

use Illuminate\Database\Eloquent\Model;

class LogisticsSetting extends Model
{
    protected $fillable = [
        'enabled', 'base_url', 'api_key', 'webhook_secret', 'environment', 'merchant_code', 'merchant_name', 'merchant_status',
        'marketplace_name', 'marketplace_badge', 'marketplace_logo', 'last_checked_at', 'last_error', 'pickup_stock_location_id',
        'default_weight_g', 'max_price_increase_percent', 'allow_receiver_pays', 'own_delivery_enabled', 'own_delivery_label', 'own_delivery_eta',
    ];

    protected $casts = [
        'enabled'              => 'boolean',
        'allow_receiver_pays'  => 'boolean',
        'own_delivery_enabled' => 'boolean',
        'api_key'              => 'encrypted',
        'webhook_secret'       => 'encrypted',
        'last_checked_at'      => 'datetime',
    ];

    protected $hidden = ['api_key', 'webhook_secret'];

    public static function current(): self
    {
        try {
            return static::query()->first() ?? new static(['own_delivery_enabled' => true, 'own_delivery_label' => 'GozakMart Delivery', 'own_delivery_eta' => 'Same day', 'allow_receiver_pays' => true, 'default_weight_g' => 2000, 'max_price_increase_percent' => 10]);
        } catch (\Throwable $e) {
            return new static(['own_delivery_enabled' => true, 'own_delivery_label' => 'GozakMart Delivery', 'own_delivery_eta' => 'Same day']);
        }
    }

    /** Couriers show at checkout only when switched on and the marketplace has verified GozakMart. */
    public function isLive(): bool
    {
        return $this->enabled && $this->base_url && $this->api_key && $this->merchant_status === 'verified';
    }

    public function maskedKey(): ?string
    {
        if (!$this->api_key) {
            return null;
        }
        return \Illuminate\Support\Str::before($this->api_key, '.') . '.••••' . substr($this->api_key, -4);
    }
}
