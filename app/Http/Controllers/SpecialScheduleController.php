<?php

namespace App\Http\Controllers;

use App\Models\SpecialSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SpecialScheduleController extends Controller
{
    /**
     * Display a listing of special schedules
     */
    public function index(Request $request): JsonResponse
    {
        $query = SpecialSchedule::query();

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Filter by schedule type
        if ($request->has('schedule_type')) {
            $query->where('schedule_type', $request->schedule_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $schedules = $query->orderBy('date', 'desc')
                          ->orderBy('priority', 'desc')
                          ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'today_schedule' => SpecialSchedule::getTodaySpecialSchedule(),
            'upcoming_schedules' => SpecialSchedule::getUpcomingSchedules()
        ]);
    }

    /**
     * Store a newly created special schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date|after_or_equal:today',
            'schedule_type' => ['required', Rule::in(['holiday', 'half_day', 'exam', 'event', 'custom'])],
            'custom_timings' => 'nullable|json',
            'is_active' => 'boolean',
            'applies_to' => ['required', Rule::in(['all', 'students', 'teachers', 'staff'])],
            'notification_message' => 'nullable|string',
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])]
        ]);

        $validated['created_by'] = auth()->id() ?? 1; // Default to admin if no auth

        $schedule = SpecialSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Special schedule created successfully',
            'data' => $schedule
        ], 201);
    }

    /**
     * Display the specified special schedule
     */
    public function show(SpecialSchedule $specialSchedule): JsonResponse
    {
        $specialSchedule->load('creator');

        return response()->json([
            'success' => true,
            'data' => $specialSchedule
        ]);
    }

    /**
     * Update the specified special schedule
     */
    public function update(Request $request, SpecialSchedule $specialSchedule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'schedule_type' => ['sometimes', 'required', Rule::in(['holiday', 'half_day', 'exam', 'event', 'custom'])],
            'custom_timings' => 'nullable|json',
            'is_active' => 'boolean',
            'applies_to' => ['sometimes', 'required', Rule::in(['all', 'students', 'teachers', 'staff'])],
            'notification_message' => 'nullable|string',
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'urgent'])]
        ]);

        $specialSchedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Special schedule updated successfully',
            'data' => $specialSchedule
        ]);
    }

    /**
     * Remove the specified special schedule
     */
    public function destroy(SpecialSchedule $specialSchedule): JsonResponse
    {
        $specialSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Special schedule deleted successfully'
        ]);
    }

    /**
     * Get today's special schedule
     */
    public function today(): JsonResponse
    {
        $todaySchedule = SpecialSchedule::getTodaySpecialSchedule();
        $effectiveSchedule = SpecialSchedule::getEffectiveSchedule();

        return response()->json([
            'success' => true,
            'data' => [
                'today_schedule' => $todaySchedule,
                'effective_schedule' => $effectiveSchedule,
                'has_special_schedule' => SpecialSchedule::hasTodaySpecialSchedule(),
                'current_date' => Carbon::now()->format('Y-m-d')
            ]
        ]);
    }

    /**
     * Get upcoming special schedules
     */
    public function upcoming(): JsonResponse
    {
        $upcomingSchedules = SpecialSchedule::getUpcomingSchedules();

        return response()->json([
            'success' => true,
            'data' => $upcomingSchedules
        ]);
    }

    /**
     * Toggle special schedule active status
     */
    public function toggleActive(SpecialSchedule $specialSchedule): JsonResponse
    {
        $specialSchedule->update(['is_active' => !$specialSchedule->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Special schedule status updated successfully',
            'data' => $specialSchedule
        ]);
    }

    /**
     * Create predefined special schedules
     */
    public function createPredefined(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['half_day', 'exam_schedule'])],
            'date' => 'required|date|after_or_equal:today',
            'name' => 'nullable|string|max:255'
        ]);

        $schedule = SpecialSchedule::createPredefinedSchedule(
            $validated['type'],
            $validated['date'],
            $validated['name'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Predefined schedule created successfully',
            'data' => $schedule
        ], 201);
    }

    /**
     * Get schedule statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_schedules' => SpecialSchedule::count(),
            'active_schedules' => SpecialSchedule::where('is_active', true)->count(),
            'upcoming_schedules' => SpecialSchedule::where('date', '>', Carbon::now()->format('Y-m-d'))->count(),
            'schedules_this_month' => SpecialSchedule::whereMonth('date', Carbon::now()->month)
                                                   ->whereYear('date', Carbon::now()->year)
                                                   ->count(),
            'schedule_types' => SpecialSchedule::selectRaw('schedule_type, COUNT(*) as count')
                                              ->groupBy('schedule_type')
                                              ->pluck('count', 'schedule_type'),
            'priority_distribution' => SpecialSchedule::selectRaw('priority, COUNT(*) as count')
                                                     ->groupBy('priority')
                                                     ->pluck('count', 'priority')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}