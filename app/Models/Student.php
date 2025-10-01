<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admission_no',
        'name',
        'father_name',
        'mother_name',
        'dob',
        'aadhaar',
        'class_id',
        'documents',
        'documents_verified_data',
        'verification_status',
        'status',
        'verified',
        'meta',
    ];

    protected $casts = [
        'documents' => 'array',
        'documents_verified_data' => 'array',
        'meta' => 'array',
        'dob' => 'date',
    ];

    // relationships
    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function documentVerifications()
    {
        return $this->hasMany(DocumentVerification::class);
    }

    // helper to get public URL for stored file
    public function documentUrl($key)
    {
        if (! $this->documents || ! isset($this->documents[$key])) {
            return null;
        }
        return Storage::url($this->documents[$key]);
    }
}
