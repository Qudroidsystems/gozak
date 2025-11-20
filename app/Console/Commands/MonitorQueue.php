<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorQueue extends Command
{
    protected $signature = 'queue:monitor';
    protected $description = 'Monitor queue performance and stats';

    public function handle()
    {
        $stats = DB::table('jobs')
            ->selectRaw('queue, count(*) as pending, 
                         SUM(CASE WHEN attempts > 0 THEN 1 ELSE 0 END) as failed_attempts,
                         MAX(created_at) as oldest_job')
            ->groupBy('queue')
            ->get();

        $this->info('Queue Statistics:');
        foreach ($stats as $stat) {
            $this->line("Queue: {$stat->queue}");
            $this->line("  Pending: {$stat->pending}");
            $this->line("  Failed Attempts: {$stat->failed_attempts}");
            $this->line("  Oldest Job: {$stat->oldest_job}");
            $this->line('---');
        }

        $failedJobs = DB::table('failed_jobs')->count();
        $this->info("Failed Jobs: {$failedJobs}");
    }
}