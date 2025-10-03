@extends('layouts.app')

@section('title', 'Document Details - Teacher Document Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Document Details</h1>
                    <p class="text-muted">View document information and status</p>
                </div>
                <a href="{{ route('teacher-documents.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Documents
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Document Information -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt me-2"></i>{{ $document->document_type_name }}
                                </h5>
                                <div>
                                    @if($document->status === 'verified')
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-check me-1"></i>Verified
                                        </span>
                                    @elseif($document->status === 'pending')
                                        <span class="badge bg-warning fs-6">
                                            <i class="fas fa-clock me-1"></i>Pending Review
                                        </span>
                                    @else
                                        <span class="badge bg-danger fs-6">
                                            <i class="fas fa-times me-1"></i>Rejected
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-muted">File Information</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-medium">File Name:</td>
                                            <td>{{ basename($document->file_path) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">File Size:</td>
                                            <td>{{ $document->human_file_size }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Upload Date:</td>
                                            <td>{{ $document->created_at->format('M d, Y \a\t g:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Last Updated:</td>
                                            <td>{{ $document->updated_at->format('M d, Y \a\t g:i A') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-muted">Document Details</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="fw-medium">Document Type:</td>
                                            <td>{{ $document->document_type_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Status:</td>
                                            <td>
                                                @if($document->status === 'verified')
                                                    <span class="text-success fw-medium">
                                                        <i class="fas fa-check me-1"></i>Verified
                                                    </span>
                                                @elseif($document->status === 'pending')
                                                    <span class="text-warning fw-medium">
                                                        <i class="fas fa-clock me-1"></i>Pending Review
                                                    </span>
                                                @else
                                                    <span class="text-danger fw-medium">
                                                        <i class="fas fa-times me-1"></i>Rejected
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Expiry Date:</td>
                                            <td>
                                                @if($document->expiry_date)
                                                    {{ $document->expiry_date->format('M d, Y') }}
                                                    @if($document->is_expiring_soon)
                                                        <span class="badge bg-warning ms-2">Expiring Soon</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">No expiry date</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Uploaded By:</td>
                                            <td>{{ $document->uploadedBy->name ?? 'Unknown' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Admin Comments -->
                            @if($document->admin_comments)
                                <div class="alert alert-info">
                                    <h6 class="fw-bold mb-2">
                                        <i class="fas fa-comment me-2"></i>Admin Comments
                                    </h6>
                                    <p class="mb-0">{{ $document->admin_comments }}</p>
                                </div>
                            @endif

                            <!-- File Preview -->
                            <div class="mb-4">
                                <h6 class="fw-bold text-muted mb-3">File Preview</h6>
                                <div class="border rounded p-3 text-center bg-light">
                                    @php
                                        $fileExtension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                                    @endphp
                                    
                                    @if($fileExtension === 'pdf')
                                        <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                                        <h5>PDF Document</h5>
                                        <p class="text-muted">{{ basename($document->file_path) }}</p>
                                        <div class="mt-3">
                                            <a href="{{ $document->file_url }}" 
                                               class="btn btn-outline-primary me-2" 
                                               target="_blank">
                                                <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                                            </a>
                                            <a href="{{ route('teacher-documents.download', $document) }}" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                        </div>
                                    @elseif(in_array($fileExtension, ['jpg', 'jpeg', 'png']))
                                        <img src="{{ $document->file_url }}" 
                                             alt="Document Preview" 
                                             class="img-fluid rounded shadow-sm" 
                                             style="max-height: 400px;">
                                        <div class="mt-3">
                                            <a href="{{ route('teacher-documents.download', $document) }}" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                        </div>
                                    @elseif(in_array($fileExtension, ['doc', 'docx']))
                                        <i class="fas fa-file-word fa-4x text-primary mb-3"></i>
                                        <h5>Word Document</h5>
                                        <p class="text-muted">{{ basename($document->file_path) }}</p>
                                        <div class="mt-3">
                                            <a href="{{ route('teacher-documents.download', $document) }}" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                        </div>
                                    @else
                                        <i class="fas fa-file fa-4x text-secondary mb-3"></i>
                                        <h5>Document File</h5>
                                        <p class="text-muted">{{ basename($document->file_path) }}</p>
                                        <div class="mt-3">
                                            <a href="{{ route('teacher-documents.download', $document) }}" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('teacher-documents.download', $document) }}" 
                                   class="btn btn-success me-2">
                                    <i class="fas fa-download me-2"></i>Download
                                </a>
                                
                                @if($document->status === 'pending' || $document->status === 'rejected')
                                    <button type="button" 
                                            class="btn btn-danger" 
                                            onclick="deleteDocument({{ $document->id }})">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
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
                                        <h6 class="mb-1">Document Uploaded</h6>
                                        <p class="text-muted mb-0">{{ $document->created_at->format('M d, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                                
                                @if($document->status === 'verified')
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Document Verified</h6>
                                            <p class="text-muted mb-0">{{ $document->updated_at->format('M d, Y \a\t g:i A') }}</p>
                                        </div>
                                    </div>
                                @elseif($document->status === 'rejected')
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-danger"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Document Rejected</h6>
                                            <p class="text-muted mb-0">{{ $document->updated_at->format('M d, Y \a\t g:i A') }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-warning"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Pending Review</h6>
                                            <p class="text-muted mb-0">Waiting for admin approval</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('teacher-documents.download', $document) }}" 
                                   class="btn btn-outline-success">
                                    <i class="fas fa-download me-2"></i>Download Document
                                </a>
                                
                                @if($document->status === 'rejected')
                                    <a href="{{ route('teacher-documents.create') }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-upload me-2"></i>Upload New Version
                                    </a>
                                @endif
                                
                                <a href="{{ route('teacher-documents.index') }}" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-2"></i>View All Documents
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this document? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
    border-left: 3px solid #0d6efd;
}
</style>
@endpush

@push('scripts')
<script>
function deleteDocument(documentId) {
    const form = document.getElementById('deleteForm');
    form.action = `/teacher-documents/${documentId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush