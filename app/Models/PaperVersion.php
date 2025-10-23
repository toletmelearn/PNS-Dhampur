<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaperVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_paper_id', 'version_number', 'content_text', 'file_path', 'mime_type', 'created_by'
    ];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}