<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublish extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id','class_id','template_id','format','status','published_at','published_by'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function classModel() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function template() { return $this->belongsTo(ResultTemplate::class, 'template_id'); }
    public function publisher() { return $this->belongsTo(User::class, 'published_by'); }
}