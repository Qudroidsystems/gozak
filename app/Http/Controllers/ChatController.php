<?php

namespace App\Http\Controllers;

use App\Models\ChatAgentStatus;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin chat inbox (/admin/chat).
 *  "Chat with customers" → waiting queue + own chats
 *  "Manage chat"         → every chat, re-assign to other agents
 */
class ChatController extends Controller
{
    public function __construct(protected ChatService $chat)
    {
        $this->middleware('permission:Chat with customers|Manage chat');
        $this->middleware('permission:Manage chat', ['only' => ['assign']]);
    }

    public function index(Request $request)
    {
        $me = $request->user();
        $status = ChatAgentStatus::for($me);

        return view('chat.index', [
            'agentStatus' => $status->status,
            'canManage'   => $me->can('Manage chat'),
            'pusher'      => $this->chat->realtime()->clientConfig(),
            'topics'      => config('chat.topics'),
            'openId'      => $request->integer('c') ?: null,
        ]);
    }

    /** GET conversations?tab=waiting|mine|open|closed|all&q= */
    public function conversations(Request $request): JsonResponse
    {
        $me  = $request->user();
        $tab = $request->input('tab', 'mine');
        $q   = ChatConversation::query()->with(['customer', 'agent']);

        switch ($tab) {
            case 'waiting':
                $q->waiting();
                break;
            case 'open':
                $q->open();
                if (!$me->can('Manage chat')) {
                    $q->where(fn ($w) => $w->where('agent_id', $me->id)->orWhereNull('agent_id'));
                }
                break;
            case 'closed':
                $q->where('status', ChatConversation::CLOSED);
                if (!$me->can('Manage chat')) {
                    $q->where('agent_id', $me->id);
                }
                break;
            case 'all':
                if (!$me->can('Manage chat')) {
                    $q->where(fn ($w) => $w->where('agent_id', $me->id)->orWhereNull('agent_id'));
                }
                break;
            default: // mine
                $q->where('agent_id', $me->id)->open();
        }

        if ($term = trim((string) $request->input('q'))) {
            $q->where(function ($w) use ($term) {
                $w->where('preview', 'like', "%{$term}%")
                  ->orWhere('order_id', 'like', "{$term}%")
                  ->orWhereHas('customer', fn ($u) => $u->where('first_name', 'like', "%{$term}%")
                      ->orWhere('last_name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('phone_number', 'like', "%{$term}%"));
            });
        }

        $tab === 'waiting'
            ? $q->orderBy('created_at')
            : $q->orderByDesc('last_message_at');

        $page = $q->paginate(30);

        return response()->json([
            'data'   => collect($page->items())->map(fn ($c) => $this->chat->conversation($c, true))->values(),
            'more'   => $page->hasMorePages(),
            'counts' => $this->counts($me),
        ]);
    }

    /** GET conversations/{conversation} — thread + customer panel. */
    public function show(Request $request, ChatConversation $conversation): JsonResponse
    {
        $conversation->load(['customer', 'agent']);
        $messages = $conversation->messages()->with('sender:id,first_name,last_name,profile_image')
            ->orderByDesc('id')->limit(41)->get();
        $hasMore = $messages->count() > 40;

        return response()->json([
            'conversation' => $this->chat->conversation($conversation, true),
            'messages'     => $messages->take(40)->reverse()->values()->map(fn ($m) => $this->chat->message($m)),
            'has_more'     => $hasMore,
            'customer'     => $this->customerPanel($conversation),
            'can_reply'    => $this->canReply($request->user(), $conversation),
        ]);
    }

    /** GET conversations/{conversation}/messages?before_id=|after_id= */
    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $q = $conversation->messages()->with('sender:id,first_name,last_name,profile_image');
        if ($request->filled('after_id')) {
            $rows = $q->where('id', '>', (int) $request->after_id)->orderBy('id')->limit(200)->get();
            return response()->json(['messages' => $rows->map(fn ($m) => $this->chat->message($m)), 'has_more' => false]);
        }
        if ($request->filled('before_id')) {
            $q->where('id', '<', (int) $request->before_id);
        }
        $rows = $q->orderByDesc('id')->limit(41)->get();
        return response()->json([
            'messages' => $rows->take(40)->reverse()->values()->map(fn ($m) => $this->chat->message($m)),
            'has_more' => $rows->count() > 40,
        ]);
    }

    public function send(Request $request, ChatConversation $conversation): JsonResponse
    {
        abort_unless($this->canReply($request->user(), $conversation), 403, 'This chat belongs to another agent.');

        $request->validate([
            'body'      => 'nullable|string|max:4000|required_without:image',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:' . (int) config('chat.max_image_kb', 6144),
            'client_id' => 'nullable|string|max:64',
        ]);

        $m = $this->chat->send($conversation, $request->user(), ChatMessage::FROM_AGENT,
            $request->input('body'), $request->file('image'), $request->input('client_id'));

        return response()->json(['message' => $this->chat->message($m->load('sender')), 'conversation' => $this->chat->conversation($conversation->fresh(['customer', 'agent']), true)]);
    }

    /** Share one of the customer's orders into the chat as a card. */
    public function sendOrder(Request $request, ChatConversation $conversation): JsonResponse
    {
        abort_unless($this->canReply($request->user(), $conversation), 403);
        $order = Order::where('id', $request->input('order_id'))->where('user_id', $conversation->user_id)->firstOrFail();
        $m = $this->chat->sendCard($conversation, $request->user(), 'order', $this->chat->orderCard($order));
        return response()->json(['message' => $this->chat->message($m->load('sender'))]);
    }

    public function take(Request $request, ChatConversation $conversation): JsonResponse
    {
        $me = $request->user();
        $result = DB::transaction(function () use ($conversation, $me) {
            $c = ChatConversation::lockForUpdate()->findOrFail($conversation->id);
            if ($c->agent_id && (int) $c->agent_id !== (int) $me->id && $c->isOpen() && !$me->can('Manage chat')) {
                return null; // somebody else got it first
            }
            return $this->chat->assign($c, $me, $me);
        });

        if (!$result) {
            return response()->json(['message' => 'Another agent has already taken this chat.'], 409);
        }
        return response()->json(['conversation' => $this->chat->conversation($result->load('customer'), true)]);
    }

    public function assign(Request $request, ChatConversation $conversation): JsonResponse
    {
        $agent = User::findOrFail($request->input('agent_id'));
        abort_unless($agent->can('Chat with customers') || $agent->can('Manage chat'), 422, 'That user is not a chat agent.');
        $c = $this->chat->assign($conversation, $agent, $request->user());
        return response()->json(['conversation' => $this->chat->conversation($c->load('customer'), true)]);
    }

    public function close(Request $request, ChatConversation $conversation): JsonResponse
    {
        abort_unless($this->canReply($request->user(), $conversation), 403);
        $c = $this->chat->close($conversation, $request->user());
        return response()->json(['conversation' => $this->chat->conversation($c->load('customer'), true)]);
    }

    public function read(Request $request, ChatConversation $conversation): JsonResponse
    {
        if ($this->canReply($request->user(), $conversation)) {
            $this->chat->markRead($conversation, ChatMessage::FROM_AGENT);
        }
        return response()->json(['counts' => $this->counts($request->user())]);
    }

    public function typing(Request $request, ChatConversation $conversation): JsonResponse
    {
        if ($this->canReply($request->user(), $conversation)) {
            $this->chat->typing($conversation, $request->user(), ChatMessage::FROM_AGENT, $request->boolean('is_typing', true), $request->input('socket_id'));
        }
        return response()->json(['ok' => true]);
    }

    /** POST status {status: online|away|offline} — also used as the heartbeat. */
    public function presence(Request $request): JsonResponse
    {
        $request->validate(['status' => 'nullable|in:online,away,offline']);
        $s = ChatAgentStatus::for($request->user());
        if ($request->filled('status')) {
            $s->status = $request->input('status');
        }
        $s->last_seen_at = now();
        $s->save();

        // Coming online → pick up whatever is waiting.
        if ($s->status === ChatAgentStatus::ONLINE && $request->filled('status')) {
            ChatConversation::waiting()->orderBy('created_at')->limit(max(1, $s->max_chats))->get()
                ->each(fn ($c) => $this->chat->autoAssign($c));
        }

        return response()->json(['status' => $s->status, 'counts' => $this->counts($request->user())]);
    }

    public function agents(): JsonResponse
    {
        $agents = User::permission(['Chat with customers', 'Manage chat'])->get(['id', 'first_name', 'last_name', 'email']);
        $status = ChatAgentStatus::whereIn('user_id', $agents->pluck('id'))->get()->keyBy('user_id');
        $load = ChatConversation::where('status', ChatConversation::ACTIVE)
            ->selectRaw('agent_id, count(*) n')->groupBy('agent_id')->pluck('n', 'agent_id');

        return response()->json(['agents' => $agents->map(fn ($u) => [
            'id'     => $u->id,
            'name'   => $u->full_name ?: $u->email,
            'status' => isset($status[$u->id]) && $status[$u->id]->isAvailable() ? $status[$u->id]->status : 'offline',
            'chats'  => (int) ($load[$u->id] ?? 0),
        ])->values()]);
    }

    /** GET summary — sidebar badge. */
    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->counts($request->user()));
    }

    /** POST pusher/auth — web session auth for private chat channels. */
    public function pusherAuth(Request $request)
    {
        $signed = $this->chat->realtime()->authorize($request->user(), (string) $request->input('channel_name'), (string) $request->input('socket_id'));
        abort_unless($signed, 403);
        return response($signed, 200, ['Content-Type' => 'application/json']);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    protected function canReply(User $me, ChatConversation $c): bool
    {
        if ($me->can('Manage chat')) {
            return true;
        }
        return !$c->agent_id || (int) $c->agent_id === (int) $me->id || $c->isClosed();
    }

    protected function counts(User $me): array
    {
        return [
            'waiting' => $this->chat->waitingCount(),
            'mine'    => ChatConversation::where('agent_id', $me->id)->open()->count(),
            'unread'  => $this->chat->agentUnread($me),
        ];
    }

    protected function customerPanel(ChatConversation $c): ?array
    {
        $u = $c->customer;
        if (!$u) {
            return null;
        }
        $orders = Order::where('user_id', $u->id);
        $recent = (clone $orders)->latest()->limit(6)->get(['id', 'invoice_number', 'status', 'total_amount', 'created_at']);

        return [
            'id'           => $u->id,
            'name'         => $u->full_name ?: 'Customer',
            'email'        => $u->email,
            'phone'        => $u->phone_number,
            'avatar'       => $this->chat->avatar($u),
            'joined'       => $u->created_at?->format('M Y'),
            'orders_count' => (clone $orders)->count(),
            'total_spent'  => (float) (clone $orders)->whereNotIn('status', ['cancelled', 'failed', 'pending'])->sum('total_amount'),
            'chats_count'  => ChatConversation::where('user_id', $u->id)->count(),
            'avg_rating'   => round((float) ChatConversation::where('user_id', $u->id)->whereNotNull('rating')->avg('rating'), 1) ?: null,
            'customer_url' => \Route::has('customers.show') ? route('customers.show', $u->id) : null,
            'context_order'=> $c->order_id,
            'recent_orders'=> $recent->map(fn ($o) => [
                'id'        => $o->id,
                'reference' => $o->invoice_number ?? strtoupper(substr($o->id, 0, 8)),
                'status'    => $o->status,
                'total'     => (float) $o->total_amount,
                'date'      => $o->created_at?->format('d M Y'),
                'url'       => route('adminorders.show', $o->id),
            ]),
        ];
    }
}
