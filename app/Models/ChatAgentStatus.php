<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatAgentStatus extends Model
{
    public const ONLINE  = 'online';
    public const AWAY    = 'away';
    public const OFFLINE = 'offline';

    protected $fillable = ['user_id', 'status', 'max_chats', 'last_seen_at'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'max_chats'    => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Online AND the inbox pinged recently (closed tabs drop off automatically). */
    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where('status', self::ONLINE)
            ->where('last_seen_at', '>=', now()->subSeconds((int) config('chat.presence_ttl', 180)));
    }

    public function isAvailable(): bool
    {
        return $this->status === self::ONLINE
            && $this->last_seen_at
            && $this->last_seen_at->gte(now()->subSeconds((int) config('chat.presence_ttl', 180)));
    }

    public static function for(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => self::OFFLINE, 'max_chats' => (int) config('chat.max_chats', 5)]
        );
    }
}
