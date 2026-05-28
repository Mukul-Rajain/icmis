<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MentionRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $filerId,
        public readonly string $caseId,
        public readonly string $caseNumber,
        public readonly string $judgeNote,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("filer.{$this->filerId}")];
    }

    public function broadcastAs(): string
    {
        return 'mention.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'case_id'     => $this->caseId,
            'case_number' => $this->caseNumber,
            'judge_note'  => $this->judgeNote,
            'message'     => "Your mention request for case {$this->caseNumber} was not accepted. Your original schedule stands.",
        ];
    }
}
