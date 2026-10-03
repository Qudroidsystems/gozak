<?php

namespace App\Services\Chat;

use App\Models\ChatAgentStatus;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * All chat business rules live here so the app API and the admin inbox
 * behave exactly the same.
 */
class ChatService
{
    public function __construct(protected ChatRealtime $realtime)
    {
    }

    public function realtime(): ChatRealtime
    {
        return $this->realtime;
    }

    // ── Availability ─────────────────────────────────────────────────────────

    public function onlineAgentCount(): int
    {
        return ChatAgentStatus::available()->count();
    }

    public function withinOfficeHours(?Carbon $at = null): bool
    {
        $cfg = config('chat.office_hours');
        $now = ($at ?? now())->copy()->setTimezone($cfg['timezone'] ?? 'Africa/Lagos');

        if (!in_array($now->dayOfWeekIso, $cfg['days'] ?? [1, 2, 3, 4, 5, 6], true)) {
            return false;
        }
        $hm = $now->format('H:i');
        return $hm >= ($cfg['open'] ?? '08:00') && $hm < ($cfg['close'] ?? '20:00');
    }

    public function status(): array
    {
        $online = $this->onlineAgentCount();
        $inHours = $this->withinOfficeHours();
        $label = config('chat.office_hours.label');

        if ($online > 0) {
            $message = 'An agent usually replies within a few minutes.';
        } elseif ($inHours) {
            $message = "All agents are busy right now. Leave a message and we'll reply shortly.";
        } else {
            $message = "We're offline now ({$label}). Leave a message and we'll reply when we're back.";
        }

        return [
            'online_agents' => $online,
            'available'     => $online > 0,
            'office_hours'  => $label,
            'within_hours'  => $inHours,
            'message'       => $message,
            'topics'        => collect(config('chat.topics'))->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'max_image_kb'  => (int) config('chat.max_image_kb'),
            'pusher'        => $this->realtime->clientConfig(),
        ];
    }

    // ── Conversations ────────────────────────────────────────────────────────

    /**
     * Re-use the customer's open chat or start a new one. Context (order /
     * product) is attached as a card message so the agent sees it.
     */
    public function startOrResume(User $customer, string $topic = 'general', ?string $orderId = null, ?int $productId = null, ?string $firstMessage = null): ChatConversation
    {
        $topic = array_key_exists($topic, config('chat.topics')) ? $topic : 'general';

        $order = null;
        if ($orderId) {
            $order = Order::where('id', $orderId)->where('user_id', $customer->id)->first();
        }
        $product = $productId ? Product::find($productId) : null;

        $conversation = DB::transaction(function () use ($customer, $topic, $order, $product, &$created) {
            $existing = ChatConversation::where('user_id', $customer->id)->open()->lockForUpdate()->latest('id')->first();
            if ($existing) {
                $created = false;
                if ($order && !$existing->order_id) {
                    $existing->order_id = $order->id;
                }
                if ($product && !$existing->product_id) {
                    $existing->product_id = $product->id;
                }
                $existing->save();
                return $existing;
            }

            $created = true;
            return ChatConversation::create([
                'user_id'         => $customer->id,
                'topic'           => $topic,
                'status'          => ChatConversation::WAITING,
                'order_id'        => $order?->id,
                'product_id'      => $product?->id,
                'last_message_at' => now(),
            ]);
        });

        if ($created) {
            $this->systemMessage($conversation, 'Chat started · ' . $conversation->topicLabel());
        }
        if ($order) {
            $this->sendCard($conversation, $customer, 'order', $this->orderCard($order));
        }
        if ($product) {
            $this->sendCard($conversation, $customer, 'product', $this->productCard($product));
        }
        if ($firstMessage !== null && trim($firstMessage) !== '') {
            $this->send($conversation, $customer, ChatMessage::FROM_CUSTOMER, trim($firstMessage));
        }

        if ($created) {
            $this->autoAssign($conversation);
            if (!$conversation->agent_id) {
                $this->systemMessage($conversation, $this->onlineAgentCount() > 0
                    ? 'You are in the queue. An agent will join shortly.'
                    : "Our agents are offline right now (" . config('chat.office_hours.label') . "). Leave your message and we'll reply as soon as we're back.");
            }
        }

        return $conversation->fresh(['agent', 'customer']);
    }

