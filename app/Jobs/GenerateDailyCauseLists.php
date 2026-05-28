<?php

namespace App\Jobs;

use App\Models\Judge;
use App\Services\CauseListGenerator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched nightly to generate the next day's cause list for every
 * available judge. Each judge gets a draft list which they can review
 * and publish in the morning.
 */
class GenerateDailyCauseLists implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Carbon $targetDate) {}

    public function handle(CauseListGenerator $generator): void
    {
        Log::info("Starting daily cause list generation for {$this->targetDate->toDateString()}");

        $judges = Judge::with('user')
            ->where('is_available', true)
            ->get();

        foreach ($judges as $judge) {
            if (! $judge->isAvailableOn($this->targetDate)) {
                Log::info("Skipping unavailable judge: {$judge->user->name}");
                continue;
            }

            try {
                $result = $generator->generate($judge, $this->targetDate);
                Log::info(sprintf(
                    'Generated cause list for %s — %d cases, %d conflicts',
                    $judge->user->name,
                    $result['cause_list']->total_cases,
                    count($result['conflicts'])
                ));
            } catch (\Throwable $e) {
                Log::error("Failed to generate cause list for judge {$judge->id}: " . $e->getMessage());
            }
        }
    }
}
