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
            ->map(fn($id) => new PrivateChannel("match-up.$id"))
            ->toArray();
    }

    public function broadcastWith()
    {
        return [
            'trade' => [
                'id' => $this->trade->id,
                'buy_order_id' => $this->trade->buy_order_id,
                'sell_order_id' => $this->trade->sell_order_id,
                'symbol' => $this->trade->symbol,
                'price' => $this->trade->price,
                'amount' => $this->trade->amount,
                'usd_volume' => $this->trade->usd_volume,
                'commission_usd' => $this->trade->commission_usd,
                'created_at' => $this->trade->created_at->toDateTimeString(),
            ],
        ];
    }

    public function broadcastAs()
    {
        return 'OrderMatched';
    }
}
