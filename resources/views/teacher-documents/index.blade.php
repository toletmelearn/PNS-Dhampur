@extends('layouts.app')

@section('title', 'My Documents - Teacher Document Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">My Documents</h1>
                    <p class="text-muted">Manage your professional documents and certificates</p>
                </div>
                <a href="{{ route('teacher-documents.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Upload Document
                </a>
            </div>

            <!-- Document Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $documents->count() }}</h4>
                                    <p class="mb-0">Total Documents</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $documents->where('status', 'verified')->count() }}</h4>
                                    <p class="mb-0">Verified</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $documents->where('status', 'pending')->count() }}</h4>
                                    <p class="mb-0">Pending</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $documents->where('status', 'rejected')->count() }}</h4>
                                    <p class="mb-0">Rejected</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Document List</h5>
                </div>
                <div class="card-body">
                    @if($documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped" id="documentsTable">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>File Name</th>
                                        <th>Status</th>
                                        <th>Upload Date</th>
                                        <th>Expiry Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $document)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                                {{ $document->document_type_name }}
                                            </td>
                                            <td>
                                                <span class="fw-medium">{{ basename($document->file_path) }}</span>
                                                <br>
                                                <small class="text-muted">{{ $document->human_file_size }}</small>
                                            </td>
                                            <td>
                                                @if($document->status === 'verified')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Verified
                                                    </span>
                                                @elseif($document->status === 'pending')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Rejected
                                                    </span>
                                                @endif
                                                
                                                @if($document->is_expiring_soon)
                                                    <br><span class="badge bg-warning mt-1">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Expiring Soon
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $document->created_at->format('M d, Y') }}</td>
                                            <td>
                                                @if($document->expiry_date)
                                                    {{ $document->expiry_date->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">No expiry</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('teacher-documents.show', $document) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       data-bs-toggle="tooltip" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('teacher-documents.download', $document) }}" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       data-bs-toggle="tooltip" 
                                                       title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    @if($document->status === 'pending' || $document->status === 'rejected')
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Delete"
                                                                onclick="deleteDocument({{ $document->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No documents uploaded yet</h5>
                            <p class="text-muted">Start by uploading your professional documents and certificates.</p>
                            <a href="{{ route('teacher-documents.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Upload Your First Document
                            </a>
                        </div>
                    @endif
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

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#documentsTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by upload date descending
        columnDefs: [
            { orderable: false, targets: [5] } // Disable sorting on actions column
        ]
    });
});

function deleteDocument(documentId) {
    const form = document.getElementById('deleteForm');
    form.action = `/teacher-documents/${documentId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush