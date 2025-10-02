@extends('layouts.app')

@section('title', 'Attendance Reports')

@section('content')
<style>
    /* Loading States */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 0.375rem;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 0.375rem;
        height: 20px;
        margin-bottom: 10px;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Error States */
    .error-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .error-icon {
        font-size: 3rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }

    .retry-btn {
        margin-top: 1rem;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 0.5rem;
        }

        .card {
            margin-bottom: 1rem;
        }

        .report-card .card-body {
            padding: 1.5rem;
        }

        .report-card .fa-3x {
            font-size: 2rem !important;
        }

        .btn-group {
            flex-wrap: wrap;
        }

        .btn-group .btn {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        .modal-dialog {
            margin: 0.5rem;
        }

        .modal-body {
            padding: 1rem;
        }

        .table-responsive {
            font-size: 0.875rem;
        }

        .d-flex.gap-2 {
            flex-direction: column;
            gap: 0.5rem !important;
        }

        .d-flex.gap-2 .btn {
            width: 100%;
        }

        .header-actions {
            flex-direction: column;
            gap: 0.5rem;
        }

        .header-actions .btn {
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .col-lg-4 {
            margin-bottom: 1rem;
        }

        .report-card .card-body {
            padding: 1rem;
        }

        .modal-dialog {
            margin: 0.25rem;
        }

        .form-row .col-md-6,
        .form-row .col-md-4 {
            width: 100%;
            margin-bottom: 0.75rem;
        }

        .btn-toolbar {
            flex-direction: column;
        }

        .btn-toolbar .btn-group {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .table th,
        .table td {
            padding: 0.5rem 0.25rem;
            font-size: 0.75rem;
        }
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1055;
    }

    .toast {
        min-width: 300px;
    }

    .toast.show {
        opacity: 1;
    }

    /* Form Validation */
    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    /* Accessibility */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Focus indicators */
    .btn:focus,
    .form-control:focus,
    .form-select:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Report card hover effects */
    .report-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    /* Progress bars */
    .progress-bar-animated {
        animation: progress-bar-stripes 1s linear infinite;
    }

    @keyframes progress-bar-stripes {
        0% { background-position: 1rem 0; }
        100% { background-position: 0 0; }
    }

    /* Modal enhancements */
    .modal-content {
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .modal-header {
        border-bottom: 1px solid #e9ecef;
        padding: 1.5rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
    }

    /* Table enhancements */
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,0.02);
    }

    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
    }

    /* Button enhancements */
    .btn {
        transition: all 0.2s ease-in-out;
    }

    .btn:hover {
        transform: translateY(-1px);
    }

    /* Card enhancements */
    .card {
        transition: transform 0.2s ease-in-out;
        border: none;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    /* Badge enhancements */
    .badge {
        font-weight: 500;
        padding: 0.375rem 0.75rem;
    }

    /* Alert enhancements */
    .alert {
        border: none;
        border-radius: 0.5rem;
    }

    .alert-dismissible .btn-close {
        padding: 0.75rem 1rem;
    }
</style>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2 fw-bold">
                                <i class="fas fa-file-alt me-2"></i>
                                Attendance Reports
                            </h1>
                            <p class="mb-0 opacity-75">Generate comprehensive attendance reports and summaries</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('attendance.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>
                                Back to Attendance
                            </a>
                            <a href="{{ route('attendance.analytics') }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-chart-line me-1"></i>
                                Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Types -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 report-card" onclick="showReportModal('daily')">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-calendar-day fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title fw-bold">Daily Reports</h5>
                    <p class="card-text text-muted">Generate daily attendance summaries for specific dates</p>
                    <div class="mt-3">
                        <span class="badge bg-primary">Quick Generate</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 report-card" onclick="showReportModal('monthly')">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-calendar-alt fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title fw-bold">Monthly Reports</h5>
                    <p class="card-text text-muted">Comprehensive monthly attendance analysis and trends</p>
                    <div class="mt-3">
                        <span class="badge bg-success">Detailed</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 report-card" onclick="showReportModal('class')">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title fw-bold">Class Reports</h5>
                    <p class="card-text text-muted">Class-wise attendance reports with student details</p>
                    <div class="mt-3">
                        <span class="badge bg-info">Class-wise</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 report-card" onclick="showReportModal('student')">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-user-graduate fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title fw-bold">Student Reports</h5>
                    <p class="card-text text-muted">Individual student attendance history and patterns</p>
                    <div class="mt-3">
                        <span class="badge bg-warning">Individual</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 report-card" onclick="showReportModal('summary')">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-chart-pie fa-3x text-danger"></i>
                    </div>
                    <h5 class="card-title fw-bold">Summary Reports</h5>
                    <p class="card-text text-muted">Overall attendance statistics and insights</p>
                    <div class="mt-3">
                        <span class="badge bg-danger">Overview</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 report-card" onclick="showReportModal('custom')">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-cogs fa-3x text-secondary"></i>
                    </div>
                    <h5 class="card-title fw-bold">Custom Reports</h5>
                    <p class="card-text text-muted">Create custom reports with specific filters and criteria</p>
                    <div class="mt-3">
                        <span class="badge bg-secondary">Customizable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="fas fa-history text-primary me-2"></i>
                            Recent Reports
                        </h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshReports()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Report Name</th>
                                    <th>Type</th>
                                    <th>Generated On</th>
                                    <th>Parameters</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reportsTable">
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            <span class="fw-semibold">Monthly Report - December 2024</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success">Monthly</span></td>
                                    <td>Dec 15, 2024 10:30 AM</td>
                                    <td>
                                        <small class="text-muted">All Classes, Dec 2024</small>
                                    </td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="downloadReport('monthly_dec_2024.pdf')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewReport('monthly_dec_2024')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteReport('monthly_dec_2024')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-excel text-success me-2"></i>
                                            <span class="fw-semibold">Class 5-A Daily Report</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">Class</span></td>
                                    <td>Dec 14, 2024 02:15 PM</td>
                                    <td>
                                        <small class="text-muted">Class 5-A, Dec 14, 2024</small>
                                    </td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="downloadReport('class_5a_daily.xlsx')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewReport('class_5a_daily')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteReport('class_5a_daily')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-csv text-warning me-2"></i>
                                            <span class="fw-semibold">Student Attendance Summary</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-warning">Student</span></td>
                                    <td>Dec 13, 2024 09:45 AM</td>
                                    <td>
                                        <small class="text-muted">Rahul Sharma, Nov-Dec 2024</small>
                                    </td>
                                    <td><span class="badge bg-warning">Processing</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary" disabled>
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Generation Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="reportModalLabel">
                    <i class="fas fa-file-alt me-2"></i>
                    Generate Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <input type="hidden" id="reportType" name="report_type">
                    
                    <!-- Report Name -->
                    <div class="mb-3">
                        <label for="reportName" class="form-label fw-semibold">Report Name</label>
                        <input type="text" class="form-control" id="reportName" name="report_name" required>
                    </div>

                    <!-- Date Range -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="startDate" class="form-label fw-semibold">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label fw-semibold">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" required>
                        </div>
                    </div>

                    <!-- Class Selection -->
                    <div class="mb-3" id="classSelection">
                        <label for="classFilter" class="form-label fw-semibold">Class</label>
                        <select class="form-select" id="classFilter" name="class_id">
                            <option value="">All Classes</option>
                            <option value="1">Class 1</option>
                            <option value="2">Class 2</option>
                            <option value="3">Class 3</option>
                            <option value="4">Class 4</option>
                            <option value="5">Class 5</option>
                        </select>
                    </div>

                    <!-- Section Selection -->
                    <div class="mb-3" id="sectionSelection">
                        <label for="sectionFilter" class="form-label fw-semibold">Section</label>
                        <select class="form-select" id="sectionFilter" name="section">
                            <option value="">All Sections</option>
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                        </select>
                    </div>

                    <!-- Student Selection -->
                    <div class="mb-3" id="studentSelection" style="display: none;">
                        <label for="studentFilter" class="form-label fw-semibold">Student</label>
                        <select class="form-select" id="studentFilter" name="student_id">
                            <option value="">Select Student</option>
                            <option value="1">Rahul Sharma - Class 5-A</option>
                            <option value="2">Priya Singh - Class 4-B</option>
                            <option value="3">Amit Kumar - Class 3-A</option>
                        </select>
                    </div>

                    <!-- Report Format -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Report Format</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="formatPDF" value="pdf" checked>
                                    <label class="form-check-label" for="formatPDF">
                                        <i class="fas fa-file-pdf text-danger me-1"></i>
                                        PDF Report
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="formatExcel" value="excel">
                                    <label class="form-check-label" for="formatExcel">
                                        <i class="fas fa-file-excel text-success me-1"></i>
                                        Excel Sheet
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="formatCSV" value="csv">
                                    <label class="form-check-label" for="formatCSV">
                                        <i class="fas fa-file-csv text-warning me-1"></i>
                                        CSV Data
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Options -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Additional Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeCharts" name="include_charts" checked>
                            <label class="form-check-label" for="includeCharts">
                                Include Charts and Graphs
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeStatistics" name="include_statistics" checked>
                            <label class="form-check-label" for="includeStatistics">
                                Include Statistical Analysis
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeRecommendations" name="include_recommendations">
                            <label class="form-check-label" for="includeRecommendations">
                                Include Recommendations
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateReport()">
                    <i class="fas fa-cog me-1"></i>
                    Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-header .btn-close {
    filter: invert(1);
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Report modal management
function showReportModal(reportType) {
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    const reportTypeInput = document.getElementById('reportType');
    const reportNameInput = document.getElementById('reportName');
    const modalTitle = document.getElementById('reportModalLabel');
    const studentSelection = document.getElementById('studentSelection');
    
    // Set report type
    reportTypeInput.value = reportType;
    
    // Update modal title and default name
    const reportTypes = {
        'daily': 'Daily Report',
        'monthly': 'Monthly Report', 
        'class': 'Class Report',
        'student': 'Student Report',
        'summary': 'Summary Report',
        'custom': 'Custom Report'
    };
    
    const reportName = reportTypes[reportType] || 'Report';
    modalTitle.innerHTML = `<i class="fas fa-file-alt me-2"></i>Generate ${reportName}`;
    reportNameInput.value = `${reportName} - ${new Date().toLocaleDateString()}`;
    
    // Show/hide student selection for student reports
    if (reportType === 'student') {
        studentSelection.style.display = 'block';
        document.getElementById('studentFilter').required = true;
    } else {
        studentSelection.style.display = 'none';
        document.getElementById('studentFilter').required = false;
    }
    
    // Set default dates
    const today = new Date();
    const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];
    
    modal.show();
}

// Generate report function
async function generateReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Show loading
    Swal.fire({
        title: 'Generating Report',
        text: 'Please wait while we prepare your report...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 3000));
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
        modal.hide();
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Report Generated Successfully!',
            text: 'Your report has been generated and will be downloaded shortly.',
            showConfirmButton: false,
            timer: 2000
        });
        
        // Add to recent reports table
        addToRecentReports(formData);
        
        // Reset form
        form.reset();
        
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Generation Failed',
            text: 'There was an error generating your report. Please try again.'
        });
    }
}

// Add report to recent reports table
function addToRecentReports(formData) {
    const table = document.getElementById('reportsTable');
    const reportType = formData.get('report_type');
    const reportName = formData.get('report_name');
    const format = formData.get('format');
    
    const formatIcons = {
        'pdf': 'fas fa-file-pdf text-danger',
        'excel': 'fas fa-file-excel text-success', 
        'csv': 'fas fa-file-csv text-warning'
    };
    
    const typeColors = {
        'daily': 'primary',
        'monthly': 'success',
        'class': 'info',
        'student': 'warning',
        'summary': 'danger',
        'custom': 'secondary'
    };
    
    const newRow = `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <i class="${formatIcons[format]} me-2"></i>
                    <span class="fw-semibold">${reportName}</span>
                </div>
            </td>
            <td><span class="badge bg-${typeColors[reportType]}">${reportType.charAt(0).toUpperCase() + reportType.slice(1)}</span></td>
            <td>${new Date().toLocaleString()}</td>
            <td>
                <small class="text-muted">${formData.get('class_id') ? 'Class ' + formData.get('class_id') : 'All Classes'}, ${formData.get('start_date')} to ${formData.get('end_date')}</small>
            </td>
            <td><span class="badge bg-success">Completed</span></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="downloadReport('${reportName.toLowerCase().replace(/\s+/g, '_')}.${format}')">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="viewReport('${reportName.toLowerCase().replace(/\s+/g, '_')}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteReport('${reportName.toLowerCase().replace(/\s+/g, '_')}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    
    table.insertAdjacentHTML('afterbegin', newRow);
}

// Download report function
function downloadReport(filename) {
    Swal.fire({
        icon: 'info',
        title: 'Download Started',
        text: `Downloading ${filename}...`,
        showConfirmButton: false,
        timer: 1500
    });
    
    // Simulate download
    setTimeout(() => {
        const link = document.createElement('a');
        link.href = '#'; // In real implementation, this would be the actual file URL
        link.download = filename;
        link.click();
    }, 500);
}

// View report function
function viewReport(reportId) {
    Swal.fire({
        title: 'Report Preview',
        html: `
            <div class="text-center">
                <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                <p>Report preview functionality would be implemented here.</p>
                <p class="text-muted">This would show a preview of the report: <strong>${reportId}</strong></p>
            </div>
        `,
        width: 600,
        showCloseButton: true,
        confirmButtonText: 'Download Full Report'
    });
}

// Delete report function
function deleteReport(reportId) {
    Swal.fire({
        title: 'Delete Report?',
        text: 'Are you sure you want to delete this report? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Find and remove the row
            const rows = document.querySelectorAll('#reportsTable tr');
            rows.forEach(row => {
                if (row.innerHTML.includes(reportId)) {
                    row.remove();
                }
            });
            
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'The report has been deleted.',
                showConfirmButton: false,
                timer: 1500
            });
        }
    });
}

// Refresh reports function
function refreshReports() {
    Swal.fire({
        title: 'Refreshing Reports',
        text: 'Loading latest reports...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Simulate API call
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Reports Refreshed',
            text: 'All reports have been updated.',
            showConfirmButton: false,
            timer: 1500
        });
    }, 2000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to report cards
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endsection
        font-size: 0.95rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .btn-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
    }

    .btn-info {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
    }

    .btn-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(66, 153, 225, 0.3);
    }

    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .report-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .report-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .report-icon {
        width: 24px;
        height: 24px;
        fill: #667eea;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f7fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #718096;
        font-weight: 500;
    }

    .chart-container {
        height: 300px;
        margin: 20px 0;
        background: #f7fafc;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #718096;
        font-style: italic;
    }

    .table-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .table th {
        background: #f7fafc;
        color: #4a5568;
        font-weight: 600;
        padding: 15px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.9rem;
    }

    .table td {
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        color: #2d3748;
    }

    .table tbody tr:hover {
        background: #f7fafc;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-present {
        background: #c6f6d5;
        color: #22543d;
    }

    .status-absent {
        background: #fed7d7;
        color: #742a2a;
    }

    .status-late {
        background: #feebc8;
        color: #7b341e;
    }

    .status-excused {
        background: #bee3f8;
        color: #2a4365;
    }

    .percentage-bar {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 5px;
    }

    .percentage-fill {
        height: 100%;
        background: linear-gradient(90deg, #48bb78, #38a169);
        transition: width 0.3s ease;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: #718096;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #718096;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            min-width: 100%;
        }
        
        .reports-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="reports-container">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Attendance Reports & Analytics</h1>
            <p class="page-subtitle">Comprehensive attendance tracking and analysis</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form id="reportFilters" method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label">Start Date</label>
                        <input type="date" name="start_date" class="filter-input" 
                               value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">End Date</label>
                        <input type="date" name="end_date" class="filter-input" 
                               value="{{ request('end_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Class</label>
                        <select name="class_id" class="filter-input">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Generate Report
                        </button>
                    </div>
                    <div class="filter-group">
                        <button type="button" class="btn btn-success" onclick="exportReport('csv')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Reports Grid -->
        <div class="reports-grid">
            <!-- Overall Statistics -->
            <div class="report-card">
                <h3 class="report-title">
                    <svg class="report-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Overall Statistics
                </h3>
                <div class="stats-grid" id="overallStats">
                    <div class="loading">Loading...</div>
                </div>
            </div>

            <!-- Daily Trends -->
            <div class="report-card">
                <h3 class="report-title">
                    <svg class="report-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Daily Attendance Trends
                </h3>
                <div class="chart-container" id="dailyTrendsChart">
                    Chart will be displayed here (requires Chart.js integration)
                </div>
            </div>
        </div>

        <!-- Class-wise Statistics (if no specific class selected) -->
        <div class="report-card" id="classStatsCard" style="display: none;">
            <h3 class="report-title">
                <svg class="report-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h4a1 1 0 011 1v5m-6 0h6"></path>
                </svg>
                Class-wise Performance
            </h3>
            <div class="table-container">
                <table class="table" id="classStatsTable">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Total Students</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody id="classStatsBody">
                        <tr>
                            <td colspan="6" class="loading">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Attendance Students -->
        <div class="report-card">
            <h3 class="report-title">
                <svg class="report-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Students with Low Attendance (&lt; 75%)
            </h3>
            <div class="table-container">
                <table class="table" id="lowAttendanceTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Total Days</th>
                            <th>Present Days</th>
                            <th>Attendance %</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody id="lowAttendanceBody">
                        <tr>
                            <td colspan="6" class="loading">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadReportData();
    
    // Auto-submit form when filters change
    document.querySelectorAll('.filter-input').forEach(input => {
        input.addEventListener('change', function() {
            if (this.type !== 'submit') {
                document.getElementById('reportFilters').submit();
            }
        });
    });
});

function loadReportData() {
    const params = new URLSearchParams(window.location.search);
    
    fetch(`/attendance/analytics?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOverallStats(data.data.overview);
                updateClassStats(data.data.class_stats);
                updateLowAttendanceStudents(data.data.low_attendance_students);
            } else {
                showError('Failed to load report data');
            }
        })
        .catch(error => {
            console.error('Error loading report data:', error);
            showError('Failed to load report data');
        });
}

