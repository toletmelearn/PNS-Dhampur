@extends('layouts.app')

@section('title', 'Upload Document for Verification')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Upload Document for Verification</h1>
            <p class="mb-0 text-muted">Upload your documents for automated verification</p>
        </div>
        <a href="{{ route('student-verifications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Verifications
        </a>
    </div>

    <div class="row">
        <!-- Upload Form -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Document Upload</h6>
                </div>
                <div class="card-body">
                    <form id="upload-form" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Document Type Selection -->
                        <div class="form-group">
                            <label for="document_type" class="form-label">
                                Document Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="document_type" name="document_type" required>
                                <option value="">Select Document Type</option>
                                <option value="birth_certificate">Birth Certificate</option>
                                <option value="aadhaar_card">Aadhaar Card</option>
                                <option value="school_leaving_certificate">School Leaving Certificate</option>
                                <option value="transfer_certificate">Transfer Certificate</option>
                                <option value="caste_certificate">Caste Certificate</option>
                                <option value="income_certificate">Income Certificate</option>
                                <option value="domicile_certificate">Domicile Certificate</option>
                                <option value="passport">Passport</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- File Upload Area -->
                        <div class="form-group">
                            <label class="form-label">
                                Document File <span class="text-danger">*</span>
                            </label>
                            <div class="upload-area" id="upload-area">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h5>Drag & Drop your document here</h5>
                                    <p class="text-muted">or <span class="text-primary">click to browse</span></p>
                                    <input type="file" id="document_file" name="document_file" 
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required hidden>
                                </div>
                                <div class="upload-preview" id="upload-preview" style="display: none;">
                                    <div class="preview-content">
                                        <div class="preview-icon">
                                            <i class="fas fa-file fa-2x"></i>
                                        </div>
                                        <div class="preview-info">
                                            <div class="file-name"></div>
                                            <div class="file-size text-muted"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger remove-file">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Supported formats: PDF, JPG, PNG, DOC, DOCX. Maximum size: 10MB
                            </small>
                        </div>

                        <!-- Upload Progress -->
                        <div class="upload-progress" id="upload-progress" style="display: none;">
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="progress-text text-center">
                                <small class="text-muted">Uploading... 0%</small>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                <i class="fas fa-upload"></i> Upload for Verification
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="resetForm()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Guidelines -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Upload Guidelines</h6>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">Document Requirements:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Clear, readable document image
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            All text should be visible
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            No blurred or damaged areas
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Original or certified copy
                        </li>
                    </ul>

                    <h6 class="text-primary mt-4">File Specifications:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-file-pdf text-danger mr-2"></i>
                            PDF files (preferred)
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-image text-info mr-2"></i>
                            JPG, PNG images
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-file-word text-primary mr-2"></i>
                            DOC, DOCX documents
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-weight-hanging text-warning mr-2"></i>
                            Maximum 10MB size
                        </li>
                    </ul>

                    <div class="alert alert-info mt-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle"></i> Verification Process
                        </h6>
                        <small>
                            Your document will be automatically processed using OCR technology. 
                            If automatic verification fails, it will be reviewed manually by our staff.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Recent Uploads -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Recent Uploads</h6>
                </div>
                <div class="card-body">
                    @if($recentUploads && $recentUploads->count() > 0)
                        @foreach($recentUploads as $upload)
                            <div class="d-flex align-items-center mb-3">
                                <div class="mr-3">
                                    @php
                                        $statusIcon = match($upload->verification_status) {
                                            'verified' => 'fas fa-check-circle text-success',
                                            'rejected' => 'fas fa-times-circle text-danger',
                                            'manual_review' => 'fas fa-eye text-warning',
                                            'processing' => 'fas fa-spinner fa-spin text-info',
                                            default => 'fas fa-clock text-secondary'
                                        };
                                    @endphp
                                    <i class="{{ $statusIcon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="font-weight-bold">{{ $upload->document_type_name }}</div>
                                    <small class="text-muted">{{ $upload->created_at->diffForHumans() }}</small>
                                </div>
                                <div>
                                    <a href="{{ route('student-verifications.show', $upload) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No recent uploads</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.upload-area {
    border: 2px dashed #d1d3e2;
    border-radius: 10px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #f8f9fc;
}

