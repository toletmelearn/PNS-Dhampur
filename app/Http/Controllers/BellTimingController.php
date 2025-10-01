<?php

namespace App\Http\Controllers;

use App\Models\BellTiming;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BellTimingController extends Controller
{
    /**
     * Display a listing of bell timings
     */
    public function index(Request $request): JsonResponse
    {
        $season = $request->get('season', BellTiming::getCurrentSeason());
        
        $bellTimings = BellTiming::where('season', $season)
                                ->orderBy('order')
                                ->orderBy('time')
                                ->get();

        return response()->json([
            'success' => true,
            'data' => $bellTimings,
            'current_season' => BellTiming::getCurrentSeason()
        ]);
    }

    /**
     * Store a newly created bell timing
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'time' => 'required|date_format:H:i',
            'season' => ['required', Rule::in(['winter', 'summer'])],
            'type' => ['required', Rule::in(['start', 'end', 'break'])],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        $bellTiming = BellTiming::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bell timing created successfully',
            'data' => $bellTiming
        ], 201);
    }

    /**
     * Display the specified bell timing
     */
    public function show(BellTiming $bellTiming): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $bellTiming
        ]);
    }

    /**
     * Update the specified bell timing
     */
    public function update(Request $request, BellTiming $bellTiming): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'time' => 'sometimes|required|date_format:H:i',
            'season' => ['sometimes', 'required', Rule::in(['winter', 'summer'])],
            'type' => ['sometimes', 'required', Rule::in(['start', 'end', 'break'])],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        $bellTiming->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bell timing updated successfully',
            'data' => $bellTiming
        ]);
    }

    /**
     * Remove the specified bell timing
     */
    public function destroy(BellTiming $bellTiming): JsonResponse
    {
        $bellTiming->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bell timing deleted successfully'
        ]);
    }

    /**
     * Get current schedule for today
     */
    public function getCurrentSchedule(): JsonResponse
    {
        $schedule = BellTiming::getCurrentSchedule();
        $nextBell = BellTiming::getNextBell();

        return response()->json([
            'success' => true,
            'data' => [
                'schedule' => $schedule,
                'next_bell' => $nextBell,
                'current_season' => BellTiming::getCurrentSeason()
            ]
        ]);
    }

    /**
     * Check for bell notifications
     */
    public function checkBellNotification(): JsonResponse
    {
        $bellsToRing = BellTiming::checkBellTime();

        return response()->json([
            'success' => true,
            'data' => [
                'bells_to_ring' => $bellsToRing,
                'should_ring' => $bellsToRing->count() > 0
            ]
        ]);
    }

    /**
     * Toggle bell timing active status
     */
    public function toggleActive(BellTiming $bellTiming): JsonResponse
    {
        $bellTiming->update(['is_active' => !$bellTiming->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Bell timing status updated successfully',
            'data' => $bellTiming
        ]);
    }

    /**
     * Bulk update bell timings order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bell_timings' => 'required|array',
            'bell_timings.*.id' => 'required|exists:bell_timings,id',
            'bell_timings.*.order' => 'required|integer|min:0'
        ]);

        foreach ($validated['bell_timings'] as $bellData) {
            BellTiming::where('id', $bellData['id'])
                     ->update(['order' => $bellData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bell timing order updated successfully'
        ]);
    }
}