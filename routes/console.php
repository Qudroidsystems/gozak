<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Live chat: close active chats nobody has written in for chat.auto_close_hours.
Artisan::command('chat:close-stale', function () {
    $n = app(\App\Services\Chat\ChatService::class)->closeStale();
    $this->info("Closed {$n} inactive chat(s).");
})->purpose('Close inactive live chats');

\Illuminate\Support\Facades\Schedule::command('chat:close-stale')->hourly()->withoutOverlapping();