    /**
     * Save + broadcast a message. $file is an uploaded image (optional).
     */
    public function send(ChatConversation $c, ?User $sender, string $senderType, ?string $body, ?UploadedFile $file = null, ?string $clientId = null, string $type = 'text', array $meta = []): ChatMessage
    {
        if ($clientId) {
            $dupe = $c->messages()->where('client_id', $clientId)->first();
            if ($dupe) {
                return $dupe;
            }
        }

        // An agent answering a waiting chat takes it.
        if ($senderType === ChatMessage::FROM_AGENT && $sender && $c->status !== ChatConversation::ACTIVE) {
            $this->assign($c, $sender, $sender);
        }

        $path = null;
        if ($file) {
            $path = $file->store('chat/' . $c->id, 'public');
            $type = 'image';
        }

        $message = $c->messages()->create([
            'sender_type'     => $senderType,
            'sender_id'       => $sender?->id,
            'type'            => $type,
            'body'            => $body !== null ? Str::limit(trim($body), 4000, '') : null,
            'attachment_path' => $path,
            'meta'            => $meta ?: null,
            'client_id'       => $clientId,
        ]);

        // Conversation bookkeeping
        $reopened = false;
        $updates = [
            'preview'         => Str::limit($message->previewText(), 250),
            'last_message_at' => $message->created_at,
        ];
        if ($senderType === ChatMessage::FROM_CUSTOMER) {
            $c->increment('agent_unread');
            if ($c->isClosed()) {
                // Customer wrote into a closed chat → re-open it to the queue
                $updates += ['status' => ChatConversation::WAITING, 'agent_id' => null, 'closed_at' => null, 'closed_by' => null, 'rating' => null, 'rating_comment' => null];
                $reopened = true;
            }
        } elseif ($senderType === ChatMessage::FROM_AGENT) {
            $c->increment('customer_unread');
            if (!$c->first_response_at) {
                $updates['first_response_at'] = now();
            }
        }
        $c->forceFill($updates)->save();
        $c->refresh();

        if ($reopened) {
            $this->autoAssign($c);
            $c->refresh();
        }

        $payload = $this->message($message->setRelation('sender', $sender));
        $this->realtime->toConversation($c, 'message.new', ['message' => $payload, 'conversation' => $this->conversation($c)]);
        $this->broadcastInbox($c);

        if ($senderType === ChatMessage::FROM_AGENT) {
            $this->realtime->toCustomer($c->user_id, 'unread.updated', ['unread' => $this->customerUnread($c->user_id), 'conversation_id' => $c->id]);
            $this->pushToCustomer($c, $message, $sender);
        }

        return $message;
    }

    public function sendCard(ChatConversation $c, User $sender, string $type, array $meta): ChatMessage
    {
        $senderType = (int) $sender->id === (int) $c->user_id ? ChatMessage::FROM_CUSTOMER : ChatMessage::FROM_AGENT;
        return $this->send($c, $sender, $senderType, null, null, null, $type, $meta);
    }

    public function systemMessage(ChatConversation $c, string $text): ChatMessage
    {
        $message = $c->messages()->create([
            'sender_type' => ChatMessage::FROM_SYSTEM,
            'type'        => 'system',
            'body'        => $text,
        ]);
        $this->realtime->toConversation($c, 'message.new', ['message' => $this->message($message), 'conversation' => $this->conversation($c->fresh(['agent']))]);
        return $message;
    }

    public function markRead(ChatConversation $c, string $readerType): int
    {
        $otherSide = $readerType === ChatMessage::FROM_CUSTOMER
            ? [ChatMessage::FROM_AGENT, ChatMessage::FROM_SYSTEM]
            : [ChatMessage::FROM_CUSTOMER];

        $lastId = (int) $c->messages()->max('id');
        $count = $c->messages()->whereIn('sender_type', $otherSide)->whereNull('read_at')->update(['read_at' => now()]);

        $c->forceFill($readerType === ChatMessage::FROM_CUSTOMER ? ['customer_unread' => 0] : ['agent_unread' => 0])->save();

        if ($count > 0) {
            $this->realtime->toConversation($c, 'message.read', ['reader' => $readerType, 'up_to_id' => $lastId, 'read_at' => now()->toIso8601String()]);
        }
        if ($readerType === ChatMessage::FROM_CUSTOMER) {
            $this->realtime->toCustomer($c->user_id, 'unread.updated', ['unread' => $this->customerUnread($c->user_id), 'conversation_id' => $c->id]);
        } else {
            $this->broadcastInbox($c);
        }
        return $count;
    }

