<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Customer ↔ agent live chat (Phase 1).
 *
 *  chat_conversations   one support thread between a customer and (eventually) an agent
 *  chat_messages        the messages in a thread (text, image, order/product card, system note)
 *  chat_agent_statuses  online / away / offline presence of each agent
 *
 * Permissions:
 *  "Chat with customers"  see the inbox, take and answer chats
 *  "Manage chat"          see every chat, re-assign and close any chat
 *  (Customers only need to be signed in to use chat.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic', 40)->default('general');      // order | payment | delivery | refund | product | account | general
            $table->string('status', 20)->default('waiting');     // waiting | active | closed
            $table->string('order_id', 64)->nullable()->index();  // orders.id is a uuid string
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('preview', 255)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('customer_unread')->default(0);
            $table->unsignedInteger('agent_unread')->default(0);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('rating_comment', 500)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index(['agent_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('sender_type', 20);                    // customer | agent | system
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('text');          // text | image | order | product | system
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->json('meta')->nullable();
            $table->string('client_id', 64)->nullable();          // de-duplicates optimistic sends from the app
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'sender_type', 'read_at']);
        });

        Schema::create('chat_agent_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('offline');     // online | away | offline
            $table->unsignedSmallInteger('max_chats')->default(5);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $defs = [
            'Chat with customers' => 'Open the chat inbox, take waiting chats and reply to customers.',
            'Manage chat'         => 'See every chat, re-assign chats to other agents and close any chat.',
        ];
        $perms = [];
        foreach ($defs as $name => $description) {
            $perms[$name] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['title' => $name, 'description' => $description]
            );
        }

        foreach (['Super Admin', 'super-admin', 'Admin', 'admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo([$perms['Chat with customers'], $perms['Manage chat']]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_agent_statuses');

        if (Schema::hasTable('permissions')) {
            Permission::whereIn('name', ['Chat with customers', 'Manage chat'])->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
