@extends('layouts.app')

@section('title', 'Auto-Assign Substitution')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Auto-Assign Substitution</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('substitution.index') }}">Substitution</a></li>
                        <li class="breadcrumb-item active">Auto-Assign</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-account-switch me-2"></i>
                        Automatic Assignment
                    </h5>
                </div>
                <div class="card-body">
                    <form id="autoAssignForm" method="POST" action="{{ route('substitution.auto-assign') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="period" class="form-label">Period <span class="text-danger">*</span></label>
                                    <select class="form-select" id="period" name="period" required>
                                        <option value="">Select Period</option>
                                        @for($i = 1; $i <= 8; $i++)
                                            <option value="{{ $i }}">Period {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="absent_teacher_id" class="form-label">Absent Teacher</label>
                                    <select class="form-select" id="absent_teacher_id" name="absent_teacher_id">
                                        <option value="">Select Teacher (Optional)</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}">{{ $teacher->name }} - {{ $teacher->employee_id }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Assignment Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="subject_expertise">Subject Expertise</option>
                                        <option value="availability">Availability</option>
                                        <option value="workload">Workload Balance</option>
                                        <option value="performance">Performance Rating</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Substitution</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" 
                                      placeholder="Enter reason for substitution (optional)"></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-primary" id="findTeachersBtn">
                                <i class="mdi mdi-account-search me-1"></i>
                                Find Available Teachers
                            </button>
                            <button type="submit" class="btn btn-primary" id="autoAssignBtn">
                                <i class="mdi mdi-account-switch me-1"></i>
                                Auto Assign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assignment Status -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-information-outline me-2"></i>
                        Assignment Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="assignmentStatus" class="text-center">
                        <div class="text-muted">
                            <i class="mdi mdi-clock-outline display-4"></i>
                            <p class="mt-2">Ready to assign substitution</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-chart-line me-2"></i>
                        Today's Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-1">{{ $todayStats['total'] ?? 0 }}</h4>
                                <p class="text-muted mb-0">Total</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-1">{{ $todayStats['assigned'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">Assigned</p>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-warning mb-1">{{ $todayStats['pending'] ?? 0 }}</h4>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info mb-1">{{ $todayStats['available_teachers'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">Available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Teachers Results -->
    <div class="row" id="availableTeachersSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-account-group me-2"></i>
                        Available Teachers
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="availableTeachersTable">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Subject Expertise</th>
                                    <th>Current Workload</th>
                                    <th>Performance</th>
                                    <th>Availability</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="availableTeachersBody">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Assignments -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-history me-2"></i>
                        Recent Auto-Assignments
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Period</th>
                                    <th>Assigned Teacher</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAssignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->date->format('d M Y') }}</td>
                                    <td>{{ $assignment->class->name }} - {{ $assignment->class->section }}</td>
                                    <td>{{ $assignment->subject->name }}</td>
                                    <td>Period {{ $assignment->period }}</td>
                                    <td>
                                        @if($assignment->substitute_teacher)
                                            <span class="badge bg-success">{{ $assignment->substitute_teacher->name }}</span>
                                        @else
                                            <span class="badge bg-warning">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $assignment->status == 'assigned' ? 'success' : ($assignment->status == 'pending' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($assignment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewAssignment({{ $assignment->id }})">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if($assignment->status == 'pending')
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="reassignSubstitution({{ $assignment->id }})">
                                                <i class="mdi mdi-refresh"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No recent assignments found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Details Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assignment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="assignmentModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-assign form submission
    $('#autoAssignForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $('#autoAssignBtn');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Assigning...');
        
        // Update status
        $('#assignmentStatus').html(`
            <div class="text-info">
                <i class="mdi mdi-loading mdi-spin display-4"></i>
                <p class="mt-2">Processing assignment...</p>
            </div>
        `);
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#assignmentStatus').html(`
                        <div class="text-success">
                            <i class="mdi mdi-check-circle display-4"></i>
                            <p class="mt-2">Successfully assigned to ${response.data.teacher_name}</p>
                            <small class="text-muted">Assignment ID: ${response.data.assignment_id}</small>
                        </div>
                    `);
                    
                    // Show success message
                    toastr.success(response.message);
                    
                    // Reset form
                    $('#autoAssignForm')[0].reset();
                    
                    // Refresh page after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    $('#assignmentStatus').html(`
                        <div class="text-danger">
                            <i class="mdi mdi-alert-circle display-4"></i>
                            <p class="mt-2">${response.message}</p>
                        </div>
                    `);
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                $('#assignmentStatus').html(`
                    <div class="text-danger">
                        <i class="mdi mdi-alert-circle display-4"></i>
                        <p class="mt-2">Assignment failed</p>
                        <small class="text-muted">${response?.message || 'Please try again'}</small>
                    </div>
                `);
                toastr.error(response?.message || 'Assignment failed');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Find available teachers
    $('#findTeachersBtn').on('click', function() {
        const formData = $('#autoAssignForm').serialize();
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Searching...');
        
        $.ajax({
            url: '{{ route("substitution.find-free-teachers") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displayAvailableTeachers(response.data);
                    $('#availableTeachersSection').show();
                    toastr.success(`Found ${response.data.length} available teachers`);
                } else {
                    toastr.warning('No available teachers found for the selected criteria');
                    $('#availableTeachersSection').hide();
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Failed to find teachers');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});

function displayAvailableTeachers(teachers) {
    const tbody = $('#availableTeachersBody');
    tbody.empty();
    
    teachers.forEach(teacher => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <span class="avatar-title bg-primary rounded-circle">
                                ${teacher.name.charAt(0)}
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0">${teacher.name}</h6>
                            <small class="text-muted">${teacher.employee_id}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${teacher.subject_match ? 'success' : 'warning'}">
                        ${teacher.subject_match ? 'Expert' : 'General'}
                    </span>
                </td>
                <td>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-${teacher.workload_percentage > 80 ? 'danger' : (teacher.workload_percentage > 60 ? 'warning' : 'success')}" 
                             style="width: ${teacher.workload_percentage}%"></div>
                    </div>
                    <small class="text-muted">${teacher.workload_percentage}% (${teacher.current_workload}/${teacher.max_workload})</small>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            ${Array.from({length: 5}, (_, i) => 
                                `<i class="mdi mdi-star${i < Math.floor(teacher.performance_rating) ? '' : '-outline'} text-warning"></i>`
                            ).join('')}
                        </div>
                        <small class="text-muted">${teacher.performance_rating}/5</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-success">Available</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" 
                            onclick="assignTeacher(${teacher.id})">
                        <i class="mdi mdi-account-plus me-1"></i>Assign
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function assignTeacher(teacherId) {
    const formData = $('#autoAssignForm').serialize() + `&preferred_teacher_id=${teacherId}`;
    
    $.ajax({
        url: '{{ route("substitution.auto-assign") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Assignment failed');
        }
    });
}

function viewAssignment(assignmentId) {
    $.ajax({
        url: `/substitution/${assignmentId}`,
        method: 'GET',
        success: function(response) {
            $('#assignmentModalBody').html(response);
            $('#assignmentModal').modal('show');
        },
        error: function() {
            toastr.error('Failed to load assignment details');
        }
    });
}

function reassignSubstitution(assignmentId) {
    if (confirm('Are you sure you want to reassign this substitution?')) {
        $.ajax({
            url: `/substitution/${assignmentId}/reassign`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Reassignment failed');
            }
        });
    }
}
</script>
@endpush