@extends('layouts.app')

@section('title', 'Teacher Documents Management - Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Teacher Documents Management</h1>
                    <p class="text-muted">Review and manage teacher document submissions</p>
                </div>
                <div>
                    <button type="button" class="btn btn-warning me-2" onclick="checkExpiringDocuments()">
                        <i class="fas fa-exclamation-triangle me-2"></i>Check Expiring
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="bulkApprove()">
                            <i class="fas fa-check me-2"></i>Bulk Approve
                        </button>
                        <button type="button" class="btn btn-danger" onclick="bulkReject()">
                            <i class="fas fa-times me-2"></i>Bulk Reject
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $totalDocuments }}</h4>
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
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $pendingDocuments }}</h4>
                                    <p class="mb-0">Pending Review</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
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
                                    <h4 class="mb-0">{{ $verifiedDocuments }}</h4>
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
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $expiringDocuments }}</h4>
                                    <p class="mb-0">Expiring Soon</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher-documents.admin.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="document_type" class="form-label">Document Type</label>
                            <select class="form-select" id="document_type" name="document_type">
                                <option value="">All Types</option>
                                <option value="resume" {{ request('document_type') == 'resume' ? 'selected' : '' }}>Resume/CV</option>
                                <option value="certificate" {{ request('document_type') == 'certificate' ? 'selected' : '' }}>Certificate</option>
                                <option value="degree" {{ request('document_type') == 'degree' ? 'selected' : '' }}>Degree</option>
                                <option value="id_proof" {{ request('document_type') == 'id_proof' ? 'selected' : '' }}>ID Proof</option>
                                <option value="experience_letter" {{ request('document_type') == 'experience_letter' ? 'selected' : '' }}>Experience Letter</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="teacher" class="form-label">Teacher</label>
                            <input type="text" class="form-control" id="teacher" name="teacher" 
                                   placeholder="Search by teacher name..." value="{{ request('teacher') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <a href="{{ route('teacher-documents.admin.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Document List</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                Select All
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped" id="documentsTable">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllTable" class="form-check-input">
                                        </th>
                                        <th>Teacher</th>
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
                                                <input type="checkbox" class="form-check-input document-checkbox" 
                                                       value="{{ $document->id }}">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        {{ substr($document->teacher->user->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $document->teacher->user->name }}</div>
                                                        <small class="text-muted">{{ $document->teacher->user->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
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
                                                    @if($document->status === 'pending')
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-success" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Approve"
                                                                onclick="approveDocument({{ $document->id }})">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Reject"
                                                                onclick="rejectDocument({{ $document->id }})">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $documents->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No documents found</h5>
                            <p class="text-muted">No teacher documents match your current filters.</p>
                        </div>
                    @endif
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
            <form id="approveForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p>Are you sure you want to approve this document?</p>
                    <div class="mb-3">
                        <label for="approve_comments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="approve_comments" name="admin_comments" rows="3" 
                                  placeholder="Add any comments about the approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
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
            <form id="rejectForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p>Are you sure you want to reject this document?</p>
                    <div class="mb-3">
                        <label for="reject_comments" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_comments" name="admin_comments" rows="3" 
                                  placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkActionTitle">Bulk Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkActionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p id="bulkActionMessage"></p>
                    <div class="mb-3">
                        <label for="bulk_comments" class="form-label">Comments</label>
                        <textarea class="form-control" id="bulk_comments" name="admin_comments" rows="3" 
                                  placeholder="Add comments for this bulk action..."></textarea>
                    </div>
                    <input type="hidden" id="bulk_document_ids" name="document_ids">
                    <input type="hidden" id="bulk_action" name="action">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="bulkActionBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#documentsTable').DataTable({
        responsive: true,
        order: [[5, 'desc']], // Sort by upload date descending
        columnDefs: [
            { orderable: false, targets: [0, 7] } // Disable sorting on checkbox and actions columns
        ]
    });

    // Select all functionality
    $('#selectAllTable').on('change', function() {
        $('.document-checkbox').prop('checked', this.checked);
    });

    $('.document-checkbox').on('change', function() {
        if (!this.checked) {
            $('#selectAllTable').prop('checked', false);
        } else if ($('.document-checkbox:checked').length === $('.document-checkbox').length) {
            $('#selectAllTable').prop('checked', true);
        }
    });
});

function approveDocument(documentId) {
    const form = document.getElementById('approveForm');
    form.action = `/teacher-documents/${documentId}/approve`;
    
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function rejectDocument(documentId) {
    const form = document.getElementById('rejectForm');
    form.action = `/teacher-documents/${documentId}/reject`;
    
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

function bulkApprove() {
    const selectedIds = getSelectedDocumentIds();
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one document to approve.'
        });
        return;
    }

    document.getElementById('bulkActionTitle').textContent = 'Bulk Approve Documents';
    document.getElementById('bulkActionMessage').textContent = `Are you sure you want to approve ${selectedIds.length} selected document(s)?`;
    document.getElementById('bulk_document_ids').value = selectedIds.join(',');
    document.getElementById('bulk_action').value = 'approve';
    document.getElementById('bulkActionBtn').className = 'btn btn-success';
    document.getElementById('bulkActionBtn').textContent = 'Approve All';
    document.getElementById('bulkActionForm').action = '{{ route("teacher-documents.admin.bulk-action") }}';

    const modal = new bootstrap.Modal(document.getElementById('bulkActionModal'));
    modal.show();
}

function bulkReject() {
    const selectedIds = getSelectedDocumentIds();
    if (selectedIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one document to reject.'
        });
        return;
    }

    document.getElementById('bulkActionTitle').textContent = 'Bulk Reject Documents';
    document.getElementById('bulkActionMessage').textContent = `Are you sure you want to reject ${selectedIds.length} selected document(s)?`;
    document.getElementById('bulk_document_ids').value = selectedIds.join(',');
    document.getElementById('bulk_action').value = 'reject';
    document.getElementById('bulkActionBtn').className = 'btn btn-danger';
    document.getElementById('bulkActionBtn').textContent = 'Reject All';
    document.getElementById('bulkActionForm').action = '{{ route("teacher-documents.admin.bulk-action") }}';

    const modal = new bootstrap.Modal(document.getElementById('bulkActionModal'));
    modal.show();
}

function getSelectedDocumentIds() {
    const selectedCheckboxes = document.querySelectorAll('.document-checkbox:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
}

function checkExpiringDocuments() {
    window.location.href = '{{ route("teacher-documents.admin.expiring") }}';
}
</script>
@endpush