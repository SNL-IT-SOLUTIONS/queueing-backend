<?php

namespace App\Events;

use App\Models\ServiceCounter;
use App\Models\ServiceQueue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $counter;
    public $queue; // optional

    public function __construct(ServiceCounter $counter, ServiceQueue $queue = null)
    {
        $this->counter = $counter;
        $this->queue = $queue;
    }

    public function broadcastOn()
    {
        return new Channel('service-counters');
    }

    public function broadcastWith()
    {
        $data = [
            'counter_id' => $this->counter->id,
            'counter_name' => $this->counter->counter_name,
            'queue_waiting' => $this->counter->queue_waiting,
            'queue_serving' => $this->counter->queue_serving,
        ];

        if ($this->queue) {
            $data['queue'] = [
                'id' => $this->queue->id,
                'customer_name' => $this->queue->customer_name,
                'queue_number' => $this->queue->queue_number,
                'status' => $this->queue->status,
                'is_priority' => $this->queue->is_priority,
                'served_at' => $this->queue->served_at,
            ];
        }

        return $data;
    }
}
