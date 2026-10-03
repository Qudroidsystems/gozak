<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    public const WAITING = 'waiting';
    public const ACTIVE  = 'active';
    public const CLOSED  = 'closed';
    public const OPEN_STATUSES = [self::WAITING, self::ACTIVE];

    protected $fillable = [
        'user_id', 'agent_id', 'topic', 'status', 'order_id', 'product_id',
        'preview', 'last_message_at', 'customer_unread', 'agent_unread',
        'rating', 'rating_comment', 'assigned_at', 'first_response_at',
        'closed_at', 'closed_by',
    ];

    protected $casts = [
        'last_message_at'   => 'datetime',
        'assigned_at'       => 'datetime',
        'first_response_at' => 'datetime',
        'closed_at'         => 'datetime',
        'customer_unread'   => 'integer',
        'agent_unread'      => 'integer',
        'rating'            => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeWaiting(Builder $q): Builder
    {
        return $q->where('status', self::WAITING);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isClosed(): bool
    {
        return $this->status === self::CLOSED;
    }

    public function topicLabel(): string
    {
        return config('chat.topics.' . $this->topic, ucfirst((string) $this->topic));
    }

    public function channelName(): string
    {
        return 'private-chat.conversation.' . $this->id;
    }

    /** Can this user read / write this conversation? */
    public function canBeAccessedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }
        return $user->can('Chat with customers') || $user->can('Manage chat');
    }
}
