<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** The app's notification centre: /api/notifications */
class APIUserNotificationController extends Controller
{
    public function index(Request $request)
    {
        $rows = UserNotification::where('user_id', Auth::id())
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->latest('id')
            ->paginate(min(50, max(10, (int) $request->input('per_page', 20))));

        return response()->json([
            'success' => true,
            'data'    => collect($rows->items())->map(fn (UserNotification $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'data'       => $n->data ?? (object) [],
                'read'       => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values(),
            'meta'    => [
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
                'unread'       => $this->unreadCount(),
            ],
        ]);
    }

    public function unread()
    {
        return response()->json(['success' => true, 'unread' => $this->unreadCount()]);
    }

    public function read($id)
    {
        UserNotification::where('user_id', Auth::id())->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread' => $this->unreadCount()]);
    }

    public function readAll()
    {
        UserNotification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread' => 0]);
    }

    private function unreadCount(): int
    {
        return UserNotification::where('user_id', Auth::id())->whereNull('read_at')->count();
    }
}
