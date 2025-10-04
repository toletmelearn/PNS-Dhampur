@extends('layouts.app')

@section('title', 'Mismatch Resolution - Student Verification')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Mismatch Resolution</h1>
            <p class="text-muted">Review and resolve data mismatches for student verification</p>
        </div>
        <div>
            <a href="{{ route('student-verifications.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Verifications
            </a>
        </div>
    </div>

    <!-- Verification Details Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-user"></i> Student: {{ $verification->student->name }}
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Student ID:</strong> {{ $verification->student->student_id }}</p>
                    <p><strong>Class:</strong> {{ $verification->student->class }} - {{ $verification->student->section }}</p>
                    <p><strong>Verification Type:</strong> 
                        <span class="badge badge-info">{{ ucfirst($verification->verification_type) }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge badge-{{ $verification->status === 'verified' ? 'success' : ($verification->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($verification->status) }}
                        </span>
                    </p>
                    <p><strong>Confidence Score:</strong> 
                        <span class="badge badge-{{ $verification->confidence_score >= 80 ? 'success' : ($verification->confidence_score >= 60 ? 'warning' : 'danger') }}">
                            {{ $verification->confidence_score }}%
                        </span>
                    </p>
                    <p><strong>Last Updated:</strong> {{ $verification->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mismatch Analysis Results -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-exclamation-triangle text-warning"></i> Detected Mismatches
            </h5>
            <div>
                <button type="button" class="btn btn-sm btn-primary" onclick="analyzeAllMismatches()">
                    <i class="fas fa-sync"></i> Re-analyze
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="applyAutomaticResolution()">
                    <i class="fas fa-magic"></i> Auto-resolve
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="mismatch-results">
                <!-- Mismatch results will be loaded here -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Analyzing mismatches...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Resolution Actions -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-tools"></i> Manual Resolution Actions
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <button type="button" class="btn btn-success btn-block" onclick="approveVerification()">
                        <i class="fas fa-check"></i> Approve Verification
                    </button>
                    <small class="text-muted">Accept the verification despite mismatches</small>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger btn-block" onclick="rejectVerification()">
                        <i class="fas fa-times"></i> Reject Verification
                    </button>
                    <small class="text-muted">Reject due to significant mismatches</small>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-warning btn-block" onclick="requestResubmission()">
                        <i class="fas fa-redo"></i> Request Resubmission
                    </button>
                    <small class="text-muted">Ask student to resubmit documents</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mismatch Detail Modal -->
<div class="modal fade" id="mismatchDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mismatch Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="mismatch-detail-content">
                    <!-- Detailed mismatch information will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="acceptSuggestion()">Accept Suggestion</button>
            </div>
        </div>
    </div>
</div>

<!-- Resolution Confirmation Modal -->
<div class="modal fade" id="resolutionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Resolution</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="resolution-message"></p>
                <div class="form-group">
                    <label for="resolution-notes">Resolution Notes (Optional):</label>
                    <textarea class="form-control" id="resolution-notes" rows="3" placeholder="Add any additional notes about this resolution..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmResolution()">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let verificationId = {{ $verification->id }};
let currentAction = null;
let mismatchData = null;

$(document).ready(function() {
    loadMismatchAnalysis();
});

function loadMismatchAnalysis() {
    $.ajax({
        url: `/student-verifications/${verificationId}/analyze-mismatches`,
        method: 'GET',
        success: function(response) {
            mismatchData = response;
            displayMismatchResults(response);
        },
        error: function(xhr) {
            showAlert('Error loading mismatch analysis: ' + xhr.responseJSON.message, 'danger');
            $('#mismatch-results').html('<div class="alert alert-danger">Failed to load mismatch analysis</div>');
        }
    });
}

function displayMismatchResults(data) {
    let html = '';
    
    if (data.mismatches.length === 0) {
        html = '<div class="alert alert-success"><i class="fas fa-check"></i> No significant mismatches detected</div>';
    } else {
        html += '<div class="row">';
        
        data.mismatches.forEach(function(mismatch, index) {
            let confidenceClass = mismatch.confidence >= 80 ? 'success' : (mismatch.confidence >= 60 ? 'warning' : 'danger');
            let severityClass = mismatch.severity === 'high' ? 'danger' : (mismatch.severity === 'medium' ? 'warning' : 'info');
            
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card border-${severityClass}">
                        <div class="card-header bg-${severityClass} text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i> ${mismatch.field.replace('_', ' ').toUpperCase()}
                                <span class="badge badge-light float-right">${mismatch.severity}</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Student Data:</small>
                                    <p class="mb-1"><strong>${mismatch.student_value || 'N/A'}</strong></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Document Data:</small>
                                    <p class="mb-1"><strong>${mismatch.document_value || 'N/A'}</strong></p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Confidence:</small>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-${confidenceClass}" style="width: ${mismatch.confidence}%">
                                        ${mismatch.confidence}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="showMismatchDetail(${index})">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Add overall recommendation
        let recommendationClass = data.recommendation === 'auto_approve' ? 'success' : 
                                 (data.recommendation === 'manual_review' ? 'warning' : 'danger');
        
        html += `
            <div class="alert alert-${recommendationClass} mt-3">
                <h6><i class="fas fa-lightbulb"></i> Recommendation: ${data.recommendation.replace('_', ' ').toUpperCase()}</h6>
                <p class="mb-0">${data.recommendation_reason}</p>
                <p class="mb-0"><strong>Overall Confidence:</strong> ${data.overall_confidence}%</p>
            </div>
        `;
    }
    
    $('#mismatch-results').html(html);
}

function showMismatchDetail(index) {
    if (!mismatchData || !mismatchData.mismatches[index]) return;
    
    let mismatch = mismatchData.mismatches[index];
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Student Information</h6>
                <p><strong>Value:</strong> ${mismatch.student_value || 'N/A'}</p>
                <p><strong>Source:</strong> Student Profile</p>
            </div>
            <div class="col-md-6">
                <h6>Document Information</h6>
                <p><strong>Value:</strong> ${mismatch.document_value || 'N/A'}</p>
                <p><strong>Source:</strong> ${mismatch.field.includes('aadhaar') ? 'Aadhaar Card' : 'Birth Certificate'}</p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-4">
                <h6>Analysis</h6>
                <p><strong>Similarity:</strong> ${mismatch.similarity_score}%</p>
                <p><strong>Confidence:</strong> ${mismatch.confidence}%</p>
                <p><strong>Severity:</strong> <span class="badge badge-${mismatch.severity === 'high' ? 'danger' : (mismatch.severity === 'medium' ? 'warning' : 'info')}">${mismatch.severity}</span></p>
            </div>
            <div class="col-md-8">
                <h6>Suggestions</h6>
                <ul>
                    ${mismatch.suggestions.map(suggestion => `<li>${suggestion}</li>`).join('')}
                </ul>
            </div>
        </div>
    `;
    
    $('#mismatch-detail-content').html(html);
    $('#mismatchDetailModal').modal('show');
}

function analyzeAllMismatches() {
    $('#mismatch-results').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Re-analyzing mismatches...</p>
        </div>
    `);
    loadMismatchAnalysis();
}

function applyAutomaticResolution() {
    if (!mismatchData) {
        showAlert('Please wait for mismatch analysis to complete', 'warning');
        return;
    }
    
    currentAction = 'auto_resolve';
    $('#resolution-message').text(`Apply automatic resolution based on AI analysis? This will ${mismatchData.recommendation.replace('_', ' ')} the verification.`);
    $('#resolutionModal').modal('show');
}

function approveVerification() {
    currentAction = 'approve';
    $('#resolution-message').text('Are you sure you want to approve this verification despite the mismatches?');
    $('#resolutionModal').modal('show');
}

function rejectVerification() {
    currentAction = 'reject';
    $('#resolution-message').text('Are you sure you want to reject this verification due to mismatches?');
    $('#resolutionModal').modal('show');
}

function requestResubmission() {
    currentAction = 'resubmit';
    $('#resolution-message').text('Request the student to resubmit their documents with correct information?');
    $('#resolutionModal').modal('show');
}

function confirmResolution() {
    let notes = $('#resolution-notes').val();
    let url, method, data;
    
    switch(currentAction) {
        case 'auto_resolve':
            url = `/student-verifications/${verificationId}/apply-resolution`;
            method = 'POST';
            data = { notes: notes };
            break;
        case 'approve':
            url = `/student-verifications/${verificationId}/approve`;
            method = 'PATCH';
            data = { notes: notes };
            break;
        case 'reject':
            url = `/student-verifications/${verificationId}/reject`;
            method = 'PATCH';
            data = { notes: notes };
            break;
        case 'resubmit':
            url = `/student-verifications/${verificationId}/reprocess`;
            method = 'POST';
            data = { notes: notes };
            break;
    }
    
    $.ajax({
        url: url,
        method: method,
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            showAlert(response.message || 'Resolution applied successfully', 'success');
            $('#resolutionModal').modal('hide');
            
            // Redirect back to verification list after a short delay
            setTimeout(function() {
                window.location.href = '{{ route("student-verifications.index") }}';
            }, 2000);
        },
        error: function(xhr) {
            showAlert('Error applying resolution: ' + xhr.responseJSON.message, 'danger');
        }
    });
}

function acceptSuggestion() {
    $('#mismatchDetailModal').modal('hide');
    applyAutomaticResolution();
}

function showAlert(message, type) {
    let alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection