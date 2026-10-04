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

// Gozak Credit: statements, reminders, automatic debits + retries, late fees, suspensions.
Artisan::command('credit:run', function () {
    $r = app(\App\Services\Credit\CreditBillingService::class)->run();
    cache(['credit:last_cron' => now()->toDateTimeString()], now()->addDays(7));
    foreach ($r as $step => $n) {
        $this->line(str_pad($step, 18) . ' ' . (is_scalar($n) ? $n : json_encode($n)));
    }
})->purpose('Run the Gozak Credit billing cycle');

\Illuminate\Support\Facades\Schedule::command('credit:run')->hourlyAt(5)->withoutOverlapping(30);

// Orders: auto-confirm delivery N days after shipping (Orders → Delivery settings).
Artisan::command('orders:auto-confirm', function () {
    $n = app(\App\Services\OrderTrackingService::class)->autoConfirm();
    $this->info("Auto-confirmed {$n} order(s).");
})->purpose('Mark shipped orders as delivered after the auto-confirm wait');
\Illuminate\Support\Facades\Schedule::command('orders:auto-confirm')->hourlyAt(20)->withoutOverlapping(30);