function updateOverallStats(overview) {
    const container = document.getElementById('overallStats');
    container.innerHTML = `
        <div class="stat-item">
            <div class="stat-value">${overview.total_records}</div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${overview.present_count}</div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${overview.absent_count}</div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${overview.late_count}</div>
            <div class="stat-label">Late</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">${overview.attendance_percentage}%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    `;
}

function updateClassStats(classStats) {
    if (Object.keys(classStats).length === 0) {
        document.getElementById('classStatsCard').style.display = 'none';
        return;
    }
    
    document.getElementById('classStatsCard').style.display = 'block';
    const tbody = document.getElementById('classStatsBody');
    
    let html = '';
    Object.values(classStats).forEach(classData => {
        const className = classData[0]?.class_model?.name || 'Unknown';
        const stats = classData.reduce((acc, item) => {
            acc[item.status] = item.count;
            acc.total += item.count;
            return acc;
        }, { present: 0, absent: 0, late: 0, total: 0 });
        
        const percentage = stats.total > 0 ? ((stats.present / stats.total) * 100).toFixed(1) : 0;
        
        html += `
            <tr>
                <td>${className}</td>
                <td>${stats.total}</td>
                <td>${stats.present}</td>
                <td>${stats.absent}</td>
                <td>${stats.late}</td>
                <td>
                    ${percentage}%
                    <div class="percentage-bar">
                        <div class="percentage-fill" style="width: ${percentage}%"></div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html || '<tr><td colspan="6" class="no-data">No class data available</td></tr>';
}

function updateLowAttendanceStudents(students) {
    const tbody = document.getElementById('lowAttendanceBody');
    
    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">No students with low attendance found</td></tr>';
        return;
    }
    
    let html = '';
    students.forEach(studentData => {
        const student = studentData.student;
        html += `
            <tr>
                <td>${student.name}</td>
                <td>${student.class_model?.name || 'N/A'}</td>
                <td>${studentData.total_days}</td>
                <td>${studentData.present_days}</td>
                <td>${studentData.percentage}%</td>
                <td>
                    <div class="percentage-bar">
                        <div class="percentage-fill" style="width: ${studentData.percentage}%; background: linear-gradient(90deg, #f56565, #e53e3e);"></div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function exportReport(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('format', format);
    
    const url = `/attendance/export-report?${params.toString()}`;
    window.open(url, '_blank');
}

function showError(message) {
    // Simple error display - could be enhanced with a proper notification system
    alert(message);
}
</script>

@push('scripts')
<script>
    // Initialize all attendance modules
    const notifications = new AttendanceNotifications();
    const validator = new AttendanceValidator();
    const accessibility = new AttendanceAccessibility();
    const performance = new AttendancePerformance();
    const loading = new AttendanceLoading();
    
    // Initialize modules
    validator.init();
    accessibility.init();
    performance.init();
    
    // Store instances globally for other scripts to use
    window.attendanceModules = {
        notifications,
        validator,
        accessibility,
        performance,
        loading
    };
</script>
@endpush

@endsection