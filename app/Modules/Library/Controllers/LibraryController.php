<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\LibraryBook;
use App\Models\LibraryIssue;

class LibraryController extends Controller
{
    public function index()
    {
        $books = LibraryBook::orderBy('title')->paginate(20);
        $recentIssues = LibraryIssue::with(['book'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        return view('library.index', compact('books', 'recentIssues'));
    }

    public function issue(Request $request, int $bookId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'due_at' => 'nullable|date',
        ]);

        $book = LibraryBook::findOrFail($bookId);

        // Check if already issued and not returned
        $openIssue = LibraryIssue::where('book_id', $bookId)
            ->whereNull('returned_at')
            ->first();
        if ($openIssue) {
            return back()->withErrors(['book' => 'Book is already issued and not yet returned.']);
        }

        LibraryIssue::create([
            'book_id' => $bookId,
            'student_id' => $request->student_id,
            'issued_by' => Auth::id(),
            'issued_at' => now(),
            'due_at' => $request->due_at,
            'status' => 'issued',
        ]);

        return redirect()->route('library.index')->with('status', 'Book issued successfully');
    }

    public function return(int $issueId)
    {
        $issue = LibraryIssue::findOrFail($issueId);
        $issue->update([
            'returned_at' => now(),
            'status' => 'returned',
        ]);

        return redirect()->route('library.index')->with('status', 'Book returned successfully');
    }
}
