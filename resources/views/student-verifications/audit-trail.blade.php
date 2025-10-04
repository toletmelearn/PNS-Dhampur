@extends('layouts.app')

@section('title', 'Student Verification Audit Trail')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-list text-info me-2"></i>
                        Student Verification Audit Trail
                    </h4>
                    <div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="loadStatistics()">
                            <i class="fas fa-chart-bar me-1"></i>Statistics
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="exportAuditTrail()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Filters</h6>
                                    <form id="filterForm">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Action</label>
                                                <select class="form-select" name="action" id="actionFilter">
                                                    <option value="">All Actions</option>
                                                    <option value="created">Created</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                    <option value="status_changed">Status Changed</option>
                                                    <option value="ocr_processed">OCR Processed</option>
                                                    <option value="mismatch_analyzed">Mismatch Analyzed</option>
                                                    <option value="auto_resolved">Auto Resolved</option>
                                                    <option value="manual_resolved">Manual Resolved</option>
                                                    <option value="reprocessed">Reprocessed</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date From</label>
                                                <input type="date" class="form-control" name="date_from" id="dateFromFilter">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date To</label>
                                                <input type="date" class="form-control" name="date_to" id="dateToFilter">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">User</label>
                                                <input type="text" class="form-control" name="user" id="userFilter" placeholder="User name...">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Verification ID</label>
                                                <input type="number" class="form-control" name="verification_id" id="verificationIdFilter" placeholder="Verification ID...">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Student Name</label>
                                                <input type="text" class="form-control" name="student_name" id="studentNameFilter" placeholder="Student name...">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Per Page</label>
                                                <select class="form-select" name="per_page" id="perPageFilter">
                                                    <option value="25">25</option>
                                                    <option value="50" selected>50</option>
                                                    <option value="100">100</option>
                                                    <option value="200">200</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="button" class="btn btn-primary me-2" onclick="loadAuditTrail()">
                                                    <i class="fas fa-search me-1"></i>Filter
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                                    <i class="fas fa-times me-1"></i>Clear
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading audit trail...</p>
                    </div>

                    <!-- Audit Trail Results -->
                    <div id="auditTrailResults">
                        <!-- Results will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    Audit Trail Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statisticsContent">
                <!-- Statistics will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download text-primary me-2"></i>
                    Export Audit Trail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" name="format" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="date" class="form-control" name="export_date_from" placeholder="From">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" name="export_date_to" placeholder="To">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_details" id="includeDetails" checked>
                            <label class="form-check-label" for="includeDetails">
                                Include detailed information
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="performExport()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAuditTrail();
});

function loadAuditTrail(page = 1) {
    const formData = new FormData(document.getElementById('filterForm'));
    formData.append('page', page);
    
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('auditTrailResults').innerHTML = '';
    
    fetch('{{ route("student-verifications.audit-trail") }}?' + new URLSearchParams(formData))
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingIndicator').style.display = 'none';
            displayAuditTrail(data);
        })
        .catch(error => {
            document.getElementById('loadingIndicator').style.display = 'none';
            console.error('Error:', error);
            showAlert('Error loading audit trail', 'danger');
        });
}

