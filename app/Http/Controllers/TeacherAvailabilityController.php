<?php

namespace App\Http\Controllers;

use App\Models\TeacherAvailability;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Http\Traits\DateRangeValidationTrait;

class TeacherAvailabilityController extends Controller
{
    use DateRangeValidationTrait;
    /**
     * Display a listing of teacher availability
     */
    public function index(Request $request): JsonResponse
    {
        $query = TeacherAvailability::with('teacher.user');

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            // Default to current week
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by availability for substitution
        if ($request->has('can_substitute')) {
            $query->where('can_substitute', $request->boolean('can_substitute'));
        }

        $availability = $query->orderBy('date')
                             ->orderBy('start_time')
                             ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Store new teacher availability
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'in:available,busy,leave',
            'subject_expertise' => 'nullable|array',
            'subject_expertise.*' => 'string|max:255',
            'notes' => 'nullable|string',
            'can_substitute' => 'boolean',
            'max_substitutions_per_day' => 'integer|min:0|max:10',
        ]);

        // Check for overlapping availability for the same teacher
        $overlapping = TeacherAvailability::where('teacher_id', $validated['teacher_id'])
                                         ->where('date', $validated['date'])
                                         ->where(function ($query) use ($validated) {
                                             $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                                                   ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                                                   ->orWhere(function ($q) use ($validated) {
                                                       $q->where('start_time', '<=', $validated['start_time'])
                                                         ->where('end_time', '>=', $validated['end_time']);
                                                   });
                                         })
                                         ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher already has availability set for this time period',
            ], 400);
        }

        $availability = TeacherAvailability::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher availability created successfully',
            'data' => $availability->load('teacher.user'),
        ], 201);
    }

    /**
     * Display the specified teacher availability
     */
    public function show(TeacherAvailability $availability): JsonResponse
    {
        $availability->load('teacher.user');

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Update the specified teacher availability
     */
    public function update(Request $request, TeacherAvailability $availability): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'date|after_or_equal:today',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i|after:start_time',
            'status' => 'in:available,busy,leave',
            'subject_expertise' => 'nullable|array',
            'subject_expertise.*' => 'string|max:255',
            'notes' => 'nullable|string',
            'can_substitute' => 'boolean',
            'max_substitutions_per_day' => 'integer|min:0|max:10',
        ]);

        // Check for overlapping availability if time or date is being changed
        if (isset($validated['date']) || isset($validated['start_time']) || isset($validated['end_time'])) {
            $date = $validated['date'] ?? $availability->date;
            $startTime = $validated['start_time'] ?? $availability->start_time;
            $endTime = $validated['end_time'] ?? $availability->end_time;

            $overlapping = TeacherAvailability::where('teacher_id', $availability->teacher_id)
                                             ->where('id', '!=', $availability->id)
                                             ->where('date', $date)
                                             ->where(function ($query) use ($startTime, $endTime) {
                                                 $query->whereBetween('start_time', [$startTime, $endTime])
                                                       ->orWhereBetween('end_time', [$startTime, $endTime])
                                                       ->orWhere(function ($q) use ($startTime, $endTime) {
                                                           $q->where('start_time', '<=', $startTime)
                                                             ->where('end_time', '>=', $endTime);
                                                       });
                                             })
                                             ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher already has availability set for this time period',
                ], 400);
            }
        }

        $availability->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Teacher availability updated successfully',
            'data' => $availability->load('teacher.user'),
        ]);
    }

    /**
     * Remove the specified teacher availability
     */
    public function destroy(TeacherAvailability $availability): JsonResponse
    {
        // Check if this availability is being used for any assigned substitutions
        $hasAssignedSubstitutions = $availability->teacher
                                                 ->substitutionAssignments()
                                                 ->where('date', $availability->date)
                                                 ->where('status', 'assigned')
                                                 ->whereBetween('start_time', [$availability->start_time, $availability->end_time])
                                                 ->exists();

        if ($hasAssignedSubstitutions) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete availability that is being used for assigned substitutions',
            ], 400);
        }

        $availability->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher availability deleted successfully',
        ]);
    }

    /**
     * Get availability for a specific teacher
     */
    public function getTeacherAvailability(Request $request, Teacher $teacher): JsonResponse
    {
        $request->validate([
            ...$this->getAvailabilityDateRangeValidationRules(),
        ], $this->getDateRangeValidationMessages());

        $query = $teacher->availability();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            // Default to current week
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
        }

        $availability = $query->orderBy('date')
                             ->orderBy('start_time')
                             ->get();

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    /**
     * Create default availability for a teacher for a week
     */
    public function createWeeklyAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            ...$this->getAvailabilityDateRangeValidationRules(),
            'schedule' => ['required', 'array'],
            'schedule.*.day' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'schedule.*.start_time' => ['required', 'date_format:H:i'],
            'schedule.*.end_time' => ['required', 'date_format:H:i', 'after:schedule.*.start_time'],
            'schedule.*.status' => ['in:available,busy,leave'],
            'schedule.*.can_substitute' => ['boolean'],
            'schedule.*.max_substitutions_per_day' => ['integer', 'min:0', 'max:10'],
            'schedule.*.subject_expertise' => ['nullable', 'array'],
            'schedule.*.subject_expertise.*' => ['string', 'max:255'],
        ], $this->getDateRangeValidationMessages());

        $startDate = Carbon::parse($request->start_date);
        $createdAvailability = [];

        foreach ($request->schedule as $daySchedule) {
            // Find the date for this day of the week
            $dayOfWeek = ucfirst($daySchedule['day']);
            $date = $startDate->copy()->next($dayOfWeek);
            
            // If the day is today or before the start date, use the start date
            if ($date->lt($startDate)) {
                $date = $startDate->copy();
                if ($date->format('l') !== $dayOfWeek) {
                    continue; // Skip if the day doesn't match
                }
            }

            // Check if availability already exists for this date and time
            $exists = TeacherAvailability::where('teacher_id', $request->teacher_id)
                                        ->where('date', $date->format('Y-m-d'))
                                        ->where('start_time', $daySchedule['start_time'])
                                        ->where('end_time', $daySchedule['end_time'])
                                        ->exists();

            if (!$exists) {
                $availability = TeacherAvailability::create([
                    'teacher_id' => $request->teacher_id,
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $daySchedule['start_time'],
                    'end_time' => $daySchedule['end_time'],
                    'status' => $daySchedule['status'] ?? 'available',
                    'can_substitute' => $daySchedule['can_substitute'] ?? true,
                    'max_substitutions_per_day' => $daySchedule['max_substitutions_per_day'] ?? 3,
                    'subject_expertise' => $daySchedule['subject_expertise'] ?? null,
                ]);

                $createdAvailability[] = $availability;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Weekly availability created successfully',
            'data' => $createdAvailability,
        ], 201);
    }

    /**
     * Get available teachers for a specific time slot
     */
    public function getAvailableTeachers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject' => 'nullable|string',
        ]);

        $availableTeachers = TeacherAvailability::findAvailableTeachers(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['subject'] ?? null
        );

        $teachersWithDetails = $availableTeachers->map(function ($availability) {
            return [
                'availability_id' => $availability->id,
                'teacher_id' => $availability->teacher_id,
                'teacher_name' => $availability->teacher->user->name,
                'teacher_email' => $availability->teacher->user->email,
                'experience_years' => $availability->teacher->experience_years,
                'qualification' => $availability->teacher->qualification,
                'subject_expertise' => $availability->subject_expertise,
                'max_substitutions_per_day' => $availability->max_substitutions_per_day,
                'current_substitutions' => $availability->teacher->substitutionAssignments()
                                                                ->where('date', $availability->date)
                                                                ->whereIn('status', ['assigned', 'completed'])
                                                                ->count(),
                'time_range' => $availability->formatted_time_range,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $teachersWithDetails,
        ]);
    }

    /**
     * Bulk create default availability for all teachers
     */
    public function createDefaultAvailabilityForAllTeachers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $teachers = Teacher::all();
        $created = 0;

        foreach ($teachers as $teacher) {
            $result = TeacherAvailability::createDefaultAvailability(
                $teacher->id,
                $validated['start_date'],
                $validated['end_date']
            );
            $created += count($result);
        }

        return response()->json([
            'success' => true,
            'message' => "Default availability created for {$teachers->count()} teachers",
            'data' => [
                'teachers_count' => $teachers->count(),
                'availability_records_created' => $created,
            ],
        ]);
    }
}