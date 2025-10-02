<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        // Return view for web requests
        return view('finance.index');
    }
}
