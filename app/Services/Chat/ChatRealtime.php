<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

/**
 * Thin wrapper around the Pusher SDK for chat.
 *
 * Channels
 *   private-chat.conversation.{id}   both sides of one chat     (message.new, message.read, typing, conversation.updated)
 *   private-chat.agents              every agent's inbox         (inbox.updated)
 *   private-chat.user.{userId}       one customer's devices      (unread.updated)
 *
 * Broadcasting never throws: if Pusher is down the message is still saved
 * and the clients catch up on their next fetch.
 */
class ChatRealtime
{
    public const AGENTS_CHANNEL = 'private-chat.agents';

    protected ?Pusher $pusher = null;

    public function client(): ?Pusher
    {
        if ($this->pusher) {
            return $this->pusher;
        }

        $cfg = config('broadcasting.connections.pusher');
        if (empty($cfg['key']) || empty($cfg['secret']) || empty($cfg['app_id'])) {
            return null;
        }

        try {
            return $this->pusher = new Pusher($cfg['key'], $cfg['secret'], $cfg['app_id'], $cfg['options'] ?? []);
        } catch (\Throwable $e) {
            Log::warning('Chat: Pusher init failed — ' . $e->getMessage());
            return null;
        }
    }

    public function enabled(): bool
    {
        return $this->client() !== null;
    }

    /** Public settings the apps need to connect. */
    public function clientConfig(): array
    {
        return [
            'enabled' => $this->enabled(),
            'key'     => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        ];
    }

    public function trigger(string|array $channels, string $event, array $data, ?string $exceptSocketId = null): void
    {
        $client = $this->client();
        if (!$client) {
            return;
        }
        try {
            $params = $exceptSocketId ? ['socket_id' => $exceptSocketId] : [];
            $client->trigger($channels, $event, $data, $params);
        } catch (\Throwable $e) {
            Log::warning("Chat: Pusher trigger {$event} failed — " . $e->getMessage());
        }
    }

    public function toConversation(ChatConversation $c, string $event, array $data, ?string $exceptSocketId = null): void
    {
        $this->trigger($c->channelName(), $event, $data, $exceptSocketId);
    }

    public function toAgents(string $event, array $data): void
    {
        $this->trigger(self::AGENTS_CHANNEL, $event, $data);
    }

    public function toCustomer(int $userId, string $event, array $data): void
    {
        $this->trigger('private-chat.user.' . $userId, $event, $data);
    }

    /**
     * Sign a private channel subscription. Returns the JSON string Pusher
     * expects, or null when the user may not join the channel.
     */
    public function authorize(User $user, string $channel, string $socketId): ?string
    {
        if (!$this->mayJoin($user, $channel)) {
            return null;
        }
        $client = $this->client();
        if (!$client) {
            return null;
        }
        try {
            return $client->authorizeChannel($channel, $socketId);
        } catch (\Throwable $e) {
            Log::warning('Chat: Pusher auth failed — ' . $e->getMessage());
            return null;
        }
    }

    public function mayJoin(User $user, string $channel): bool
    {
        if ($channel === self::AGENTS_CHANNEL) {
            return $user->can('Chat with customers') || $user->can('Manage chat');
        }

        if (preg_match('/^private-chat\.user\.(\d+)$/', $channel, $m)) {
            return (int) $m[1] === (int) $user->id;
        }

        if (preg_match('/^private-chat\.conversation\.(\d+)$/', $channel, $m)) {
            $conversation = ChatConversation::find((int) $m[1]);
            return $conversation?->canBeAccessedBy($user) ?? false;
        }

        return false;
    }
}
