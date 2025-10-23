@extends('layouts.app')

@section('title', 'Attendance Regularization')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Attendance Regularization</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.redirect') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('biometric-attendance.index') }}">Biometric Attendance</a></li>
                        <li class="breadcrumb-item active">Regularization</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                        <i class="fas fa-plus"></i> New Request
                    </button>
                    <button type="button" class="btn btn-outline-info" id="bulkApproveBtn">
                        <i class="fas fa-check-double"></i> Bulk Approve
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="exportRequestsBtn">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="statusFilter" id="all" value="" checked>
                    <label class="btn btn-outline-primary" for="all">All</label>
                    
                    <input type="radio" class="btn-check" name="statusFilter" id="pending" value="pending">
                    <label class="btn btn-outline-warning" for="pending">Pending</label>
                    
                    <input type="radio" class="btn-check" name="statusFilter" id="approved" value="approved">
                    <label class="btn btn-outline-success" for="approved">Approved</label>
                    
                    <input type="radio" class="btn-check" name="statusFilter" id="rejected" value="rejected">
                    <label class="btn btn-outline-danger" for="rejected">Rejected</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Requests</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-primary fs-14 mb-0">
                                <i class="ri-file-list-3-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="totalRequests">0</h4>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Pending</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-warning fs-14 mb-0">
                                <i class="ri-time-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="pendingRequests">0</h4>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Approved</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-check-double-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="approvedRequests">0</h4>
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
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Rejected</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-close-circle-line align-middle"></i>
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4" id="rejectedRequests">0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Regularization Requests Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">Regularization Requests</h5>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <input type="date" class="form-control form-control-sm" id="dateFilter" placeholder="Filter by date">
                                <select class="form-select form-select-sm" id="teacherFilter">
                                    <option value="">All Teachers</option>
                                </select>
                                <button class="btn btn-sm btn-outline-secondary" id="clearFilters">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="regularizationTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Request ID</th>
                                    <th>Teacher</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Current Time</th>
                                    <th>Requested Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="regularizationTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalRecords">0</span> entries
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                <!-- Pagination will be generated here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1" aria-labelledby="createRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRequestModalLabel">Create Regularization Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createRequestForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="teacherId" class="form-label">Teacher <span class="text-danger">*</span></label>
                                <select class="form-select" id="teacherId" name="teacher_id" required>
                                    <option value="">Select Teacher</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="attendanceDate" class="form-label">Attendance Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="attendanceDate" name="attendance_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="requestType" class="form-label">Request Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="requestType" name="request_type" required>
                                    <option value="">Select Type</option>
                                    <option value="check_in">Check In Correction</option>
                                    <option value="check_out">Check Out Correction</option>
                                    <option value="both">Both Check In & Out</option>
                                    <option value="absent_to_present">Mark as Present</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="biometricAttendanceId" class="form-label">Existing Attendance Record</label>
                                <select class="form-select" id="biometricAttendanceId" name="biometric_attendance_id">
                                    <option value="">No existing record</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="timeFields">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="requestedCheckIn" class="form-label">Requested Check In Time</label>
                                <input type="time" class="form-control" id="requestedCheckIn" name="requested_check_in">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="requestedCheckOut" class="form-label">Requested Check Out Time</label>
                                <input type="time" class="form-control" id="requestedCheckOut" name="requested_check_out">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Please provide a detailed reason for this regularization request..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="supportingDocuments" class="form-label">Supporting Documents</label>
                        <input type="file" class="form-control" id="supportingDocuments" name="supporting_documents" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text">Upload supporting documents (PDF, Images, Word documents). Max 5MB per file.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Process Request Modal -->
<div class="modal fade" id="processRequestModal" tabindex="-1" aria-labelledby="processRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processRequestModalLabel">Process Regularization Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="processRequestModalBody">
                <!-- Request details will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentPage = 1;
let currentFilters = {};

