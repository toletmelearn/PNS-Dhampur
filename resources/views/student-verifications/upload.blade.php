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
                            <div class="drop-zone" id="upload-area">
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt drop-zone-icon"></i>
                                    <div class="drop-zone-text">Drag & Drop your document here</div>
                                    <div class="drop-zone-subtext">or click to browse files</div>
                                    <input type="file" id="document_file" name="document_file" 
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" 
                                           data-max-size="{{ config('fileupload.max_file_sizes.document') }}"
                                           required style="display: none;">
                                </div>
                            </div>
                            <div class="file-preview mt-3" id="file-preview"></div>
                            <small class="form-text text-muted">
                                Supported formats: PDF, JPG, PNG, DOC, DOCX. Maximum size: {{ number_format(config('fileupload.max_file_sizes.document') / 1024, 0) }}MB
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
<link href="{{ asset('css/file-upload-enhanced.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('js/file-upload-enhanced.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize enhanced file upload with specific configuration
    const uploader = new EnhancedFileUpload({
        maxFileSize: {{ config('fileupload.max_file_sizes.document') }},
        allowedTypes: @json(explode(',', config('fileupload.allowed_file_types.verification.extensions'))),
        dropZone: '#upload-area',
        fileInput: '#document_file',
        previewContainer: '#file-preview',
        autoUpload: false
    });

    // Form submission with enhanced progress tracking
    $('#upload-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#document_file')[0].files.length) {
            Swal.fire('Error!', 'Please select a file to upload', 'error');
            return;
        }

        if (!$('#document_type').val()) {
            Swal.fire('Error!', 'Please select a document type', 'error');
            return;
        }

        // Use the enhanced uploader's form submission handler
        return uploader.handleFormSubmission(this);
    });

    window.resetForm = function() {
        $('#upload-form')[0].reset();
        $('#file-preview').empty();
        $('#submit-btn').prop('disabled', false);
    };
});
</script>
@endpush