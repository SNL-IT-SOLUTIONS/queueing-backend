<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueListUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $counterId;
    public $queues;

    public function __construct($counterId, $queues)
    {
        $this->counterId = $counterId;
        $this->queues = $queues;
    }

    public function broadcastOn()
    {
        return new Channel('service-counter.' . $this->counterId);
    }

    public function broadcastWith()
    {
        return [
            'counter_id' => $this->counterId,
            'queues' => $this->queues
        ];
    }
}
