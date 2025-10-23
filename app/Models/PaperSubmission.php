<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaperSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_paper_id', 'submitted_by', 'content_text', 'file_path', 'mime_type', 'status', 'notes'
    ];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approval()
    {
        return $this->hasOne(PaperApproval::class, 'submission_id');
    }
}