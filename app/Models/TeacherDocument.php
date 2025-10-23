<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TeacherDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'category_id',
        'document_type',
        'original_name',
        'file_path',
        'file_extension',
        'file_size',
        'mime_type',
        'status',
        'admin_comments',
        'expiry_date',
        'is_expired',
        'uploaded_by',
        'reviewed_by',
        'reviewed_at',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'expiry_date' => 'date',
        'reviewed_at' => 'datetime',
        'is_expired' => 'boolean'
    ];

    // Document type constants
    const DOCUMENT_TYPES = [
        'resume' => 'Resume',
        'certificate' => 'Certificate',
        'degree' => 'Degree',
        'id_proof' => 'ID Proof',
        'experience_letter' => 'Experience Letter'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    // Allowed file types and sizes
    const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB in bytes

    /**
     * Relationships
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class, 'teacher_document_id')->orderBy('version_number', 'desc');
    }

    public function currentVersion()
    {
        return $this->hasOne(DocumentVersion::class, 'teacher_document_id')
            ->where('is_current_version', true);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_expired', true);
    }

    public function scopeExpiringWithin($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', Carbon::now()->addDays($days))
                    ->where('is_expired', false);
    }

    /**
     * Accessors & Mutators
     */
    public function getDocumentTypeNameAttribute()
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    public function getIsExpiringSoonAttribute()
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return $this->expiry_date->diffInDays(Carbon::now()) <= 30;
    }

    /**
     * Helper Methods
     */
    public function markAsVerified($reviewedBy, $comments = null)
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'admin_comments' => $comments
        ]);
    }

    public function markAsRejected($reviewedBy, $comments)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'admin_comments' => $comments
        ]);
    }

    public function checkExpiry()
    {
        if ($this->expiry_date && $this->expiry_date->isPast()) {
            $this->update(['is_expired' => true]);
            return true;
        }
        return false;
    }

    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    /**
     * Versioning helper: create a new version from stored file metadata
     */
    public function createNewVersion(array $meta): DocumentVersion
    {
        // Mark existing current version as not current
        DocumentVersion::where('teacher_document_id', $this->id)
            ->update(['is_current_version' => false]);

        $nextVersion = (int) (DocumentVersion::where('teacher_document_id', $this->id)->max('version_number') ?? 0) + 1;

        return DocumentVersion::create([
            'teacher_document_id' => $this->id,
            'version_number' => $nextVersion,
            'original_name' => $meta['original_name'] ?? $this->original_name,
            'file_path' => $meta['file_path'] ?? $this->file_path,
            'file_extension' => $meta['file_extension'] ?? $this->file_extension,
            'file_size' => $meta['file_size'] ?? $this->file_size,
            'mime_type' => $meta['mime_type'] ?? $this->mime_type,
            'status' => DocumentVersion::STATUS_PENDING,
            'change_summary' => $meta['change_summary'] ?? null,
            'is_current_version' => true,
            'created_by' => $meta['created_by'] ?? $this->uploaded_by,
            'checksum' => $meta['checksum'] ?? null,
            'metadata' => $meta['metadata'] ?? []
        ]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->deleteFile();
        });
    }
}