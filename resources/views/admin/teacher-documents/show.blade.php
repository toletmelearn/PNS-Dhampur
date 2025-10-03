@extends('layouts.app')

@section('title', 'Document Details - ' . $document->document_type_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.teacher-documents.index') }}">Teacher Documents</a>
                            </li>
                            <li class="breadcrumb-item active">Document Details</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">{{ $document->document_type_name }}</h1>
                    <p class="text-muted mb-0">Submitted by {{ $document->teacher->user->name }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.teacher-documents.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                    @if($document->status === 'pending')
                        <button type="button" class="btn btn-success me-2" onclick="approveDocument()">
                            <i class="fas fa-check me-2"></i>Approve
                        </button>
                        <button type="button" class="btn btn-danger" onclick="rejectDocument()">
                            <i class="fas fa-times me-2"></i>Reject
                        </button>
                    @endif
                </div>
            </div>

            <div class="row">
                <!-- Document Information -->
                <div class="col-lg-8">
                    <!-- Document Preview -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Document Preview
                            </h5>
                        </div>
                        <div class="card-body">
                            @php
                                $fileExtension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                            @endphp

                            @if(in_array($fileExtension, ['pdf']))
                                <div class="text-center">
                                    <embed src="{{ $document->file_url }}" type="application/pdf" width="100%" height="600px" />
                                    <p class="mt-2">
                                        <a href="{{ route('teacher-documents.download', $document) }}" class="btn btn-primary">
                                            <i class="fas fa-download me-2"></i>Download PDF
                                        </a>
                                    </p>
                                </div>
                            @elseif(in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']))
                                <div class="text-center">
                                    <img src="{{ $document->file_url }}" class="img-fluid" alt="Document Image" style="max-height: 600px;">
                                    <p class="mt-2">
                                        <a href="{{ route('teacher-documents.download', $document) }}" class="btn btn-primary">
                                            <i class="fas fa-download me-2"></i>Download Image
                                        </a>
                                    </p>
                                </div>
                            @elseif(in_array($fileExtension, ['doc', 'docx']))
                                <div class="text-center py-5">
                                    <i class="fas fa-file-word fa-5x text-primary mb-3"></i>
                                    <h5>Microsoft Word Document</h5>
                                    <p class="text-muted">{{ basename($document->file_path) }}</p>
                                    <p class="text-muted">Size: {{ $document->human_file_size }}</p>
                                    <a href="{{ route('teacher-documents.download', $document) }}" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Download Document
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-file fa-5x text-muted mb-3"></i>
                                    <h5>Document File</h5>
                                    <p class="text-muted">{{ basename($document->file_path) }}</p>
                                    <p class="text-muted">Size: {{ $document->human_file_size }}</p>
                                    <a href="{{ route('teacher-documents.download', $document) }}" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Download File
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Comments Section -->
                    @if($document->admin_comments)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-comments me-2"></i>Admin Comments
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <p class="mb-0">{{ $document->admin_comments }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar Information -->
                <div class="col-lg-4">
                    <!-- Status Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Document Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                @if($document->status === 'verified')
                                    <div class="badge bg-success fs-6 p-3">
                                        <i class="fas fa-check-circle me-2"></i>Verified
                                    </div>
                                @elseif($document->status === 'pending')
                                    <div class="badge bg-warning fs-6 p-3">
                                        <i class="fas fa-clock me-2"></i>Pending Review
                                    </div>
                                @else
                                    <div class="badge bg-danger fs-6 p-3">
                                        <i class="fas fa-times-circle me-2"></i>Rejected
                                    </div>
                                @endif
                            </div>

                            @if($document->is_expiring_soon)
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Expiring Soon!</strong><br>
                                    This document will expire on {{ $document->expiry_date->format('M d, Y') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Teacher Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Teacher Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    {{ substr($document->teacher->user->name, 0, 2) }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $document->teacher->user->name }}</h6>
                                    <small class="text-muted">{{ $document->teacher->user->email }}</small>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted">Qualification</small>
                                    <div class="fw-medium">{{ $document->teacher->qualification ?? 'N/A' }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Experience</small>
                                    <div class="fw-medium">{{ $document->teacher->experience_years ?? 0 }} years</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Joining Date</small>
                                    <div class="fw-medium">
                                        {{ $document->teacher->joining_date ? $document->teacher->joining_date->format('M d, Y') : 'N/A' }}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total Documents</small>
                                    <div class="fw-medium">{{ $document->teacher->teacherDocuments->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Document Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-12">
                                    <small class="text-muted">Document Type</small>
                                    <div class="fw-medium">{{ $document->document_type_name }}</div>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">File Name</small>
                                    <div class="fw-medium">{{ basename($document->file_path) }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">File Size</small>
                                    <div class="fw-medium">{{ $document->human_file_size }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">File Type</small>
                                    <div class="fw-medium">{{ strtoupper($fileExtension) }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Upload Date</small>
                                    <div class="fw-medium">{{ $document->created_at->format('M d, Y') }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Expiry Date</small>
                                    <div class="fw-medium">
                                        @if($document->expiry_date)
                                            {{ $document->expiry_date->format('M d, Y') }}
                                        @else
                                            No expiry
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Status Timeline
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Document Uploaded</h6>
                                        <p class="timeline-text">{{ $document->created_at->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                                
                                @if($document->status === 'verified')
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">Document Verified</h6>
                                            <p class="timeline-text">{{ $document->updated_at->format('M d, Y \a\t g:i A') }}</p>
                                        </div>
                                    </div>
                                @elseif($document->status === 'rejected')
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-danger"></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">Document Rejected</h6>
                                            <p class="timeline-text">{{ $document->updated_at->format('M d, Y \a\t g:i A') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.teacher-documents.approve', $document) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        You are about to approve this document. This action will mark the document as verified.
                    </div>
                    <div class="mb-3">
                        <label for="approve_comments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="approve_comments" name="admin_comments" rows="3" 
                                  placeholder="Add any comments about the approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.teacher-documents.reject', $document) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You are about to reject this document. Please provide a clear reason for rejection.
                    </div>
                    <div class="mb-3">
                        <label for="reject_comments" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_comments" name="admin_comments" rows="4" 
                                  placeholder="Please provide a detailed reason for rejection..." required></textarea>
                        <div class="form-text">This reason will be visible to the teacher.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-lg {
    width: 48px;
    height: 48px;
    font-size: 18px;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    padding-left: 15px;
}

.timeline-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 5px;
}

.timeline-text {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 0;
}
</style>
@endpush

@push('scripts')
<script>
function approveDocument() {
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function rejectDocument() {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endpush