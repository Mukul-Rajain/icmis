<?php

namespace App\Events;

use App\Models\CourtCase;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseScheduled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $filerId,
        public readonly string $caseId,
        public readonly string $caseNumber,
        public readonly string $nextHearingDate,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("filer.{$this->filerId}")];
    }

    public function broadcastAs(): string
    {
        return 'case.scheduled';
    }

    public function broadcastWith(): array
    {
        return [
            'case_id'           => $this->caseId,
            'case_number'       => $this->caseNumber,
            'next_hearing_date' => $this->nextHearingDate,
            'message'           => "Your case {$this->caseNumber} has been approved and scheduled.",
        ];
    }
}
