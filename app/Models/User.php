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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'    => 'date',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // Relationships
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

    // === ACCESSORS ===

    /**
     * Get the user's full name: "John Doe"
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Override the legacy "name" attribute
     * This makes all old code using $user->name still work perfectly
     * SAFE: No recursion!
     */
    public function getNameAttribute(): string
    {
        return $this->getFullNameAttribute(); // or just repeat the trim() logic
    }

    /**
     * Optional: Allow setting full name via $user->name = "John Doe";
     */
    public function setNameAttribute($value): void
    {
        $parts = explode(' ', trim($value), 2);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['last_name']  = $parts[1] ?? '';
    }

    // Custom email verification notification
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }
}