    public function typing(ChatConversation $c, User $user, string $side, bool $isTyping, ?string $socketId = null): void
    {
        $this->realtime->toConversation($c, 'typing', [
            'side'      => $side,
            'user_id'   => $user->id,
            'name'      => $side === ChatMessage::FROM_AGENT ? $this->agentDisplayName($user) : $user->first_name,
            'is_typing' => $isTyping,
        ], $socketId);
    }

    public function assign(ChatConversation $c, User $agent, ?User $by = null): ChatConversation
    {
        $previous = $c->agent_id;
        $c->forceFill([
            'agent_id'    => $agent->id,
            'status'      => ChatConversation::ACTIVE,
            'assigned_at' => now(),
            'closed_at'   => null,
            'closed_by'   => null,
        ])->save();

        if ((int) $previous !== (int) $agent->id) {
            $who = $this->agentDisplayName($agent);
            $this->systemMessage($c, $previous ? "{$who} has taken over this chat." : "{$who} joined the chat.");
        }

        $c->load('agent');
        $this->realtime->toConversation($c, 'conversation.updated', ['conversation' => $this->conversation($c)]);
        $this->broadcastInbox($c);
        return $c;
    }

    public function close(ChatConversation $c, ?User $by = null, string $reason = 'resolved'): ChatConversation
    {
        if ($c->isClosed()) {
            return $c;
        }
        $c->forceFill([
            'status'    => ChatConversation::CLOSED,
            'closed_at' => now(),
            'closed_by' => $by?->id,
        ])->save();

        $text = match (true) {
            $reason === 'inactive'                      => 'Chat closed after a period of inactivity.',
            $by && (int) $by->id === (int) $c->user_id  => 'You ended the chat.',
            default                                     => 'This chat has been marked as resolved. You can still reply to reopen it.',
        };
        $this->systemMessage($c, $text);

        $c->load('agent');
        $this->realtime->toConversation($c, 'conversation.updated', ['conversation' => $this->conversation($c)]);
        $this->broadcastInbox($c);
        return $c;
    }

    public function rate(ChatConversation $c, int $rating, ?string $comment = null): ChatConversation
    {
        $c->forceFill([
            'rating'         => max(1, min(5, $rating)),
            'rating_comment' => $comment ? Str::limit(trim($comment), 500, '') : null,
        ])->save();
        $this->broadcastInbox($c);
        return $c;
    }

    /** Give a new chat to the least-busy online agent that has room. */
    public function autoAssign(ChatConversation $c): void
    {
        if (!config('chat.auto_assign') || $c->agent_id) {
            return;
        }

        $agents = ChatAgentStatus::available()->with('user')->get()
            ->filter(fn ($s) => $s->user && ($s->user->can('Chat with customers') || $s->user->can('Manage chat')))
            ->map(function ($s) {
                $s->load_count = ChatConversation::where('agent_id', $s->user_id)->where('status', ChatConversation::ACTIVE)->count();
                return $s;
            })
            ->filter(fn ($s) => $s->load_count < max(1, (int) $s->max_chats))
            ->sortBy(fn ($s) => [$s->load_count, optional($s->last_seen_at)->timestamp * -1]);

        $pick = $agents->first();
        if ($pick) {
            $this->assign($c, $pick->user);
        }
    }

    public function customerUnread(int $userId): int
    {
        return (int) ChatConversation::where('user_id', $userId)->sum('customer_unread');
    }

    public function agentUnread(User $agent): int
    {
        $q = ChatConversation::open();
        if (!$agent->can('Manage chat')) {
            $q->where(fn ($w) => $w->where('agent_id', $agent->id)->orWhereNull('agent_id'));
        }
        return (int) $q->sum('agent_unread');
    }

    public function waitingCount(): int
    {
        return ChatConversation::waiting()->count();
    }

    public function broadcastInbox(ChatConversation $c): void
    {
        $c->loadMissing(['agent', 'customer']);
        $this->realtime->toAgents('inbox.updated', [
            'conversation' => $this->conversation($c, true),
            'waiting'      => $this->waitingCount(),
        ]);
    }

    /** Close chats nobody touched for a while (run from the scheduler). */
    public function closeStale(): int
    {
        $hours = (int) config('chat.auto_close_hours', 24);
        $n = 0;
        ChatConversation::where('status', ChatConversation::ACTIVE)
            ->where('last_message_at', '<', now()->subHours($hours))
            ->each(function ($c) use (&$n) {
                $this->close($c, null, 'inactive');
                $n++;
            });
        return $n;
    }

    // ── Push notifications ───────────────────────────────────────────────────

