<?php

namespace App\Http\Controllers;

use App\Models\ServiceCounter;
use Illuminate\Http\Request;

class ServiceCounterController extends Controller
{
    /**
     * List all service counters
     */
    public function listCounters()
    {
        $counters = ServiceCounter::all();
        return response()->json([
            'success' => true,
            'data' => $counters
        ]);
    }

    /**
     * Get a single service counter by ID
     */
    public function getCounter($id)
    {
        $counter = ServiceCounter::find($id);

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
        ]);

        $counter = ServiceCounter::create([
            'counter_name' => $request->counter_name,
            'prefix'       => $request->prefix,
            'status'       => $request->status ?? 'Active',
        ]);

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
        ]);

        $counter->update($request->only(['counter_name', 'prefix', 'status']));

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

        return response()->json([
            'success' => true,
            'message' => 'Queue counts reset successfully.',
            'data' => $counter
        ]);
    }
}
