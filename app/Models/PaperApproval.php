<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaperApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'submission_id', 'approved_by', 'status', 'remarks'
    ];

    public function submission()
    {
        return $this->belongsTo(PaperSubmission::class, 'submission_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}