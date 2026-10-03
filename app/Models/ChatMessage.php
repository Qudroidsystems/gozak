<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatMessage extends Model
{
    public const FROM_CUSTOMER = 'customer';
    public const FROM_AGENT    = 'agent';
    public const FROM_SYSTEM   = 'system';

    public const TYPES = ['text', 'image', 'order', 'product', 'system'];

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'type', 'body',
        'attachment_path', 'meta', 'client_id', 'read_at',
    ];

    protected $casts = [
        'meta'    => 'array',
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function imageUrl(): ?string
    {
        return $this->attachment_path
            ? url(Storage::disk('public')->url($this->attachment_path))
            : null;
    }

    /** One-line text used for the conversation preview and push notifications. */
    public function previewText(): string
    {
        return match ($this->type) {
            'image'   => '📷 Photo' . ($this->body ? ' · ' . $this->body : ''),
            'order'   => '🧾 Order ' . ($this->meta['reference'] ?? ''),
            'product' => '🛍️ ' . ($this->meta['title'] ?? 'Product'),
            default   => (string) $this->body,
        };
    }
}
