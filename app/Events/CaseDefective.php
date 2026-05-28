<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseDefective implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $filerId,
        public readonly string $caseId,
        public readonly string $caseNumber,
        public readonly array  $defectFlags,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("filer.{$this->filerId}")];
    }

    public function broadcastAs(): string
    {
        return 'case.defective';
    }

    public function broadcastWith(): array
    {
        return [
            'case_id'      => $this->caseId,
            'case_number'  => $this->caseNumber,
            'defect_flags' => $this->defectFlags,
            'message'      => "Case {$this->caseNumber} has been returned with defects. Please review your inbox.",
        ];
    }
}