    protected function pushToCustomer(ChatConversation $c, ChatMessage $m, ?User $agent): void
    {
        $customer = $c->customer;
        if (!$customer || empty($customer->fcm_token)) {
            return;
        }
        try {
            $title = ($agent ? $this->agentDisplayName($agent) : 'Gozak Mart Support') . ' replied';
            app(FcmService::class)->sendToUser($customer, $title, Str::limit($m->previewText(), 120), [
                'type'            => 'chat',
                'conversation_id' => (string) $c->id,
                'message_id'      => (string) $m->id,
                'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
            ], 'chat');
        } catch (\Throwable $e) {
            Log::info('Chat: FCM push skipped — ' . $e->getMessage());
        }
    }

    // ── Presenters ───────────────────────────────────────────────────────────

    /** Agents are shown by first name only to customers. */
    public function agentDisplayName(?User $agent): string
    {
        return $agent ? (trim((string) $agent->first_name) ?: 'Support') : 'Support';
    }

    public function avatar(?User $user): ?string
    {
        if (!$user || !$user->profile_image) {
            return null;
        }
        $p = $user->profile_image;
        return Str::startsWith($p, ['http://', 'https://']) ? $p : url(Storage::disk('public')->url(preg_replace('/^storage\//', '', $p)));
    }

    public function message(ChatMessage $m): array
    {
        $sender = $m->relationLoaded('sender') ? $m->sender : ($m->sender_id ? $m->sender()->first() : null);

        return [
            'id'              => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_type'     => $m->sender_type,
            'sender_id'       => $m->sender_id,
            'sender_name'     => match ($m->sender_type) {
                ChatMessage::FROM_AGENT    => $this->agentDisplayName($sender),
                ChatMessage::FROM_CUSTOMER => $sender?->first_name ?? 'Customer',
                default                    => 'Gozak Mart',
            },
            'sender_avatar'   => $m->sender_type === ChatMessage::FROM_SYSTEM ? null : $this->avatar($sender),
            'type'            => $m->type,
            'body'            => $m->body,
            'image_url'       => $m->imageUrl(),
            'meta'            => $m->meta,
            'client_id'       => $m->client_id,
            'read_at'         => $m->read_at?->toIso8601String(),
            'created_at'      => $m->created_at?->toIso8601String(),
        ];
    }

    public function conversation(ChatConversation $c, bool $forAgents = false): array
    {
        $data = [
            'id'              => $c->id,
            'status'          => $c->status,
            'topic'           => $c->topic,
            'topic_label'     => $c->topicLabel(),
            'order_id'        => $c->order_id,
            'product_id'      => $c->product_id,
            'preview'         => $c->preview,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'customer_unread' => (int) $c->customer_unread,
            'rating'          => $c->rating,
            'rating_comment'  => $c->rating_comment,
            'agent'           => $c->agent_id && $c->agent ? [
                'id'     => $c->agent->id,
                'name'   => $this->agentDisplayName($c->agent),
                'avatar' => $this->avatar($c->agent),
            ] : null,
            'created_at'      => $c->created_at?->toIso8601String(),
            'closed_at'       => $c->closed_at?->toIso8601String(),
        ];

        if ($forAgents) {
            $customer = $c->customer;
            $data['agent_unread'] = (int) $c->agent_unread;
            $data['agent_full_name'] = $c->agent?->full_name;
            $data['customer'] = $customer ? [
                'id'     => $customer->id,
                'name'   => $customer->full_name ?: ($customer->username ?? 'Customer'),
                'email'  => $customer->email,
                'phone'  => $customer->phone_number,
                'avatar' => $this->avatar($customer),
            ] : null;
            $data['wait_seconds'] = $c->status === ChatConversation::WAITING && $c->created_at
                ? now()->diffInSeconds($c->created_at, true) : null;
        }

        return $data;
    }

    public function orderCard(Order $order): array
    {
        $order->loadMissing('items');
        return [
            'order_id'   => $order->id,
            'reference'  => $order->invoice_number ?? strtoupper(substr($order->id, 0, 8)),
            'status'     => $order->status,
            'total'      => (float) $order->total_amount,
            'items'      => $order->items->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    public function productCard(Product $product): array
    {
        $thumb = $product->thumbnail
            ? url(Storage::url(preg_replace('/^storage\//', '', $product->thumbnail)))
            : null;
        return [
            'product_id' => $product->id,
            'title'      => $product->title,
            'price'      => (float) ($product->sale_price ?: $product->price),
            'thumbnail'  => $thumb,
        ];
    }
}
