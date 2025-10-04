@extends('layouts.app')

@section('title', 'Available Teachers')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Available Teachers</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('substitution.index') }}">Substitution</a></li>
                        <li class="breadcrumb-item active">Available Teachers</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-filter-variant me-2"></i>
                        Search Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form id="searchForm" method="POST" action="{{ route('substitution.find-free-teachers') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class</label>
                                    <select class="form-select" id="class_id" name="class_id">
                                        <option value="">All Classes</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }} - {{ $class->section }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject</label>
                                    <select class="form-select" id="subject_id" name="subject_id">
                                        <option value="">All Subjects</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="{{ request('date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="period" class="form-label">Period</label>
                                    <select class="form-select" id="period" name="period">
                                        <option value="">All Periods</option>
                                        @for($i = 1; $i <= 8; $i++)
                                            <option value="{{ $i }}" {{ request('period') == $i ? 'selected' : '' }}>
                                                Period {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Sort By</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="subject_expertise" {{ request('priority') == 'subject_expertise' ? 'selected' : '' }}>Subject Expertise</option>
                                        <option value="availability" {{ request('priority') == 'availability' ? 'selected' : '' }}>Availability</option>
                                        <option value="workload" {{ request('priority') == 'workload' ? 'selected' : '' }}>Workload</option>
                                        <option value="performance" {{ request('priority') == 'performance' ? 'selected' : '' }}>Performance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <select class="form-select" id="department" name="department">
                                        <option value="">All Departments</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                                {{ $department }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="experience" class="form-label">Minimum Experience (Years)</label>
                                    <select class="form-select" id="experience" name="experience">
                                        <option value="">Any Experience</option>
                                        <option value="1" {{ request('experience') == '1' ? 'selected' : '' }}>1+ Years</option>
                                        <option value="3" {{ request('experience') == '3' ? 'selected' : '' }}>3+ Years</option>
                                        <option value="5" {{ request('experience') == '5' ? 'selected' : '' }}>5+ Years</option>
                                        <option value="10" {{ request('experience') == '10' ? 'selected' : '' }}>10+ Years</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="subject_experts_only" name="subject_experts_only" 
                                       {{ request('subject_experts_only') ? 'checked' : '' }}>
                                <label class="form-check-label" for="subject_experts_only">
                                    Subject Experts Only
                                </label>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="resetFilters()">
                                    <i class="mdi mdi-refresh me-1"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary" id="searchBtn">
                                    <i class="mdi mdi-magnify me-1"></i>Search Teachers
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-primary rounded-circle">
                                    <i class="mdi mdi-account-group"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="totalTeachers">{{ $stats['total_teachers'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Total Teachers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="mdi mdi-account-check"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="availableTeachers">{{ $stats['available_teachers'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Available Now</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-warning rounded-circle">
                                    <i class="mdi mdi-account-clock"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="busyTeachers">{{ $stats['busy_teachers'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Currently Busy</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-danger rounded-circle">
                                    <i class="mdi mdi-account-off"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="onLeave">{{ $stats['on_leave'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">On Leave</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Teachers List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-account-search me-2"></i>
                        Available Teachers
                        <span class="badge bg-primary ms-2" id="teacherCount">{{ count($teachers ?? []) }}</span>
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleView('grid')">
                            <i class="mdi mdi-view-grid"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm active" onclick="toggleView('list')">
                            <i class="mdi mdi-view-list"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading State -->
                    <div id="loadingState" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Searching for available teachers...</p>
                    </div>

                    <!-- List View -->
                    <div id="listView">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Department</th>
                                        <th>Subjects</th>
                                        <th>Experience</th>
                                        <th>Current Workload</th>
                                        <th>Performance</th>
                                        <th>Availability</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="teachersTableBody">
                                    @forelse($teachers ?? [] as $teacher)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    @if($teacher->profile_photo)
                                                        <img src="{{ asset('storage/' . $teacher->profile_photo) }}" 
                                                             class="avatar-img rounded-circle" alt="{{ $teacher->name }}">
                                                    @else
                                                        <span class="avatar-title bg-primary rounded-circle">
                                                            {{ substr($teacher->name, 0, 1) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $teacher->name }}</h6>
                                                    <small class="text-muted">{{ $teacher->employee_id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $teacher->department ?? 'N/A' }}</td>
                                        <td>
                                            @if($teacher->subjects && count($teacher->subjects) > 0)
                                                @foreach($teacher->subjects->take(2) as $subject)
                                                    <span class="badge bg-light text-dark me-1">{{ $subject->name }}</span>
                                                @endforeach
                                                @if(count($teacher->subjects) > 2)
                                                    <span class="badge bg-secondary">+{{ count($teacher->subjects) - 2 }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">No subjects assigned</span>
                                            @endif
                                        </td>
                                        <td>{{ $teacher->experience ?? 0 }} years</td>
                                        <td>
                                            @php
                                                $workloadPercentage = $teacher->workload_percentage ?? 0;
                                                $currentWorkload = $teacher->current_workload ?? 0;
                                                $maxWorkload = $teacher->max_workload ?? 40;
                                            @endphp
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $workloadPercentage > 80 ? 'danger' : ($workloadPercentage > 60 ? 'warning' : 'success') }}" 
                                                     style="width: {{ $workloadPercentage }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $workloadPercentage }}% ({{ $currentWorkload }}/{{ $maxWorkload }})</small>
                                        </td>
                                        <td>
                                            @php $rating = $teacher->performance_rating ?? 3; @endphp
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="mdi mdi-star{{ $i <= $rating ? '' : '-outline' }} text-warning"></i>
                                                    @endfor
                                                </div>
                                                <small class="text-muted">{{ number_format($rating, 1) }}/5</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Available</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="viewTeacherDetails({{ $teacher->id }})">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="assignTeacher({{ $teacher->id }})">
                                                    <i class="mdi mdi-account-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" 
                                                        onclick="viewSchedule({{ $teacher->id }})">
                                                    <i class="mdi mdi-calendar"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="mdi mdi-account-search display-4"></i>
                                            <p class="mt-2">No teachers found matching your criteria</p>
                                            <button type="button" class="btn btn-outline-primary" onclick="resetFilters()">
                                                Reset Filters
                                            </button>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Grid View -->
                    <div id="gridView" style="display: none;">
                        <div class="row" id="teachersGridContainer">
                            @foreach($teachers ?? [] as $teacher)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card teacher-card">
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <div class="avatar-lg mx-auto mb-2">
                                                @if($teacher->profile_photo)
                                                    <img src="{{ asset('storage/' . $teacher->profile_photo) }}" 
                                                         class="avatar-img rounded-circle" alt="{{ $teacher->name }}">
                                                @else
                                                    <span class="avatar-title bg-primary rounded-circle fs-4">
                                                        {{ substr($teacher->name, 0, 1) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h6 class="mb-1">{{ $teacher->name }}</h6>
                                            <small class="text-muted">{{ $teacher->employee_id }}</small>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">Department:</small>
                                            <div>{{ $teacher->department ?? 'N/A' }}</div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">Experience:</small>
                                            <div>{{ $teacher->experience ?? 0 }} years</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">Workload:</small>
                                            @php $workloadPercentage = $teacher->workload_percentage ?? 0; @endphp
                                            <div class="progress mt-1" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $workloadPercentage > 80 ? 'danger' : ($workloadPercentage > 60 ? 'warning' : 'success') }}" 
                                                     style="width: {{ $workloadPercentage }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $workloadPercentage }}%</small>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                    onclick="assignTeacher({{ $teacher->id }})">
                                                <i class="mdi mdi-account-plus me-1"></i>Assign
                                            </button>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="viewTeacherDetails({{ $teacher->id }})">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="viewSchedule({{ $teacher->id }})">
                                                    <i class="mdi mdi-calendar"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Teacher Details Modal -->
<div class="modal fade" id="teacherDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teacher Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="teacherDetailsBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="assignFromModal">
                    <i class="mdi mdi-account-plus me-1"></i>Assign Teacher
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignmentForm">
                    @csrf
                    <input type="hidden" id="selected_teacher_id" name="teacher_id">
                    
                    <div class="mb-3">
                        <label for="assign_class_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select class="form-select" id="assign_class_id" name="class_id" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assign_subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select class="form-select" id="assign_subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assign_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="assign_date" name="date" 
                                       value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assign_period" class="form-label">Period <span class="text-danger">*</span></label>
                                <select class="form-select" id="assign_period" name="period" required>
                                    <option value="">Select Period</option>
                                    @for($i = 1; $i <= 8; $i++)
                                        <option value="{{ $i }}">Period {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assign_reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="assign_reason" name="reason" rows="3" 
                                  placeholder="Enter reason for substitution"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAssignment()">
                    <i class="mdi mdi-check me-1"></i>Assign
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Search form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        searchTeachers();
    });
});

function searchTeachers() {
    const btn = $('#searchBtn');
    const originalText = btn.html();
    
    btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Searching...');
    $('#loadingState').show();
    $('#listView, #gridView').hide();
    
    $.ajax({
        url: $('#searchForm').attr('action'),
        method: 'POST',
        data: $('#searchForm').serialize(),
        success: function(response) {
            if (response.success) {
                updateTeachersList(response.data);
                updateStats(response.stats);
                toastr.success(`Found ${response.data.length} available teachers`);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Search failed');
        },
        complete: function() {
            btn.prop('disabled', false).html(originalText);
            $('#loadingState').hide();
            $('#listView').show();
        }
    });
}

function updateTeachersList(teachers) {
    const tbody = $('#teachersTableBody');
    const gridContainer = $('#teachersGridContainer');
    
    tbody.empty();
    gridContainer.empty();
    
    $('#teacherCount').text(teachers.length);
    
    if (teachers.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="mdi mdi-account-search display-4"></i>
                    <p class="mt-2">No teachers found matching your criteria</p>
                    <button type="button" class="btn btn-outline-primary" onclick="resetFilters()">
                        Reset Filters
                    </button>
                </td>
            </tr>
        `);
        return;
    }
    
    teachers.forEach(teacher => {
        // List view row
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
                <td>${teacher.department || 'N/A'}</td>
                <td>
                    ${teacher.subjects ? teacher.subjects.map(s => `<span class="badge bg-light text-dark me-1">${s.name}</span>`).join('') : 'No subjects'}
                </td>
                <td>${teacher.experience || 0} years</td>
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
                <td><span class="badge bg-success">Available</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" onclick="viewTeacherDetails(${teacher.id})">
                            <i class="mdi mdi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="assignTeacher(${teacher.id})">
                            <i class="mdi mdi-account-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="viewSchedule(${teacher.id})">
                            <i class="mdi mdi-calendar"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
        
        // Grid view card
        const card = `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card teacher-card">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar-lg mx-auto mb-2">
                                <span class="avatar-title bg-primary rounded-circle fs-4">
                                    ${teacher.name.charAt(0)}
                                </span>
                            </div>
                            <h6 class="mb-1">${teacher.name}</h6>
                            <small class="text-muted">${teacher.employee_id}</small>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Department:</small>
                            <div>${teacher.department || 'N/A'}</div>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Experience:</small>
                            <div>${teacher.experience || 0} years</div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Workload:</small>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-${teacher.workload_percentage > 80 ? 'danger' : (teacher.workload_percentage > 60 ? 'warning' : 'success')}" 
                                     style="width: ${teacher.workload_percentage}%"></div>
                            </div>
                            <small class="text-muted">${teacher.workload_percentage}%</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="assignTeacher(${teacher.id})">
                                <i class="mdi mdi-account-plus me-1"></i>Assign
                            </button>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="viewTeacherDetails(${teacher.id})">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="viewSchedule(${teacher.id})">
                                    <i class="mdi mdi-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        gridContainer.append(card);
    });
}

function updateStats(stats) {
    $('#totalTeachers').text(stats.total_teachers || 0);
    $('#availableTeachers').text(stats.available_teachers || 0);
    $('#busyTeachers').text(stats.busy_teachers || 0);
    $('#onLeave').text(stats.on_leave || 0);
}

function toggleView(viewType) {
    if (viewType === 'grid') {
        $('#listView').hide();
        $('#gridView').show();
        $('.btn-group .btn').removeClass('active');
        $('button[onclick="toggleView(\'grid\')"]').addClass('active');
    } else {
        $('#gridView').hide();
        $('#listView').show();
        $('.btn-group .btn').removeClass('active');
        $('button[onclick="toggleView(\'list\')"]').addClass('active');
    }
}

function resetFilters() {
    $('#searchForm')[0].reset();
    $('#date').val('{{ date("Y-m-d") }}');
    searchTeachers();
}

function viewTeacherDetails(teacherId) {
    $.ajax({
        url: `/teachers/${teacherId}/details`,
        method: 'GET',
        success: function(response) {
            $('#teacherDetailsBody').html(response);
            $('#assignFromModal').data('teacher-id', teacherId);
            $('#teacherDetailsModal').modal('show');
        },
        error: function() {
            toastr.error('Failed to load teacher details');
        }
    });
}

function assignTeacher(teacherId) {
    $('#selected_teacher_id').val(teacherId);
    
    // Pre-fill form with current search criteria
    $('#assign_class_id').val($('#class_id').val());
    $('#assign_subject_id').val($('#subject_id').val());
    $('#assign_date').val($('#date').val());
    $('#assign_period').val($('#period').val());
    
    $('#assignmentModal').modal('show');
}

function submitAssignment() {
    const formData = $('#assignmentForm').serialize();
    
    $.ajax({
        url: '{{ route("substitution.store") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#assignmentModal').modal('hide');
                searchTeachers(); // Refresh the list
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

function viewSchedule(teacherId) {
    window.open(`/teachers/${teacherId}/schedule`, '_blank');
}

// Assign from details modal
$('#assignFromModal').on('click', function() {
    const teacherId = $(this).data('teacher-id');
    $('#teacherDetailsModal').modal('hide');
    assignTeacher(teacherId);
});
</script>
@endpush