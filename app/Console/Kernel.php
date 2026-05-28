<?php

namespace App\Console;

use App\Jobs\GenerateDailyCauseLists;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Rescore all active cases daily at 12:30 AM
        // — ensures age, overdue status, and adjournment factors stay current
        $schedule->command('cases:rescore')
            ->dailyAt('00:30')
            ->onOneServer()
            ->runInBackground();

        // Generate cause lists for the next working day at 11:00 PM
        // — judges arrive in the morning with drafts ready to review
        $schedule->call(function () {
            $tomorrow = Carbon::tomorrow();
            // Skip weekends (configure based on local court calendar)
            if (! $tomorrow->isWeekend()) {
                GenerateDailyCauseLists::dispatch($tomorrow);
            }
        })->dailyAt('23:00')->name('generate-daily-cause-lists');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
