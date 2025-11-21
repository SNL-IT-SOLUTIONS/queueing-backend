<?php

namespace App\Events;

use App\Models\ServiceQueue;
use Illuminate\Broadcasting\Channel; // public channel
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceQueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue;

    public function __construct(ServiceQueue $queue)
    {
        $this->queue = $queue;
    }

    public function broadcastOn()
    {
        return new Channel('service-counter.' . $this->queue->service_counter_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->queue->id,
            'customer_name' => $this->queue->customer_name,
            'queue_number' => $this->queue->queue_number,
            'status' => $this->queue->status,
            'is_priority' => $this->queue->is_priority,
            'counter_id' => $this->queue->service_counter_id,
            'served_at' => $this->queue->served_at,
        ];
    }
}
