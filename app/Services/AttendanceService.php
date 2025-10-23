<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Mark attendance for a student on a given date.
     */
    public function markAttendance(array $data): array
    {
        $studentId = (int) ($data['student_id'] ?? 0);
        $date = $data['date'] ?? null;
        $status = $data['status'] ?? null;
        $classId = $data['class_id'] ?? null;

        if (!$studentId || !$date || !$status) {
            return ['success' => false, 'message' => 'Invalid attendance data'];
        }

        $existing = Attendance::where('student_id', $studentId)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Attendance already marked for this date'];
        }

        $attendance = Attendance::create([
            'student_id' => $studentId,
            'class_id' => $classId,
            'date' => Carbon::parse($date)->format('Y-m-d'),
            'status' => $status,
            'remarks' => $data['remarks'] ?? null,
        ]);

        return ['success' => true, 'attendance' => $attendance];
    }

    /**
     * Update an existing attendance record.
     */
    public function updateAttendance(int $attendanceId, array $updateData): array
    {
        $attendance = Attendance::find($attendanceId);
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance record not found'];
        }

        $attendance->fill($updateData);
        $attendance->save();

        return ['success' => true, 'attendance' => $attendance];
    }

    /**
     * Calculate attendance percentage in a date range.
     */
    public function calculateAttendancePercentage(int $studentId, string $startDate, string $endDate): float
    {
        $query = Attendance::where('student_id', $studentId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        $total = (int) $query->count();
        if ($total === 0) {
            return 0.0;
        }

        $present = (int) Attendance::where('student_id', $studentId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->where('status', 'present')
            ->count();

        return round(($present / $total) * 100, 2);
    }

    /**
     * Get class attendance for a specific date.
     */
    public function getClassAttendance(int $classId, string $date): array
    {
        $attendances = Attendance::where('class_id', $classId)
            ->whereDate('date', $date)
            ->get();

        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();
        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;

        return [
            'attendance' => $attendances,
            'summary' => [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'percentage' => $percentage,
            ],
        ];
    }

    /**
     * Generate monthly attendance report for a student.
     */
    public function generateMonthlyReport(int $studentId, int $month, int $year): array
    {
        $attendances = Attendance::where('student_id', $studentId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $totalPresent = $attendances->where('status', 'present')->count();
        $totalAbsent = $attendances->where('status', 'absent')->count();
        $totalDays = $attendances->count();
        $percentage = $totalDays > 0 ? round(($totalPresent / $totalDays) * 100, 2) : 0.0;

        return [
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'total_days' => $totalDays,
            'percentage' => $percentage,
        ];
    }

    /**
     * Bulk mark attendance.
     */
    public function markBulkAttendance(array $bulkData): array
    {
        $processed = 0;
        $failed = 0;

        foreach ($bulkData as $data) {
            $studentId = (int) ($data['student_id'] ?? 0);
            $date = $data['date'] ?? null;
            if (!$studentId || !$date) {
                $failed++;
                continue;
            }

            $existing = Attendance::where('student_id', $studentId)
                ->whereDate('date', $date)
                ->first();

            if ($existing) {
                // Skip duplicates
                $processed++;
                continue;
            }

            Attendance::create([
                'student_id' => $studentId,
                'class_id' => $data['class_id'] ?? null,
                'date' => Carbon::parse($date)->format('Y-m-d'),
                'status' => $data['status'] ?? 'present',
                'remarks' => $data['remarks'] ?? null,
            ]);
            $processed++;
        }

        return ['success' => $failed === 0, 'processed' => $processed, 'failed' => $failed];
    }

    /**
     * Get students with attendance percentage below threshold.
     */
    public function getStudentsWithLowAttendance(int $classId, float $threshold): array
    {
        $students = Student::where('class_id', $classId)->get();
        $result = [];

        foreach ($students as $student) {
            $total = Attendance::where('student_id', $student->id)->count();
            if ($total === 0) {
                continue;
            }

            $present = Attendance::where('student_id', $student->id)
                ->where('status', 'present')
                ->count();

            $percentage = round(($present / $total) * 100, 2);
            if ($percentage < $threshold) {
                $result[] = [
                    'student_id' => $student->id,
                    'percentage' => $percentage,
                ];
            }
        }

        return $result;
    }

    /**
     * Validate attendance date is not in the future.
     */
    public function validateAttendanceDate(string $date): array
    {
        $parsed = Carbon::parse($date);
        if ($parsed->isFuture()) {
            return [
                'valid' => false,
                'message' => 'Attendance date cannot be in the future',
            ];
        }
        return ['valid' => true, 'message' => 'OK'];
    }

    /**
     * Validate attendance status.
     */
    public function isValidAttendanceStatus(string $status): bool
    {
        $valid = ['present', 'absent', 'late', 'excused'];
        return in_array($status, $valid, true);
    }

    /**
     * Get attendance summary for a student in a date range.
     */
    public function getAttendanceSummary(int $studentId, string $startDate, string $endDate): array
    {
        $attendances = Attendance::where('student_id', $studentId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();
        $excused = $attendances->where('status', 'excused')->count();
        $total = $attendances->count();

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'total_days' => $total,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0.0,
        ];
    }
}