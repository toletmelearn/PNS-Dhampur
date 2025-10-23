<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LibraryIssue extends Model
{
    use HasFactory;

    protected $table = 'library_issues';

    protected $fillable = [
        'book_id','student_id','issued_by','issued_at','due_at','returned_at','status'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
