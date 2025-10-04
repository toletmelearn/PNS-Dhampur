@extends('layouts.app')

@section('title', 'Student History - ' . $student->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-clock text-info me-2"></i>
                        Student Verification History
                    </h4>
                    <div>
                        <a href="{{ route('students.show', $student) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Student
                        </a>
                        <a href="{{ route('student-verifications.audit-trail.export', ['student_id' => $student->id]) }}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i>Export History
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Student Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="card-title">Student Information</h6>
                                            <p class="mb-1"><strong>Name:</strong> {{ $student->name }}</p>
                                            <p class="mb-1"><strong>Roll Number:</strong> {{ $student->roll_number }}</p>
                                            <p class="mb-1"><strong>Class:</strong> {{ $student->class_name }}</p>
                                            <p class="mb-0"><strong>Section:</strong> {{ $student->section }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="card-title">Contact Information</h6>
                                            <p class="mb-1"><strong>Father's Name:</strong> {{ $student->father_name }}</p>
                                            <p class="mb-1"><strong>Mother's Name:</strong> {{ $student->mother_name }}</p>
                                            <p class="mb-0"><strong>Phone:</strong> {{ $student->phone ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Verification Summary</h6>
                                    <h3 class="mb-1">{{ $verifications->count() }}</h3>
                                    <p class="mb-0">Total Verifications</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Status Summary -->
                    @if($verifications->count() > 0)
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $verifications->where('status', 'verified')->count() }}</h4>
                                        <p class="mb-0">Verified</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $verifications->whereIn('status', ['pending', 'manual_review'])->count() }}</h4>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $verifications->where('status', 'rejected')->count() }}</h4>
                                        <p class="mb-0">Rejected</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h4>{{ $verifications->where('status', 'processing')->count() }}</h4>
                                        <p class="mb-0">Processing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Verifications List -->
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-list text-primary me-2"></i>
                            All Verifications
                        </h5>
                        
                        @if($verifications->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Document Type</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($verifications as $verification)
                                            <tr>
                                                <td>#{{ $verification->id }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ ucfirst(str_replace('_', ' ', $verification->document_type)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $verification->status === 'verified' ? 'success' : ($verification->status === 'rejected' ? 'danger' : 'warning') }}">
                                                        {{ ucfirst(str_replace('_', ' ', $verification->status)) }}
                                                    </span>
                                                </td>
                                                <td>{{ $verification->created_at->format('M d, Y H:i') }}</td>
                                                <td>{{ $verification->updated_at->format('M d, Y H:i') }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('student-verifications.show', $verification) }}" 
                                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('student-verifications.history', $verification) }}" 
                                                           class="btn btn-sm btn-outline-info" title="View History">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No verifications found for this student.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Recent Activity Timeline -->
                    <div class="timeline-container">
                        <h5 class="mb-3">
                            <i class="fas fa-clock text-primary me-2"></i>
                            Recent Activity
                        </h5>
                        
                        @if($auditLogs->count() > 0)
                            <div class="timeline">
                                @foreach($auditLogs as $log)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-{{ $log->action === 'approved' ? 'success' : ($log->action === 'rejected' ? 'danger' : 'primary') }}">
                                            <i class="fas fa-{{ 
                                                $log->action === 'created' ? 'plus' : 
                                                ($log->action === 'approved' ? 'check' : 
                                                ($log->action === 'rejected' ? 'times' : 
                                                ($log->action === 'status_changed' ? 'exchange-alt' : 
                                                ($log->action === 'ocr_processed' ? 'eye' : 
                                                ($log->action === 'mismatch_analyzed' ? 'search' : 
                                                ($log->action === 'auto_resolved' ? 'magic' : 
                                                ($log->action === 'manual_resolved' ? 'hand-paper' : 
                                                ($log->action === 'reprocessed' ? 'redo' : 'info')))))))) }}"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-header d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="timeline-title mb-1">
                                                        {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                                        @if($log->verification)
                                                            <small class="text-muted">
                                                                (Verification #{{ $log->verification->id }} - {{ ucfirst($log->verification->document_type) }})
                                                            </small>
                                                        @endif
                                                    </h6>
                                                    <small class="text-muted">
                                                        {{ $log->created_at->format('M d, Y H:i:s') }}
                                                        @if($log->user)
                                                            by {{ $log->user->name }}
                                                        @endif
                                                    </small>
                                                </div>
                                                @if($log->verification)
                                                    <a href="{{ route('student-verifications.show', $log->verification) }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            
                                            @if($log->details)
                                                <div class="timeline-details mt-2">
                                                    @if(is_array($log->details))
                                                        @foreach($log->details as $key => $value)
                                                            <div class="detail-item">
                                                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                                @if(is_array($value))
                                                                    <ul class="mb-0 mt-1">
                                                                        @foreach($value as $item)
                                                                            <li>{{ $item }}</li>
                                                                        @endforeach
                                                                    </ul>
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <p class="mb-0">{{ $log->details }}</p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @if($auditLogs->hasPages())
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $auditLogs->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activity found for this student.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.timeline-title {
    color: #495057;
    font-weight: 600;
}

.detail-item {
    margin-bottom: 8px;
}

.detail-item:last-child {
    margin-bottom: 0;
}
</style>
@endsection