<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Syllabus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'syllabus';
    
    protected $fillable = [
        'title',
        'description',
        'subject_id',
        'class_id',
        'teacher_id',
        'academic_year',
        'semester',
        'file_path',
        'file_type',
        'file_size',
        'original_filename',
        'is_active',
        'visibility',
        'download_count',
        'view_count',
        'tags',
        'created_by',
        'updated_by',
        // Legacy fields
        'subject',
        'note'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags' => 'array',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Visibility constants
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_CLASS_ONLY = 'class_only';
    const VISIBILITY_PRIVATE = 'private';

    // File type constants
    const FILE_TYPE_PDF = 'pdf';
    const FILE_TYPE_DOC = 'doc';
    const FILE_TYPE_DOCX = 'docx';
    const FILE_TYPE_VIDEO = 'video';
    const FILE_TYPE_IMAGE = 'image';
    const FILE_TYPE_OTHER = 'other';

    /**
     * Relationships
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function syllabusViews()
    {
        return $this->hasMany(SyllabusView::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeByFileType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function getFileUrl()
    {
        if ($this->file_path) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    public function getFileSizeFormatted()
    {
        if (!$this->file_size) return '0 B';
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isVideo()
    {
        return $this->file_type === self::FILE_TYPE_VIDEO;
    }

    public function isPdf()
    {
        return $this->file_type === self::FILE_TYPE_PDF;
    }

    public function isDocument()
    {
        return in_array($this->file_type, [self::FILE_TYPE_DOC, self::FILE_TYPE_DOCX]);
    }

    public function canBeViewedBy($user)
    {
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        if ($this->visibility === self::VISIBILITY_PRIVATE) {
            return $user->id === $this->teacher_id || $user->id === $this->created_by;
        }

        if ($this->visibility === self::VISIBILITY_CLASS_ONLY) {
            // Check if user is a student in this class or the teacher
            if ($user->role === 'student') {
                return $user->student && $user->student->class_id === $this->class_id;
            }
            return $user->id === $this->teacher_id || $user->id === $this->created_by;
        }

        return false;
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    /**
     * Static Methods
     */
    public static function getVisibilityOptions()
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_CLASS_ONLY => 'Class Only',
            self::VISIBILITY_PRIVATE => 'Private'
        ];
    }

    public static function getFileTypeOptions()
    {
        return [
            self::FILE_TYPE_PDF => 'PDF',
            self::FILE_TYPE_DOC => 'Word Document',
            self::FILE_TYPE_DOCX => 'Word Document (DOCX)',
            self::FILE_TYPE_VIDEO => 'Video',
            self::FILE_TYPE_IMAGE => 'Image',
            self::FILE_TYPE_OTHER => 'Other'
        ];
    }

    public static function getPopularSyllabi($limit = 10)
    {
        return self::active()
            ->orderBy('view_count', 'desc')
            ->orderBy('download_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getRecentSyllabi($limit = 10)
    {
        return self::active()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getSyllabusStats()
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'by_type' => self::selectRaw('file_type, COUNT(*) as count')
                ->groupBy('file_type')
                ->pluck('count', 'file_type'),
            'by_subject' => self::with('subject')
                ->selectRaw('subject_id, COUNT(*) as count')
                ->groupBy('subject_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->subject->name ?? 'Unknown' => $item->count];
                }),
            'total_downloads' => self::sum('download_count'),
            'total_views' => self::sum('view_count'),
            'recent_uploads' => self::recent(7)->count()
        ];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($syllabus) {
            // Delete associated file when syllabus is deleted
            if ($syllabus->file_path && Storage::exists($syllabus->file_path)) {
                Storage::delete($syllabus->file_path);
            }
        });
    }
}
