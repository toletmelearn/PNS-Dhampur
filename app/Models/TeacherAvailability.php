<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Helpers\SecurityHelper;
use Carbon\Carbon;

class TeacherAvailability extends Model
{
    use HasFactory;

    protected $table = 'teacher_availability';

    protected $fillable = [
        'teacher_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'subject_expertise',
        'notes',
        'can_substitute',
        'max_substitutions_per_day',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'can_substitute' => 'boolean',
        'max_substitutions_per_day' => 'integer',
        'subject_expertise' => 'array',
    ];

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeCanSubstitute($query)
    {
        return $query->where('can_substitute', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', Carbon::today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '<=', $startTime)
                    ->where('end_time', '>=', $endTime);
    }

    public function scopeForSubject($query, $subject)
    {
        return $query->where('subject_expertise', 'like', SecurityHelper::buildLikePattern($subject));
    }

    // Helper methods
    public function getFormattedTimeRange()
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . 
               Carbon::parse($this->end_time)->format('H:i');
    }

    public function getDurationInMinutes()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $end->diffInMinutes($start);
    }

    public function isAvailableForTimeRange($startTime, $endTime)
    {
        $availStart = Carbon::parse($this->start_time);
        $availEnd = Carbon::parse($this->end_time);
        $reqStart = Carbon::parse($startTime);
        $reqEnd = Carbon::parse($endTime);

        return $availStart->lte($reqStart) && $availEnd->gte($reqEnd);
    }

    public function hasSubjectExpertise($subject)
    {
        if (!$this->subject_expertise) {
            return false;
        }

        $subjects = array_map('trim', explode(',', strtolower($this->subject_expertise)));
        return in_array(strtolower($subject), $subjects);
    }

    public function getCurrentSubstitutionCount()
    {
        return TeacherSubstitution::where('substitute_teacher_id', $this->teacher_id)
                                 ->where('date', $this->date)
                                 ->whereIn('status', ['assigned', 'completed'])
                                 ->count();
    }

    public function canTakeMoreSubstitutions()
    {
        return $this->getCurrentSubstitutionCount() < $this->max_substitutions_per_day;
    }

    public function getSubjectExpertiseArray()
    {
        if (!$this->subject_expertise) {
            return [];
        }

        return array_map('trim', explode(',', $this->subject_expertise));
    }

    // Static methods
    public static function createDefaultAvailability($teacherId, $date)
    {
        return self::create([
            'teacher_id' => $teacherId,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '15:00',
            'status' => 'available',
            'can_substitute' => true,
            'max_substitutions_per_day' => 3,
        ]);
    }

    public static function bulkCreateForWeek($teacherId, $startDate)
    {
        $availabilities = [];
        $start = Carbon::parse($startDate);

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            
            // Skip weekends (assuming school operates Mon-Fri)
            if ($date->isWeekend()) {
                continue;
            }

            $availabilities[] = [
                'teacher_id' => $teacherId,
                'date' => $date->format('Y-m-d'),
                'start_time' => '08:00',
                'end_time' => '15:00',
                'status' => 'available',
                'can_substitute' => true,
                'max_substitutions_per_day' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return self::insert($availabilities);
    }

    public static function findAvailableForSubstitution($date, $startTime, $endTime, $subject = null)
    {
        $query = self::with('teacher')
                    ->available()
                    ->canSubstitute()
                    ->forDate($date)
                    ->forTimeRange($startTime, $endTime);

        if ($subject) {
            $query->forSubject($subject);
        }

        return $query->get()->filter(function ($availability) {
            return $availability->canTakeMoreSubstitutions();
        });
    }
}