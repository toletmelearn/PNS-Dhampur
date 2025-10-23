@extends('layouts.app')

@section('title', 'Parent Portal')

@section('content')
<div class="container py-3">
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Parent Portal</h4>
        <span class="ms-2 badge bg-primary">Mobile-Friendly</span>
    </div>

    @if($children->isEmpty())
        <div class="alert alert-info">No linked children found.</div>
    @else
        <div class="row g-3">
            @foreach($children as $child)
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title mb-1">{{ $child->name }}</h6>
                                    <small class="text-muted">Class: {{ $child->class_id }} • Roll: {{ $child->roll_number }}</small>
                                </div>
                                <a href="{{ route('parentportal.child.progress', $child->id) }}" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fw-bold">{{ $progress[$child->id]['attendance_this_month'] ?? 0 }}</div>
                                    <small class="text-muted">Days</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-danger">{{ $progress[$child->id]['absent_this_month'] ?? 0 }}</div>
                                    <small class="text-muted">Absent</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-warning">₹{{ number_format($progress[$child->id]['due_fees'] ?? 0) }}</div>
                                    <small class="text-muted">Fees Due</small>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h6 class="mb-2">Recent Results</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Marks</th>
                                                <th>Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(($progress[$child->id]['latest_results'] ?? []) as $res)
                                                <tr>
                                                    <td>{{ $res->subject }}</td>
                                                    <td>{{ $res->marks_obtained }}/{{ $res->total_marks }}</td>
                                                    <td>{{ $res->grade }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-muted">No recent results</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
