<?php

namespace App\Console\Commands;

use App\Services\PriorityScorer;
use Illuminate\Console\Command;

/**
 * Runs daily at midnight via Laravel's scheduler.
 * Recomputes priority scores for all active cases so that age,
 * overdue status, and other time-based factors stay current.
 *
 * Register in app/Console/Kernel.php:
 *   $schedule->command('cases:rescore')->dailyAt('00:30');
 */
class RescoreAllCases extends Command
{
    protected $signature = 'cases:rescore';
    protected $description = 'Recompute priority scores for all active cases';

    public function handle(PriorityScorer $scorer): int
    {
        $this->info('Starting daily priority rescoring...');
        $start = microtime(true);

        $count = $scorer->rescoreAllActive();

        $duration = round(microtime(true) - $start, 2);
        $this->info("Rescored {$count} cases in {$duration}s.");

        return self::SUCCESS;
    }
}
