<?php

namespace App\Traits;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditActivity('created');
        });

        static::updated(function ($model) {
            $model->auditActivity('updated');
        });

        static::deleted(function ($model) {
            $model->auditActivity('deleted');
        });
    }

    /**
     * Log activity for this model
     */
    public function auditActivity(string $event, array $customData = [])
    {
        // Skip if no authenticated user (for system operations)
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $oldValues = [];
        $newValues = [];

        // Get old and new values for updates
        if ($event === 'updated' && $this->isDirty()) {
            $oldValues = $this->getOriginal();
            $newValues = $this->getAttributes();
            
            // Only include changed fields
            $changedFields = array_keys($this->getDirty());
            $oldValues = array_intersect_key($oldValues, array_flip($changedFields));
            $newValues = array_intersect_key($newValues, array_flip($changedFields));
            
            // Remove sensitive fields
            $sensitiveFields = ['password', 'remember_token', 'api_token'];
            foreach ($sensitiveFields as $field) {
                unset($oldValues[$field], $newValues[$field]);
            }
        } elseif ($event === 'created') {
            $newValues = $this->getAttributes();
            // Remove sensitive fields
            $sensitiveFields = ['password', 'remember_token', 'api_token'];
            foreach ($sensitiveFields as $field) {
                unset($newValues[$field]);
            }
        } elseif ($event === 'deleted') {
            $oldValues = $this->getOriginal();
            // Remove sensitive fields
            $sensitiveFields = ['password', 'remember_token', 'api_token'];
            foreach ($sensitiveFields as $field) {
                unset($oldValues[$field]);
            }
        }

        // Determine related IDs
        $studentId = $this->getStudentId();
        $classId = $this->getClassId();
        $subjectId = $this->getSubjectId();

        // Create audit log
        AuditTrail::logActivity(
            $user,
            $event,
            get_class($this),
            $this->getKey(),
            $oldValues,
            $newValues,
            Request::fullUrl(),
            Request::ip(),
            Request::userAgent(),
            $this->getAuditTags(),
            array_merge([
                'model' => class_basename($this),
                'model_id' => $this->getKey(),
            ], $customData),
            $studentId,
            $classId,
            $subjectId
        );
    }

    /**
     * Get student ID if this model is related to a student
     */
    protected function getStudentId()
    {
        // Check if model has student_id field
        if (isset($this->attributes['student_id'])) {
            return $this->attributes['student_id'];
        }

        // Check if model is a Student
        if ($this instanceof \App\Models\Student) {
            return $this->getKey();
        }

        // Check if model has a student relationship
        if (method_exists($this, 'student') && $this->student) {
            return $this->student->id;
        }

        return null;
    }

    /**
     * Get class ID if this model is related to a class
     */
    protected function getClassId()
    {
        // Check if model has class_id field
        if (isset($this->attributes['class_id'])) {
            return $this->attributes['class_id'];
        }

        // Check if model is a SchoolClass
        if ($this instanceof \App\Models\SchoolClass) {
            return $this->getKey();
        }

        // Check if model has a class relationship
        if (method_exists($this, 'schoolClass') && $this->schoolClass) {
            return $this->schoolClass->id;
        }

        return null;
    }

    /**
     * Get subject ID if this model is related to a subject
     */
    protected function getSubjectId()
    {
        // Check if model has subject_id field
        if (isset($this->attributes['subject_id'])) {
            return $this->attributes['subject_id'];
        }

        // Check if model is a Subject
        if ($this instanceof \App\Models\Subject) {
            return $this->getKey();
        }

        // Check if model has a subject relationship
        if (method_exists($this, 'subject') && $this->subject) {
            return $this->subject->id;
        }

        return null;
    }

    /**
     * Get audit tags for this model
     */
    protected function getAuditTags(): array
    {
        $tags = [class_basename($this)];

        // Add specific tags based on model type
        if ($this instanceof \App\Models\Student) {
            $tags[] = 'student_management';
        } elseif ($this instanceof \App\Models\Teacher) {
            $tags[] = 'teacher_management';
        } elseif ($this instanceof \App\Models\SchoolClass) {
            $tags[] = 'class_management';
        } elseif ($this instanceof \App\Models\Subject) {
            $tags[] = 'subject_management';
        } elseif ($this instanceof \App\Models\Attendance) {
            $tags[] = 'attendance_management';
        } elseif ($this instanceof \App\Models\Fee) {
            $tags[] = 'fee_management';
        } elseif ($this instanceof \App\Models\User) {
            $tags[] = 'user_management';
        }

        return $tags;
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditTrail::class, 'auditable');
    }

    /**
     * Get recent audit logs for this model
     */
    public function recentAuditLogs($limit = 10)
    {
        return $this->auditLogs()
                   ->with('user')
                   ->latest()
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get audit summary for this model
     */
    public function getAuditSummary()
    {
        $logs = $this->auditLogs()->get();

        return [
            'total_changes' => $logs->count(),
            'created_at' => $logs->where('event', 'created')->first()?->created_at,
            'last_updated_at' => $logs->where('event', 'updated')->last()?->created_at,
            'updated_by' => $logs->where('event', 'updated')->last()?->user,
            'total_updates' => $logs->where('event', 'updated')->count(),
            'created_by' => $logs->where('event', 'created')->first()?->user,
        ];
    }
}