function displayAuditTrail(data) {
    const container = document.getElementById('auditTrailResults');
    
    if (data.data.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="text-muted">No audit trail records found matching your criteria.</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Showing ${data.from} to ${data.to} of ${data.total} records</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date/Time</th>
                        <th>Action</th>
                        <th>Verification</th>
                        <th>Student</th>
                        <th>User</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.data.forEach(log => {
        const actionBadge = getActionBadge(log.action);
        const statusBadge = log.verification ? getStatusBadge(log.verification.status) : '';
        
        html += `
            <tr>
                <td>
                    <small class="text-muted">${formatDateTime(log.created_at)}</small>
                </td>
                <td>${actionBadge}</td>
                <td>
                    ${log.verification ? `
                        <div>
                            <strong>#${log.verification.id}</strong>
                            <br>
                            <small class="text-muted">${log.verification.document_type}</small>
                            <br>
                            ${statusBadge}
                        </div>
                    ` : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    ${log.student ? `
                        <div>
                            <strong>${log.student.name}</strong>
                            <br>
                            <small class="text-muted">${log.student.roll_number}</small>
                        </div>
                    ` : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    ${log.user ? `
                        <div>
                            <strong>${log.user.name}</strong>
                            <br>
                            <small class="text-muted">${log.user.email}</small>
                        </div>
                    ` : '<span class="text-muted">System</span>'}
                </td>
                <td>
                    ${log.details ? `
                        <button class="btn btn-sm btn-outline-info" onclick="showDetails(${log.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    ` : '<span class="text-muted">-</span>'}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        ${log.verification ? `
                            <a href="/student-verifications/${log.verification.id}" 
                               class="btn btn-sm btn-outline-primary" title="View Verification">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/student-verifications/${log.verification.id}/history" 
                               class="btn btn-sm btn-outline-info" title="View History">
                                <i class="fas fa-history"></i>
                            </a>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    // Add pagination
    if (data.last_page > 1) {
        html += generatePagination(data);
    }
    
    container.innerHTML = html;
}

function getActionBadge(action) {
    const badges = {
        'created': 'bg-primary',
        'approved': 'bg-success',
        'rejected': 'bg-danger',
        'status_changed': 'bg-warning',
        'ocr_processed': 'bg-info',
        'mismatch_analyzed': 'bg-secondary',
        'auto_resolved': 'bg-success',
        'manual_resolved': 'bg-warning',
        'reprocessed': 'bg-info'
    };
    
    const badgeClass = badges[action] || 'bg-secondary';
    const actionText = action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    
    return `<span class="badge ${badgeClass}">${actionText}</span>`;
}

function getStatusBadge(status) {
    const badges = {
        'verified': 'bg-success',
        'rejected': 'bg-danger',
        'pending': 'bg-warning',
        'manual_review': 'bg-warning',
        'processing': 'bg-info'
    };
    
    const badgeClass = badges[status] || 'bg-secondary';
    const statusText = status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    
    return `<span class="badge ${badgeClass}">${statusText}</span>`;
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}

function generatePagination(data) {
    let html = '<nav class="mt-3"><ul class="pagination justify-content-center">';
    
    // Previous button
    if (data.current_page > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadAuditTrail(${data.current_page - 1})">Previous</a>
        </li>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, data.current_page - 2);
    const endPage = Math.min(data.last_page, data.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadAuditTrail(${i})">${i}</a>
        </li>`;
    }
    
    // Next button
    if (data.current_page < data.last_page) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadAuditTrail(${data.current_page + 1})">Next</a>
        </li>`;
    }
    
    html += '</ul></nav>';
    return html;
}

function clearFilters() {
    document.getElementById('filterForm').reset();
    loadAuditTrail();
}

function loadStatistics() {
    fetch('{{ route("student-verifications.audit-trail.statistics") }}')
        .then(response => response.json())
        .then(data => {
            displayStatistics(data);
            new bootstrap.Modal(document.getElementById('statisticsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading statistics', 'danger');
        });
}

function displayStatistics(stats) {
    const container = document.getElementById('statisticsContent');
    
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Actions Summary</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    Object.entries(stats.actions_summary).forEach(([action, count]) => {
        html += `
            <tr>
                <td>${action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</td>
                <td><span class="badge bg-primary">${count}</span></td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Daily Activity (Last 7 Days)</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activities</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    Object.entries(stats.daily_activity).forEach(([date, count]) => {
        html += `
            <tr>
                <td>${date}</td>
                <td><span class="badge bg-info">${count}</span></td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>Total Records:</strong> ${stats.total_records}<br>
                    <strong>Most Active User:</strong> ${stats.most_active_user || 'N/A'}<br>
                    <strong>Most Common Action:</strong> ${stats.most_common_action || 'N/A'}
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function exportAuditTrail() {
    new bootstrap.Modal(document.getElementById('exportModal')).show();
}

function performExport() {
    const formData = new FormData(document.getElementById('exportForm'));
    
    // Add current filters to export
    const filterData = new FormData(document.getElementById('filterForm'));
    for (let [key, value] of filterData.entries()) {
        if (value) {
            formData.append(key, value);
        }
    }
    
    const params = new URLSearchParams(formData);
    window.open('{{ route("student-verifications.audit-trail.export") }}?' + params, '_blank');
    
    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
}

function showDetails(logId) {
    // This would show detailed information about the log entry
    // Implementation depends on your specific needs
    alert('Show details for log ID: ' + logId);
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection