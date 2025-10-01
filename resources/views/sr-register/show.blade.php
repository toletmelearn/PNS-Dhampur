@extends('layouts.app')

@section('title', 'SR Register - Student Record Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Student Record Details</h2>
                    <p class="text-muted mb-0">Comprehensive academic record for {{ $record->student->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    @can('edit', $record)
                        <a href="{{ route('sr-register.edit', $record) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Edit Record
                        </a>
                    @endcan
                    <button type="button" class="btn btn-outline-info" onclick="printRecord()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                    <a href="{{ route('sr-register.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Records
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Student Information -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Student Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-lg bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <span class="text-white fw-bold fs-2">{{ substr($record->student->name, 0, 1) }}</span>
                        </div>
                        <h4 class="mb-1">{{ $record->student->name }}</h4>
                        <p class="text-muted mb-0">{{ $record->student->admission_number }}</p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Class:</span>
                                <span class="fw-bold">{{ $record->class->name }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Subject:</span>
                                <span class="fw-bold">{{ $record->subject->name }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Academic Year:</span>
                                <span class="fw-bold">{{ $record->academic_year }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Term:</span>
                                <span class="fw-bold">
                                    @if($record->term == 'annual')
                                        Annual
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $record->term)) }} Term
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Date of Birth:</span>
                                <span class="fw-bold">{{ $record->student->date_of_birth ? $record->student->date_of_birth->format('d M Y') : 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Father's Name:</span>
                                <span class="fw-bold">{{ $record->student->father_name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Mother's Name:</span>
                                <span class="fw-bold">{{ $record->student->mother_name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Performance -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Academic Performance
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Performance Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-2 fw-bold text-primary">{{ $record->total_marks ?? 'N/A' }}</div>
                                <div class="text-muted small">Total Marks</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-2 fw-bold text-success">{{ $record->grade ?? 'N/A' }}</div>
                                <div class="text-muted small">Grade</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-2 fw-bold text-info">{{ $record->attendance_percentage ?? 'N/A' }}%</div>
                                <div class="text-muted small">Attendance</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="fs-2 fw-bold text-warning">{{ $record->conduct_grade ?? 'N/A' }}</div>
                                <div class="text-muted small">Conduct</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Marks -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Assessment Type</th>
                                    <th class="text-center">Marks Obtained</th>
                                    <th class="text-center">Maximum Marks</th>
                                    <th class="text-center">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-book me-2"></i>Theory</td>
                                    <td class="text-center">{{ $record->theory_marks ?? '-' }}</td>
                                    <td class="text-center">100</td>
                                    <td class="text-center">
                                        @if($record->theory_marks)
                                            {{ number_format($record->theory_marks, 1) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-flask me-2"></i>Practical</td>
                                    <td class="text-center">{{ $record->practical_marks ?? '-' }}</td>
                                    <td class="text-center">100</td>
                                    <td class="text-center">
                                        @if($record->practical_marks)
                                            {{ number_format($record->practical_marks, 1) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-clipboard-check me-2"></i>Internal Assessment</td>
                                    <td class="text-center">{{ $record->internal_assessment ?? '-' }}</td>
                                    <td class="text-center">100</td>
                                    <td class="text-center">
                                        @if($record->internal_assessment)
                                            {{ number_format($record->internal_assessment, 1) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-project-diagram me-2"></i>Project Work</td>
                                    <td class="text-center">{{ $record->project_marks ?? '-' }}</td>
                                    <td class="text-center">100</td>
                                    <td class="text-center">
                                        @if($record->project_marks)
                                            {{ number_format($record->project_marks, 1) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="fw-bold"><i class="fas fa-calculator me-2"></i>Total</td>
                                    <td class="text-center fw-bold">{{ $record->total_marks ?? '-' }}</td>
                                    <td class="text-center fw-bold">400</td>
                                    <td class="text-center fw-bold">
                                        @if($record->total_marks)
                                            {{ number_format(($record->total_marks / 400) * 100, 1) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Attendance & Conduct -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check me-2"></i>Attendance & Conduct
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Attendance Percentage</span>
                                <span class="fw-bold">{{ $record->attendance_percentage ?? 'N/A' }}%</span>
                            </div>
                            @if($record->attendance_percentage)
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar 
                                        @if($record->attendance_percentage >= 90) bg-success
                                        @elseif($record->attendance_percentage >= 75) bg-warning
                                        @else bg-danger
                                        @endif" 
                                        style="width: {{ $record->attendance_percentage }}%"></div>
                                </div>
                                <small class="text-muted">
                                    @if($record->attendance_percentage >= 90)
                                        Excellent Attendance
                                    @elseif($record->attendance_percentage >= 75)
                                        Good Attendance
                                    @else
                                        Poor Attendance
                                    @endif
                                </small>
                            @endif
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Conduct Grade</span>
                                <span class="badge 
                                    @if($record->conduct_grade == 'A') bg-success
                                    @elseif($record->conduct_grade == 'B') bg-primary
                                    @elseif($record->conduct_grade == 'C') bg-warning
                                    @elseif($record->conduct_grade == 'D') bg-orange
                                    @elseif($record->conduct_grade == 'E') bg-danger
                                    @else bg-secondary
                                    @endif fs-6">
                                    {{ $record->conduct_grade ?? 'Not Graded' }}
                                </span>
                            </div>
                            @if($record->conduct_grade)
                                <small class="text-muted">
                                    @switch($record->conduct_grade)
                                        @case('A')
                                            Excellent Behavior
                                            @break
                                        @case('B')
                                            Good Behavior
                                            @break
                                        @case('C')
                                            Satisfactory Behavior
                                            @break
                                        @case('D')
                                            Needs Improvement
                                            @break
                                        @case('E')
                                            Poor Behavior
                                            @break
                                    @endswitch
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks & Additional Information -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comment-alt me-2"></i>Remarks & Notes
                    </h5>
                </div>
                <div class="card-body">
                    @if($record->remarks)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            {{ $record->remarks }}
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comment-slash fa-2x mb-2"></i>
                            <p class="mb-0">No remarks available</p>
                        </div>
                    @endif

                    <!-- Performance Status -->
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Performance Status</h6>
                        <div class="d-flex align-items-center">
                            @php
                                $status = $record->getPerformanceStatus();
                                $statusClass = match($status) {
                                    'Excellent' => 'success',
                                    'Good' => 'primary',
                                    'Average' => 'warning',
                                    'Below Average' => 'orange',
                                    'Poor' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }} fs-6 me-2">{{ $status }}</span>
                            <small class="text-muted">
                                Based on overall academic performance
                            </small>
                        </div>
                    </div>

                    <!-- Comprehensive Remarks -->
                    @if($record->getComprehensiveRemarks())
                        <div class="mt-4">
                            <h6 class="fw-bold mb-3">Comprehensive Assessment</h6>
                            <div class="bg-light p-3 rounded">
                                <small class="text-muted">{{ $record->getComprehensiveRemarks() }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Record Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Record Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Created By:</span>
                                <span class="fw-bold">{{ $record->createdBy->name ?? 'System' }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Created On:</span>
                                <span class="fw-bold">{{ $record->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Last Updated:</span>
                                <span class="fw-bold">{{ $record->updated_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Updated By:</span>
                                <span class="fw-bold">{{ $record->updatedBy->name ?? 'System' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-lg {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }
    
    .bg-orange {
        background-color: #fd7e14 !important;
    }
    
    .progress {
        border-radius: 10px;
    }
    
    .progress-bar {
        border-radius: 10px;
    }
    
    @media print {
        .btn, .card-header {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function printRecord() {
        window.print();
    }
</script>
@endpush