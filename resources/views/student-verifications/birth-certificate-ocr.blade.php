@extends('layouts.app')

@section('title', 'Birth Certificate OCR')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-medical-alt text-primary me-2"></i>
                        Birth Certificate OCR Processing
                    </h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info btn-sm" id="checkServiceBtn">
                            <i class="fas fa-server me-1"></i>
                            Check Service Status
                        </button>
                        <a href="{{ route('student-verifications.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Verifications
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Service Status Alert -->
                    <div id="serviceStatusAlert" class="alert alert-info d-none" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="serviceStatusMessage"></span>
                    </div>

                    <!-- OCR Processing Form -->
                    <form id="birthCertificateOCRForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">
                                        <i class="fas fa-user-graduate me-1"></i>
                                        Select Student <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">Choose a student...</option>
                                        @foreach($students as $student)
                                            <option value="{{ $student->id }}" 
                                                    data-name="{{ $student->name }}"
                                                    data-admission="{{ $student->admission_number }}"
                                                    data-father="{{ $student->father_name }}"
                                                    data-mother="{{ $student->mother_name }}"
                                                    data-dob="{{ $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '' }}">
                                                {{ $student->name }} ({{ $student->admission_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="birth_certificate" class="form-label">
                                        <i class="fas fa-file-upload me-1"></i>
                                        Birth Certificate Document <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="birth_certificate" name="birth_certificate" 
                                           accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="form-text">
                                        Supported formats: PDF, JPG, JPEG, PNG. Maximum size: 10MB
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mb-3">
                                    <button type="submit" class="btn btn-primary" id="processBtn">
                                        <i class="fas fa-cogs me-1"></i>
                                        Process Birth Certificate
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="checkStatusBtn">
                                        <i class="fas fa-search me-1"></i>
                                        Check Status
                                    </button>
                                    <button type="button" class="btn btn-outline-info" id="fillStudentDataBtn">
                                        <i class="fas fa-user-plus me-1"></i>
                                        Fill Student Data
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Current Status Display -->
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Current Status
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="currentStatus">
                                            <p class="text-muted mb-0">Select a student to check OCR status</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Progress Indicator -->
                    <div id="progressContainer" class="d-none mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Processing Birth Certificate...
                                </h6>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 100%"></div>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Please wait while we extract and validate the document data.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Results Display -->
                    <div id="resultsContainer" class="d-none mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-alt me-2"></i>
                                    OCR Processing Results
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="resultsContent"></div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-success btn-sm" id="saveResultsBtn">
                                        <i class="fas fa-save me-1"></i>
                                        Save Results
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" id="viewDetailsBtn" data-bs-toggle="modal" data-bs-target="#detailsModal">
                                        <i class="fas fa-eye me-1"></i>
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="fas fa-file-alt me-2"></i>
                    Birth Certificate OCR Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">
                    <!-- Details will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.status-badge {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}

.confidence-bar {
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
}

.extracted-data-item {
    border-left: 3px solid #007bff;
    padding-left: 15px;
    margin-bottom: 10px;
}

.validation-item {
    padding: 8px 12px;
    border-radius: 5px;
    margin-bottom: 8px;
}

.validation-passed {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.validation-failed {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.validation-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('birthCertificateOCRForm');
    const studentSelect = document.getElementById('student_id');
    const processBtn = document.getElementById('processBtn');
    const checkStatusBtn = document.getElementById('checkStatusBtn');
    const checkServiceBtn = document.getElementById('checkServiceBtn');
    const fillStudentDataBtn = document.getElementById('fillStudentDataBtn');
    const progressContainer = document.getElementById('progressContainer');
    const resultsContainer = document.getElementById('resultsContainer');
    const currentStatus = document.getElementById('currentStatus');
    const serviceStatusAlert = document.getElementById('serviceStatusAlert');

    let currentOCRResult = null;

    // Check service status on page load
    checkServiceStatus();

    // Student selection change handler
    studentSelect.addEventListener('change', function() {
        if (this.value) {
            checkStudentStatus(this.value);
        } else {
            currentStatus.innerHTML = '<p class="text-muted mb-0">Select a student to check OCR status</p>';
        }
    });

    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        processOCR();
    });

    // Check status button handler
    checkStatusBtn.addEventListener('click', function() {
        const studentId = studentSelect.value;
        if (studentId) {
            checkStudentStatus(studentId);
        } else {
            showAlert('Please select a student first.', 'warning');
        }
    });

    // Check service button handler
    checkServiceBtn.addEventListener('click', function() {
        checkServiceStatus();
    });

    // Fill student data button handler
    fillStudentDataBtn.addEventListener('click', function() {
        fillStudentData();
    });

    // Process OCR function
    function processOCR() {
        const formData = new FormData(form);
        
        // Show progress
        progressContainer.classList.remove('d-none');
        resultsContainer.classList.add('d-none');
        processBtn.disabled = true;

        fetch('{{ route("birth-certificate.ocr") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            progressContainer.classList.add('d-none');
            processBtn.disabled = false;

            if (data.success) {
                currentOCRResult = data;
                displayResults(data);
                showAlert('Birth certificate processed successfully!', 'success');
                
                // Update current status
                checkStudentStatus(studentSelect.value);
            } else {
                showAlert(data.message || 'OCR processing failed', 'danger');
            }
        })
        .catch(error => {
            progressContainer.classList.add('d-none');
            processBtn.disabled = false;
            console.error('Error:', error);
            showAlert('An error occurred during processing', 'danger');
        });
    }

    // Check student status function
    function checkStudentStatus(studentId) {
        fetch(`{{ route("birth-certificate.status") }}?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCurrentStatus(data);
            } else {
                currentStatus.innerHTML = '<p class="text-danger mb-0">Error loading status</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            currentStatus.innerHTML = '<p class="text-danger mb-0">Error loading status</p>';
        });
    }

    // Check service status function
    function checkServiceStatus() {
        fetch('{{ route("birth-certificate.service-check") }}')
        .then(response => response.json())
        .then(data => {
            displayServiceStatus(data);
        })
        .catch(error => {
            console.error('Error:', error);
            showServiceStatus(false, 'Failed to check service status');
        });
    }

    // Display current status
    function displayCurrentStatus(data) {
        if (data.status === 'not_processed') {
            currentStatus.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-file-medical-alt fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No birth certificate processed yet</p>
                </div>
            `;
        } else {
            const statusBadge = getStatusBadge(data.status);
            const confidenceColor = getConfidenceColor(data.confidence_score);
            
            currentStatus.innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><strong>Status:</strong></span>
                            ${statusBadge}
                        </div>
                        <div class="mb-2">
                            <span><strong>Confidence:</strong> ${data.confidence_score}%</span>
                            <div class="progress mt-1" style="height: 8px;">
                                <div class="progress-bar ${confidenceColor}" style="width: ${data.confidence_score}%"></div>
                            </div>
                        </div>
                        <small class="text-muted">
                            Processed: ${formatDateTime(data.processed_at)}
                        </small>
                    </div>
                </div>
            `;
        }
    }

    // Display service status
    function displayServiceStatus(data) {
        if (data.success && data.service_available) {
            showServiceStatus(true, `Service is available (${data.service_mode} mode)`);
        } else {
            showServiceStatus(false, data.message || 'Service unavailable');
        }
    }

    // Show service status alert
    function showServiceStatus(available, message) {
        serviceStatusAlert.className = `alert ${available ? 'alert-success' : 'alert-warning'}`;
        document.getElementById('serviceStatusMessage').textContent = message;
        serviceStatusAlert.classList.remove('d-none');
    }

    // Display OCR results
    function displayResults(data) {
        const resultsContent = document.getElementById('resultsContent');
        const statusBadge = getStatusBadge(data.status);
        const confidenceColor = getConfidenceColor(data.confidence_score);

        let extractedDataHtml = '';
        if (data.extracted_data && Object.keys(data.extracted_data).length > 0) {
            extractedDataHtml = Object.entries(data.extracted_data).map(([key, value]) => `
                <div class="extracted-data-item">
                    <strong>${formatFieldName(key)}:</strong> ${value || 'Not found'}
                </div>
            `).join('');
        }

        let validationHtml = '';
        if (data.validation_results && data.validation_results.length > 0) {
            validationHtml = data.validation_results.map(result => {
                const statusClass = result.passed ? 'validation-passed' : 'validation-failed';
                const icon = result.passed ? 'fas fa-check-circle' : 'fas fa-times-circle';
                return `
                    <div class="validation-item ${statusClass}">
                        <i class="${icon} me-2"></i>
                        ${result.message}
                    </div>
                `;
            }).join('');
        }

        resultsContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-chart-line me-2"></i>Processing Summary</h6>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><strong>Status:</strong></span>
                            ${statusBadge}
                        </div>
                        <div class="mb-2">
                            <span><strong>Confidence Score:</strong> ${data.confidence_score}%</span>
                            <div class="progress mt-1">
                                <div class="progress-bar ${confidenceColor}" style="width: ${data.confidence_score}%"></div>
                            </div>
                        </div>
                        ${data.reference_id ? `<small class="text-muted">Reference ID: ${data.reference_id}</small>` : ''}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-database me-2"></i>Extracted Data</h6>
                    <div class="mb-3">
                        ${extractedDataHtml || '<p class="text-muted">No data extracted</p>'}
                    </div>
                </div>
            </div>
            ${validationHtml ? `
                <div class="mt-3">
                    <h6><i class="fas fa-check-double me-2"></i>Validation Results</h6>
                    ${validationHtml}
                </div>
            ` : ''}
        `;

        resultsContainer.classList.remove('d-none');
    }

    // Fill student data from selection
    function fillStudentData() {
        const selectedOption = studentSelect.options[studentSelect.selectedIndex];
        if (!selectedOption.value) {
            showAlert('Please select a student first.', 'warning');
            return;
        }

        // This would typically populate form fields with student data
        // For now, just show the selected student info
        const studentInfo = {
            name: selectedOption.dataset.name,
            admission: selectedOption.dataset.admission,
            father: selectedOption.dataset.father,
            mother: selectedOption.dataset.mother,
            dob: selectedOption.dataset.dob
        };

        showAlert(`Student data loaded: ${studentInfo.name} (${studentInfo.admission})`, 'info');
    }

    // Utility functions
    function getStatusBadge(status) {
        const badges = {
            'verified': '<span class="badge bg-success status-badge">Verified</span>',
            'manual_review': '<span class="badge bg-warning status-badge">Manual Review</span>',
            'failed': '<span class="badge bg-danger status-badge">Failed</span>',
            'processing': '<span class="badge bg-info status-badge">Processing</span>',
            'pending': '<span class="badge bg-secondary status-badge">Pending</span>'
        };
        return badges[status] || '<span class="badge bg-secondary status-badge">Unknown</span>';
    }

    function getConfidenceColor(score) {
        if (score >= 85) return 'bg-success';
        if (score >= 60) return 'bg-warning';
        return 'bg-danger';
    }

    function formatFieldName(fieldName) {
        return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    }

    function showAlert(message, type) {
        // Create and show bootstrap alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at top of card body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alertDiv, cardBody.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
});
</script>
@endsection