<?php

namespace App\Http\Controllers;

use App\Models\ServiceQueue;
use App\Models\ServiceCounter;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ServiceQueueController extends Controller
{
    /**
     * List all queue entries, optionally filtered by counter
     */
    public function listQueue($counterId = null)
    {
        $query = ServiceQueue::with('counter');

        if ($counterId) {
            $query->where('service_counter_id', $counterId);
        }

        $queue = $query->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $queue
        ]);
    }

    /**
     * Add a new person to the queue
     */
    public function addPerson(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'is_priority'   => 'required|boolean', // 0 or 1
        ]);

        // ---------------------------------------------------------
        // AUTO-SELECT COUNTER BASED ON PRIORITY
        // ---------------------------------------------------------
        $counter = ServiceCounter::where('is_prioritylane', $request->is_priority)
            ->where('status', 'Active')
            ->where('is_archived', 0)
            ->orderBy('queue_waiting', 'asc') // pick counter with shortest waiting queue
            ->first();

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => $request->is_priority
                    ? 'No priority counters available.'
                    : 'No regular counters available.',
            ], 404);
        }

        // ---------------------------------------------------------
        // GENERATE QUEUE NUMBER
        // ---------------------------------------------------------
        $lastQueueNumber = ServiceQueue::where('service_counter_id', $counter->id)
            ->latest('id')
            ->value('queue_number');

        $nextNumber = 1;

        if ($lastQueueNumber) {
            $lastNumber = (int)substr($lastQueueNumber, strlen($counter->prefix) + 1);
            $nextNumber = $lastNumber + 1;
        }

        $queueNumber = $counter->prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // ---------------------------------------------------------
        // INSERT PERSON INTO QUEUE
        // ---------------------------------------------------------
        $person = ServiceQueue::create([
            'service_counter_id' => $counter->id,
            'customer_name'      => $request->customer_name,
            'queue_number'       => $queueNumber,
            'is_priority'        => $request->is_priority,
            'status'             => 'waiting',
        ]);

        // ---------------------------------------------------------
        // UPDATE COUNTER'S WAITING QUEUE COUNT
        // ---------------------------------------------------------
        $counter->increment('queue_waiting');

        // ---------------------------------------------------------
        // SEND RESPONSE WITH QUEUE NUMBER INCLUDED
        // ---------------------------------------------------------
        return response()->json([
            'success' => true,
            'message' => 'Person added to queue successfully.',
            'queue_number' => $queueNumber,    // ← HERE
            'assigned_counter' => $counter->counter_name,
            'data' => $person
        ], 201);
    }

    /**
     * Call the next person in the queue
     */
    public function callNext($counterId)
    {
        $nextPerson = ServiceQueue::where('service_counter_id', $counterId)
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->first();

        if (!$nextPerson) {
            return response()->json([
                'success' => false,
                'message' => 'No waiting customers in the queue.'
            ], 404);
        }

        $nextPerson->update(['status' => 'serving']);

        return response()->json([
            'success' => true,
            'message' => 'Next customer called.',
            'data' => $nextPerson
        ]);
    }

    /**
     * Recall the currently serving person
     */
    public function recall($queueId)
    {
        $person = ServiceQueue::find($queueId);

        if (!$person || $person->status !== 'serving') {
            return response()->json([
                'success' => false,
                'message' => 'No currently serving customer found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer recalled.',
            'data' => $person
        ]);
    }

    /**
     * Mark a person as completed
     */
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

        return response()->json([
            'success' => true,
            'message' => 'Customer service completed.',
            'data' => $person
        ]);
    }

    /**
     * Reset the queue for a counter
     */
    public function resetQueue($counterId)
    {
        ServiceQueue::where('service_counter_id', $counterId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queue reset successfully for this counter.'
        ]);
    }
}
