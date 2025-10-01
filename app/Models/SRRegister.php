<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SRRegister extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sr_registers';

    protected $fillable = [
        'student_id',
        'class_id',
        'subject_id',
        'academic_year',
        'term',
        'attendance_percentage',
        'theory_marks',
        'practical_marks',
        'internal_assessment',
        'project_marks',
        'total_marks',
        'grade',
        'remarks',
        'conduct_grade',
        'discipline_remarks',
        'co_curricular_activities',
        'sports_achievements',
        'special_achievements',
        'teacher_id',
        'updated_by',
        'last_updated_at'
    ];

    protected $casts = [
        'attendance_percentage' => 'decimal:2',
        'theory_marks' => 'decimal:2',
        'practical_marks' => 'decimal:2',
        'internal_assessment' => 'decimal:2',
        'project_marks' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'last_updated_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByTerm($query, $term)
    {
        return $query->where('term', $term);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeWithGrades($query)
    {
        return $query->whereNotNull('grade');
    }

    public function scopeWithAttendance($query)
    {
        return $query->whereNotNull('attendance_percentage');
    }

    public function scopeRecentlyUpdated($query, $days = 7)
    {
        return $query->where('last_updated_at', '>=', Carbon::now()->subDays($days));
    }

    // Accessors
    public function getFormattedAttendanceAttribute()
    {
        return $this->attendance_percentage ? number_format($this->attendance_percentage, 1) . '%' : 'N/A';
    }

    public function getFormattedTotalMarksAttribute()
    {
        return $this->total_marks ? number_format($this->total_marks, 1) : 'N/A';
    }

    public function getGradeColorAttribute()
    {
        $gradeColors = [
            'A+' => 'success',
            'A' => 'success',
            'B+' => 'info',
            'B' => 'info',
            'C+' => 'warning',
            'C' => 'warning',
            'D' => 'danger',
            'F' => 'danger'
        ];

        return $gradeColors[$this->grade] ?? 'secondary';
    }

    public function getConductGradeColorAttribute()
    {
        $conductColors = [
            'A' => 'success',
            'B' => 'info',
            'C' => 'warning',
            'D' => 'danger',
            'E' => 'danger'
        ];

        return $conductColors[$this->conduct_grade] ?? 'secondary';
    }

    public function getTermNameAttribute()
    {
        $termNames = [
            '1' => 'First Term',
            '2' => 'Second Term',
            '3' => 'Third Term',
            'annual' => 'Annual'
        ];

        return $termNames[$this->term] ?? $this->term;
    }

    public function getLastUpdatedFormattedAttribute()
    {
        return $this->last_updated_at ? $this->last_updated_at->format('d M Y, h:i A') : 'Never';
    }

    // Mutators
    public function setAttendancePercentageAttribute($value)
    {
        $this->attributes['attendance_percentage'] = $value ? round($value, 2) : null;
    }

    public function setTheoryMarksAttribute($value)
    {
        $this->attributes['theory_marks'] = $value ? round($value, 2) : null;
    }

    public function setPracticalMarksAttribute($value)
    {
        $this->attributes['practical_marks'] = $value ? round($value, 2) : null;
    }

    public function setInternalAssessmentAttribute($value)
    {
        $this->attributes['internal_assessment'] = $value ? round($value, 2) : null;
    }

    public function setProjectMarksAttribute($value)
    {
        $this->attributes['project_marks'] = $value ? round($value, 2) : null;
    }

    public function setTotalMarksAttribute($value)
    {
        $this->attributes['total_marks'] = $value ? round($value, 2) : null;
    }

    // Methods
    public function calculateTotalMarks()
    {
        $total = 0;
        
        if ($this->theory_marks) $total += $this->theory_marks;
        if ($this->practical_marks) $total += $this->practical_marks;
        if ($this->internal_assessment) $total += $this->internal_assessment;
        if ($this->project_marks) $total += $this->project_marks;
        
        return $total > 0 ? $total : null;
    }

    public function calculateGrade($totalMarks = null)
    {
        $marks = $totalMarks ?? $this->total_marks;
        
        if (!$marks) return null;
        
        // Standard grading system
        if ($marks >= 90) return 'A+';
        if ($marks >= 80) return 'A';
        if ($marks >= 70) return 'B+';
        if ($marks >= 60) return 'B';
        if ($marks >= 50) return 'C+';
        if ($marks >= 40) return 'C';
        if ($marks >= 33) return 'D';
        
        return 'F';
    }

    public function isPassingGrade()
    {
        return !in_array($this->grade, ['D', 'F']) && $this->grade !== null;
    }

    public function hasGoodAttendance($threshold = 75)
    {
        return $this->attendance_percentage >= $threshold;
    }

    public function getPerformanceStatus()
    {
        if (!$this->grade) return 'incomplete';
        
        if (in_array($this->grade, ['A+', 'A'])) return 'excellent';
        if (in_array($this->grade, ['B+', 'B'])) return 'good';
        if (in_array($this->grade, ['C+', 'C'])) return 'average';
        if ($this->grade === 'D') return 'below_average';
        
        return 'poor';
    }

    public function getComprehensiveRemarks()
    {
        $remarks = [];
        
        // Academic performance
        if ($this->grade) {
            $performance = $this->getPerformanceStatus();
            switch ($performance) {
                case 'excellent':
                    $remarks[] = 'Excellent academic performance';
                    break;
                case 'good':
                    $remarks[] = 'Good academic performance';
                    break;
                case 'average':
                    $remarks[] = 'Average academic performance';
                    break;
                case 'below_average':
                    $remarks[] = 'Needs improvement in academics';
                    break;
                case 'poor':
                    $remarks[] = 'Requires immediate academic attention';
                    break;
            }
        }
        
        // Attendance
        if ($this->attendance_percentage) {
            if ($this->attendance_percentage >= 90) {
                $remarks[] = 'Excellent attendance';
            } elseif ($this->attendance_percentage >= 75) {
                $remarks[] = 'Good attendance';
            } else {
                $remarks[] = 'Poor attendance - needs improvement';
            }
        }
        
        // Conduct
        if ($this->conduct_grade) {
            switch ($this->conduct_grade) {
                case 'A':
                    $remarks[] = 'Excellent conduct and behavior';
                    break;
                case 'B':
                    $remarks[] = 'Good conduct and behavior';
                    break;
                case 'C':
                    $remarks[] = 'Satisfactory conduct';
                    break;
                case 'D':
                case 'E':
                    $remarks[] = 'Conduct needs improvement';
                    break;
            }
        }
        
        // Add custom remarks if any
        if ($this->remarks) {
            $remarks[] = $this->remarks;
        }
        
        return implode('. ', $remarks);
    }

    public function duplicate($newAcademicYear, $newTerm)
    {
        $newRecord = $this->replicate();
        $newRecord->academic_year = $newAcademicYear;
        $newRecord->term = $newTerm;
        $newRecord->teacher_id = auth()->id();
        $newRecord->updated_by = auth()->id();
        $newRecord->last_updated_at = now();
        
        // Clear marks and grades for new term
        $newRecord->theory_marks = null;
        $newRecord->practical_marks = null;
        $newRecord->internal_assessment = null;
        $newRecord->project_marks = null;
        $newRecord->total_marks = null;
        $newRecord->grade = null;
        $newRecord->remarks = null;
        
        return $newRecord;
    }

    public function getAuditTrail()
    {
        // This would return audit trail records
        // For now, return basic info
        return [
            'created_by' => $this->teacher->name ?? 'Unknown',
            'created_at' => $this->created_at->format('d M Y, h:i A'),
            'last_updated_by' => $this->updatedBy->name ?? 'Unknown',
            'last_updated_at' => $this->last_updated_at ? $this->last_updated_at->format('d M Y, h:i A') : 'Never'
        ];
    }

    // Static methods
    public static function getAcademicYears()
    {
        return self::distinct()->pluck('academic_year')->sort()->values();
    }

    public static function getTerms()
    {
        return [
            '1' => 'First Term',
            '2' => 'Second Term', 
            '3' => 'Third Term',
            'annual' => 'Annual'
        ];
    }

    public static function getGrades()
    {
        return ['A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F'];
    }

    public static function getConductGrades()
    {
        return [
            'A' => 'Excellent',
            'B' => 'Good',
            'C' => 'Satisfactory',
            'D' => 'Needs Improvement',
            'E' => 'Unsatisfactory'
        ];
    }

    public static function getClassStatistics($classId, $academicYear, $term)
    {
        $records = self::where('class_id', $classId)
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->get();

        if ($records->isEmpty()) {
            return null;
        }

        $totalStudents = $records->count();
        $passedStudents = $records->filter(function($record) {
            return $record->isPassingGrade();
        })->count();

        $averageAttendance = $records->whereNotNull('attendance_percentage')
            ->avg('attendance_percentage');

        $averageMarks = $records->whereNotNull('total_marks')
            ->avg('total_marks');

        return [
            'total_students' => $totalStudents,
            'passed_students' => $passedStudents,
            'pass_percentage' => $totalStudents > 0 ? round(($passedStudents / $totalStudents) * 100, 1) : 0,
            'average_attendance' => $averageAttendance ? round($averageAttendance, 1) : null,
            'average_marks' => $averageMarks ? round($averageMarks, 1) : null,
            'grade_distribution' => $records->whereNotNull('grade')
                ->groupBy('grade')
                ->map(function($group) {
                    return $group->count();
                })
        ];
    }
}