$(document).ready(function() {
    // Load initial data
    loadRegularizationRequests();
    loadStatistics();
    loadTeachers();
    
    // Event handlers
    $('input[name="statusFilter"]').on('change', function() {
        currentFilters.status = $(this).val();
        currentPage = 1;
        loadRegularizationRequests();
    });
    
    $('#dateFilter, #teacherFilter').on('change', function() {
        currentFilters.date = $('#dateFilter').val();
        currentFilters.teacher_id = $('#teacherFilter').val();
        currentPage = 1;
        loadRegularizationRequests();
    });
    
    $('#clearFilters').on('click', function() {
        $('#dateFilter').val('');
        $('#teacherFilter').val('');
        $('input[name="statusFilter"][value=""]').prop('checked', true);
        currentFilters = {};
        currentPage = 1;
        loadRegularizationRequests();
    });
    
    $('#createRequestForm').on('submit', function(e) {
        e.preventDefault();
        submitRegularizationRequest();
    });
    
    $('#requestType').on('change', function() {
        toggleTimeFields();
    });
    
    $('#teacherId, #attendanceDate').on('change', function() {
        loadExistingAttendance();
    });
    
    $('#selectAll').on('change', function() {
        $('.request-checkbox').prop('checked', $(this).is(':checked'));
    });
    
    $('#bulkApproveBtn').on('click', function() {
        bulkApproveRequests();
    });
    
    $('#exportRequestsBtn').on('click', function() {
        exportRequests();
    });
});

function loadRegularizationRequests() {
    const params = new URLSearchParams({
        page: currentPage,
        ...currentFilters
    });
    
    $.ajax({
        url: '/biometric-attendance/regularization-requests?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateRequestsTable(response.data);
                updatePagination(response.pagination);
            }
        },
        error: function() {
            showAlert('Failed to load regularization requests', 'error');
        }
    });
}

function updateRequestsTable(requests) {
    let html = '';
    
    if (requests && requests.length > 0) {
        requests.forEach(request => {
            const statusBadge = getStatusBadge(request.status);
            const typeBadge = getTypeBadge(request.request_type);
            
            html += `
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input request-checkbox" value="${request.id}">
                    </td>
                    <td>#${request.id.toString().padStart(4, '0')}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <div class="avatar-title rounded-circle bg-light text-primary">
                                    ${request.teacher.name.charAt(0)}
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0">${request.teacher.name}</h6>
                                <small class="text-muted">${request.teacher.employee_id}</small>
                            </div>
                        </div>
                    </td>
                    <td>${formatDate(request.attendance_date)}</td>
                    <td>${typeBadge}</td>
                    <td>
                        ${request.biometric_attendance ? 
                            `In: ${request.biometric_attendance.check_in_time || 'N/A'}<br>Out: ${request.biometric_attendance.check_out_time || 'N/A'}` : 
                            '<span class="text-muted">No record</span>'
                        }
                    </td>
                    <td>
                        ${request.requested_check_in ? `In: ${request.requested_check_in}<br>` : ''}
                        ${request.requested_check_out ? `Out: ${request.requested_check_out}` : ''}
                    </td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 150px;" title="${request.reason}">
                            ${request.reason}
                        </span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <small class="text-muted">
                            ${formatDateTime(request.created_at)}
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-primary" onclick="viewRequest(${request.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${request.status === 'pending' ? `
                                <button class="btn btn-outline-success" onclick="approveRequest(${request.id})">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="rejectRequest(${request.id})">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="11" class="text-center text-muted py-4">No regularization requests found</td></tr>';
    }
    
    $('#regularizationTableBody').html(html);
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'approved': '<span class="badge bg-success">Approved</span>',
        'rejected': '<span class="badge bg-danger">Rejected</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getTypeBadge(type) {
    const badges = {
        'check_in': '<span class="badge bg-info">Check In</span>',
        'check_out': '<span class="badge bg-primary">Check Out</span>',
        'both': '<span class="badge bg-purple">Both</span>',
        'absent_to_present': '<span class="badge bg-success">Mark Present</span>'
    };
    return badges[type] || '<span class="badge bg-secondary">Unknown</span>';
}

function loadStatistics() {
    $.ajax({
        url: '/biometric-attendance/regularization-statistics',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#totalRequests').text(response.data.total || 0);
                $('#pendingRequests').text(response.data.pending || 0);
                $('#approvedRequests').text(response.data.approved || 0);
                $('#rejectedRequests').text(response.data.rejected || 0);
            }
        }
    });
}

function loadTeachers() {
    $.ajax({
        url: '/api/teachers',
        method: 'GET',
        success: function(response) {
            let options = '<option value="">Select Teacher</option>';
            let filterOptions = '<option value="">All Teachers</option>';
            
            if (response.data) {
                response.data.forEach(teacher => {
                    const option = `<option value="${teacher.id}">${teacher.name} (${teacher.employee_id})</option>`;
                    options += option;
                    filterOptions += option;
                });
            }
            
            $('#teacherId').html(options);
            $('#teacherFilter').html(filterOptions);
        }
    });
}

