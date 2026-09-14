<?php

namespace App\Events\Offerings;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OfferingAvailabilityChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $offeringId,
        public int $remainingCapacity,
        public int $version,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("offerings.{$this->offeringId}"),
        ];
    }

    /** @return array{offeringId: int, remainingCapacity: int, version: int} */
    public function broadcastWith(): array
    {
        return [
            'offeringId' => $this->offeringId,
            'remainingCapacity' => $this->remainingCapacity,
            'version' => $this->version,
        ];
    }
}
