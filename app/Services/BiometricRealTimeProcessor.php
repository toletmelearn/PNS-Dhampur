<?php

namespace App\Services;

use App\Models\BiometricAttendance;
use App\Models\Teacher;
use App\Models\AttendanceAnalytics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BiometricRealTimeProcessor
{
    protected $schoolStartTime;
    protected $schoolEndTime;
    protected $minimumWorkingHours;
    protected $graceMinutes;
    protected $overtimeThreshold;
    
    public function __construct()
    {
        $this->schoolStartTime = config('attendance.school_start_time', '08:00:00');
        $this->schoolEndTime = config('attendance.school_end_time', '16:00:00');
        $this->minimumWorkingHours = config('attendance.minimum_working_hours', 8);
        $this->graceMinutes = config('attendance.grace_minutes', 15);
        $this->overtimeThreshold = config('attendance.overtime_threshold', 8.5);
    }

    /**
     * Process real-time check-in with advanced calculations
     */
    public function processCheckIn($teacherId, $checkInTime = null, $deviceId = null, $location = null)
    {
        try {
            $checkInTime = $checkInTime ? Carbon::parse($checkInTime) : now();
            $date = $checkInTime->format('Y-m-d');
            
            // Check for existing attendance record
            $attendance = BiometricAttendance::where('teacher_id', $teacherId)
                ->where('date', $date)
                ->first();
            
            if ($attendance && $attendance->check_in_time) {
                throw new \Exception('Teacher has already checked in today');
            }
            
            // Calculate late arrival with advanced logic
            $lateCalculation = $this->calculateLateArrival($checkInTime);
            
            // Create or update attendance record
            $attendanceData = [
                'teacher_id' => $teacherId,
                'date' => $date,
                'check_in_time' => $checkInTime,
                'status' => 'present',
                'is_late' => $lateCalculation['is_late'],
                'device_id' => $deviceId,
                'check_in_location' => $location,
                'notes' => $lateCalculation['notes']
            ];
            
            if ($attendance) {
                $attendance->update($attendanceData);
            } else {
                $attendance = BiometricAttendance::create($attendanceData);
            }
            
            // Update real-time cache
            $this->updateRealTimeCache($teacherId, $attendance);
            
            // Trigger real-time notifications if late
            if ($lateCalculation['is_late']) {
                $this->triggerLateArrivalNotification($attendance, $lateCalculation);
            }
            
            // Update daily statistics
            $this->updateDailyStatistics($date);
            
            return [
                'success' => true,
                'attendance' => $attendance,
                'late_info' => $lateCalculation,
                'message' => $lateCalculation['is_late'] ? 
                    "Check-in recorded. Late by {$lateCalculation['minutes_late']} minutes." :
                    'Check-in recorded successfully.'
            ];
            
        } catch (\Exception $e) {
            Log::error('Check-in processing failed', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process real-time check-out with advanced calculations
     */
    public function processCheckOut($teacherId, $checkOutTime = null, $deviceId = null, $location = null)
    {
        try {
            $checkOutTime = $checkOutTime ? Carbon::parse($checkOutTime) : now();
            $date = $checkOutTime->format('Y-m-d');
            
            // Find existing attendance record
            $attendance = BiometricAttendance::where('teacher_id', $teacherId)
                ->where('date', $date)
                ->whereNotNull('check_in_time')
                ->first();
            
            if (!$attendance) {
                throw new \Exception('No check-in record found for today');
            }
            
            if ($attendance->check_out_time) {
                throw new \Exception('Teacher has already checked out today');
            }
            
            // Calculate working hours and early departure
            $workingCalculation = $this->calculateWorkingHours($attendance->check_in_time, $checkOutTime);
            $earlyDepartureCalculation = $this->calculateEarlyDeparture($checkOutTime, $workingCalculation['working_hours']);
            
            // Update attendance record
            $attendance->update([
                'check_out_time' => $checkOutTime,
                'working_hours' => $workingCalculation['working_hours'],
                'is_early_departure' => $earlyDepartureCalculation['is_early'],
                'check_out_location' => $location,
                'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . $earlyDepartureCalculation['notes']
            ]);
            
            // Update real-time cache
            $this->updateRealTimeCache($teacherId, $attendance);
            
            // Trigger notifications for early departure or overtime
            if ($earlyDepartureCalculation['is_early']) {
                $this->triggerEarlyDepartureNotification($attendance, $earlyDepartureCalculation);
            } elseif ($workingCalculation['is_overtime']) {
                $this->triggerOvertimeNotification($attendance, $workingCalculation);
            }
            
            // Update daily statistics
            $this->updateDailyStatistics($date);
            
            // Calculate and update monthly analytics if end of month
            if ($checkOutTime->isLastOfMonth()) {
                $this->scheduleMonthlyAnalyticsUpdate($teacherId, $checkOutTime->year, $checkOutTime->month);
            }
            
            return [
                'success' => true,
                'attendance' => $attendance,
                'working_info' => $workingCalculation,
                'early_departure_info' => $earlyDepartureCalculation,
                'message' => $this->generateCheckOutMessage($workingCalculation, $earlyDepartureCalculation)
            ];
            
        } catch (\Exception $e) {
            Log::error('Check-out processing failed', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Advanced late arrival calculation with grace period and patterns
     */
    protected function calculateLateArrival($checkInTime)
    {
        $schoolStart = Carbon::createFromFormat('H:i:s', $this->schoolStartTime);
        $graceTime = $schoolStart->copy()->addMinutes($this->graceMinutes);
        
        $checkInTimeOnly = Carbon::createFromFormat('H:i:s', $checkInTime->format('H:i:s'));
        
        $isLate = $checkInTimeOnly->gt($graceTime);
        $minutesLate = $isLate ? $checkInTimeOnly->diffInMinutes($schoolStart) : 0;
        
        // Determine severity level
        $severity = 'on_time';
        if ($minutesLate > 0) {
            if ($minutesLate <= 15) {
                $severity = 'minor_late';
            } elseif ($minutesLate <= 30) {
                $severity = 'moderate_late';
            } else {
                $severity = 'severe_late';
            }
        }
        
        return [
            'is_late' => $isLate,
            'minutes_late' => $minutesLate,
            'severity' => $severity,
            'grace_period_used' => $checkInTimeOnly->between($schoolStart, $graceTime),
            'notes' => $isLate ? "Late arrival: {$minutesLate} minutes ({$severity})" : 'On-time arrival'
        ];
    }

    /**
     * Advanced working hours calculation with break deductions
     */
    protected function calculateWorkingHours($checkInTime, $checkOutTime)
    {
        $checkIn = Carbon::parse($checkInTime);
        $checkOut = Carbon::parse($checkOutTime);
        
        $totalMinutes = $checkIn->diffInMinutes($checkOut);
        
        // Deduct lunch break (1 hour) if worked more than 6 hours
        $lunchBreakMinutes = $totalMinutes > 360 ? 60 : 0;
        
        // Deduct tea breaks (15 minutes each) if worked more than 4 hours
        $teaBreakMinutes = $totalMinutes > 240 ? 30 : 0;
        
        $workingMinutes = $totalMinutes - $lunchBreakMinutes - $teaBreakMinutes;
        $workingHours = round($workingMinutes / 60, 2);
        
        $isOvertime = $workingHours > $this->overtimeThreshold;
        $overtimeHours = $isOvertime ? round($workingHours - $this->minimumWorkingHours, 2) : 0;
        
        return [
            'working_hours' => $workingHours,
            'total_minutes' => $totalMinutes,
            'break_deductions' => $lunchBreakMinutes + $teaBreakMinutes,
            'is_overtime' => $isOvertime,
            'overtime_hours' => $overtimeHours,
            'efficiency_score' => $this->calculateEfficiencyScore($workingHours)
        ];
    }

    /**
     * Advanced early departure calculation
     */
    protected function calculateEarlyDeparture($checkOutTime, $workingHours)
    {
        $schoolEnd = Carbon::createFromFormat('H:i:s', $this->schoolEndTime);
        $checkOutTimeOnly = Carbon::createFromFormat('H:i:s', $checkOutTime->format('H:i:s'));
        
        $isEarlyByTime = $checkOutTimeOnly->lt($schoolEnd);
        $isEarlyByHours = $workingHours < $this->minimumWorkingHours;
        $isEarly = $isEarlyByTime || $isEarlyByHours;
        
        $minutesEarly = $isEarlyByTime ? $schoolEnd->diffInMinutes($checkOutTimeOnly) : 0;
        $hoursShort = $isEarlyByHours ? round($this->minimumWorkingHours - $workingHours, 2) : 0;
        
        $reason = '';
        if ($isEarlyByTime && $isEarlyByHours) {
            $reason = 'Both time and hours insufficient';
        } elseif ($isEarlyByTime) {
            $reason = 'Left before school end time';
        } elseif ($isEarlyByHours) {
            $reason = 'Insufficient working hours';
        }
        
        return [
            'is_early' => $isEarly,
            'minutes_early' => $minutesEarly,
            'hours_short' => $hoursShort,
            'reason' => $reason,
            'notes' => $isEarly ? "Early departure: {$reason}" : 'Full day completed'
        ];
    }

    /**
     * Calculate efficiency score based on working hours and patterns
     */
    protected function calculateEfficiencyScore($workingHours)
    {
        $baseScore = 100;
        
        if ($workingHours < $this->minimumWorkingHours) {
            $baseScore -= (($this->minimumWorkingHours - $workingHours) * 10);
        } elseif ($workingHours > $this->overtimeThreshold) {
            $baseScore += min(20, ($workingHours - $this->overtimeThreshold) * 5);
        }
        
        return max(0, min(120, round($baseScore, 2)));
    }

    /**
     * Update real-time cache for dashboard
     */
    protected function updateRealTimeCache($teacherId, $attendance)
    {
        $cacheKey = "teacher_attendance_{$teacherId}_{$attendance->date}";
        Cache::put($cacheKey, $attendance, now()->addHours(24));
        
        // Update daily summary cache
        $dailySummaryKey = "daily_attendance_summary_{$attendance->date}";
        Cache::forget($dailySummaryKey);
    }

    /**
     * Get real-time attendance status
     */
    public function getRealTimeStatus($teacherId, $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "teacher_attendance_{$teacherId}_{$date}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($teacherId, $date) {
            return BiometricAttendance::where('teacher_id', $teacherId)
                ->where('date', $date)
                ->with('teacher')
                ->first();
        });
    }

    /**
     * Get real-time dashboard data
     */
    public function getRealTimeDashboard($date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "realtime_dashboard_{$date}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($date) {
            $attendances = BiometricAttendance::forDate($date)->with('teacher')->get();
            $totalTeachers = Teacher::count();
            
            $present = $attendances->where('status', 'present')->count();
            $checkedOut = $attendances->whereNotNull('check_out_time')->count();
            $currentlyPresent = $present - $checkedOut;
            $lateArrivals = $attendances->where('is_late', true)->count();
            $earlyDepartures = $attendances->where('is_early_departure', true)->count();
            
            return [
                'date' => $date,
                'total_teachers' => $totalTeachers,
                'present' => $present,
                'absent' => $totalTeachers - $present,
                'currently_present' => $currentlyPresent,
                'checked_out' => $checkedOut,
                'late_arrivals' => $lateArrivals,
                'early_departures' => $earlyDepartures,
                'attendance_percentage' => $totalTeachers > 0 ? round(($present / $totalTeachers) * 100, 2) : 0,
                'punctuality_percentage' => $present > 0 ? round((($present - $lateArrivals) / $present) * 100, 2) : 0,
                'last_updated' => now()->format('H:i:s')
            ];
        });
    }

    /**
     * Process bulk attendance data with validation
     */
    public function processBulkAttendance($attendanceData)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        DB::beginTransaction();
        
        try {
            foreach ($attendanceData as $index => $data) {
                try {
                    $this->validateAttendanceData($data);
                    
                    if (isset($data['check_out_time'])) {
                        $result = $this->processCheckOut(
                            $data['teacher_id'],
                            $data['check_out_time'],
                            $data['device_id'] ?? null,
                            $data['location'] ?? null
                        );
                    } else {
                        $result = $this->processCheckIn(
                            $data['teacher_id'],
                            $data['check_in_time'],
                            $data['device_id'] ?? null,
                            $data['location'] ?? null
                        );
                    }
                    
                    if ($result['success']) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Row {$index}: " . $result['message'];
                    }
                    
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row {$index}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        
        return $results;
    }

    /**
     * Validate attendance data
     */
    protected function validateAttendanceData($data)
    {
        if (!isset($data['teacher_id']) || !Teacher::find($data['teacher_id'])) {
            throw new \Exception('Invalid teacher ID');
        }
        
        if (!isset($data['check_in_time']) && !isset($data['check_out_time'])) {
            throw new \Exception('Either check-in or check-out time is required');
        }
        
        if (isset($data['check_in_time']) && !Carbon::parse($data['check_in_time'])) {
            throw new \Exception('Invalid check-in time format');
        }
        
        if (isset($data['check_out_time']) && !Carbon::parse($data['check_out_time'])) {
            throw new \Exception('Invalid check-out time format');
        }
    }

    /**
     * Update daily statistics
     */
    protected function updateDailyStatistics($date)
    {
        $cacheKey = "daily_stats_{$date}";
        Cache::forget($cacheKey);
        
        // Trigger background job for heavy calculations
        // dispatch(new UpdateDailyStatisticsJob($date));
    }

    /**
     * Schedule monthly analytics update
     */
    protected function scheduleMonthlyAnalyticsUpdate($teacherId, $year, $month)
    {
        // Trigger background job for monthly analytics
        // dispatch(new UpdateMonthlyAnalyticsJob($teacherId, $year, $month));
    }

    /**
     * Generate check-out message
     */
    protected function generateCheckOutMessage($workingInfo, $earlyDepartureInfo)
    {
        $message = "Check-out recorded. ";
        $message .= "Worked {$workingInfo['working_hours']} hours. ";
        
        if ($workingInfo['is_overtime']) {
            $message .= "Overtime: {$workingInfo['overtime_hours']} hours. ";
        }
        
        if ($earlyDepartureInfo['is_early']) {
            $message .= "Early departure: {$earlyDepartureInfo['reason']}. ";
        }
        
        return trim($message);
    }

    /**
     * Trigger late arrival notification
     */
    protected function triggerLateArrivalNotification($attendance, $lateCalculation)
    {
        // Implementation for notifications (email, SMS, push notifications)
        Log::info('Late arrival detected', [
            'teacher_id' => $attendance->teacher_id,
            'minutes_late' => $lateCalculation['minutes_late'],
            'severity' => $lateCalculation['severity']
        ]);
    }

    /**
     * Trigger early departure notification
     */
    protected function triggerEarlyDepartureNotification($attendance, $earlyDepartureCalculation)
    {
        // Implementation for notifications
        Log::info('Early departure detected', [
            'teacher_id' => $attendance->teacher_id,
            'reason' => $earlyDepartureCalculation['reason']
        ]);
    }

    /**
     * Trigger overtime notification
     */
    protected function triggerOvertimeNotification($attendance, $workingCalculation)
    {
        // Implementation for notifications
        Log::info('Overtime detected', [
            'teacher_id' => $attendance->teacher_id,
            'overtime_hours' => $workingCalculation['overtime_hours']
        ]);
    }
}