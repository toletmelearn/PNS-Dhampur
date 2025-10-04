@extends('layouts.app')

@section('title', 'Bulk Document Upload')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Bulk Document Upload</h1>
                    <p class="text-muted">Upload multiple documents at once for faster processing</p>
                </div>
                <a href="{{ route('teacher-documents.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Documents
                </a>
            </div>

            <!-- Upload Instructions -->
            <div class="alert alert-info mb-4">
                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Upload Instructions</h5>
                <ul class="mb-0">
                    <li>You can upload up to 10 documents at once</li>
                    <li>Supported formats: PDF, DOC, DOCX, JPG, JPEG, PNG</li>
                    <li>Maximum file size: 10MB per document</li>
                    <li>Each document must have a document type assigned</li>
                    <li>Expiry dates are optional but recommended for certificates</li>
                </ul>
            </div>

            <!-- Bulk Upload Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Upload Documents
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('teacher-documents.bulk-upload.store') }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        @csrf
                        
                        <div id="documentContainer">
                            <!-- Initial document upload row -->
                            <div class="document-row border rounded p-3 mb-3" data-index="0">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Document #1</h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-document" style="display: none;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Document File <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" name="documents[]" 
                                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                        <div class="form-text">Max size: 10MB</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="document_types[]" required>
                                            <option value="">Select Type</option>
                                            <option value="resume">Resume/CV</option>
                                            <option value="certificate">Certificate</option>
                                            <option value="degree">Degree</option>
                                            <option value="id_proof">ID Proof</option>
                                            <option value="experience_letter">Experience Letter</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Expiry Date (Optional)</label>
                                        <input type="date" class="form-control" name="expiry_dates[]" 
                                               min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                        <div class="form-text">Leave blank if no expiry</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add More Button -->
                        <div class="text-center mb-4">
                            <button type="button" class="btn btn-outline-primary" id="addDocument">
                                <i class="fas fa-plus me-2"></i>Add Another Document
                            </button>
                            <div class="form-text mt-2">You can add up to 10 documents</div>
                        </div>

                        <!-- Upload Progress -->
                        <div id="uploadProgress" style="display: none;">
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="text-center">
                                <small class="text-muted">Uploading documents... Please wait.</small>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('teacher-documents.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload me-2"></i>Upload All Documents
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.document-row {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.document-row:hover {
    background-color: #e9ecef;
}

.remove-document {
    opacity: 0.7;
}

.remove-document:hover {
    opacity: 1;
}

.progress {
    height: 8px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let documentCount = 1;
    const maxDocuments = 10;

    // Add new document row
    $('#addDocument').on('click', function() {
        if (documentCount >= maxDocuments) {
            Swal.fire({
                icon: 'warning',
                title: 'Maximum Limit Reached',
                text: `You can only upload up to ${maxDocuments} documents at once.`
            });
            return;
        }

        documentCount++;
        const newRow = createDocumentRow(documentCount);
        $('#documentContainer').append(newRow);
        
        // Show remove buttons if more than one document
        if (documentCount > 1) {
            $('.remove-document').show();
        }
        
        // Hide add button if max reached
        if (documentCount >= maxDocuments) {
            $('#addDocument').hide();
        }
    });

    // Remove document row
    $(document).on('click', '.remove-document', function() {
        $(this).closest('.document-row').remove();
        documentCount--;
        
        // Update document numbers
        updateDocumentNumbers();
        
        // Hide remove buttons if only one document left
        if (documentCount <= 1) {
            $('.remove-document').hide();
        }
        
        // Show add button if below max
        if (documentCount < maxDocuments) {
            $('#addDocument').show();
        }
    });

    // Form submission with progress
    $('#bulkUploadForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show progress
        $('#uploadProgress').show();
        $('#submitBtn').prop('disabled', true);
        
        // Create FormData
        const formData = new FormData(this);
        
        // Submit with AJAX for progress tracking
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $('.progress-bar').css('width', percentComplete + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Upload Successful',
                    text: 'All documents have been uploaded successfully!',
                    confirmButtonText: 'View Documents'
                }).then((result) => {
                    window.location.href = '{{ route("teacher-documents.index") }}';
                });
            },
            error: function(xhr) {
                $('#uploadProgress').hide();
                $('#submitBtn').prop('disabled', false);
                
                let errorMessage = 'An error occurred during upload.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: errorMessage
                });
            }
        });
    });

    function createDocumentRow(index) {
        return `
            <div class="document-row border rounded p-3 mb-3" data-index="${index - 1}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Document #${index}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-document">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Document File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="documents[]" 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <div class="form-text">Max size: 10MB</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="document_types[]" required>
                            <option value="">Select Type</option>
                            <option value="resume">Resume/CV</option>
                            <option value="certificate">Certificate</option>
                            <option value="degree">Degree</option>
                            <option value="id_proof">ID Proof</option>
                            <option value="experience_letter">Experience Letter</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Expiry Date (Optional)</label>
                        <input type="date" class="form-control" name="expiry_dates[]" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        <div class="form-text">Leave blank if no expiry</div>
                    </div>
                </div>
            </div>
        `;
    }

    function updateDocumentNumbers() {
        $('.document-row').each(function(index) {
            $(this).find('h6').text(`Document #${index + 1}`);
        });
    }

    function validateForm() {
        let isValid = true;
        
        // Check if at least one document is selected
        const fileInputs = $('input[type="file"]');
        let hasFile = false;
        
        fileInputs.each(function() {
            if (this.files.length > 0) {
                hasFile = true;
                return false; // break
            }
        });
        
        if (!hasFile) {
            Swal.fire({
                icon: 'warning',
                title: 'No Files Selected',
                text: 'Please select at least one document to upload.'
            });
            return false;
        }
        
        // Validate file sizes
        fileInputs.each(function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'File Too Large',
                        text: `File "${file.name}" is larger than 10MB. Please choose a smaller file.`
                    });
                    isValid = false;
                    return false; // break
                }
            }
        });
        
        return isValid;
    }
});
</script>
@endpush