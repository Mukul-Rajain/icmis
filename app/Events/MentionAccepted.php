<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MentionAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $filerId,
        public readonly string $caseId,
        public readonly string $caseNumber,
        public readonly string $newHearingDate,
        public readonly ?string $judgeNote,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("filer.{$this->filerId}")];
    }

    public function broadcastAs(): string
    {
        return 'mention.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'case_id'          => $this->caseId,
            'case_number'      => $this->caseNumber,
            'new_hearing_date' => $this->newHearingDate,
            'judge_note'       => $this->judgeNote,
            'message'          => "Your urgent mention for case {$this->caseNumber} has been accepted. New date: {$this->newHearingDate}.",
        ];
    }
}
