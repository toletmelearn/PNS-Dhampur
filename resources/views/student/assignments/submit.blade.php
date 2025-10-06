@extends('layouts.app')

@section('title', 'Submit Assignment - ' . $assignment->title)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student.assignments') }}">Assignments</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student.assignments.show', $assignment) }}">{{ Str::limit($assignment->title, 20) }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Submit</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Submit Assignment</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Assignment
            </a>
            <button type="button" class="btn btn-outline-info" onclick="previewSubmission()">
                <i class="fas fa-eye me-1"></i>Preview
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Submission Form -->
        <div class="col-lg-8">
            <form id="submissionForm" action="{{ route('student.assignments.store-submission', $assignment) }}" 
                  method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Assignment Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">{{ $assignment->title }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subject:</span>
                                    <span class="fw-bold">{{ $assignment->subject->name ?? 'N/A' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Type:</span>
                                    <span class="badge bg-secondary">{{ ucfirst($assignment->type) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Marks:</span>
                                    <span class="fw-bold text-primary">{{ $assignment->total_marks ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Due Date:</span>
                                    <span class="fw-bold text-{{ $assignment->isOverdue() ? 'danger' : 'dark' }}">
                                        @if($assignment->due_date)
                                            {{ $assignment->due_date->format('M d, Y') }}
                                            @if($assignment->due_time)
                                                {{ $assignment->due_time->format('g:i A') }}
                                            @endif
                                        @else
                                            No due date
                                        @endif
                                    </span>
                                </div>
                                @if($assignment->isOverdue())
                                    <div class="alert alert-warning py-2 mb-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <small>This assignment is overdue. 
                                        @if($assignment->late_penalty > 0)
                                            Late penalty: {{ $assignment->late_penalty }}% per day.
                                        @endif
                                        </small>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Submission Type:</span>
                                    <span class="fw-bold">{{ ucfirst($assignment->submission_type ?? 'online') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submission Content -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Your Submission</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoSave" checked>
                            <label class="form-check-label" for="autoSave">
                                Auto-save
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Text Submission -->
                        <div class="form-group mb-4">
                            <label for="content" class="form-label">
                                Submission Content <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content" rows="10" 
                                      placeholder="Enter your assignment submission here..."
                                      required>{{ old('content', $submission->content ?? '') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <span id="contentCount">0</span> characters
                                @if($assignment->min_words)
                                    | Minimum {{ $assignment->min_words }} words required
                                @endif
                                @if($assignment->max_words)
                                    | Maximum {{ $assignment->max_words }} words allowed
                                @endif
                            </div>
                        </div>

                        <!-- File Attachments -->
                        <div class="form-group mb-4">
                            <label for="attachments" class="form-label">
                                File Attachments
                                @if($assignment->submission_type == 'file')
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <div class="drop-zone" id="assignment-drop-zone" 
                                 data-max-size="{{ config('fileupload.max_file_sizes.assignment') }}">
                                <input type="file" class="form-control @error('attachments') is-invalid @enderror" 
                                       id="attachments" name="attachments[]" multiple 
                                       accept="{{ config('fileupload.allowed_file_types.assignment.accept') }}"
                                       {{ $assignment->submission_type == 'file' ? 'required' : '' }}>
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <p class="mb-1">Click to select files or drag and drop</p>
                                    <small class="text-muted">
                                        Supported formats: {{ config('fileupload.allowed_file_types.assignment.display') }}
                                        | Max size: {{ number_format(config('fileupload.max_file_sizes.assignment') / (1024 * 1024), 1) }}MB per file
                                    </small>
                                </div>
                            </div>
                            @error('attachments')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            
                            <!-- File Preview -->
                            <div id="assignment-preview" class="mt-3"></div>
                            
                            <!-- Existing Files (for editing) -->
                            @if(isset($submission) && $submission->attachments && count($submission->attachments) > 0)
                                <div class="mt-3">
                                    <label class="form-label">Current Files:</label>
                                    <div id="existingFiles">
                                        @foreach($submission->attachments as $index => $attachment)
                                            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file me-2 text-muted"></i>
                                                    <span>{{ basename($attachment) }}</span>
                                                    <small class="text-muted ms-2">
                                                        ({{ number_format(Storage::size($attachment) / 1024, 1) }} KB)
                                                    </small>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('student.submissions.download', [$submission, 'file' => $attachment]) }}" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="removeExistingFile('{{ $attachment }}', this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="keep_files[]" value="{{ $attachment }}" id="keep_{{ $index }}">
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Additional Notes -->
                        <div class="form-group mb-4">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional comments or notes for your teacher...">{{ old('notes', $submission->notes ?? '') }}</textarea>
                            <div class="form-text">
                                Use this space to explain your approach, challenges faced, or any other relevant information.
                            </div>
                        </div>

                        <!-- Submission Options -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirmOriginal" name="confirm_original" required>
                                    <label class="form-check-label" for="confirmOriginal">
                                        <span class="text-danger">*</span> I confirm this is my original work
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="allowFeedback" name="allow_feedback" checked>
                                    <label class="form-check-label" for="allowFeedback">
                                        Allow teacher to provide feedback
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                    <i class="fas fa-save me-1"></i>Save as Draft
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="previewSubmission()">
                                    <i class="fas fa-eye me-1"></i>Preview
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-success" id="submitBtn">
                                    <i class="fas fa-paper-plane me-1"></i>Submit Assignment
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div id="saveStatus" class="text-muted small"></div>
                            <div class="progress mt-2" style="height: 3px; display: none;" id="uploadProgress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Assignment Instructions -->
            @if($assignment->instructions)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Instructions</h6>
                    </div>
                    <div class="card-body">
                        <div class="instructions-content">
                            {!! nl2br(e($assignment->instructions)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Assignment Resources -->
            @if($assignment->attachments && count($assignment->attachments) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Resources</h6>
                    </div>
                    <div class="card-body">
                        @foreach($assignment->attachments as $attachment)
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-file me-2 text-muted"></i>
                                <a href="{{ route('student.assignments.download', [$assignment, 'file' => $attachment]) }}" 
                                   class="text-decoration-none" target="_blank">
                                    {{ basename($attachment) }}
                                </a>
                                <small class="text-muted ms-2">
                                    ({{ number_format(Storage::size($attachment) / 1024, 1) }} KB)
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Submission Guidelines -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Submission Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Review your work before submitting
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Ensure all required fields are filled
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Check file formats and sizes
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Save drafts regularly
                        </li>
                        @if($assignment->allow_resubmission)
                            <li class="mb-2">
                                <i class="fas fa-info text-info me-2"></i>
                                Resubmission is allowed
                            </li>
                        @endif
                        @if($assignment->plagiarism_check)
                            <li class="mb-2">
                                <i class="fas fa-shield-alt text-warning me-2"></i>
                                Plagiarism check enabled
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Need Help?</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="fas fa-question-circle me-1"></i>Submission Help
                        </a>
                        <a href="mailto:{{ $assignment->teacher->email ?? 'support@school.com' }}" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-envelope me-1"></i>Contact Teacher
                        </a>
                        <a href="#" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-life-ring me-1"></i>Technical Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Submission Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="submitFromPreview()">Submit Assignment</button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Submission Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>How to submit your assignment:</h6>
                <ol>
                    <li>Fill in your submission content in the text area</li>
                    <li>Upload any required files (if applicable)</li>
                    <li>Add any additional notes</li>
                    <li>Confirm it's your original work</li>
                    <li>Click "Submit Assignment"</li>
                </ol>
                
                <h6 class="mt-3">Tips:</h6>
                <ul>
                    <li>Save your work as draft frequently</li>
                    <li>Preview your submission before submitting</li>
                    <li>Check file size limits</li>
                    <li>Ensure all required fields are filled</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('css/file-upload-enhanced.css') }}" rel="stylesheet">
<style>
.file-upload-area {
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa;
}

.file-upload-area.dragover {
    border-color: #007bff !important;
    background-color: #e3f2fd;
}

.instructions-content {
    max-height: 200px;
    overflow-y: auto;
}

.file-item {
    transition: all 0.3s ease;
}

.file-item:hover {
    background-color: #f8f9fa;
}

#contentCount {
    font-weight: bold;
}

.progress {
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.gap-2 {
        justify-content: center;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/file-upload-enhanced.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize enhanced file upload for assignment submissions
    const assignmentUploader = new EnhancedFileUpload({
        maxFileSize: {{ config('fileupload.max_file_sizes.assignment') }},
        allowedTypes: {!! json_encode(explode(',', config('fileupload.allowed_file_types.assignment.extensions'))) !!},
        dropZone: '#assignment-drop-zone',
        fileInput: '#attachments',
        previewContainer: '#assignment-preview',
        autoUpload: false,
        multiple: true
    });

    // Character count
    updateCharacterCount();
    $('#content').on('input', updateCharacterCount);
    
    // Auto-save functionality
    setupAutoSave();
    
    // Form validation
    setupFormValidation();
});

function updateCharacterCount() {
    const content = $('#content').val();
    const count = content.length;
    $('#contentCount').text(count.toLocaleString());
    
    // Word count validation
    const words = content.trim().split(/\s+/).filter(word => word.length > 0).length;
    @if($assignment->min_words || $assignment->max_words)
        let valid = true;
        @if($assignment->min_words)
            if (words < {{ $assignment->min_words }}) {
                valid = false;
                $('#contentCount').addClass('text-danger').removeClass('text-success');
            }
        @endif
        @if($assignment->max_words)
            if (words > {{ $assignment->max_words }}) {
                valid = false;
                $('#contentCount').addClass('text-danger').removeClass('text-success');
            }
        @endif
        if (valid) {
            $('#contentCount').addClass('text-success').removeClass('text-danger');
        }
    @endif
}

function removeExistingFile(filename, button) {
    $(button).closest('.d-flex').remove();
    $(`input[value="${filename}"]`).remove();
}

function setupAutoSave() {
    let autoSaveInterval;
    
    $('#autoSave').on('change', function() {
        if (this.checked) {
            autoSaveInterval = setInterval(saveDraft, 30000); // Auto-save every 30 seconds
        } else {
            clearInterval(autoSaveInterval);
        }
    });
    
    // Start auto-save by default
    if ($('#autoSave').is(':checked')) {
        autoSaveInterval = setInterval(saveDraft, 30000);
    }
}

function saveDraft() {
    const formData = new FormData($('#submissionForm')[0]);
    formData.append('is_draft', '1');
    
    $.ajax({
        url: '{{ route("student.assignments.save-draft", $assignment) }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#saveStatus').text('Draft saved at ' + new Date().toLocaleTimeString()).addClass('text-success');
            setTimeout(() => $('#saveStatus').removeClass('text-success'), 3000);
        },
        error: function() {
            $('#saveStatus').text('Failed to save draft').addClass('text-danger');
            setTimeout(() => $('#saveStatus').removeClass('text-danger'), 3000);
        }
    });
}

function setupFormValidation() {
    $('#submissionForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return false;
        }
        
        // Show confirmation dialog
        if (confirm('Are you sure you want to submit this assignment? You cannot modify it after submission.')) {
            submitForm();
        }
    });
}

function validateForm() {
    let valid = true;
    
    // Check required fields
    if (!$('#content').val().trim()) {
        showAlert('error', 'Submission content is required.');
        valid = false;
    }
    
    // Check file requirement
    @if($assignment->submission_type == 'file')
        const hasFiles = $('#attachments')[0].files.length > 0 || $('#existingFiles .d-flex').length > 0;
        if (!hasFiles) {
            showAlert('error', 'File attachment is required for this assignment.');
            valid = false;
        }
    @endif
    
    // Check confirmation
    if (!$('#confirmOriginal').is(':checked')) {
        showAlert('error', 'You must confirm this is your original work.');
        valid = false;
    }
    
    return valid;
}

function submitForm() {
    const formData = new FormData($('#submissionForm')[0]);
    const submitBtn = $('#submitBtn');
    const progressBar = $('#uploadProgress');
    
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Submitting...');
    progressBar.show();
    
    $.ajax({
        url: $('#submissionForm').attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    progressBar.find('.progress-bar').css('width', percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            showAlert('success', 'Assignment submitted successfully!');
            setTimeout(() => {
                window.location.href = '{{ route("student.assignments.show", $assignment) }}';
            }, 2000);
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors || {};
            let errorMessage = 'Failed to submit assignment. ';
            
            if (Object.keys(errors).length > 0) {
                errorMessage += Object.values(errors).flat().join(' ');
            }
            
            showAlert('error', errorMessage);
            submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Submit Assignment');
            progressBar.hide();
        }
    });
}

function previewSubmission() {
    const content = $('#content').val();
    const notes = $('#notes').val();
    const files = $('#attachments')[0].files;
    
    let previewHtml = `
        <div class="mb-3">
            <h6>Assignment: {{ $assignment->title }}</h6>
            <p class="text-muted">{{ $assignment->subject->name ?? 'N/A' }} - {{ ucfirst($assignment->type) }}</p>
        </div>
        <div class="mb-3">
            <h6>Submission Content:</h6>
            <div class="p-3 bg-light rounded">${content.replace(/\n/g, '<br>')}</div>
        </div>
    `;
    
    if (notes) {
        previewHtml += `
            <div class="mb-3">
                <h6>Additional Notes:</h6>
                <div class="p-3 bg-light rounded">${notes.replace(/\n/g, '<br>')}</div>
            </div>
        `;
    }
    
    if (files.length > 0) {
        previewHtml += '<div class="mb-3"><h6>Attached Files:</h6><ul>';
        Array.from(files).forEach(file => {
            previewHtml += `<li>${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</li>`;
        });
        previewHtml += '</ul></div>';
    }
    
    $('#previewContent').html(previewHtml);
    $('#previewModal').modal('show');
}

function submitFromPreview() {
    $('#previewModal').modal('hide');
    $('#submissionForm').submit();
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

// Prevent accidental page leave
window.addEventListener('beforeunload', function(e) {
    const content = $('#content').val();
    if (content.trim() && !window.submissionCompleted) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
@endpush