.upload-area:hover {
    border-color: #4e73df;
    background-color: #f1f3ff;
}

.upload-area.dragover {
    border-color: #4e73df;
    background-color: #e3f2fd;
    transform: scale(1.02);
}

.upload-preview {
    border: 2px solid #4e73df;
    border-radius: 10px;
    padding: 20px;
    background-color: #f1f3ff;
}

.preview-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.preview-icon {
    color: #4e73df;
}

.preview-info {
    flex-grow: 1;
}

.file-name {
    font-weight: bold;
    color: #5a5c69;
}

.upload-progress {
    margin-top: 20px;
}

.progress {
    height: 25px;
}

.btn-lg {
    padding: 12px 30px;
    font-size: 16px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const uploadArea = $('#upload-area');
    const fileInput = $('#document_file');
    const uploadPreview = $('#upload-preview');
    const uploadProgress = $('#upload-progress');
    const submitBtn = $('#submit-btn');

    // Click to browse
    uploadArea.on('click', function() {
        if (!uploadPreview.is(':visible')) {
            fileInput.click();
        }
    });

    // Drag and drop events
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    uploadArea.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    // File input change
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    // Remove file
    $(document).on('click', '.remove-file', function() {
        resetFileUpload();
    });

    function handleFileSelect(file) {
        // Validate file
        if (!validateFile(file)) {
            return;
        }

        // Show preview
        showFilePreview(file);
    }

    function validateFile(file) {
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 
                             'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (!allowedTypes.includes(file.type)) {
            Swal.fire('Error!', 'Please select a valid file type (PDF, JPG, PNG, DOC, DOCX)', 'error');
            return false;
        }

        if (file.size > maxSize) {
            Swal.fire('Error!', 'File size must be less than 10MB', 'error');
            return false;
        }

        return true;
    }

    function showFilePreview(file) {
        const fileName = file.name;
        const fileSize = formatFileSize(file.size);
        
        uploadPreview.find('.file-name').text(fileName);
        uploadPreview.find('.file-size').text(fileSize);
        
        // Update icon based on file type
        let iconClass = 'fas fa-file';
        if (file.type.includes('pdf')) {
            iconClass = 'fas fa-file-pdf text-danger';
        } else if (file.type.includes('image')) {
            iconClass = 'fas fa-file-image text-info';
        } else if (file.type.includes('word')) {
            iconClass = 'fas fa-file-word text-primary';
        }
        
        uploadPreview.find('.preview-icon i').attr('class', iconClass + ' fa-2x');
        
        uploadArea.find('.upload-content').hide();
        uploadPreview.show();
    }

    function resetFileUpload() {
        fileInput.val('');
        uploadPreview.hide();
        uploadArea.find('.upload-content').show();
        uploadProgress.hide();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form submission
    $('#upload-form').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        
        if (!fileInput[0].files.length) {
            Swal.fire('Error!', 'Please select a file to upload', 'error');
            return;
        }

        if (!$('#document_type').val()) {
            Swal.fire('Error!', 'Please select a document type', 'error');
            return;
        }

        // Show progress
        uploadProgress.show();
        submitBtn.prop('disabled', true);

        $.ajax({
            url: '{{ route("student-verifications.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        updateProgress(percentComplete);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                updateProgress(100);
                setTimeout(function() {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'View Verification'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = response.redirect_url;
                        } else {
                            resetForm();
                        }
                    });
                }, 500);
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                let errorMessage = 'An error occurred during upload';
                
                if (response && response.errors) {
                    errorMessage = Object.values(response.errors).flat().join('\n');
                } else if (response && response.message) {
                    errorMessage = response.message;
                }
                
                Swal.fire('Error!', errorMessage, 'error');
                submitBtn.prop('disabled', false);
                uploadProgress.hide();
            }
        });
    });

    function updateProgress(percent) {
        const progressBar = uploadProgress.find('.progress-bar');
        const progressText = uploadProgress.find('.progress-text small');
        
        progressBar.css('width', percent + '%');
        progressText.text(`Uploading... ${percent}%`);
        
        if (percent === 100) {
            progressText.text('Processing...');
        }
    }

    window.resetForm = function() {
        $('#upload-form')[0].reset();
        resetFileUpload();
        submitBtn.prop('disabled', false);
    };
});
</script>
@endpush