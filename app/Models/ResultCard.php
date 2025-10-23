<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id','exam_id','class_id','template_id','format',
        'total_marks','max_marks','percentage','grade','position',
        'card_data','pdf_path','generated_at'
    ];

    protected $casts = [
        'total_marks' => 'float',
        'max_marks' => 'float',
        'percentage' => 'float',
        'card_data' => 'array',
        'generated_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function classModel() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function template() { return $this->belongsTo(ResultTemplate::class, 'template_id'); }
}