@extends('layouts.app')

@section('title', 'Upload Document - Teacher Document Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Upload Document</h1>
                    <p class="text-muted">Upload your professional documents and certificates</p>
                </div>
                <a href="{{ route('teacher-documents.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Documents
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Document Upload Form</h5>
                        </div>
                        <div class="card-body">
                            <form id="documentUploadForm" action="{{ route('teacher-documents.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <!-- Document Type Selection -->
                                <div class="mb-4">
                                    <label for="document_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('document_type') is-invalid @enderror" 
                                            id="document_type" 
                                            name="document_type" 
                                            required>
                                        <option value="">Select document type...</option>
                                        <option value="resume" {{ old('document_type') == 'resume' ? 'selected' : '' }}>Resume/CV</option>
                                        <option value="certificate" {{ old('document_type') == 'certificate' ? 'selected' : '' }}>Certificate</option>
                                        <option value="degree" {{ old('document_type') == 'degree' ? 'selected' : '' }}>Degree</option>
                                        <option value="id_proof" {{ old('document_type') == 'id_proof' ? 'selected' : '' }}>ID Proof</option>
                                        <option value="experience_letter" {{ old('document_type') == 'experience_letter' ? 'selected' : '' }}>Experience Letter</option>
                                    </select>
                                    @error('document_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- File Upload Area -->
                                <div class="mb-4">
                                    <label for="document_file" class="form-label">Document File <span class="text-danger">*</span></label>
                                    <div class="file-upload-area" id="fileUploadArea">
                                        <div class="file-upload-content">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                            <h5>Drag and drop your file here</h5>
                                            <p class="text-muted mb-3">or click to browse files</p>
                                            <input type="file" 
                                                   class="form-control d-none @error('document_file') is-invalid @enderror" 
                                                   id="document_file" 
                                                   name="document_file" 
                                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                   required>
                                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('document_file').click()">
                                                Choose File
                                            </button>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Supported formats: PDF, DOC, DOCX, JPG, JPEG, PNG<br>
                                                    Maximum file size: 5MB
                                                </small>
                                            </div>
                                        </div>
                                        <div class="file-preview d-none" id="filePreview">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-alt fa-2x text-primary me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-medium" id="fileName"></div>
                                                    <div class="text-muted" id="fileSize"></div>
                                                    <div class="progress mt-2 d-none" id="uploadProgress">
                                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @error('document_file')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Expiry Date (Optional) -->
                                <div class="mb-4">
                                    <label for="expiry_date" class="form-label">Expiry Date (Optional)</label>
                                    <input type="date" 
                                           class="form-control @error('expiry_date') is-invalid @enderror" 
                                           id="expiry_date" 
                                           name="expiry_date" 
                                           value="{{ old('expiry_date') }}"
                                           min="{{ date('Y-m-d') }}">
                                    <div class="form-text">Leave blank if the document doesn't expire</div>
                                    @error('expiry_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-upload me-2"></i>Upload Document
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Upload Guidelines -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Upload Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold">Document Types:</h6>
                            <ul class="list-unstyled mb-3">
                                <li><i class="fas fa-check text-success me-2"></i>Resume/CV</li>
                                <li><i class="fas fa-check text-success me-2"></i>Educational Certificates</li>
                                <li><i class="fas fa-check text-success me-2"></i>Degree Certificates</li>
                                <li><i class="fas fa-check text-success me-2"></i>ID Proof (Aadhaar, PAN, etc.)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Experience Letters</li>
                            </ul>

                            <h6 class="fw-bold">File Requirements:</h6>
                            <ul class="list-unstyled mb-3">
                                <li><i class="fas fa-check text-success me-2"></i>Formats: PDF, DOC, DOCX, JPG, JPEG, PNG</li>
                                <li><i class="fas fa-check text-success me-2"></i>Maximum size: 5MB</li>
                                <li><i class="fas fa-check text-success me-2"></i>Clear and readable quality</li>
                            </ul>

                            <h6 class="fw-bold">Approval Process:</h6>
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-clock text-warning me-2"></i>Documents are reviewed by admin</li>
                                <li><i class="fas fa-check text-success me-2"></i>Verified documents are marked as approved</li>
                                <li><i class="fas fa-times text-danger me-2"></i>Rejected documents can be re-uploaded</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bell me-2"></i>Important Notes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Tip:</strong> Ensure your documents are clear and all text is readable. 
                                Poor quality documents may be rejected and require re-upload.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.file-upload-area.dragover {
    border-color: #0d6efd;
    background-color: #e7f3ff;
}

.file-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('document_file');
    const filePreview = document.getElementById('filePreview');
    const fileUploadContent = document.querySelector('.file-upload-content');
    
    // Drag and drop functionality
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUploadArea.classList.add('dragover');
    });
    
    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
    });
    
    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(files[0]);
        }
    });
    
    // Click to upload
    fileUploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // File input change
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });
    
    function handleFileSelect(file) {
        // Validate file size
        if (file.size > 5 * 1024 * 1024) { // 5MB
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select a file smaller than 5MB.'
            });
            return;
        }
        
        // Validate file type
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select a PDF, DOC, DOCX, JPG, JPEG, or PNG file.'
            });
            return;
        }
        
        // Show file preview
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        
        fileUploadContent.classList.add('d-none');
        filePreview.classList.remove('d-none');
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Form submission with progress
    $('#documentUploadForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const progressBar = document.getElementById('uploadProgress');
        
        // Show progress bar
        progressBar.classList.remove('d-none');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
        
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
                    title: 'Success!',
                    text: 'Document uploaded successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route("teacher-documents.index") }}';
                });
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while uploading the document.';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: errorMessage
                });
                
                // Reset form
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Document';
                progressBar.classList.add('d-none');
                $('.progress-bar').css('width', '0%');
            }
        });
    });
});

function removeFile() {
    document.getElementById('document_file').value = '';
    document.querySelector('.file-upload-content').classList.remove('d-none');
    document.getElementById('filePreview').classList.add('d-none');
}
</script>
@endpush