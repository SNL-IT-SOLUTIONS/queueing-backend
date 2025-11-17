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
            'service_counter_id' => 'required|exists:service_counters,id',
            'customer_name' => 'required|string|max:100',
        ]);

        $counter = ServiceCounter::find($request->service_counter_id);

        // Generate next queue number based on counter prefix
        $lastQueueNumber = ServiceQueue::where('service_counter_id', $counter->id)
            ->latest('id')
            ->value('queue_number');

        $nextNumber = 1;
        if ($lastQueueNumber) {
            $lastNumber = (int)substr($lastQueueNumber, strlen($counter->prefix) + 1);
            $nextNumber = $lastNumber + 1;
        }

        $queueNumber = $counter->prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $person = ServiceQueue::create([
            'service_counter_id' => $counter->id,
            'customer_name' => $request->customer_name,
            'queue_number' => $queueNumber,
            'status' => 'waiting',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Person added to queue successfully.',
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