function toggleTimeFields() {
    const requestType = $('#requestType').val();
    const checkInField = $('#requestedCheckIn').closest('.col-md-6');
    const checkOutField = $('#requestedCheckOut').closest('.col-md-6');
    
    checkInField.hide();
    checkOutField.hide();
    
    if (requestType === 'check_in' || requestType === 'both' || requestType === 'absent_to_present') {
        checkInField.show();
    }
    
    if (requestType === 'check_out' || requestType === 'both') {
        checkOutField.show();
    }
}

function loadExistingAttendance() {
    const teacherId = $('#teacherId').val();
    const date = $('#attendanceDate').val();
    
    if (teacherId && date) {
        $.ajax({
            url: `/biometric-attendance/existing-attendance?teacher_id=${teacherId}&date=${date}`,
            method: 'GET',
            success: function(response) {
                let options = '<option value="">No existing record</option>';
                if (response.data) {
                    options += `<option value="${response.data.id}">Record #${response.data.id} - In: ${response.data.check_in_time || 'N/A'}, Out: ${response.data.check_out_time || 'N/A'}</option>`;
                }
                $('#biometricAttendanceId').html(options);
            }
        });
    }
}

function submitRegularizationRequest() {
    const formData = new FormData($('#createRequestForm')[0]);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    
    $.ajax({
        url: '/biometric-attendance/regularization-requests',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('Regularization request submitted successfully', 'success');
                $('#createRequestModal').modal('hide');
                $('#createRequestForm')[0].reset();
                loadRegularizationRequests();
                loadStatistics();
            } else {
                showAlert(response.message || 'Failed to submit request', 'error');
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                let errorMessage = 'Please fix the following errors:\n';
                Object.keys(errors).forEach(key => {
                    errorMessage += `- ${errors[key][0]}\n`;
                });
                showAlert(errorMessage, 'error');
            } else {
                showAlert('Failed to submit request', 'error');
            }
        }
    });
}

function viewRequest(requestId) {
    $.ajax({
        url: `/biometric-attendance/regularization-requests/${requestId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                showProcessModal(response.data);
            }
        }
    });
}

function showProcessModal(request) {
    // Implementation for showing detailed request modal
    $('#processRequestModal').modal('show');
}

function approveRequest(requestId) {
    processRequest(requestId, 'approved');
}

function rejectRequest(requestId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        processRequest(requestId, 'rejected', reason);
    }
}

function processRequest(requestId, status, adminRemarks = '') {
    $.ajax({
        url: `/biometric-attendance/regularization-requests/${requestId}/process`,
        method: 'POST',
        data: {
            status: status,
            admin_remarks: adminRemarks,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(`Request ${status} successfully`, 'success');
                loadRegularizationRequests();
                loadStatistics();
            } else {
                showAlert(response.message || 'Failed to process request', 'error');
            }
        },
        error: function() {
            showAlert('Failed to process request', 'error');
        }
    });
}

function bulkApproveRequests() {
    const selectedIds = $('.request-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedIds.length === 0) {
        showAlert('Please select requests to approve', 'warning');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${selectedIds.length} selected requests?`)) {
        $.ajax({
            url: '/biometric-attendance/regularization-requests/bulk-approve',
            method: 'POST',
            data: {
                request_ids: selectedIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert(`${response.data.approved_count} requests approved successfully`, 'success');
                    loadRegularizationRequests();
                    loadStatistics();
                    $('#selectAll').prop('checked', false);
                }
            }
        });
    }
}

function exportRequests() {
    const params = new URLSearchParams(currentFilters);
    window.open('/biometric-attendance/regularization-requests/export?' + params.toString(), '_blank');
}

function updatePagination(pagination) {
    // Implementation for pagination
    $('#showingFrom').text(pagination.from || 0);
    $('#showingTo').text(pagination.to || 0);
    $('#totalRecords').text(pagination.total || 0);
}

function formatDate(dateString) {
    return moment(dateString).format('DD MMM YYYY');
}

function formatDateTime(dateTimeString) {
    return moment(dateTimeString).format('DD MMM YYYY, hh:mm A');
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'warning' ? 'alert-warning' : 'alert-danger';
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.container-fluid').prepend(alert);
    
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
</script>
@endsection