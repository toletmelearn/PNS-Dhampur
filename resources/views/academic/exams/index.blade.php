@extends('layouts.app')

@section('title', 'Exam Management')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-1">Exam Management</h2>
                            <p class="mb-0">Schedule, manage, and track all examinations</p>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 id="totalExams">0</h3>
                            <p class="mb-0">Total Exams</p>
                            <small>This Academic Year</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
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
                            <h3 id="upcomingExams">0</h3>
                            <p class="mb-0">Upcoming Exams</p>
                            <small>Next 30 days</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-alt fa-2x"></i>
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
                            <h3 id="ongoingExams">0</h3>
                            <p class="mb-0">Ongoing Exams</p>
                            <small>Currently Active</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 id="completedExams">0</h3>
                            <p class="mb-0">Completed Exams</p>
                            <small>Results Published</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-lg w-100 mb-3" onclick="scheduleExam()">
                                <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                                <br>Schedule Exam
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-success btn-lg w-100 mb-3" onclick="publishResults()">
                                <i class="fas fa-trophy fa-2x mb-2"></i>
                                <br>Publish Results
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-info btn-lg w-100 mb-3" onclick="manageSyllabus()">
                                <i class="fas fa-book fa-2x mb-2"></i>
                                <br>Manage Syllabus
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-warning btn-lg w-100 mb-3" onclick="generateMarksheet()">
                                <i class="fas fa-certificate fa-2x mb-2"></i>
                                <br>Generate Marksheet
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-secondary btn-lg w-100 mb-3" onclick="viewAnalytics()">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <br>View Analytics
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-dark btn-lg w-100 mb-3" onclick="exportReports()">
                                <i class="fas fa-file-export fa-2x mb-2"></i>
                                <br>Export Reports
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exams Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list me-2"></i>All Exams</h5>
                    <button class="btn btn-primary" onclick="scheduleExam()">
                        <i class="fas fa-plus me-2"></i>Schedule New Exam
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="classFilter">
                                <option value="">All Classes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="dateFilter" placeholder="Filter by date">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchFilter" placeholder="Search exams...">
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="examsTable">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="examsTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Exam Modal -->
<div class="modal fade" id="scheduleExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule New Exam</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleExamForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="examName" class="form-label">Exam Name *</label>
                                <input type="text" class="form-control" id="examName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="examClass" class="form-label">Class *</label>
                                <select class="form-select" id="examClass" name="class_id" required>
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="examDate" class="form-label">Exam Date *</label>
                                <input type="date" class="form-control" id="examDate" name="date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="examTime" class="form-label">Exam Time</label>
                                <input type="time" class="form-control" id="examTime" name="time">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="examDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="examDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" id="scheduleExamSpinner"></span>
                        Schedule Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Exam Modal -->
<div class="modal fade" id="viewExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exam Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewExamContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadExams();
    loadClasses();
    
    // Initialize DataTable
    $('#examsTable').DataTable({
        "pageLength": 10,
        "order": [[ 2, "desc" ]], // Sort by date descending
        "columnDefs": [
            { "orderable": false, "targets": 5 } // Disable sorting for Actions column
        ]
    });
    
    // Filter event listeners
    $('#classFilter, #statusFilter, #dateFilter').change(function() {
        loadExams();
    });
    
    $('#searchFilter').on('keyup', function() {
        loadExams();
    });
});

function loadExams() {
    const filters = {
        class_id: $('#classFilter').val(),
        status: $('#statusFilter').val(),
        date: $('#dateFilter').val(),
        search: $('#searchFilter').val()
    };
    
    $.ajax({
        url: '/exams',
        method: 'GET',
        data: filters,
        headers: {
            'Accept': 'application/json'
        },
        success: function(response) {
            updateExamStats(response);
            populateExamsTable(response);
        },
        error: function(xhr) {
            console.error('Error loading exams:', xhr);
            showAlert('Error loading exams. Please try again.', 'danger');
        }
    });
}

function loadClasses() {
    $.ajax({
        url: '/api/classes',
        method: 'GET',
        success: function(classes) {
            const classOptions = classes.map(cls => 
                `<option value="${cls.id}">${cls.name} ${cls.section || ''}</option>`
            ).join('');
            
            $('#classFilter, #examClass').append(classOptions);
        },
        error: function(xhr) {
            console.error('Error loading classes:', xhr);
        }
    });
}

