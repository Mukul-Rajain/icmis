<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CauseListUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $courtId,
        public readonly string $judgeId,
        public readonly string $date,
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast to the whole court channel — all filers with cases today listen here
        return [new Channel("court.{$this->courtId}")];
    }

    public function broadcastAs(): string
    {
        return 'causelist.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'judge_id' => $this->judgeId,
            'date'     => $this->date,
            'message'  => 'The cause list has been updated. Please refresh your schedule.',
        ];
    }
}
