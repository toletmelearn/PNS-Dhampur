@extends('layouts.app')

@section('title','Library Management')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Library</h4>
        @if(session('status'))
            <span class="badge bg-success">{{ session('status') }}</span>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">Books</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($books as $book)
                                    <tr>
                                        <td>{{ $book->title }}</td>
                                        <td>{{ $book->author }}</td>
                                        <td>{{ $book->isbn }}</td>
                                        <td>{{ $book->status }}</td>
                                        <td>
                                            <form action="{{ route('library.issue', $book->id) }}" method="POST" class="d-flex gap-2">
                                                @csrf
                                                <input type="number" name="student_id" class="form-control form-control-sm" placeholder="Student ID" required>
                                                <input type="date" name="due_at" class="form-control form-control-sm" placeholder="Due date">
                                                <button type="submit" class="btn btn-sm btn-primary">Issue</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No books found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $books->links() }}
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Recent Issues</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($recentIssues as $issue)
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <strong>{{ $issue->book->title }}</strong>
                                    <div class="small text-muted">Issued: {{ $issue->issued_at?->format('d M, Y') }}</div>
                                </div>
                                @if(!$issue->returned_at)
                                <form action="{{ route('library.return', $issue->id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Mark Returned</button>
                                </form>
                                @else
                                    <span class="badge bg-success">Returned</span>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No recent issues</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
