@extends('layouts.app')

@section('title', 'Substitution History')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Substitution History</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('substitution.index') }}">Substitution</a></li>
                        <li class="breadcrumb-item active">History</li>
                    </ol>
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
                                    <i class="mdi mdi-swap-horizontal"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['total_substitutions'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Total Substitutions</p>
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
                                    <i class="mdi mdi-check-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['completed'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Completed</p>
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
                                    <i class="mdi mdi-clock-outline"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['pending'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Pending</p>
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
                                <span class="avatar-title bg-info rounded-circle">
                                    <i class="mdi mdi-percent"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['success_rate'] ?? 0, 1) }}%</h5>
                            <p class="text-muted mb-0">Success Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-filter-variant me-2"></i>
                        Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('substitution.history') }}">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="{{ request('date_from', date('Y-m-01')) }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                           value="{{ request('date_to', date('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
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
                            <div class="col-md-2">
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
                                    <label for="teacher_id" class="form-label">Teacher</label>
                                    <select class="form-select" id="teacher_id" name="teacher_id">
                                        <option value="">All Teachers</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_assigned_only" name="auto_assigned_only" 
                                       {{ request('auto_assigned_only') ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_assigned_only">
                                    Auto-Assigned Only
                                </label>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="resetFilters()">
                                    <i class="mdi mdi-refresh me-1"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-filter me-1"></i>Apply Filters
                                </button>
                                <button type="button" class="btn btn-success ms-2" onclick="exportHistory()">
                                    <i class="mdi mdi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-history me-2"></i>
                        Substitution Records
                        <span class="badge bg-primary ms-2">{{ $substitutions->total() }}</span>
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshTable()">
                            <i class="mdi mdi-refresh"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleDetails()">
                            <i class="mdi mdi-eye"></i> Details
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="historyTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Date</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Period</th>
                                    <th>Original Teacher</th>
                                    <th>Substitute Teacher</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($substitutions as $substitution)
                                <tr data-id="{{ $substitution->id }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox" value="{{ $substitution->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $substitution->date->format('d M Y') }}</strong>
                                            <br><small class="text-muted">{{ $substitution->date->format('l') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $substitution->class->name ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $substitution->class->section ?? '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $substitution->subject->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">Period {{ $substitution->period }}</span>
                                    </td>
                                    <td>
                                        @if($substitution->original_teacher)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title bg-secondary rounded-circle">
                                                        {{ substr($substitution->original_teacher->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div>{{ $substitution->original_teacher->name }}</div>
                                                    <small class="text-muted">{{ $substitution->original_teacher->employee_id }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($substitution->substitute_teacher)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    <span class="avatar-title bg-primary rounded-circle">
                                                        {{ substr($substitution->substitute_teacher->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div>{{ $substitution->substitute_teacher->name }}</div>
                                                    <small class="text-muted">{{ $substitution->substitute_teacher->employee_id }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge bg-warning">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $substitution->status == 'completed' ? 'success' : 
                                            ($substitution->status == 'assigned' ? 'primary' : 
                                            ($substitution->status == 'pending' ? 'warning' : 'secondary')) 
                                        }}">
                                            {{ ucfirst($substitution->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($substitution->is_auto_assigned)
                                            <span class="badge bg-info">
                                                <i class="mdi mdi-robot me-1"></i>Auto
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="mdi mdi-account me-1"></i>Manual
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewDetails({{ $substitution->id }})" 
                                                    title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if($substitution->status == 'pending')
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="reassign({{ $substitution->id }})" 
                                                    title="Reassign">
                                                <i class="mdi mdi-refresh"></i>
                                            </button>
                                            @endif
                                            @if(in_array($substitution->status, ['pending', 'assigned']))
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="cancelSubstitution({{ $substitution->id }})" 
                                                    title="Cancel">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="viewLogs({{ $substitution->id }})" 
                                                    title="View Logs">
                                                <i class="mdi mdi-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Details Row (Hidden by default) -->
                                <tr class="details-row" id="details-{{ $substitution->id }}" style="display: none;">
                                    <td colspan="10">
                                        <div class="bg-light p-3 rounded">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Substitution Details</h6>
                                                    <ul class="list-unstyled mb-0">
                                                        <li><strong>Reason:</strong> {{ $substitution->reason ?: 'Not specified' }}</li>
                                                        <li><strong>Created:</strong> {{ $substitution->created_at->format('d M Y H:i') }}</li>
                                                        <li><strong>Created By:</strong> {{ $substitution->created_by_user->name ?? 'System' }}</li>
                                                        @if($substitution->assigned_at)
                                                        <li><strong>Assigned:</strong> {{ $substitution->assigned_at->format('d M Y H:i') }}</li>
                                                        @endif
                                                        @if($substitution->completed_at)
                                                        <li><strong>Completed:</strong> {{ $substitution->completed_at->format('d M Y H:i') }}</li>
                                                        @endif
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Additional Information</h6>
                                                    <ul class="list-unstyled mb-0">
                                                        <li><strong>Priority:</strong> {{ ucfirst($substitution->priority ?? 'normal') }}</li>
                                                        <li><strong>Duration:</strong> {{ $substitution->duration ?? 1 }} period(s)</li>
                                                        @if($substitution->notes)
                                                        <li><strong>Notes:</strong> {{ $substitution->notes }}</li>
                                                        @endif
                                                        @if($substitution->feedback)
                                                        <li><strong>Feedback:</strong> {{ $substitution->feedback }}</li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="mdi mdi-history display-4"></i>
                                        <p class="mt-2">No substitution records found</p>
                                        <button type="button" class="btn btn-outline-primary" onclick="resetFilters()">
                                            Reset Filters
                                        </button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($substitutions->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <p class="text-muted mb-0">
                                Showing {{ $substitutions->firstItem() }} to {{ $substitutions->lastItem() }} 
                                of {{ $substitutions->total() }} results
                            </p>
                        </div>
                        <div>
                            {{ $substitutions->appends(request()->query())->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="row" id="bulkActions" style="display: none;">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span id="selectedCount">0</span> items selected
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-success" onclick="bulkReassign()">
                                <i class="mdi mdi-refresh me-1"></i>Bulk Reassign
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="bulkCancel()">
                                <i class="mdi mdi-close me-1"></i>Bulk Cancel
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="bulkExport()">
                                <i class="mdi mdi-download me-1"></i>Export Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Substitution Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Substitution Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logsModalBody">
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
    // Handle select all checkbox
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });
    
    // Handle individual checkboxes
    $('.row-checkbox').on('change', function() {
        updateBulkActions();
        
        // Update select all checkbox
        const totalCheckboxes = $('.row-checkbox').length;
        const checkedCheckboxes = $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
});

function updateBulkActions() {
    const selectedCount = $('.row-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkActions').show();
    } else {
        $('#bulkActions').hide();
    }
}

function resetFilters() {
    $('#filterForm')[0].reset();
    $('#date_from').val('{{ date("Y-m-01") }}');
    $('#date_to').val('{{ date("Y-m-d") }}');
    $('#filterForm').submit();
}

function refreshTable() {
    location.reload();
}

function toggleDetails() {
    $('.details-row').toggle();
}

function viewDetails(substitutionId) {
    $.ajax({
        url: `/substitution/${substitutionId}/details`,
        method: 'GET',
        success: function(response) {
            $('#detailsModalBody').html(response);
            $('#detailsModal').modal('show');
        },
        error: function() {
            toastr.error('Failed to load substitution details');
        }
    });
}

function viewLogs(substitutionId) {
    $.ajax({
        url: `/substitution/${substitutionId}/logs`,
        method: 'GET',
        success: function(response) {
            $('#logsModalBody').html(response);
            $('#logsModal').modal('show');
        },
        error: function() {
            toastr.error('Failed to load substitution logs');
        }
    });
}

function reassign(substitutionId) {
    if (confirm('Are you sure you want to reassign this substitution?')) {
        $.ajax({
            url: `/substitution/${substitutionId}/reassign`,
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

function cancelSubstitution(substitutionId) {
    if (confirm('Are you sure you want to cancel this substitution?')) {
        $.ajax({
            url: `/substitution/${substitutionId}/cancel`,
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
                toastr.error(response?.message || 'Cancellation failed');
            }
        });
    }
}

function bulkReassign() {
    const selectedIds = $('.row-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        toastr.warning('Please select at least one substitution');
        return;
    }
    
    if (confirm(`Are you sure you want to reassign ${selectedIds.length} substitution(s)?`)) {
        $.ajax({
            url: '{{ route("substitution.bulk-reassign") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                substitution_ids: selectedIds
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
                toastr.error(response?.message || 'Bulk reassignment failed');
            }
        });
    }
}

function bulkCancel() {
    const selectedIds = $('.row-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        toastr.warning('Please select at least one substitution');
        return;
    }
    
    if (confirm(`Are you sure you want to cancel ${selectedIds.length} substitution(s)?`)) {
        $.ajax({
            url: '{{ route("substitution.bulk-cancel") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                substitution_ids: selectedIds
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
                toastr.error(response?.message || 'Bulk cancellation failed');
            }
        });
    }
}

function bulkExport() {
    const selectedIds = $('.row-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        toastr.warning('Please select at least one substitution');
        return;
    }
    
    const form = $('<form>', {
        method: 'POST',
        action: '{{ route("substitution.export") }}'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: '{{ csrf_token() }}'
    }));
    
    selectedIds.forEach(id => {
        form.append($('<input>', {
            type: 'hidden',
            name: 'substitution_ids[]',
            value: id
        }));
    });
    
    $('body').append(form);
    form.submit();
    form.remove();
}

function exportHistory() {
    const formData = $('#filterForm').serialize();
    
    const form = $('<form>', {
        method: 'POST',
        action: '{{ route("substitution.export") }}'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: '{{ csrf_token() }}'
    }));
    
    // Add filter parameters
    const params = new URLSearchParams(formData);
    for (const [key, value] of params) {
        form.append($('<input>', {
            type: 'hidden',
            name: key,
            value: value
        }));
    }
    
    $('body').append(form);
    form.submit();
    form.remove();
}
</script>
@endpush