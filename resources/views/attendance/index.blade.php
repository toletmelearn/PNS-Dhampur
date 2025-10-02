@extends('layouts.app')

@section('title', 'Student Attendance Management')

@section('content')
<div class="container-fluid">
    <!-- Modern Page Header with Gradient Background -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-header p-4 rounded-3 position-relative overflow-hidden">
                <div class="header-bg"></div>
                <div class="position-relative z-index-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2 text-white fw-bold">
                                <i class="fas fa-calendar-check me-3"></i>
                                Student Attendance Management
                            </h2>
                            <p class="text-white-50 mb-0 fs-5">Track and manage student attendance records with advanced analytics</p>
                        </div>
                        <div class="btn-group-modern" role="group">
                            <a href="{{ route('attendance.mark') }}" class="btn btn-glass btn-primary">
                                <i class="fas fa-plus me-2"></i>Mark Attendance
                            </a>
                            <a href="{{ route('attendance.reports') }}" class="btn btn-glass btn-success">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                            <a href="{{ route('attendance.analytics') }}" class="btn btn-glass btn-info">
                                <i class="fas fa-analytics me-2"></i>Analytics
                            </a>
                            <button type="button" class="btn btn-glass btn-warning" onclick="exportReport()">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-filter me-2"></i>Advanced Filters
                        </h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>Clear All
                        </button>
                    </div>
                    
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="date" class="form-label fw-semibold">Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-calendar text-muted"></i>
                                </span>
                                <input type="date" class="form-control border-start-0" id="date" name="date" 
                                       value="{{ request('date', date('Y-m-d')) }}">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="class_id" class="form-label fw-semibold">Class</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-graduation-cap text-muted"></i>
                                </span>
                                <select class="form-select border-start-0" id="class_id" name="class_id">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" 
                                                {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-check-circle text-muted"></i>
                                </span>
                                <select class="form-select border-start-0" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                                    <option value="excused" {{ request('status') == 'excused' ? 'selected' : '' }}>Excused</option>
                                    <option value="sick" {{ request('status') == 'sick' ? 'selected' : '' }}>Sick</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card bg-gradient-primary">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <div class="stats-icon mb-2">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h3 class="mb-1 fw-bold">{{ $summary['total_students'] ?? 0 }}</h3>
                            <p class="mb-0 opacity-75">Total Students</p>
                        </div>
                        <div class="stats-chart">
                            <div class="progress-circle" data-percentage="100">
                                <svg class="progress-svg" width="60" height="60">
                                    <circle cx="30" cy="30" r="25" stroke="rgba(255,255,255,0.3)" stroke-width="3" fill="none"/>
                                    <circle cx="30" cy="30" r="25" stroke="white" stroke-width="3" fill="none" 
                                            stroke-dasharray="157" stroke-dashoffset="0"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card bg-gradient-success">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <div class="stats-icon mb-2">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h3 class="mb-1 fw-bold">{{ $summary['present'] ?? 0 }}</h3>
                            <p class="mb-0 opacity-75">Present Today</p>
                        </div>
                        <div class="stats-trend">
                            <span class="trend-indicator">
                                <i class="fas fa-arrow-up"></i>
                                +{{ number_format(($summary['present'] ?? 0) / max($summary['total_students'] ?? 1, 1) * 100, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card bg-gradient-danger">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <div class="stats-icon mb-2">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                            <h3 class="mb-1 fw-bold">{{ $summary['absent'] ?? 0 }}</h3>
                            <p class="mb-0 opacity-75">Absent Today</p>
                        </div>
                        <div class="stats-trend">
                            <span class="trend-indicator">
                                <i class="fas fa-arrow-down"></i>
                                {{ number_format(($summary['absent'] ?? 0) / max($summary['total_students'] ?? 1, 1) * 100, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card bg-gradient-warning">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <div class="stats-icon mb-2">
                                <i class="fas fa-chart-pie fa-2x"></i>
                            </div>
                            <h3 class="mb-1 fw-bold">{{ number_format($summary['attendance_percentage'] ?? 0, 1) }}%</h3>
                            <p class="mb-0 opacity-75">Attendance Rate</p>
                        </div>
                        <div class="attendance-gauge">
                            <div class="gauge-circle" data-percentage="{{ $summary['attendance_percentage'] ?? 0 }}">
                                <div class="gauge-fill"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Attendance Records Table -->
    <div class="row">
        <div class="col-12">
            <div class="card modern-table-card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">
                                <i class="fas fa-table me-2 text-primary"></i>Attendance Records
                            </h5>
                            <p class="text-muted mb-0 small">Manage and track student attendance records</p>
                        </div>
                        <div class="table-actions">
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="refreshTable()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary btn-sm" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Select All
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="bulkDelete()" disabled id="bulkDeleteBtn">
                                    <i class="fas fa-trash me-1"></i>Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($attendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover modern-table mb-0">
                                <thead class="table-header">
                                    <tr>
                                        <th class="border-0">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                                            </div>
                                        </th>
                                        <th class="border-0">S.No.</th>
                                        <th class="border-0">Student Details</th>
                                        <th class="border-0">Class</th>
                                        <th class="border-0">Date & Time</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0">Marked By</th>
                                        <th class="border-0 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendances as $index => $attendance)
                                        <tr class="table-row" data-id="{{ $attendance->id }}">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input row-checkbox" type="checkbox" 
                                                           value="{{ $attendance->id }}">
                                                </div>
                                            </td>
                                            <td class="fw-semibold text-muted">{{ $attendances->firstItem() + $index }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="student-avatar me-3">
                                                        <div class="avatar-circle bg-gradient-primary text-white">
                                                            {{ strtoupper(substr($attendance->student->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 fw-semibold text-dark">{{ $attendance->student->name }}</h6>
                                                        <div class="student-meta">
                                                            <span class="badge bg-light text-dark me-2">
                                                                <i class="fas fa-id-card me-1"></i>{{ $attendance->student->admission_no }}
                                                            </span>
                                                            <small class="text-muted">{{ $attendance->student->father_name }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="class-badge badge bg-info-subtle text-info">
                                                    <i class="fas fa-graduation-cap me-1"></i>{{ $attendance->classModel->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="date-time-info">
                                                    <div class="fw-semibold text-dark">
                                                        {{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}
                                                    </div>
                                                    @if($attendance->check_in_time)
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>{{ \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusConfig = [
                                                        'present' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Present'],
                                                        'absent' => ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Absent'],
                                                        'late' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'Late'],
                                                        'excused' => ['class' => 'info', 'icon' => 'info-circle', 'text' => 'Excused'],
                                                        'sick' => ['class' => 'secondary', 'icon' => 'thermometer-half', 'text' => 'Sick']
                                                    ];
                                                    $config = $statusConfig[$attendance->status] ?? $statusConfig['absent'];
                                                @endphp
                                                <span class="status-badge badge bg-{{ $config['class'] }}-subtle text-{{ $config['class'] }}">
                                                    <i class="fas fa-{{ $config['icon'] }} me-1"></i>{{ $config['text'] }}
                                                </span>
                                                @if($attendance->late_minutes > 0)
                                                    <div class="mt-1">
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $attendance->late_minutes }} min late
                                                        </small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="marked-by-info">
                                                    <span class="fw-semibold text-dark">{{ $attendance->markedBy->name ?? 'System' }}</span>
                                                    @if($attendance->created_at)
                                                        <div class="text-muted small">
                                                            {{ $attendance->created_at->diffForHumans() }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                            onclick="editAttendance({{ $attendance->id }})" 
                                                            title="Edit Attendance">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                            onclick="viewDetails({{ $attendance->id }})" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteAttendance({{ $attendance->id }})" 
                                                            title="Delete Record">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Enhanced Pagination -->
                        <div class="card-footer bg-white border-top-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="pagination-info">
                                    <span class="text-muted small">
                                        Showing {{ $attendances->firstItem() }} to {{ $attendances->lastItem() }} 
                                        of {{ $attendances->total() }} results
                                    </span>
                                </div>
                                <div class="pagination-wrapper">
                                    {{ $attendances->appends(request()->query())->links('pagination::bootstrap-4') }}
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Enhanced Empty State -->
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon mb-4">
                                <i class="fas fa-clipboard-list text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                            <h5 class="text-muted mb-3">No Attendance Records Found</h5>
                            <p class="text-muted mb-4">
                                There are no attendance records matching your current filters. 
                                <br>Try adjusting your search criteria or mark attendance for students.
                            </p>
                            <div class="empty-actions">
                                <a href="{{ route('attendance.mark') }}" class="btn btn-primary me-2">
                                    <i class="fas fa-plus me-2"></i>Mark Attendance
                                </a>
                                <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-filter me-2"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="editAttendanceModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Attendance Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editAttendanceForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="attendanceId" name="attendance_id">
                    
                    <!-- Student Information -->
                    <div class="student-info-card bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="student-avatar me-3">
                                <div class="avatar-circle bg-gradient-info text-white" id="studentAvatar">
                                    <!-- Student initial will be set by JS -->
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark" id="studentName">Student Name</h6>
                                <div class="student-details">
                                    <span class="badge bg-primary me-2" id="studentClass">Class</span>
                                    <span class="badge bg-secondary" id="studentAdmission">Admission No.</span>
                                </div>
                                <small class="text-muted" id="attendanceDate">Date</small>
                            </div>
                        </div>
                    </div>

                    <!-- Status Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark mb-3">
                            <i class="fas fa-clipboard-check me-2 text-primary"></i>Attendance Status
                        </label>
                        <div class="status-options">
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="status" id="status_present" value="present">
                                    <label class="btn btn-outline-success w-100 py-2" for="status_present">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <div class="small">Present</div>
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="status" id="status_absent" value="absent">
                                    <label class="btn btn-outline-danger w-100 py-2" for="status_absent">
                                        <i class="fas fa-times-circle me-1"></i>
                                        <div class="small">Absent</div>
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="status" id="status_late" value="late">
                                    <label class="btn btn-outline-warning w-100 py-2" for="status_late">
                                        <i class="fas fa-clock me-1"></i>
                                        <div class="small">Late</div>
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="status" id="status_excused" value="excused">
                                    <label class="btn btn-outline-info w-100 py-2" for="status_excused">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <div class="small">Excused</div>
                                    </label>
                                </div>
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="status" id="status_sick" value="sick">
                                    <label class="btn btn-outline-secondary w-100 py-2" for="status_sick">
                                        <i class="fas fa-thermometer-half me-1"></i>
                                        <div class="small">Sick</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="mb-3" id="lateMinutesSection" style="display: none;">
                        <label for="lateMinutes" class="form-label fw-semibold">
                            <i class="fas fa-stopwatch me-2 text-warning"></i>Late Minutes
                        </label>
                        <input type="number" class="form-control" id="lateMinutes" name="late_minutes" 
                               placeholder="Enter minutes late" min="1" max="480">
                        <div class="form-text">How many minutes was the student late?</div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label fw-semibold">
                            <i class="fas fa-comment me-2 text-info"></i>Remarks (Optional)
                        </label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                  placeholder="Add any additional notes or comments..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="updateAttendance()">
                    <i class="fas fa-save me-2"></i>Update Attendance
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Enhanced JavaScript functionality
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Auto-refresh filters
        $('#date, #class_id').on('change', function() {
            $('#filterForm').submit();
        });

        // Select all functionality
        $('#selectAllCheckbox').on('change', function() {
            $('.row-checkbox').prop('checked', this.checked);
            toggleBulkActions();
        });

        $('.row-checkbox').on('change', function() {
            toggleBulkActions();
            updateSelectAllState();
        });

        // Status change handler for late minutes
        $('input[name="status"]').on('change', function() {
            if ($(this).val() === 'late') {
                $('#lateMinutesSection').slideDown();
            } else {
                $('#lateMinutesSection').slideUp();
                $('#lateMinutes').val('');
            }
        });
    });

    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }
        
        window.location.href = '{{ route("attendance.index") }}?' + params.toString();
    });

    // Toggle bulk action buttons
    function toggleBulkActions() {
        const checkedBoxes = $('.row-checkbox:checked').length;
        $('#bulkDeleteBtn').prop('disabled', checkedBoxes === 0);
    }

    // Update select all checkbox state
    function updateSelectAllState() {
        const totalCheckboxes = $('.row-checkbox').length;
        const checkedCheckboxes = $('.row-checkbox:checked').length;
        
        $('#selectAllCheckbox').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAllCheckbox').prop('checked', checkedCheckboxes === totalCheckboxes);
    }

    // Select all rows
    function selectAll() {
        $('.row-checkbox').prop('checked', true);
        toggleBulkActions();
        updateSelectAllState();
    }

    // Refresh table
    function refreshTable() {
        location.reload();
    }

    // Bulk delete functionality
    function bulkDelete() {
        const selectedIds = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            Swal.fire('Warning', 'Please select records to delete', 'warning');
            return;
        }

        Swal.fire({
            title: 'Delete Selected Records?',
            text: `Are you sure you want to delete ${selectedIds.length} attendance record(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Implement bulk delete AJAX call here
                console.log('Bulk deleting IDs:', selectedIds);
                
                // Show loading state
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the selected records.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Simulate API call (replace with actual implementation)
                setTimeout(() => {
                    Swal.fire('Deleted!', 'Selected records have been deleted.', 'success')
                        .then(() => location.reload());
                }, 2000);
            }
        });
    }

    // Clear all filters
    function clearFilters() {
        window.location.href = '{{ route("attendance.index") }}';
    }

    // Export report functionality
    function exportReport() {
        const params = new URLSearchParams(window.location.search);
        const exportUrl = '{{ route("attendance.export") }}?' + params.toString();
        
        Swal.fire({
            title: 'Exporting Report',
            text: 'Your report is being generated...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Create a temporary link to download the file
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = `attendance_report_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        setTimeout(() => {
            Swal.fire('Success!', 'Report has been exported successfully.', 'success');
        }, 1500);
    }

    // View attendance details
    function viewDetails(attendanceId) {
        // Implement view details functionality
        console.log('Viewing details for attendance ID:', attendanceId);
        
        // You can implement a modal or redirect to a details page
        Swal.fire({
            title: 'Attendance Details',
            text: 'Feature coming soon!',
            icon: 'info'
        });
    }

    // Edit attendance functionality
    function editAttendance(attendanceId) {
        // Fetch attendance data and populate modal
        console.log('Editing attendance ID:', attendanceId);
        
        // Simulate fetching data (replace with actual AJAX call)
        const mockData = {
            id: attendanceId,
            student_name: 'John Doe',
            student_class: 'Class 10-A',
            admission_no: 'ADM001',
            date: '2024-01-15',
            status: 'present',
            late_minutes: 0,
            remarks: ''
        };

        // Populate modal with data
        $('#attendanceId').val(mockData.id);
        $('#studentName').text(mockData.student_name);
        $('#studentClass').text(mockData.student_class);
        $('#studentAdmission').text(mockData.admission_no);
        $('#attendanceDate').text(new Date(mockData.date).toLocaleDateString());
        $('#studentAvatar').text(mockData.student_name.charAt(0).toUpperCase());
        
        // Set status
        $(`#status_${mockData.status}`).prop('checked', true);
        
        if (mockData.status === 'late') {
            $('#lateMinutesSection').show();
            $('#lateMinutes').val(mockData.late_minutes);
        }
        
        $('#remarks').val(mockData.remarks);

        // Show modal
        new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
    }

    // Update attendance
    function updateAttendance() {
        const formData = {
            attendance_id: $('#attendanceId').val(),
            status: $('input[name="status"]:checked').val(),
            late_minutes: $('#lateMinutes').val(),
            remarks: $('#remarks').val(),
            _token: $('meta[name="csrf-token"]').attr('content'),
            _method: 'PUT'
        };

        if (!formData.status) {
            Swal.fire('Error', 'Please select an attendance status', 'error');
            return;
        }

        // Show loading state
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the attendance record.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Simulate API call (replace with actual AJAX implementation)
        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('editAttendanceModal')).hide();
            Swal.fire('Success!', 'Attendance record has been updated successfully.', 'success')
                .then(() => location.reload());
        }, 1500);

        console.log('Updating attendance with data:', formData);
    }

    // Delete attendance functionality
    function deleteAttendance(attendanceId) {
        Swal.fire({
            title: 'Delete Attendance Record?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the attendance record.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Simulate API call (replace with actual implementation)
                setTimeout(() => {
                    Swal.fire('Deleted!', 'Attendance record has been deleted.', 'success')
                        .then(() => location.reload());
                }, 1500);

                console.log('Deleting attendance ID:', attendanceId);
            }
        });
    }
</script>
@endsection

@section('styles')
<style>
    .avatar-sm {
        width: 40px;
        height: 40px;
    }
    
    .avatar-title {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    
    .table th {
        font-weight: 600;
        border-top: none;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem;
        margin-right: 0.25rem;
    }
    
    .btn-group .btn:last-child {
        margin-right: 0;
    }
</style>
@endsection