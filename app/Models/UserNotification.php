<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'sent_via',
        'delivery_status',
        'fcm_response',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'fcm_response' => 'array',
        'read_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_READ = 'read';

    const TYPE_ORDER_CREATED = 'order_created';
    const TYPE_ORDER_UPDATED = 'order_updated';
    const TYPE_PROMOTIONAL = 'promotional';
    const TYPE_SECURITY = 'security';
    const TYPE_SYSTEM = 'system';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
