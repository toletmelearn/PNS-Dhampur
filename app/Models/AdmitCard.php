<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmitCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id','class_id','student_id','template_id','seat_allocation_id',
        'admit_card_no','barcode','qr_code','pdf_path','html_snapshot','is_published','generated_at'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'generated_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function classModel() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function template() { return $this->belongsTo(AdmitTemplate::class, 'template_id'); }
    public function seatAllocation() { return $this->belongsTo(ExamSeatAllocation::class, 'seat_allocation_id'); }

    public function verificationLogs()
    {
        return $this->hasMany(AdmitVerificationLog::class);
    }
}