function updateExamStats(exams) {
    const total = exams.length;
    const upcoming = exams.filter(exam => new Date(exam.date) > new Date()).length;
    const ongoing = exams.filter(exam => exam.status === 'ongoing').length;
    const completed = exams.filter(exam => exam.status === 'completed').length;
    
    $('#totalExams').text(total);
    $('#upcomingExams').text(upcoming);
    $('#ongoingExams').text(ongoing);
    $('#completedExams').text(completed);
}

function populateExamsTable(exams) {
    const tbody = $('#examsTableBody');
    tbody.empty();
    
    if (exams.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <br>No exams found
                </td>
            </tr>
        `);
        return;
    }
    
    exams.forEach(exam => {
        const statusBadge = getStatusBadge(exam.status || 'scheduled');
        const formattedDate = new Date(exam.date).toLocaleDateString();
        
        tbody.append(`
            <tr>
                <td><strong>${exam.name}</strong></td>
                <td>${exam.class ? exam.class.name + ' ' + (exam.class.section || '') : 'N/A'}</td>
                <td>${formattedDate}</td>
                <td>${statusBadge}</td>
                <td>${exam.description || 'No description'}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewExam(${exam.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="editExam(${exam.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteExam(${exam.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function getStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'ongoing': '<span class="badge bg-warning">Ongoing</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function scheduleExam() {
    $('#scheduleExamModal').modal('show');
}

$('#scheduleExamForm').submit(function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const spinner = $('#scheduleExamSpinner');
    
    submitBtn.prop('disabled', true);
    spinner.removeClass('d-none');
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '/exams',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            showAlert('Exam scheduled successfully!', 'success');
            $('#scheduleExamModal').modal('hide');
            $('#scheduleExamForm')[0].reset();
            loadExams();
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                let errorMsg = 'Please fix the following errors:\n';
                Object.values(errors).forEach(error => {
                    errorMsg += '- ' + error[0] + '\n';
                });
                showAlert(errorMsg, 'danger');
            } else {
                showAlert('Error scheduling exam. Please try again.', 'danger');
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false);
            spinner.addClass('d-none');
        }
    });
});

function viewExam(id) {
    $.ajax({
        url: `/exams/${id}`,
        method: 'GET',
        success: function(exam) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Exam Information</h6>
                        <table class="table table-borderless">
                            <tr><td><strong>Name:</strong></td><td>${exam.name}</td></tr>
                            <tr><td><strong>Class:</strong></td><td>${exam.class ? exam.class.name + ' ' + (exam.class.section || '') : 'N/A'}</td></tr>
                            <tr><td><strong>Date:</strong></td><td>${new Date(exam.date).toLocaleDateString()}</td></tr>
                            <tr><td><strong>Status:</strong></td><td>${getStatusBadge(exam.status || 'scheduled')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Description</h6>
                        <p>${exam.description || 'No description provided'}</p>
                    </div>
                </div>
            `;
            $('#viewExamContent').html(content);
            $('#viewExamModal').modal('show');
        },
        error: function(xhr) {
            showAlert('Error loading exam details.', 'danger');
        }
    });
}

function editExam(id) {
    // Load exam data and populate form for editing
    $.ajax({
        url: `/exams/${id}`,
        method: 'GET',
        success: function(exam) {
            $('#examName').val(exam.name);
            $('#examClass').val(exam.class_id);
            $('#examDate').val(exam.date);
            $('#examDescription').val(exam.description);
            
            // Change form action to update
            $('#scheduleExamForm').attr('data-exam-id', id);
            $('#scheduleExamModal .modal-title').text('Edit Exam');
            $('#scheduleExamModal').modal('show');
        },
        error: function(xhr) {
            showAlert('Error loading exam for editing.', 'danger');
        }
    });
}

function deleteExam(id) {
    if (confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
        $.ajax({
            url: `/exams/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showAlert('Exam deleted successfully!', 'success');
                loadExams();
            },
            error: function(xhr) {
                showAlert('Error deleting exam. Please try again.', 'danger');
            }
        });
    }
}

// Quick Action Functions
function publishResults() {
    showAlert('Publish Results functionality will be implemented soon.', 'info');
}

function manageSyllabus() {
    showAlert('Manage Syllabus functionality will be implemented soon.', 'info');
}

function generateMarksheet() {
    showAlert('Generate Marksheet functionality will be implemented soon.', 'info');
}

function viewAnalytics() {
    showAlert('View Analytics functionality will be implemented soon.', 'info');
}

function exportReports() {
    showAlert('Export Reports functionality will be implemented soon.', 'info');
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection