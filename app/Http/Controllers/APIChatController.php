<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer side of live chat (mobile app). All routes: auth:sanctum.
 */
class APIChatController extends Controller
{
    public function __construct(protected ChatService $chat)
    {
    }

    /** GET /chat/status — are agents online + Pusher settings for the app. */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $open = ChatConversation::where('user_id', $user->id)->open()->with('agent')->latest('id')->first();

        return response()->json([
            'success' => true,
            'data'    => $this->chat->status() + [
                'unread'            => $this->chat->customerUnread($user->id),
                'open_conversation' => $open ? $this->chat->conversation($open) : null,
                'user_channel'      => 'private-chat.user.' . $user->id,
            ],
        ]);
    }

    /** GET /chat/unread-count */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['unread' => $this->chat->customerUnread($request->user()->id)]]);
    }

    /** GET /chat/conversations — the customer's chat history. */
    public function index(Request $request): JsonResponse
    {
        $page = ChatConversation::where('user_id', $request->user()->id)
            ->with('agent')
            ->orderByRaw("CASE WHEN status = 'closed' THEN 1 ELSE 0 END")
            ->orderByDesc('last_message_at')
            ->paginate(min(50, (int) $request->input('per_page', 20)));

        return response()->json([
            'success' => true,
            'data'    => collect($page->items())->map(fn ($c) => $this->chat->conversation($c))->values(),
            'meta'    => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /** POST /chat/conversations — start a chat (or continue the open one). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'topic'      => 'nullable|string|in:' . implode(',', array_keys(config('chat.topics'))),
            'order_id'   => 'nullable|string|max:64',
            'product_id' => 'nullable|integer',
            'message'    => 'nullable|string|max:4000',
        ]);

        $c = $this->chat->startOrResume(
            $request->user(),
            $data['topic'] ?? 'general',
            $data['order_id'] ?? null,
            isset($data['product_id']) ? (int) $data['product_id'] : null,
            $data['message'] ?? null,
        );

        return response()->json(['success' => true, 'data' => $this->chat->conversation($c)], 201);
    }

    /** GET /chat/conversations/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);
        return response()->json(['success' => true, 'data' => $this->chat->conversation($c->load('agent'))]);
    }

    /**
     * GET /chat/conversations/{id}/messages
     *   ?before_id=  older page (scrolling up)
     *   ?after_id=   anything newer (catch-up after reconnect)
     */
    public function messages(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);
        $limit = max(1, min(100, (int) $request->input('limit', 30)));

        $q = $c->messages()->with('sender:id,first_name,last_name,profile_image');

        if ($request->filled('after_id')) {
            $rows = $q->where('id', '>', (int) $request->after_id)->orderBy('id')->limit(200)->get();
            $hasMore = false;
        } else {
            if ($request->filled('before_id')) {
                $q->where('id', '<', (int) $request->before_id);
            }
            $rows = $q->orderByDesc('id')->limit($limit + 1)->get();
            $hasMore = $rows->count() > $limit;
            $rows = $rows->take($limit)->reverse()->values();
        }

        return response()->json([
            'success'  => true,
            'data'     => $rows->map(fn ($m) => $this->chat->message($m))->values(),
            'has_more' => $hasMore,
            'conversation' => $this->chat->conversation($c->load('agent')),
        ]);
    }

    /** POST /chat/conversations/{id}/messages — text and/or one photo (multipart "image"). */
    public function send(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);

        $request->validate([
            'body'      => 'nullable|string|max:4000|required_without:image',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,heic|max:' . (int) config('chat.max_image_kb', 6144),
            'client_id' => 'nullable|string|max:64',
        ]);

        $message = $this->chat->send(
            $c,
            $request->user(),
            ChatMessage::FROM_CUSTOMER,
            $request->input('body'),
            $request->file('image'),
            $request->input('client_id'),
        );

        return response()->json(['success' => true, 'data' => $this->chat->message($message->load('sender'))], 201);
    }

    /** POST /chat/conversations/{id}/read */
    public function read(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);
        $this->chat->markRead($c, ChatMessage::FROM_CUSTOMER);
        return response()->json(['success' => true, 'data' => ['unread' => $this->chat->customerUnread($request->user()->id)]]);
    }

    /** POST /chat/conversations/{id}/typing  {is_typing, socket_id?} */
    public function typing(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);
        $this->chat->typing($c, $request->user(), ChatMessage::FROM_CUSTOMER, $request->boolean('is_typing', true), $request->input('socket_id'));
        return response()->json(['success' => true]);
    }

    /** POST /chat/conversations/{id}/close — customer ends the chat. */
    public function close(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);
        $this->chat->close($c, $request->user());
        return response()->json(['success' => true, 'data' => $this->chat->conversation($c->fresh('agent'))]);
    }

    /** POST /chat/conversations/{id}/rate  {rating 1-5, comment?} */
    public function rate(Request $request, int $id): JsonResponse
    {
        $c = $this->owned($request, $id);
        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);
        $this->chat->rate($c, (int) $data['rating'], $data['comment'] ?? null);
        return response()->json(['success' => true, 'message' => 'Thanks for your feedback!', 'data' => $this->chat->conversation($c->fresh('agent'))]);
    }

    /**
     * POST /chat/pusher/auth  {socket_id, channel_name}
     * Signs private channel subscriptions for the app (Bearer token).
     */
    public function pusherAuth(Request $request): JsonResponse
    {
        $request->validate([
            'socket_id'    => 'required|string|max:100',
            'channel_name' => 'required|string|max:200',
        ]);

        $signed = $this->chat->realtime()->authorize($request->user(), $request->channel_name, $request->socket_id);
        if (!$signed) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        return response()->json(json_decode($signed, true));
    }

    protected function owned(Request $request, int $id): ChatConversation
    {
        return ChatConversation::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
    }
}
