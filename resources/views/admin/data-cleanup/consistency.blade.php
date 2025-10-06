@extends('layouts.admin')

@section('title', 'Data Consistency Checks')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-check-double mr-2"></i>
                        Data Consistency Checks
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="runConsistencyCheck()">
                            <i class="fas fa-sync mr-1"></i>
                            Run Check
                        </button>
                        <a href="{{ route('admin.data-cleanup.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-ban"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $issues['invalid_users'] ?? 0 }}</h3>
                                    <p>Invalid User References</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $issues['classes_without_teachers'] ?? 0 }}</h3>
                                    <p>Classes Without Teachers</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $issues['invalid_attendance'] ?? 0 }}</h3>
                                    <p>Invalid Attendance Records</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3>{{ $issues['invalid_fees'] ?? 0 }}</h3>
                                    <p>Invalid Fee Records</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consistency Issues Tabs -->
                    <ul class="nav nav-tabs" id="consistencyTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="users-tab" data-toggle="tab" href="#users" role="tab">
                                <i class="fas fa-user-times mr-1"></i>
                                Invalid Users 
                                <span class="badge badge-danger">{{ count($details['invalid_users'] ?? []) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="teachers-tab" data-toggle="tab" href="#teachers" role="tab">
                                <i class="fas fa-chalkboard-teacher mr-1"></i>
                                Classes Without Teachers 
                                <span class="badge badge-warning">{{ count($details['classes_without_teachers'] ?? []) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                                <i class="fas fa-calendar-times mr-1"></i>
                                Invalid Attendance 
                                <span class="badge badge-info">{{ count($details['invalid_attendance'] ?? []) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="fees-tab" data-toggle="tab" href="#fees" role="tab">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                Invalid Fees 
                                <span class="badge badge-secondary">{{ count($details['invalid_fees'] ?? []) }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="consistencyTabContent">
                        <!-- Invalid Users Tab -->
                        <div class="tab-pane fade show active" id="users" role="tabpanel">
                            @if(count($details['invalid_users'] ?? []) > 0)
                                <div class="card">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-times mr-2"></i>
                                            Records with Invalid User References
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('admin.data-cleanup.fix-consistency') }}" method="POST" 
                                              onsubmit="return confirmFix('invalid_users')">
                                            @csrf
                                            <input type="hidden" name="issue_type" value="invalid_users">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Table</th>
                                                            <th>Record ID</th>
                                                            <th>Invalid User ID</th>
                                                            <th>Field</th>
                                                            <th>Created</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($details['invalid_users'] as $record)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="record_ids[]" 
                                                                           value="{{ $record->table }}.{{ $record->id }}" checked>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-primary">{{ $record->table }}</span>
                                                                </td>
                                                                <td>{{ $record->id }}</td>
                                                                <td>
                                                                    <span class="badge badge-danger">{{ $record->user_id }}</span>
                                                                </td>
                                                                <td>{{ $record->field }}</td>
                                                                <td>
                                                                    <small>{{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('Y-m-d H:i') : 'N/A' }}</small>
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" 
                                                                            onclick="viewRecord('{{ $record->table }}', {{ $record->id }})">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Fix Action:</label>
                                                        <select name="fix_action" class="form-control" required>
                                                            <option value="set_null">Set user reference to NULL</option>
                                                            <option value="delete">Delete invalid records</option>
                                                            <option value="assign_admin">Assign to admin user</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-tools mr-1"></i>
                                                        Fix Selected Issues
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No invalid user references found.
                                </div>
                            @endif
                        </div>

                        <!-- Classes Without Teachers Tab -->
                        <div class="tab-pane fade" id="teachers" role="tabpanel">
                            @if(count($details['classes_without_teachers'] ?? []) > 0)
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                                            Classes Without Active Teachers
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('admin.data-cleanup.fix-consistency') }}" method="POST" 
                                              onsubmit="return confirmFix('classes_without_teachers')">
                                            @csrf
                                            <input type="hidden" name="issue_type" value="classes_without_teachers">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Class ID</th>
                                                            <th>Class Name</th>
                                                            <th>Section</th>
                                                            <th>Current Teacher ID</th>
                                                            <th>Student Count</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($details['classes_without_teachers'] as $class)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="record_ids[]" 
                                                                           value="{{ $class->id }}" checked>
                                                                </td>
                                                                <td>{{ $class->id }}</td>
                                                                <td>
                                                                    <strong>{{ $class->name }}</strong>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-secondary">{{ $class->section }}</span>
                                                                </td>
                                                                <td>
                                                                    @if($class->class_teacher_id)
                                                                        <span class="badge badge-danger">{{ $class->class_teacher_id }} (Invalid)</span>
                                                                    @else
                                                                        <span class="text-muted">Not assigned</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-info">{{ $class->students_count ?? 0 }}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-{{ $class->is_active ? 'success' : 'secondary' }}">
                                                                        {{ $class->is_active ? 'Active' : 'Inactive' }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" 
                                                                            onclick="viewClass({{ $class->id }})">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Fix Action:</label>
                                                        <select name="fix_action" class="form-control" required>
                                                            <option value="unassign">Remove invalid teacher assignment</option>
                                                            <option value="deactivate">Deactivate classes without teachers</option>
                                                            <option value="assign_available">Assign available teacher</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-tools mr-1"></i>
                                                        Fix Selected Classes
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    All classes have valid teacher assignments.
                                </div>
                            @endif
                        </div>

                        <!-- Invalid Attendance Tab -->
                        <div class="tab-pane fade" id="attendance" role="tabpanel">
                            @if(count($details['invalid_attendance'] ?? []) > 0)
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-calendar-times mr-2"></i>
                                            Invalid Attendance Records
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('admin.data-cleanup.fix-consistency') }}" method="POST" 
                                              onsubmit="return confirmFix('invalid_attendance')">
                                            @csrf
                                            <input type="hidden" name="issue_type" value="invalid_attendance">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Record ID</th>
                                                            <th>Student ID</th>
                                                            <th>Date</th>
                                                            <th>Status</th>
                                                            <th>Issue</th>
                                                            <th>Created</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($details['invalid_attendance'] as $attendance)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="record_ids[]" 
                                                                           value="{{ $attendance->id }}" checked>
                                                                </td>
                                                                <td>{{ $attendance->id }}</td>
                                                                <td>
                                                                    @if($attendance->student_id)
                                                                        <span class="badge badge-{{ $attendance->student_exists ? 'success' : 'danger' }}">
                                                                            {{ $attendance->student_id }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">NULL</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $attendance->date }}</td>
                                                                <td>
                                                                    <span class="badge badge-secondary">{{ $attendance->status }}</span>
                                                                </td>
                                                                <td>
                                                                    @if(!$attendance->student_exists)
                                                                        <span class="text-danger">Student not found</span>
                                                                    @elseif($attendance->future_date)
                                                                        <span class="text-warning">Future date</span>
                                                                    @else
                                                                        <span class="text-info">Other issue</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <small>{{ $attendance->created_at ? \Carbon\Carbon::parse($attendance->created_at)->format('Y-m-d H:i') : 'N/A' }}</small>
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" 
                                                                            onclick="viewAttendance({{ $attendance->id }})">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Fix Action:</label>
                                                        <select name="fix_action" class="form-control" required>
                                                            <option value="delete">Delete invalid records</option>
                                                            <option value="correct_date">Correct future dates</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-info">
                                                        <i class="fas fa-tools mr-1"></i>
                                                        Fix Selected Records
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No invalid attendance records found.
                                </div>
                            @endif
                        </div>

                        <!-- Invalid Fees Tab -->
                        <div class="tab-pane fade" id="fees" role="tabpanel">
                            @if(count($details['invalid_fees'] ?? []) > 0)
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            Invalid Fee Records
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('admin.data-cleanup.fix-consistency') }}" method="POST" 
                                              onsubmit="return confirmFix('invalid_fees')">
                                            @csrf
                                            <input type="hidden" name="issue_type" value="invalid_fees">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Record ID</th>
                                                            <th>Student ID</th>
                                                            <th>Amount</th>
                                                            <th>Due Date</th>
                                                            <th>Status</th>
                                                            <th>Issue</th>
                                                            <th>Created</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($details['invalid_fees'] as $fee)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="record_ids[]" 
                                                                           value="{{ $fee->id }}" checked>
                                                                </td>
                                                                <td>{{ $fee->id }}</td>
                                                                <td>
                                                                    @if($fee->student_id)
                                                                        <span class="badge badge-{{ $fee->student_exists ? 'success' : 'danger' }}">
                                                                            {{ $fee->student_id }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">NULL</span>
                                                                    @endif
                                                                </td>
                                                                <td>₹{{ number_format($fee->amount, 2) }}</td>
                                                                <td>{{ $fee->due_date }}</td>
                                                                <td>
                                                                    <span class="badge badge-secondary">{{ $fee->status }}</span>
                                                                </td>
                                                                <td>
                                                                    @if(!$fee->student_exists)
                                                                        <span class="text-danger">Student not found</span>
                                                                    @elseif($fee->negative_amount)
                                                                        <span class="text-warning">Negative amount</span>
                                                                    @else
                                                                        <span class="text-info">Other issue</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <small>{{ $fee->created_at ? \Carbon\Carbon::parse($fee->created_at)->format('Y-m-d H:i') : 'N/A' }}</small>
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" 
                                                                            onclick="viewFee({{ $fee->id }})">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Fix Action:</label>
                                                        <select name="fix_action" class="form-control" required>
                                                            <option value="delete">Delete invalid records</option>
                                                            <option value="correct_amount">Correct negative amounts</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-secondary">
                                                        <i class="fas fa-tools mr-1"></i>
                                                        Fix Selected Records
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No invalid fee records found.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function runConsistencyCheck() {
    if (confirm('This will run a comprehensive data consistency check. It may take a few minutes. Continue?')) {
        window.location.href = '{{ route("admin.data-cleanup.consistency") }}?refresh=1';
    }
}

function confirmFix(issueType) {
    const messages = {
        'invalid_users': 'This will fix all selected records with invalid user references. The action cannot be undone.',
        'classes_without_teachers': 'This will fix all selected classes without valid teachers. The action cannot be undone.',
        'invalid_attendance': 'This will fix all selected invalid attendance records. The action cannot be undone.',
        'invalid_fees': 'This will fix all selected invalid fee records. The action cannot be undone.'
    };
    
    return confirm(`Are you sure you want to proceed?\n\n${messages[issueType]}`);
}

function viewRecord(table, id) {
    // Implement record viewing functionality
    alert(`Viewing ${table} record ID: ${id}`);
}

function viewClass(id) {
    // Implement class viewing functionality
    alert(`Viewing class ID: ${id}`);
}

function viewAttendance(id) {
    // Implement attendance viewing functionality
    alert(`Viewing attendance record ID: ${id}`);
}

function viewFee(id) {
    // Implement fee viewing functionality
    alert(`Viewing fee record ID: ${id}`);
}
</script>
@endpush