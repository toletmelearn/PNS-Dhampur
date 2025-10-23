<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryBook extends Model
{
    use HasFactory;

    protected $table = 'library_books';

    protected $fillable = [
        'title','isbn','author','publisher','classification','language','edition','pages','shelf_location','condition','barcode','acquisition_date','purchase_price','donor','status','notes'
    ];

    public function issues()
    {
        return $this->hasMany(LibraryIssue::class, 'book_id');
    }
}
