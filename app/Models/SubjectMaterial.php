<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SubjectMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subject_materials';

    protected $fillable = [
        'daily_syllabus_id',
        'uploaded_by',
        'type',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_disk',
        'visibility',
        'is_active',
        'download_count',
        'view_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function syllabus()
    {
        return $this->belongsTo(DailySyllabus::class, 'daily_syllabus_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function comments()
    {
        return $this->hasMany(MaterialComment::class, 'material_id');
    }

    public function accessLogs()
    {
        return $this->hasMany(StudentAccessLog::class, 'material_id');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function existsOnDisk(): bool
    {
        $disk = $this->storage_disk ?: 'public';
        return $this->file_path && Storage::disk($disk)->exists($this->file_path);
    }
}
