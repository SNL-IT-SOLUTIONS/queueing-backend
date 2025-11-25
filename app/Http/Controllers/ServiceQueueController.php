<?php

namespace App\Http\Controllers;

use App\Models\ServiceQueue;
use App\Models\ServiceCounter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Events\ServiceQueueUpdated;
use App\Events\QueueUpdated;
use App\Events\QueueListUpdated;

class ServiceQueueController extends Controller
{
    public function listQueue($counterId = null)
    {
        $query = ServiceQueue::with('counter');

        if ($counterId) {
            $query->where('service_counter_id', $counterId);
        }

        $queue = $query->orderByDesc('is_priority')
            ->orderBy('created_at')
            ->get();

        // ONLY broadcast if counterId provided
        if ($counterId) {
            event(new QueueListUpdated($counterId, $queue));
        }

        return response()->json([
            'success' => true,
            'data' => $queue
        ]);
    }


    public function addPerson(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'is_priority'   => 'required|boolean',
        ]);

        // Force priority people into priority lane only
        if ($request->is_priority) {
            $counter = ServiceCounter::where('is_prioritylane', 1)
                ->where('status', 'Active')
                ->where('is_archived', 0)
                ->orderBy('queue_waiting', 'asc')
                ->first();
        } else {
            // Regular customers go to non-priority counters
            $counter = ServiceCounter::where('is_prioritylane', 0)
                ->where('status', 'Active')
                ->where('is_archived', 0)
                ->orderBy('queue_waiting', 'asc')
                ->first();
        }

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => $request->is_priority
                    ? 'No priority counters available.'
                    : 'No regular counters available.',
            ], 404);
        }

        // Get last queue number
        $lastQueueNumber = ServiceQueue::where('service_counter_id', $counter->id)
            ->latest('id')
            ->value('queue_number');

        $nextNumber = 1;
        if ($lastQueueNumber) {
            $lastNumber = (int) substr($lastQueueNumber, strlen($counter->prefix) + 1);
            $nextNumber = $lastNumber + 1;
        }

        // Create queue number
        $queueNumber = $counter->prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Create person
        $person = ServiceQueue::create([
            'service_counter_id' => $counter->id,
            'customer_name'      => $request->customer_name,
            'queue_number'       => $queueNumber,
            'is_priority'        => $request->is_priority,
            'status'             => 'waiting',
        ]);

        // Increment waiting count
        $counter->increment('queue_waiting');

        event(new ServiceQueueUpdated($person));
        $this->broadcastFullQueueList();



        return response()->json([
            'success' => true,
            'message' => 'Person added to queue successfully.',
            'queue_number' => $queueNumber,
            'assigned_counter' => $counter->counter_name,
            'data' => $person
        ], 201);
    }




    /**
     * Move a person to another counter
     */
    public function movePerson(Request $request, $queueId)
    {
        $request->validate([
            'target_counter_id' => 'required|exists:service_counters,id',
        ]);

        $person = ServiceQueue::find($queueId);

        if (!$person) {
            return response()->json([
                'success' => false,
                'message' => 'Queue item not found.'
            ], 404);
        }

        $targetCounter = ServiceCounter::find($request->target_counter_id);

        if (!$targetCounter || $targetCounter->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Target counter is not available.'
            ], 400);
        }

        $currentCounter = $person->counter;

        // Update queue counts
        if ($currentCounter) {
            $currentCounter->decrement('queue_waiting');
            event(new QueueUpdated($currentCounter));
        }

        $targetCounter->increment('queue_waiting');

        // Move the person
        $person->update([
            'service_counter_id' => $targetCounter->id
        ]);

        // Broadcast new counter + moved queue
        event(new QueueUpdated($targetCounter, $person));
        $this->broadcastFullQueueList();

        return response()->json([
            'success' => true,
            'message' => "Customer moved to counter {$targetCounter->counter_name}.",
            'data' => $person,
            'from_counter' => $currentCounter?->counter_name,
            'to_counter' => $targetCounter->counter_name,
        ]);
    }


    public function callNext($counterId)
    {
        $nextPerson = ServiceQueue::where('service_counter_id', $counterId)
            ->where('status', 'waiting')
            ->orderByDesc('is_priority')
            ->orderBy('created_at')
            ->first();

        if (!$nextPerson) {
            return response()->json([
                'success' => false,
                'message' => 'No waiting customers in the queue.'
            ], 404);
        }

        $nextPerson->update(['status' => 'serving']);

        event(new ServiceQueueUpdated($nextPerson));
        $this->broadcastFullQueueList();


        return response()->json([
            'success' => true,
            'message' => 'Next customer called.',
            'data' => $nextPerson
        ]);
    }

    public function recall($queueId)
    {
        $person = ServiceQueue::find($queueId);

        if (!$person || $person->status !== 'serving') {
            return response()->json([
                'success' => false,
                'message' => 'No currently serving customer found.'
            ], 404);
        }
        $this->broadcastFullQueueList();


        return response()->json([
            'success' => true,
            'message' => 'Customer recalled.',
            'data' => $person
        ]);
    }

    public function completeQueue($queueId)
    {
        $person = ServiceQueue::find($queueId);

        if (!$person || $person->status !== 'serving') {
            return response()->json([
                'success' => false,
                'message' => 'No currently serving customer found.'
            ], 404);
        }

        $person->update([
            'status' => 'completed',
            'served_at' => Carbon::now(),
        ]);

        event(new ServiceQueueUpdated($person));
        $this->broadcastFullQueueList();

        return response()->json([
            'success' => true,
            'message' => 'Customer service completed.',
            'data' => $person
        ]);
    }

    public function resetQueue($counterId)
    {
        $queues = ServiceQueue::where('service_counter_id', $counterId)->get();

        foreach ($queues as $queue) {
            event(new ServiceQueueUpdated($queue));
            $queue->delete();
        }

        $counter = ServiceCounter::find($counterId);
        if ($counter) {
            $counter->update([
                'queue_waiting' => 0,
                'queue_serving' => 0
            ]);
        }
        $this->broadcastFullQueueList();


        return response()->json([
            'success' => true,
            'message' => 'Queue reset successfully for this counter.'
        ]);
    }

    // Helpers
    private function broadcastFullQueueList()
    {
        $counters = ServiceCounter::with(['queues' => function ($q) {
            $q->orderByDesc('is_priority')->orderBy('created_at');
        }])
            ->where('status', 'Active')
            ->where('is_archived', 0)
            ->get();

        foreach ($counters as $counter) {
            event(new QueueListUpdated($counter->id, $counter->queues));
        }
    }
}
