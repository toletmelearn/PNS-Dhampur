@extends('layouts.app')

@section('title', 'SR Register - Student Records')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">SR Register</h2>
                    <p class="text-muted mb-0">Comprehensive Student Records Management</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('sr-register.bulk-entry') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Bulk Entry
                    </a>
                    <a href="{{ route('sr-register.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Add Record
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Total Records</h6>
                            <h3 class="mb-0">{{ $srRegisters->total() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Classes Covered</h6>
                            <h3 class="mb-0">{{ $classes->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-school fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Subjects</h6>
                            <h3 class="mb-0">{{ $subjects->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-book fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Academic Years</h6>
                            <h3 class="mb-0">{{ $academicYears->count() }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filters & Search
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('sr-register.index') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-select">
                            <option value="">All Subjects</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <select name="academic_year" id="academic_year" class="form-select">
                            <option value="">All Years</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="term" class="form-label">Term</label>
                        <select name="term" id="term" class="form-select">
                            <option value="">All Terms</option>
                            <option value="1" {{ request('term') == '1' ? 'selected' : '' }}>First Term</option>
                            <option value="2" {{ request('term') == '2' ? 'selected' : '' }}>Second Term</option>
                            <option value="3" {{ request('term') == '3' ? 'selected' : '' }}>Third Term</option>
                            <option value="annual" {{ request('term') == 'annual' ? 'selected' : '' }}>Annual</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Student name, admission no..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                        <a href="{{ route('sr-register.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportReport()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Records Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-table me-2"></i>Student Records
                <span class="badge bg-primary ms-2">{{ $srRegisters->total() }} records</span>
            </h5>
        </div>
        <div class="card-body p-0">
            @if($srRegisters->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Academic Year</th>
                                <th>Term</th>
                                <th>Attendance</th>
                                <th>Total Marks</th>
                                <th>Grade</th>
                                <th>Conduct</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($srRegisters as $record)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-white fw-bold">{{ substr($record->student->name, 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $record->student->name }}</div>
                                                <small class="text-muted">{{ $record->student->admission_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $record->class->name }}</span>
                                    </td>
                                    <td>{{ $record->subject->name }}</td>
                                    <td>{{ $record->academic_year }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $record->term_name }}</span>
                                    </td>
                                    <td>
                                        @if($record->attendance_percentage)
                                            <div class="d-flex align-items-center">
                                                <div class="progress me-2" style="width: 60px; height: 8px;">
                                                    <div class="progress-bar {{ $record->attendance_percentage >= 75 ? 'bg-success' : 'bg-warning' }}" 
                                                         style="width: {{ $record->attendance_percentage }}%"></div>
                                                </div>
                                                <small>{{ $record->formatted_attendance }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->total_marks)
                                            <span class="fw-bold">{{ $record->formatted_total_marks }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->grade)
                                            <span class="badge bg-{{ $record->grade_color }}">{{ $record->grade }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->conduct_grade)
                                            <span class="badge bg-{{ $record->conduct_grade_color }}">{{ $record->conduct_grade }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $record->last_updated_formatted }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('sr-register.show', $record) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('sr-register.edit', $record) }}" 
                                               class="btn btn-sm btn-outline-warning" title="Edit Record">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(auth()->user()->hasRole('admin'))
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteRecord({{ $record->id }})" title="Delete Record">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing {{ $srRegisters->firstItem() }} to {{ $srRegisters->lastItem() }} 
                            of {{ $srRegisters->total() }} records
                        </div>
                        {{ $srRegisters->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Records Found</h5>
                    <p class="text-muted mb-4">No SR Register records match your current filters.</p>
                    <a href="{{ route('sr-register.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add First Record
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this SR Register record?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Record</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .progress {
        background-color: #e9ecef;
    }
    
    .card-body.p-0 .table td {
        vertical-align: middle;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem;
        margin-right: 2px;
    }
    
    .badge {
        font-size: 0.75em;
    }
</style>
@endpush

@push('scripts')
<script>
    function deleteRecord(recordId) {
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = `/sr-register/${recordId}`;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    
    function exportReport() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        
        // Add export parameter
        formData.append('export', 'true');
        
        // Create URL with parameters
        const params = new URLSearchParams(formData);
        const exportUrl = `{{ route('sr-register.export-report') }}?${params.toString()}`;
        
        // Open in new window or download
        window.open(exportUrl, '_blank');
    }
    
    // Auto-submit form when filters change
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelects = document.querySelectorAll('#class_id, #subject_id, #academic_year, #term');
        
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
        
        // Search with debounce
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    });
</script>
@endpush