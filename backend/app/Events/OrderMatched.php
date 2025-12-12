<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        $buyerChannel = $this->payload['buyer_id'] ?? null;
        $sellerChannel = $this->payload['seller_id'] ?? null;

        return array_filter([
            $buyerChannel ? new PrivateChannel('user.' . $buyerChannel) : null,
            $sellerChannel ? new PrivateChannel('user.' . $sellerChannel) : null,
        ]);
    }
}
