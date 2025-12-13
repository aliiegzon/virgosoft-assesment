<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        $userId = $this->payload['user']['id'] ?? null;
        $symbol = $this->payload['order']['symbol'] ?? null;

        return array_filter([
            $userId ? new PrivateChannel('user.' . $userId) : null,
            $symbol ? new Channel('orderbook.' . $symbol) : null,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'OrderCancelled';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
