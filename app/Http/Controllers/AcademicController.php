<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function index(Request $request)
    {
        // Return view for web requests
        return view('academic.index');
    }
}
