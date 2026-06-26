<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Stock;
use App\Models\Address;
use App\Models\Setting;
use App\Models\WishlistItem;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'role',
        'first_name',
        'last_name',
        'username',
        'password', // make sure this is here
        'email',
        'phone_number',
        'profile_image',
        'social_provider',
        'gender',
        'date_of_birth',
        'password',
        'email_verified_at',
        'fcm_token',                              // ← single string token
        'push_notifications_enabled',
        'order_updates_enabled',
        'promotional_notifications_enabled',
        'security_alerts_enabled',
        'email_notifications_enabled',
        'last_device_platform',
        'last_app_version',
        'quiet_hours_start',
        'quiet_hours_end',
        'last_notification_at',
        'notification_count',
    ];

    // ── IMPORTANT: fcm_token is NOT in $hidden ────────────────────────────────
    // Keeping it in $hidden can interfere with update() in some Laravel versions.
    // We exclude it from API responses manually in formatUser() instead.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'                 => 'datetime',
        'date_of_birth'                     => 'date',
        'created_at'                        => 'datetime',
        'updated_at'                        => 'datetime',
        'last_notification_at'              => 'datetime',
        'quiet_hours_start'                 => 'datetime:H:i',
        'quiet_hours_end'                   => 'datetime:H:i',
        'push_notifications_enabled'        => 'boolean',
        'order_updates_enabled'             => 'boolean',
        'promotional_notifications_enabled' => 'boolean',
        'security_alerts_enabled'           => 'boolean',
        'email_notifications_enabled'       => 'boolean',
        'notification_count'                => 'integer',
        'password'                          => 'hashed',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }

    public function setNameAttribute($value): void
    {
        $parts = explode(' ', trim($value), 2);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['last_name']  = $parts[1] ?? '';
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeCustomers($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeStaff($query)
    {
        return $query->where('role', 'staff');
    }

    public function scopeWithOrderStats($query)
    {
        return $query->withCount('orders')
                     ->withSum('orders', 'total_amount');
    }

    // ── FCM helpers ───────────────────────────────────────────────────────────

    public function hasActiveFcmToken(): bool
    {
        return !empty($this->fcm_token);
    }

    public function clearFcmToken(): void
    {
        \DB::table('users')->where('id', $this->id)->update(['fcm_token' => null]);
        $this->fcm_token = null;
    }

    public function canReceivePushNotifications(string $type = 'general'): bool
    {
        if (!$this->hasActiveFcmToken()) return false;
        if (!($this->push_notifications_enabled ?? true)) return false;
        if ($this->isInQuietHours()) return false;

        return match ($type) {
            'order_update' => $this->order_updates_enabled             ?? true,
            'promotional'  => $this->promotional_notifications_enabled ?? false,
            'security'     => $this->security_alerts_enabled           ?? true,
            default        => true,
        };
    }

    public function canReceiveEmailNotifications(): bool
    {
        return ($this->email_notifications_enabled ?? true)
            && $this->hasVerifiedEmail();
    }

    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) return false;

        $now   = now()->format('H:i');
        $start = $this->quiet_hours_start->format('H:i');
        $end   = $this->quiet_hours_end->format('H:i');

        return $start < $end
            ? ($now >= $start && $now <= $end)
            : ($now >= $start || $now <= $end);
    }

    public function recordNotificationSent(): void
    {
        \DB::table('users')->where('id', $this->id)->update([
            'last_notification_at' => now(),
            'notification_count'   => ($this->notification_count ?? 0) + 1,
        ]);
    }

    public function getNotificationPreferences(): array
    {
        return [
            'push_notifications_enabled'        => $this->push_notifications_enabled        ?? true,
            'order_updates_enabled'             => $this->order_updates_enabled             ?? true,
            'promotional_notifications_enabled' => $this->promotional_notifications_enabled ?? false,
            'security_alerts_enabled'           => $this->security_alerts_enabled           ?? true,
            'email_notifications_enabled'       => $this->email_notifications_enabled       ?? true,
            'quiet_hours' => [
                'start' => $this->quiet_hours_start?->format('H:i'),
                'end'   => $this->quiet_hours_end?->format('H:i'),
            ],
            'has_active_token' => $this->hasActiveFcmToken(),
        ];
    }

    // ── Email verification ────────────────────────────────────────────────────

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }
}
