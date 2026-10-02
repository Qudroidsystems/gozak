<?php

namespace App\Models;

use App\Support\PaymentGatewayCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Credentials are entered by an admin (Settings › Payment Gateways) and kept in
 * config['credentials'][test|live][field]. Secret fields are encrypted with the
 * app key; always read them with credential().
 * Ported from the CSS Kabba portal.
 */
class PaymentGateway extends Model
{
    protected $table = 'payment_gateways';

    protected $fillable = [
        'name', 'provider_key', 'secret_key', 'public_key', 'mode',
        'fee_percentage', 'fee_fixed', 'config', 'is_active',
    ];

    protected $hidden = ['secret_key', 'config'];

    protected $casts = [
        'config'         => 'array',
        'fee_percentage' => 'decimal:2',
        'fee_fixed'      => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    protected const ENC = 'enc:';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider_key', $provider);
    }

    public function catalog(): array
    {
        return PaymentGatewayCatalog::get($this->provider_key) ?? ['name' => $this->name, 'supported' => false, 'fields' => []];
    }

    /** Plain value of a credential for a set ("test"/"live"; default = current mode). */
    public function credential(string $field, ?string $set = null): ?string
    {
        $set    = $set ?? PaymentGatewayCatalog::set($this->mode);
        $config = $this->config ?? [];
        $stored = $config['credentials'][$set][$field] ?? null;

        if ($stored !== null && $stored !== '') {
            if (str_starts_with($stored, self::ENC)) {
                try {
                    $stored = Crypt::decryptString(substr($stored, strlen(self::ENC)));
                } catch (\Throwable $e) {
                    return null; // APP_KEY changed — the admin must re-enter the key
                }
            }
            return $this->real($stored);
        }
        return null;
    }

    /** Store (or clear with null) a credential. Secret fields are encrypted. */
    public function putCredential(string $field, string $set, ?string $value): void
    {
        $config = $this->config ?? [];
        $value  = $value === null ? null : trim($value);

        if ($value === null || $value === '') {
            unset($config['credentials'][$set][$field]);
        } else {
            $secret = $this->catalog()['fields'][$field]['secret'] ?? true;
            $config['credentials'][$set][$field] = $secret ? self::ENC . Crypt::encryptString($value) : $value;
        }
        $this->config = $config;
    }

    /** "••••••b654" for secrets, the value itself otherwise; null when unset. */
    public function maskedCredential(string $field, string $set): ?string
    {
        $v = $this->credential($field, $set);
        if ($v === null) return null;

        $secret = $this->catalog()['fields'][$field]['secret'] ?? true;
        return $secret ? '••••••' . substr($v, -4) : $v;
    }

    /** Every required credential present for a set? */
    public function isConfigured(?string $set = null): bool
    {
        $set = $set ?? PaymentGatewayCatalog::set($this->mode);
        foreach ($this->catalog()['fields'] as $field => $def) {
            if (!empty($def['required']) && !$this->credential($field, $set)) {
                return false;
            }
        }
        return !empty($this->catalog()['fields']);
    }

    /** Switched on and has the keys for its current mode. */
    public function isReady(): bool
    {
        return $this->is_active && $this->isConfigured();
    }

    /** Ignore empty values and placeholders such as "sk_test_xxxxxxxx". */
    protected function real($v): ?string
    {
        $v = trim((string) $v);
        return ($v === '' || str_contains(strtolower($v), 'xxxx')) ? null : $v;
    }
}
