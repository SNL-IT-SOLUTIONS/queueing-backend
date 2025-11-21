<?php

namespace App\Http\Controllers;

use App\Models\ServiceCounter;
use Illuminate\Http\Request;
use App\Events\QueueUpdated;

class ServiceCounterController extends Controller
{
    /**
     * List all service counters
     */
    public function listCounters()
    {
        $counters = ServiceCounter::all();

        // Summary stats
        $totalCounters = $counters->count();
        $activeCounters = $counters->where('status', 'Active')->count();
        $totalWaiting = $counters->sum('queue_waiting');
        $totalServing = $counters->sum('queue_serving');

        foreach ($counters as $counter) {
            event(new QueueUpdated($counter));
        }



        return response()->json([
            'success' => true,
            'summary' => [
                'total_counters' => $totalCounters,
                'active_counters' => $activeCounters,
                'total_waiting' => $totalWaiting,
                'total_serving' => $totalServing,
            ],
            'data' => $counters
        ]);
    }

    /**
     * Get a single service counter by ID
     */
    public function getCounter($id)
    {
        $counter = ServiceCounter::find($id);

        foreach ($counters as $counter) {
            event(new QueueUpdated($counter));
        }

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Service counter not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $counter
        ]);
    }

    /**
     * Create a new service counter
     */
    public function createCounter(Request $request)
    {
        $request->validate([
            'counter_name' => 'required|string|max:100',
            'prefix'       => 'required|string|max:10|unique:service_counters,prefix',
            'status'       => 'nullable|in:Active,Inactive',
            'is_prioritylane' => 'nullable|boolean',
        ]);

        $counter = ServiceCounter::create([
            'counter_name' => $request->counter_name,
            'prefix'       => $request->prefix,
            'status'       => $request->status ?? 'Active',
        ]);

        event(new QueueUpdated($counter));

        return response()->json([
            'success' => true,
            'message' => 'Service counter created successfully.',
            'data'    => $counter
        ], 201);
    }

    /**
     * Update an existing service counter
     */
    public function updateCounter(Request $request, $id)
    {
        $counter = ServiceCounter::find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Service counter not found.'
            ], 404);
        }

        $request->validate([
            'counter_name' => 'sometimes|required|string|max:100',
            'prefix'       => 'sometimes|required|string|max:10|unique:service_counters,prefix,' . $id,
            'status'       => 'sometimes|in:Active,Inactive',
            'is_prioritylane' => 'sometimes|boolean',
        ]);

        $counter->update($request->only(['counter_name', 'prefix', 'status']));

        event(new QueueUpdated($counter));

        return response()->json([
            'success' => true,
            'message' => 'Service counter updated successfully.',
            'data'    => $counter
        ]);
    }

    /**
     * Delete a service counter
     */
    public function archiveCounter($id)
    {
        $counter = ServiceCounter::find($id);

        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Service counter not found.'
            ], 404);
        }

        $counter->is_archived = true;
        $counter->save();

        event(new QueueUpdated($counter));

        return response()->json([
            'success' => true,
            'message' => 'Service counter deleted successfully.'
        ]);
    }

    /**
     * Increment the waiting queue count
     */
    public function incrementQueueWaiting($id)
    {
        $counter = ServiceCounter::find($id);
        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Service counter not found.'
            ], 404);
        }

        $counter->increment('queue_waiting');
        $counter->refresh();

        event(new QueueUpdated($counter));

        return response()->json([
            'success' => true,
            'data' => $counter
        ]);
    }

    /**
     * Increment the serving queue count
     */
    public function incrementQueueServing($id)
    {
        $counter = ServiceCounter::find($id);
        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Service counter not found.'
            ], 404);
        }

        $counter->increment('queue_serving');
        $counter->refresh();

        event(new QueueUpdated($counter));

        return response()->json([
            'success' => true,
            'data' => $counter
        ]);
    }

    /**
     * Reset both waiting and serving queue counts to 0
     */
    public function resetQueue($id)
    {
        $counter = ServiceCounter::find($id);
        if (!$counter) {
            return response()->json([
                'success' => false,
                'message' => 'Service counter not found.'
            ], 404);
        }

        $counter->update([
            'queue_waiting' => 0,
            'queue_serving' => 0
        ]);

        event(new QueueUpdated($counter));

        return response()->json([
            'success' => true,
            'message' => 'Queue counts reset successfully.',
            'data' => $counter
        ]);
    }
}
