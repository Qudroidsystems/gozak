<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'role',
        'first_name',
        'last_name', 
        'username',
        'email',
        'phone_number',
        'profile_image',
        'social_provider',
        'gender',
        'date_of_birth',
        'password',
        'email_verified_at',
        // Notification fields
        'fcm_tokens',
        'push_notifications_enabled',
        'order_updates_enabled',
        'promotional_notifications_enabled', 
        'security_alerts_enabled',
        'email_notifications_enabled',
        'last_device_platform',
        'last_app_version',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_tokens',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'    => 'date',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'last_notification_at' => 'datetime',
        'fcm_tokens' => 'array',
        'push_notifications_enabled' => 'boolean',
        'order_updates_enabled' => 'boolean',
        'promotional_notifications_enabled' => 'boolean',
        'security_alerts_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime',
        'quiet_hours_end' => 'datetime',
    ];

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

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('read_at', null);
    }

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

    public function addFcmToken(string $token, string $deviceId, string $platform = 'flutter', string $appVersion = '1.0.0'): void
    {
        $tokens = $this->fcm_tokens ?? [];
        
        $tokens = array_filter($tokens, function($item) use ($deviceId) {
            return $item['device_id'] !== $deviceId;
        });
        
        $tokens[] = [
            'token' => $token,
            'device_id' => $deviceId,
            'platform' => $platform,
            'app_version' => $appVersion,
            'added_at' => now()->toISOString(),
            'last_used_at' => now()->toISOString(),
        ];
        
        $this->update([
            'fcm_tokens' => $tokens,
            'last_device_platform' => $platform,
            'last_app_version' => $appVersion,
        ]);
    }

    public function removeFcmToken(string $deviceId): void
    {
        $tokens = $this->fcm_tokens ?? [];
        $tokens = array_filter($tokens, function($item) use ($deviceId) {
            return $item['device_id'] !== $deviceId;
        });
        
        $this->update(['fcm_tokens' => $tokens]);
    }

    public function clearAllFcmTokens(): void
    {
        $this->update(['fcm_tokens' => []]);
    }

    public function getActiveFcmTokens(): array
    {
        return array_column($this->fcm_tokens ?? [], 'token');
    }

    public function hasActiveFcmTokens(): bool
    {
        return !empty($this->getActiveFcmTokens());
    }

    public function canReceivePushNotifications(string $type = 'general'): bool
    {
        if (!$this->push_notifications_enabled) {
            return false;
        }

        switch ($type) {
            case 'order_update':
                if (!$this->order_updates_enabled) return false;
                break;
            case 'promotional':
                if (!$this->promotional_notifications_enabled) return false;
                break;
            case 'security':
                if (!$this->security_alerts_enabled) return false;
                break;
        }

        if ($this->isInQuietHours()) {
            return false;
        }

        return true;
    }

    public function canReceiveEmailNotifications(string $type = 'general'): bool
    {
        if (!$this->email_notifications_enabled) {
            return false;
        }

        return true;
    }

    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now();
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        if ($start < $end) {
            return $now->between($start, $end);
        } else {
            return $now >= $start || $now <= $end;
        }
    }

    public function recordNotificationSent(): void
    {
        $this->update([
            'last_notification_at' => now(),
            'notification_count' => $this->notification_count + 1,
        ]);
    }

    public function getNotificationPreferences(): array
    {
        return [
            'push_notifications_enabled' => $this->push_notifications_enabled,
            'order_updates_enabled' => $this->order_updates_enabled,
            'promotional_notifications_enabled' => $this->promotional_notifications_enabled,
            'security_alerts_enabled' => $this->security_alerts_enabled,
            'email_notifications_enabled' => $this->email_notifications_enabled,
            'quiet_hours' => [
                'start' => $this->quiet_hours_start?->format('H:i'),
                'end' => $this->quiet_hours_end?->format('H:i'),
            ],
            'has_active_tokens' => $this->hasActiveFcmTokens(),
            'device_count' => count($this->fcm_tokens ?? []),
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }
}