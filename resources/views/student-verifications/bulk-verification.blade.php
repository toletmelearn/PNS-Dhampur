@extends('layouts.app')

@section('title', 'Bulk Student Verification')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-users-cog mr-2"></i>
                        Bulk Student Verification
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info btn-sm" id="statsBtn">
                            <i class="fas fa-chart-bar"></i> Statistics
                        </button>
                        <a href="{{ route('student-verifications.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Verifications
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4" id="statsCards" style="display: none;">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Students</span>
                                    <span class="info-box-number" id="totalStudents">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Verified</span>
                                    <span class="info-box-number" id="verifiedStudents">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pending</span>
                                    <span class="info-box-number" id="pendingVerifications">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Failed</span>
                                    <span class="info-box-number" id="failedVerifications">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Verification Form -->
                    <form id="bulkVerificationForm">
                        @csrf
                        <div class="row">
                            <!-- Student Selection -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-user-check mr-2"></i>
                                            Select Students
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label>Students to Verify:</label>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" id="selectAllBtn">
                                                        <i class="fas fa-check-square"></i> Select All
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" id="clearAllBtn">
                                                        <i class="fas fa-square"></i> Clear All
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Filter Options -->
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <select class="form-control form-control-sm" id="classFilter">
                                                        <option value="">All Classes</option>
                                                        @foreach($students->unique('class')->sortBy('class') as $student)
                                                            <option value="{{ $student->class }}">Class {{ $student->class }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <select class="form-control form-control-sm" id="sectionFilter">
                                                        <option value="">All Sections</option>
                                                        @foreach($students->unique('section')->sortBy('section') as $student)
                                                            <option value="{{ $student->section }}">Section {{ $student->section }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="student-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                                @foreach($students as $student)
                                                    <div class="form-check student-item" data-class="{{ $student->class }}" data-section="{{ $student->section }}">
                                                        <input class="form-check-input student-checkbox" type="checkbox" 
                                                               value="{{ $student->id }}" id="student_{{ $student->id }}" name="student_ids[]">
                                                        <label class="form-check-label" for="student_{{ $student->id }}">
                                                            <strong>{{ $student->name }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                {{ $student->admission_number }} | 
                                                                Class {{ $student->class }}-{{ $student->section }} |
                                                                Father: {{ $student->father_name }}
                                                            </small>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            
                                            <div class="mt-2">
                                                <small class="text-info">
                                                    <span id="selectedCount">0</span> students selected
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Verification Options -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-cogs mr-2"></i>
                                            Verification Options
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Verification Types -->
                                        <div class="form-group">
                                            <label>Verification Types:</label>
                                            @foreach($verificationTypes as $type => $details)
                                                <div class="form-check">
                                                    <input class="form-check-input verification-type" type="checkbox" 
                                                           value="{{ $type }}" id="type_{{ $type }}" name="verification_types[]">
                                                    <label class="form-check-label" for="type_{{ $type }}">
                                                        <strong>{{ $details['name'] }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $details['description'] }}</small>
                                                        <br>
                                                        <small class="text-info">{{ $details['estimated_time'] }}</small>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Processing Options -->
                                        <div class="form-group">
                                            <label>Processing Options:</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="batchSize" class="form-label">Batch Size:</label>
                                                    <select class="form-control" id="batchSize" name="batch_size">
                                                        <option value="5">5 students</option>
                                                        <option value="10" selected>10 students</option>
                                                        <option value="20">20 students</option>
                                                        <option value="50">50 students</option>
                                                    </select>
                                                    <small class="text-muted">Smaller batches are more reliable</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="maxRetries" class="form-label">Max Retries:</label>
                                                    <select class="form-control" id="maxRetries" name="max_retries">
                                                        <option value="1">1 retry</option>
                                                        <option value="2">2 retries</option>
                                                        <option value="3" selected>3 retries</option>
                                                        <option value="5">5 retries</option>
                                                    </select>
                                                    <small class="text-muted">Retries for failed verifications</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-block" id="startVerificationBtn">
                                                <i class="fas fa-play"></i> Start Bulk Verification
                                            </button>
                                            <button type="button" class="btn btn-danger btn-block" id="cancelVerificationBtn" style="display: none;">
                                                <i class="fas fa-stop"></i> Cancel Verification
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Progress Section -->
                    <div class="row mt-4" id="progressSection" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tasks mr-2"></i>
                                        Verification Progress
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="progress mb-3">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" id="progressBar" style="width: 0%">
                                            <span id="progressText">0%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="info-box bg-info">
                                                <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Processing</span>
                                                    <span class="info-box-number" id="processingCount">0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box bg-success">
                                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Successful</span>
                                                    <span class="info-box-number" id="successCount">0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box bg-danger">
                                                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Failed</span>
                                                    <span class="info-box-number" id="failedCount">0</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="info-box bg-secondary">
                                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Elapsed</span>
                                                    <span class="info-box-number" id="elapsedTime">0s</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="row mt-4" id="resultsSection" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line mr-2"></i>
                                        Verification Results
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Summary -->
                                    <div class="alert alert-info" id="resultsSummary"></div>
                                    
                                    <!-- Detailed Results -->
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="resultsTable">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Admission No.</th>
                                                    <th>Verification Types</th>
                                                    <th>Overall Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="resultsTableBody">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Verification Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="studentDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let verificationInProgress = false;
    let verificationSessionId = null;
    let progressInterval = null;
    let startTime = null;

    // Student selection functionality
    $('#selectAllBtn').click(function() {
        $('.student-checkbox:visible').prop('checked', true);
        updateSelectedCount();
    });

    $('#clearAllBtn').click(function() {
        $('.student-checkbox').prop('checked', false);
        updateSelectedCount();
    });

    $('.student-checkbox').change(function() {
        updateSelectedCount();
    });

    // Filter functionality
    $('#classFilter, #sectionFilter').change(function() {
        filterStudents();
    });

    function filterStudents() {
        const selectedClass = $('#classFilter').val();
        const selectedSection = $('#sectionFilter').val();

        $('.student-item').each(function() {
            const studentClass = $(this).data('class');
            const studentSection = $(this).data('section');
            
            let show = true;
            
            if (selectedClass && studentClass != selectedClass) {
                show = false;
            }
            
            if (selectedSection && studentSection != selectedSection) {
                show = false;
            }
            
            $(this).toggle(show);
        });
        
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const count = $('.student-checkbox:checked').length;
        $('#selectedCount').text(count);
    }

    // Statistics functionality
    $('#statsBtn').click(function() {
        if ($('#statsCards').is(':visible')) {
            $('#statsCards').hide();
            $(this).html('<i class="fas fa-chart-bar"></i> Statistics');
        } else {
            loadStatistics();
            $('#statsCards').show();
            $(this).html('<i class="fas fa-eye-slash"></i> Hide Statistics');
        }
    });

    function loadStatistics() {
        $.get('{{ route("student-verifications.bulk-verification.stats") }}')
            .done(function(response) {
                if (response.success) {
                    $('#totalStudents').text(response.data.total_students);
                    $('#verifiedStudents').text(response.data.verified_students);
                    $('#pendingVerifications').text(response.data.pending_verifications);
                    $('#failedVerifications').text(response.data.failed_verifications);
                }
            })
            .fail(function() {
                showAlert('Failed to load statistics', 'danger');
            });
    }

    // Form submission
    $('#bulkVerificationForm').submit(function(e) {
        e.preventDefault();
        
        if (verificationInProgress) {
            return;
        }

        const selectedStudents = $('.student-checkbox:checked').length;
        const selectedTypes = $('.verification-type:checked').length;

        if (selectedStudents === 0) {
            showAlert('Please select at least one student', 'warning');
            return;
        }

        if (selectedTypes === 0) {
            showAlert('Please select at least one verification type', 'warning');
            return;
        }

        if (!confirm(`Start bulk verification for ${selectedStudents} students with ${selectedTypes} verification type(s)?`)) {
            return;
        }

        startBulkVerification();
    });

    function startBulkVerification() {
        verificationInProgress = true;
        startTime = new Date();
        
        $('#startVerificationBtn').hide();
        $('#cancelVerificationBtn').show();
        $('#progressSection').show();
        $('#resultsSection').hide();

        const formData = new FormData($('#bulkVerificationForm')[0]);

        $.ajax({
            url: '{{ route("student-verifications.bulk-verification.process") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    verificationSessionId = response.data.session_id || 'bulk_' + Date.now();
                    showVerificationResults(response.data);
                } else {
                    showAlert('Verification failed: ' + response.message, 'danger');
                    resetVerificationState();
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('Verification failed: ' + (response?.message || 'Unknown error'), 'danger');
                resetVerificationState();
            }
        });

        // Start progress tracking
        startProgressTracking();
    }

    function startProgressTracking() {
        let elapsedSeconds = 0;
        
        progressInterval = setInterval(function() {
            elapsedSeconds++;
            $('#elapsedTime').text(elapsedSeconds + 's');
            
            // Simulate progress updates (in real implementation, this would poll the server)
            if (verificationInProgress && elapsedSeconds < 60) {
                const progress = Math.min(95, (elapsedSeconds / 60) * 100);
                updateProgress(progress);
            }
        }, 1000);
    }

    function updateProgress(percentage) {
        $('#progressBar').css('width', percentage + '%');
        $('#progressText').text(Math.round(percentage) + '%');
    }

    function showVerificationResults(results) {
        verificationInProgress = false;
        clearInterval(progressInterval);
        
        $('#progressBar').css('width', '100%');
        $('#progressText').text('100%');
        $('#cancelVerificationBtn').hide();
        $('#startVerificationBtn').show();
        $('#resultsSection').show();

        // Update counters
        $('#processingCount').text(results.processed || 0);
        $('#successCount').text(results.successful || 0);
        $('#failedCount').text(results.failed || 0);

        // Show summary
        const summary = results.summary || {};
        const summaryHtml = `
            <h6>Verification Summary</h6>
            <p><strong>Total Students:</strong> ${results.total_students || 0}</p>
            <p><strong>Processed:</strong> ${results.processed || 0}</p>
            <p><strong>Successful:</strong> ${results.successful || 0} (${summary.overview?.success_rate || 0}%)</p>
            <p><strong>Failed:</strong> ${results.failed || 0}</p>
            <p><strong>Processing Time:</strong> ${results.processing_time || 0} seconds</p>
        `;
        $('#resultsSummary').html(summaryHtml);

        // Populate results table
        const tbody = $('#resultsTableBody');
        tbody.empty();

        if (results.results && results.results.length > 0) {
            results.results.forEach(function(studentResult) {
                const verificationTypes = Object.keys(studentResult.verifications || {}).join(', ');
                const statusClass = studentResult.overall_status === 'success' ? 'success' : 'danger';
                
                const row = `
                    <tr>
                        <td>${studentResult.student_name || 'Unknown'}</td>
                        <td>${studentResult.admission_number || 'N/A'}</td>
                        <td>${verificationTypes}</td>
                        <td><span class="badge badge-${statusClass}">${studentResult.overall_status || 'Unknown'}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info view-details" data-student-id="${studentResult.student_id}">
                                <i class="fas fa-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            tbody.append('<tr><td colspan="5" class="text-center">No results available</td></tr>');
        }
    }

    // Cancel verification
    $('#cancelVerificationBtn').click(function() {
        if (confirm('Are you sure you want to cancel the bulk verification?')) {
            cancelVerification();
        }
    });

    function cancelVerification() {
        if (verificationSessionId) {
            $.post('{{ route("student-verifications.bulk-verification.cancel") }}', {
                _token: '{{ csrf_token() }}',
                session_id: verificationSessionId
            });
        }
        
        resetVerificationState();
        showAlert('Verification cancelled', 'info');
    }

    function resetVerificationState() {
        verificationInProgress = false;
        verificationSessionId = null;
        
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
        
        $('#startVerificationBtn').show();
        $('#cancelVerificationBtn').hide();
        $('#progressSection').hide();
        
        updateProgress(0);
        $('#processingCount, #successCount, #failedCount').text('0');
        $('#elapsedTime').text('0s');
    }

    // View student details
    $(document).on('click', '.view-details', function() {
        const studentId = $(this).data('student-id');
        // In a real implementation, this would load detailed verification results
        $('#studentDetailsContent').html(`
            <p>Detailed verification results for student ID: ${studentId}</p>
            <p>This would show individual verification results, confidence scores, and any errors.</p>
        `);
        $('#studentDetailsModal').modal('show');
    });

    function showAlert(message, type) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of the card body
        $('.card-body').first().prepend(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endsection