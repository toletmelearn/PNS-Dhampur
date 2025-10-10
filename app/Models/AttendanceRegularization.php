<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRegularization extends Model
{
    use HasFactory;

    protected $table = 'attendance_regularizations';

    protected $fillable = [
        'teacher_id',
        'biometric_attendance_id',
        'date',
        'request_type',
        'original_check_in',
        'original_check_out',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'supporting_documents',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'admin_notes'
    ];

    protected $casts = [
        'date' => 'date',
        'original_check_in' => 'datetime',
        'original_check_out' => 'datetime',
        'requested_check_in' => 'datetime',
        'requested_check_out' => 'datetime',
        'approved_at' => 'datetime',
        'supporting_documents' => 'array'
    ];

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function biometricAttendance()
    {
        return $this->belongsTo(BiometricAttendance::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        // Return safe status text only - HTML should be handled in views
        $statuses = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeClassAttribute()
    {
        // Return CSS class for safe styling
        $classes = [
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger'
        ];

        return $classes[$this->status] ?? 'bg-secondary';
    }

    public function getRequestTypeDisplayAttribute()
    {
        $types = [
            'check_in_correction' => 'Check-in Time Correction',
            'check_out_correction' => 'Check-out Time Correction',
            'both_correction' => 'Both Times Correction',
            'missed_punch' => 'Missed Punch',
            'absent_to_present' => 'Mark as Present'
        ];

        return $types[$this->request_type] ?? $this->request_type;
    }

    // Methods
    public function approve($approvedBy = null, $adminNotes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'admin_notes' => $adminNotes
        ]);

        // Apply the regularization to the biometric attendance record
        $this->applyRegularization();
    }

    public function reject($rejectionReason, $approvedBy = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $rejectionReason
        ]);
    }

    protected function applyRegularization()
    {
        if ($this->biometric_attendance_id && $this->status === 'approved') {
            $attendance = $this->biometricAttendance;
            
            $updateData = [];
            
            if ($this->requested_check_in) {
                $updateData['check_in_time'] = $this->requested_check_in;
            }
            
            if ($this->requested_check_out) {
                $updateData['check_out_time'] = $this->requested_check_out;
            }

            // Recalculate working hours and flags
            if (isset($updateData['check_in_time']) || isset($updateData['check_out_time'])) {
                $checkIn = $updateData['check_in_time'] ?? $attendance->check_in_time;
                $checkOut = $updateData['check_out_time'] ?? $attendance->check_out_time;
                
                if ($checkIn && $checkOut) {
                    $workingHours = Carbon::parse($checkIn)->diffInMinutes(Carbon::parse($checkOut)) / 60;
                    $updateData['working_hours'] = round($workingHours, 2);
                }
                
                // Recalculate late arrival flag
                if (isset($updateData['check_in_time'])) {
                    $schoolStartTime = Carbon::createFromFormat('H:i:s', '08:00:00');
                    $updateData['is_late'] = Carbon::parse($updateData['check_in_time'])->format('H:i:s') > $schoolStartTime->format('H:i:s');
                }
                
                // Recalculate early departure flag
                if (isset($updateData['working_hours'])) {
                    $updateData['is_early_departure'] = $updateData['working_hours'] < 8;
                }
            }

            $attendance->update($updateData);
        }
    }

    // Static methods
    public static function createRequest($data)
    {
        return static::create(array_merge($data, [
            'status' => 'pending',
            'requested_by' => auth()->id()
        ]));
    }

    public static function getPendingRequests()
    {
        return static::with(['teacher', 'requestedBy'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getTeacherRequests($teacherId, $limit = 10)
    {
        return static::with(['approvedBy'])
            ->forTeacher($teacherId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getRegularizationStats($startDate, $endDate)
    {
        $requests = static::forDateRange($startDate, $endDate)->get();
        
        return [
            'total_requests' => $requests->count(),
            'pending_requests' => $requests->where('status', 'pending')->count(),
            'approved_requests' => $requests->where('status', 'approved')->count(),
            'rejected_requests' => $requests->where('status', 'rejected')->count(),
            'approval_rate' => $requests->count() > 0 ? 
                round(($requests->where('status', 'approved')->count() / $requests->count()) * 100, 2) : 0
        ];
    }
}