<?php

namespace App\Events;

use App\Models\ServiceCounter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $counter;

    public function __construct(ServiceCounter $counter)
    {
        $this->counter = $counter;
    }

    public function broadcastOn()
    {
        return new Channel('service-counters');
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->counter->id,
            'counter_name' => $this->counter->counter_name,
            'queue_waiting' => $this->counter->queue_waiting,
            'queue_serving' => $this->counter->queue_serving,
        ];
    }
}
