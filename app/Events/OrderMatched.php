<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $trade;
    public $userIds;

    public function __construct(Trade $trade, array $userIds)
    {
        $this->trade = $trade;
        $this->userIds = $userIds;
    }

    public function broadcastOn()
    {
        return collect($this->userIds)
            ->map(fn($id) => new PrivateChannel("user.$id"))
            ->toArray();
    }

    public function broadcastWith()
    {
        return [
            'trade' => $this->trade->toArray(),
        ];
    }
}
