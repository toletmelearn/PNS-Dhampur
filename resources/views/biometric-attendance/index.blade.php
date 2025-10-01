@extends('layouts.app')

@section('title', 'Teacher Biometric Attendance')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Teacher Biometric Attendance</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Biometric Attendance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Teachers</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-group-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $summary['total_teachers'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Present Today</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-checkbox-circle-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-success">{{ $summary['present'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Absent Today</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-close-circle-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-danger">{{ $summary['absent'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Attendance %</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-info fs-14 mb-0">
                                <i class="ri-percent-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-info">{{ $summary['attendance_percentage'] }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Attendance Records</h5>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkCheckInModal">
                                    <i class="ri-login-box-line align-middle me-1"></i> Bulk Check-in
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#markAbsentModal">
                                    <i class="ri-user-unfollow-line align-middle me-1"></i> Mark Absent
                                </button>
                                <button type="button" class="btn btn-success" onclick="exportReport()">
                                    <i class="ri-download-line align-middle me-1"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
                        </div>
                        <div class="col-md-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ $teacher->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ri-search-line align-middle me-1"></i> Filter
                            </button>
                            <a href="{{ route('biometric-attendance.index') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line align-middle me-1"></i> Reset
                            </a>
                        </div>
                    </form>

                    <!-- Attendance Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Teacher</th>
                                    <th>Employee ID</th>
                                    <th>Date</th>
                                    <th>Check-in Time</th>
                                    <th>Check-out Time</th>
                                    <th>Working Hours</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                        {{ substr($attendance->teacher->name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $attendance->teacher->name }}</h6>
                                                    <small class="text-muted">{{ $attendance->teacher->department ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $attendance->teacher->employee_id }}</td>
                                        <td>{{ $attendance->date->format('d M Y') }}</td>
                                        <td>
                                            @if($attendance->check_in_time)
                                                <span class="badge bg-{{ $attendance->is_late ? 'warning' : 'success' }}">
                                                    {{ $attendance->check_in_time->format('H:i:s') }}
                                                    @if($attendance->is_late)
                                                        <i class="ri-time-line ms-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->check_out_time)
                                                <span class="badge bg-{{ $attendance->is_early_departure ? 'info' : 'success' }}">
                                                    {{ $attendance->check_out_time->format('H:i:s') }}
                                                    @if($attendance->is_early_departure)
                                                        <i class="ri-arrow-left-line ms-1"></i>
                                                    @endif
                                                </span>
                                            @else
                                                @if($attendance->status === 'present' && $attendance->check_in_time)
                                                    <button class="btn btn-sm btn-outline-primary" onclick="checkOut({{ $attendance->teacher->id }})">
                                                        Check Out
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->working_hours)
                                                {{ number_format($attendance->working_hours, 2) }}h
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->status === 'present')
                                                <span class="badge bg-success">Present</span>
                                            @elseif($attendance->status === 'absent')
                                                <span class="badge bg-danger">Absent</span>
                                            @else
                                                <span class="badge bg-secondary">Unknown</span>
                                            @endif
                                            
                                            @if($attendance->is_late)
                                                <span class="badge bg-warning ms-1">Late</span>
                                            @endif
                                            
                                            @if($attendance->is_early_departure)
                                                <span class="badge bg-info ms-1">Early</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="viewDetails({{ $attendance->id }})">
                                                        <i class="ri-eye-line me-2"></i>View Details
                                                    </a></li>
                                                    @if($attendance->status === 'absent')
                                                        <li><a class="dropdown-item" href="#" onclick="markPresent({{ $attendance->teacher->id }})">
                                                            <i class="ri-checkbox-circle-line me-2"></i>Mark Present
                                                        </a></li>
                                                    @endif
                                                    @if(!$attendance->check_in_time && $attendance->status !== 'absent')
                                                        <li><a class="dropdown-item" href="#" onclick="checkIn({{ $attendance->teacher->id }})">
                                                            <i class="ri-login-box-line me-2"></i>Check In
                                                        </a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ri-file-list-line fs-1 mb-3 d-block"></i>
                                                No attendance records found for the selected date.
                                            </div>
                                        </td>
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

<!-- Bulk Check-in Modal -->
<div class="modal fade" id="bulkCheckInModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Check-in</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkCheckInForm">
                    <div class="mb-3">
                        <label class="form-label">Select Teachers</label>
                        <div class="row">
                            @foreach($teachers as $teacher)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}" id="teacher_{{ $teacher->id }}">
                                        <label class="form-check-label" for="teacher_{{ $teacher->id }}">
                                            {{ $teacher->name }} ({{ $teacher->employee_id }})
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllTeachers">
                            <label class="form-check-label" for="selectAllTeachers">
                                Select All Teachers
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processBulkCheckIn()">Check In Selected</button>
            </div>
        </div>
    </div>
</div>

<!-- Mark Absent Modal -->
<div class="modal fade" id="markAbsentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Teacher Absent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="markAbsentForm">
                    <div class="mb-3">
                        <label for="absent_teacher_id" class="form-label">Teacher</label>
                        <select class="form-select" id="absent_teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="absent_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="absent_date" name="date" value="{{ $date }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="absence_reason" class="form-label">Reason (Optional)</label>
                        <textarea class="form-control" id="absence_reason" name="reason" rows="3" placeholder="Enter reason for absence..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="processMarkAbsent()">Mark Absent</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Select all teachers functionality
document.getElementById('selectAllTeachers').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="teacher_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Check In function
function checkIn(teacherId) {
    if (confirm('Are you sure you want to check in this teacher?')) {
        fetch('/biometric-attendance/check-in', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                teacher_id: teacherId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                location.reload();
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'An error occurred while checking in.');
        });
    }
}

// Check Out function
function checkOut(teacherId) {
    if (confirm('Are you sure you want to check out this teacher?')) {
        fetch('/biometric-attendance/check-out', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                teacher_id: teacherId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                location.reload();
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'An error occurred while checking out.');
        });
    }
}

// Bulk Check In
function processBulkCheckIn() {
    const selectedTeachers = Array.from(document.querySelectorAll('input[name="teacher_ids[]"]:checked'))
        .map(checkbox => checkbox.value);
    
    if (selectedTeachers.length === 0) {
        showAlert('warning', 'Please select at least one teacher.');
        return;
    }
    
    fetch('/biometric-attendance/bulk-check-in', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            teacher_ids: selectedTeachers
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            document.getElementById('bulkCheckInModal').querySelector('.btn-close').click();
            location.reload();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred during bulk check-in.');
    });
}

// Mark Absent
function processMarkAbsent() {
    const form = document.getElementById('markAbsentForm');
    const formData = new FormData(form);
    
    fetch('/biometric-attendance/mark-absent', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            document.getElementById('markAbsentModal').querySelector('.btn-close').click();
            location.reload();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred while marking absent.');
    });
}

// Export Report
function exportReport() {
    const date = document.getElementById('date').value;
    const teacherId = document.getElementById('teacher_id').value;
    
    const params = new URLSearchParams({
        date: date,
        teacher_id: teacherId,
        format: 'excel'
    });
    
    window.open(`/biometric-attendance/export?${params.toString()}`, '_blank');
}

// Show Alert function
function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Auto-refresh every 30 seconds for real-time updates
setInterval(() => {
    if (document.getElementById('date').value === new Date().toISOString().split('T')[0]) {
        location.reload();
    }
}, 30000);
</script>
@endpush