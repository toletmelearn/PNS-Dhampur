@extends('layouts.app')

@section('title', 'Aadhaar Verification')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-id-card text-primary me-2"></i>
                        Aadhaar Verification
                    </h4>
                    <button type="button" class="btn btn-outline-info btn-sm" id="checkServiceBtn">
                        <i class="fas fa-server me-1"></i>
                        Check Service Status
                    </button>
                </div>
                <div class="card-body">
                    <!-- Service Status Alert -->
                    <div id="serviceStatusAlert" class="alert alert-info d-none">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="serviceStatusText"></span>
                        </div>
                    </div>

                    <!-- Student Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="studentSelect" class="form-label">Select Student</label>
                            <select class="form-select" id="studentSelect" required>
                                <option value="">Choose a student...</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" data-name="{{ $student->name }}" 
                                            data-father="{{ $student->father_name }}" 
                                            data-dob="{{ $student->date_of_birth }}" 
                                            data-gender="{{ $student->gender }}">
                                        {{ $student->name }} ({{ $student->admission_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Aadhaar Status</label>
                            <div id="currentStatus" class="p-2 bg-light rounded">
                                <span class="text-muted">Select a student to view status</span>
                            </div>
                        </div>
                    </div>

                    <!-- Aadhaar Verification Form -->
                    <form id="aadhaarVerificationForm" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="aadhaarNumber" class="form-label">
                                        Aadhaar Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="aadhaarNumber" 
                                           placeholder="XXXX XXXX XXXX" 
                                           maxlength="14" 
                                           required
                                           pattern="[0-9\s]{12,14}"
                                           title="Enter 12-digit Aadhaar number (spaces optional)">
                                    <div class="form-text">Enter 12-digit Aadhaar number (spaces optional)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="studentName" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="studentName" 
                                           placeholder="As per Aadhaar card">
                                    <div class="form-text">Leave blank to skip name verification</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fatherName" class="form-label">Father's Name</label>
                                    <input type="text" class="form-control" id="fatherName" 
                                           placeholder="As per Aadhaar card">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dateOfBirth">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="verifyBtn">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Verify Aadhaar
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="prefillBtn">
                                        <i class="fas fa-fill-drip me-1"></i>
                                        Pre-fill from Student Data
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Verification Progress -->
                    <div id="verificationProgress" class="d-none mt-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h5>Verifying Aadhaar...</h5>
                                <p class="text-muted mb-0">Please wait while we verify the Aadhaar details</p>
                                <div class="progress mt-3" style="height: 6px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Results -->
                    <div id="verificationResults" class="d-none mt-4">
                        <!-- Results will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verification Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verification Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Modal content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveResultBtn">Save Result</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .verification-card {
        border-left: 4px solid #007bff;
    }
    
    .match-score {
        font-weight: bold;
        font-size: 1.1em;
    }
    
    .match-score.high { color: #28a745; }
    .match-score.medium { color: #ffc107; }
    .match-score.low { color: #dc3545; }
    
    .confidence-badge {
        font-size: 0.9em;
        padding: 0.25rem 0.5rem;
    }
    
    .service-status {
        padding: 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.9em;
    }
    
    .service-status.available {
        background-color: #d1edff;
        color: #0c63e4;
        border: 1px solid #b8daff;
    }
    
    .service-status.unavailable {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .aadhaar-input {
        font-family: 'Courier New', monospace;
        letter-spacing: 1px;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentStudentId = null;
    let verificationResult = null;

    // Format Aadhaar number input
    $('#aadhaarNumber').on('input', function() {
        let value = $(this).val().replace(/\s/g, '');
        if (value.length <= 12) {
            let formatted = value.replace(/(\d{4})(\d{4})(\d{4})/, '$1 $2 $3');
            $(this).val(formatted);
        }
    });

    // Student selection handler
    $('#studentSelect').on('change', function() {
        const studentId = $(this).val();
        const selectedOption = $(this).find('option:selected');
        
        if (studentId) {
            currentStudentId = studentId;
            $('#aadhaarVerificationForm').removeClass('d-none');
            
            // Load current Aadhaar status
            loadAadhaarStatus(studentId);
            
            // Store student data for pre-fill
            const studentData = {
                name: selectedOption.data('name'),
                father: selectedOption.data('father'),
                dob: selectedOption.data('dob'),
                gender: selectedOption.data('gender')
            };
            
            $('#prefillBtn').data('student', studentData);
        } else {
            currentStudentId = null;
            $('#aadhaarVerificationForm').addClass('d-none');
            $('#currentStatus').html('<span class="text-muted">Select a student to view status</span>');
        }
    });

    // Pre-fill button handler
    $('#prefillBtn').on('click', function() {
        const studentData = $(this).data('student');
        if (studentData) {
            $('#studentName').val(studentData.name || '');
            $('#fatherName').val(studentData.father || '');
            $('#dateOfBirth').val(studentData.dob || '');
            $('#gender').val(studentData.gender || '');
        }
    });

    // Check service status
    $('#checkServiceBtn').on('click', function() {
        checkServiceStatus();
    });

    // Form submission
    $('#aadhaarVerificationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!currentStudentId) {
            showAlert('Please select a student first.', 'warning');
            return;
        }
        
        const aadhaarNumber = $('#aadhaarNumber').val().replace(/\s/g, '');
        if (aadhaarNumber.length !== 12) {
            showAlert('Please enter a valid 12-digit Aadhaar number.', 'warning');
            return;
        }
        
        verifyAadhaar();
    });

    // Load Aadhaar status for selected student
    function loadAadhaarStatus(studentId) {
        $.get(`/student-verifications/aadhaar/status/${studentId}`)
            .done(function(response) {
                if (response.success) {
                    displayCurrentStatus(response);
                }
            })
            .fail(function() {
                $('#currentStatus').html('<span class="text-warning">Unable to load status</span>');
            });
    }

    // Display current Aadhaar status
    function displayCurrentStatus(data) {
        let statusHtml = '';
        
        if (data.status === 'not_verified') {
            statusHtml = '<span class="badge bg-secondary">Not Verified</span>';
        } else {
            const statusClass = getStatusClass(data.status);
            const confidenceClass = getConfidenceClass(data.confidence_score);
            
            statusHtml = `
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge ${statusClass}">${formatStatus(data.status)}</span>
                    <span class="match-score ${confidenceClass}">${data.confidence_score}%</span>
                </div>
                <small class="text-muted d-block mt-1">
                    Verified: ${formatDate(data.verified_at)}
                </small>
            `;
        }
        
        $('#currentStatus').html(statusHtml);
    }

    // Check service status
    function checkServiceStatus() {
        $('#checkServiceBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Checking...');
        
        $.get('/student-verifications/aadhaar/service-check')
            .done(function(response) {
                if (response.success) {
                    displayServiceStatus(response);
                }
            })
            .fail(function() {
                showAlert('Unable to check service status.', 'warning');
            })
            .always(function() {
                $('#checkServiceBtn').prop('disabled', false).html('<i class="fas fa-server me-1"></i>Check Service Status');
            });
    }

    // Display service status
    function displayServiceStatus(data) {
        const statusClass = data.service_available ? 'available' : 'unavailable';
        const statusIcon = data.service_available ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const statusText = data.service_available ? 'Service Available' : 'Service Unavailable';
        const modeText = data.mock_mode ? ' (Mock Mode)' : ' (Live Mode)';
        
        const alertHtml = `
            <div class="service-status ${statusClass}">
                <i class="fas ${statusIcon} me-2"></i>
                <strong>${statusText}</strong>${modeText}
                <div class="mt-2 small">
                    <div>Success Rate: ${data.stats.mock_success_rate}</div>
                    <div>Timeout: ${data.stats.timeout}s</div>
                </div>
            </div>
        `;
        
        $('#serviceStatusAlert').removeClass('d-none').html(alertHtml);
    }

    // Verify Aadhaar
    function verifyAadhaar() {
        const formData = {
            aadhaar_number: $('#aadhaarNumber').val(),
            student_id: currentStudentId,
            name: $('#studentName').val() || null,
            father_name: $('#fatherName').val() || null,
            date_of_birth: $('#dateOfBirth').val() || null,
            gender: $('#gender').val() || null,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        // Show progress
        $('#verificationProgress').removeClass('d-none');
        $('#verifyBtn').prop('disabled', true);
        
        $.post('/student-verifications/aadhaar/verify', formData)
            .done(function(response) {
                if (response.success) {
                    verificationResult = response;
                    displayVerificationResults(response);
                    loadAadhaarStatus(currentStudentId); // Refresh status
                } else {
                    showAlert(response.message || 'Verification failed.', 'danger');
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                showAlert(response?.message || 'An error occurred during verification.', 'danger');
            })
            .always(function() {
                $('#verificationProgress').addClass('d-none');
                $('#verifyBtn').prop('disabled', false);
            });
    }

    // Display verification results
    function displayVerificationResults(data) {
        const confidenceClass = getConfidenceClass(data.confidence_score);
        const statusClass = getStatusClass(data.status);
        
        const resultsHtml = `
            <div class="card verification-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Verification Results</h5>
                    <span class="badge ${statusClass}">${formatStatus(data.status)}</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Overall Match Score</h6>
                            <div class="match-score ${confidenceClass} mb-3">${data.confidence_score}%</div>
                            
                            <h6>Confidence Level</h6>
                            <span class="badge confidence-badge bg-${getConfidenceBadgeColor(data.confidence_level)}">${data.confidence_level}</span>
                        </div>
                        <div class="col-md-6">
                            <h6>Individual Match Scores</h6>
                            <div class="match-scores">
                                ${formatMatchScores(data.match_scores)}
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Verified Data</h6>
                            <div class="verified-data">
                                ${formatVerifiedData(data.verified_data)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Reference Information</h6>
                            <small class="text-muted">
                                <div>Reference ID: ${data.reference_id}</div>
                                <div>Verification ID: ${data.verification_id}</div>
                            </small>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-info btn-sm" onclick="showDetailedResults()">
                            <i class="fas fa-eye me-1"></i>View Detailed Results
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('#verificationResults').html(resultsHtml).removeClass('d-none');
    }

    // Show detailed results in modal
    window.showDetailedResults = function() {
        if (verificationResult) {
            const modalContent = `
                <div class="row">
                    <div class="col-12">
                        <h6>Complete Verification Data</h6>
                        <pre class="bg-light p-3 rounded"><code>${JSON.stringify(verificationResult, null, 2)}</code></pre>
                    </div>
                </div>
            `;
            
            $('#modalBody').html(modalContent);
            $('#resultModal').modal('show');
        }
    };

    // Helper functions
    function getStatusClass(status) {
        const classes = {
            'approved': 'bg-success',
            'manual_review': 'bg-warning',
            'rejected': 'bg-danger',
            'pending': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    }

    function getConfidenceClass(score) {
        if (score >= 90) return 'high';
        if (score >= 70) return 'medium';
        return 'low';
    }

    function getConfidenceBadgeColor(level) {
        const colors = {
            'HIGH': 'success',
            'MEDIUM': 'warning',
            'LOW': 'danger',
            'VERY_LOW': 'dark'
        };
        return colors[level] || 'secondary';
    }

    function formatStatus(status) {
        return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatMatchScores(scores) {
        if (!scores) return '<span class="text-muted">No match scores available</span>';
        
        let html = '';
        Object.keys(scores).forEach(key => {
            if (key !== 'overall') {
                const score = scores[key];
                const confidenceClass = getConfidenceClass(score);
                html += `
                    <div class="d-flex justify-content-between">
                        <span>${formatStatus(key)}:</span>
                        <span class="match-score ${confidenceClass}">${score}%</span>
                    </div>
                `;
            }
        });
        
        return html || '<span class="text-muted">No individual scores available</span>';
    }

    function formatVerifiedData(data) {
        if (!data) return '<span class="text-muted">No verified data available</span>';
        
        let html = '';
        Object.keys(data).forEach(key => {
            if (data[key]) {
                html += `
                    <div class="mb-1">
                        <strong>${formatStatus(key)}:</strong> ${data[key]}
                    </div>
                `;
            }
        });
        
        return html || '<span class="text-muted">No verified data available</span>';
    }

    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('#verificationResults').html(alertHtml).removeClass('d-none');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }

    // Initialize service status check on page load
    checkServiceStatus();
});
</script>
@endpush