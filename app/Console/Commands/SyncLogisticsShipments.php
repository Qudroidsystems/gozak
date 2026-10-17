<?php

namespace App\Console\Commands;

use App\Models\Logistics\OrderShipment;
use App\Services\Logistics\LogisticsService;
use Illuminate\Console\Command;

/** Backup for missed webhooks: ask the marketplace where each active courier delivery is. */
class SyncLogisticsShipments extends Command
{
    protected $signature = 'logistics:sync {--limit=100}';
    protected $description = 'Refresh the status of marketplace courier deliveries';

    public function handle(LogisticsService $logistics): int
    {
        if (!$logistics->settings()->isLive()) {
            return self::SUCCESS;
        }
        $n = 0;
        OrderShipment::whereIn('status', OrderShipment::ACTIVE)
            ->where(fn ($q) => $q->whereNull('synced_at')->orWhere('synced_at', '<=', now()->subMinutes(10)))
            ->orderBy('synced_at')->limit((int) $this->option('limit'))->get()
            ->each(function ($sh) use ($logistics, &$n) {
                try {
                    $logistics->sync($sh);
                    $n++;
                } catch (\Throwable $e) {
                    $sh->forceFill(['synced_at' => now(), 'last_error' => mb_substr('Sync: ' . $e->getMessage(), 0, 500)])->save();
                }
            });
        $this->info("{$n} courier deliveries refreshed.");
        return self::SUCCESS;
    }
}
