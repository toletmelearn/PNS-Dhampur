@extends('layouts.app')

@section('title', 'Student Document Verifications')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Student Document Verifications</h1>
            <p class="text-muted">Manage and review student document verifications</p>
        </div>
        <div>
            <a href="{{ route('admin.student-verifications.create') }}" class="btn btn-primary">
                <i class="fas fa-upload"></i> Upload Document
            </a>
            <button type="button" class="btn btn-info" onclick="refreshStats()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Verifications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-verifications">
                                {{ $stats['total'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Review
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pending-verifications">
                                {{ $stats['pending'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Verified
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="verified-documents">
                                {{ $stats['verified'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Rejected
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="rejected-documents">
                                {{ $stats['rejected'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.student-verifications.index') }}" id="filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="manual_review" {{ request('status') == 'manual_review' ? 'selected' : '' }}>Manual Review</option>
                                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="document_type">Document Type</label>
                            <select name="document_type" id="document_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="aadhaar" {{ request('document_type') == 'aadhaar' ? 'selected' : '' }}>Aadhaar Card</option>
                                <option value="birth_certificate" {{ request('document_type') == 'birth_certificate' ? 'selected' : '' }}>Birth Certificate</option>
                                <option value="school_leaving" {{ request('document_type') == 'school_leaving' ? 'selected' : '' }}>School Leaving Certificate</option>
                                <option value="caste_certificate" {{ request('document_type') == 'caste_certificate' ? 'selected' : '' }}>Caste Certificate</option>
                                <option value="income_certificate" {{ request('document_type') == 'income_certificate' ? 'selected' : '' }}>Income Certificate</option>
                                <option value="passport_photo" {{ request('document_type') == 'passport_photo' ? 'selected' : '' }}>Passport Photo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="confidence_level">Confidence Level</label>
                            <select name="confidence_level" id="confidence_level" class="form-control">
                                <option value="">All Levels</option>
                                <option value="high" {{ request('confidence_level') == 'high' ? 'selected' : '' }}>High (≥90%)</option>
                                <option value="medium" {{ request('confidence_level') == 'medium' ? 'selected' : '' }}>Medium (70-89%)</option>
                                <option value="low" {{ request('confidence_level') == 'low' ? 'selected' : '' }}>Low (<70%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="student_name">Student Name</label>
                            <input type="text" name="student_name" id="student_name" class="form-control" 
                                   placeholder="Search by student name..." value="{{ request('student_name') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.student-verifications.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                        <button type="button" class="btn btn-info" onclick="processPending()">
                            <i class="fas fa-cogs"></i> Process Pending
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Verifications Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Document Verifications</h6>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="bulkApprove()" id="bulk-approve-btn" disabled>
                    <i class="fas fa-check"></i> Bulk Approve
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="bulkReject()" id="bulk-reject-btn" disabled>
                    <i class="fas fa-times"></i> Bulk Reject
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($verifications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="verifications-table">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Student</th>
                                <th>Document Type</th>
                                <th>Status</th>
                                <th>Confidence</th>
                                <th>Uploaded By</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($verifications as $verification)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="verification-checkbox" 
                                               value="{{ $verification->id }}"
                                               {{ in_array($verification->verification_status, ['verified', 'rejected']) ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="font-weight-bold">{{ $verification->student->name }}</div>
                                                <div class="text-muted small">{{ $verification->student->class?->name ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $verification->document_type_name }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($verification->verification_status) {
                                                'verified' => 'success',
                                                'rejected' => 'danger',
                                                'manual_review' => 'warning',
                                                'processing' => 'info',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $verification->verification_status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($verification->confidence_score)
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 mr-2" style="height: 20px;">
                                                    @php
                                                        $confidenceClass = $verification->confidence_score >= 90 ? 'success' : 
                                                                         ($verification->confidence_score >= 70 ? 'warning' : 'danger');
                                                    @endphp
                                                    <div class="progress-bar bg-{{ $confidenceClass }}" 
                                                         style="width: {{ $verification->confidence_score }}%">
                                                        {{ number_format($verification->confidence_score, 1) }}%
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            {{ $verification->uploader->name ?? 'System' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            {{ $verification->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="text-muted text-xs">
                                            {{ $verification->created_at->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.student-verifications.show', $verification) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.student-verifications.download', $verification) }}" 
                                               class="btn btn-sm btn-outline-info" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if(in_array($verification->verification_status, ['manual_review', 'pending']))
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="approveVerification({{ $verification->id }})" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="rejectVerification({{ $verification->id }})" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="reprocessVerification({{ $verification->id }})" title="Reprocess">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing {{ $verifications->firstItem() }} to {{ $verifications->lastItem() }} 
                        of {{ $verifications->total() }} results
                    </div>
                    {{ $verifications->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-file-alt fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No verifications found</h5>
                    <p class="text-muted">No document verifications match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bulk Action Modals -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Approve Verifications</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="bulk-approve-form">
                <div class="modal-body">
                    <p>Are you sure you want to approve the selected verifications?</p>
                    <div class="form-group">
                        <label for="bulk-approve-comments">Comments (Optional)</label>
                        <textarea class="form-control" id="bulk-approve-comments" name="comments" rows="3"
                                  placeholder="Add any comments for the approval..."></textarea>
                    </div>
                    <div id="bulk-approve-list"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Reject Verifications</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="bulk-reject-form">
                <div class="modal-body">
                    <p>Are you sure you want to reject the selected verifications?</p>
                    <div class="form-group">
                        <label for="bulk-reject-comments">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk-reject-comments" name="comments" rows="3" required
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    <div id="bulk-reject-list"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Single Action Modals -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Verification</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="approve-form">
                <div class="modal-body">
                    <p>Are you sure you want to approve this verification?</p>
                    <div class="form-group">
                        <label for="approve-comments">Comments (Optional)</label>
                        <textarea class="form-control" id="approve-comments" name="comments" rows="3"
                                  placeholder="Add any comments for the approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Verification</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="reject-form">
                <div class="modal-body">
                    <p>Are you sure you want to reject this verification?</p>
                    <div class="form-group">
                        <label for="reject-comments">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject-comments" name="comments" rows="3" required
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#verifications-table').DataTable({
        "paging": false,
        "searching": false,
        "ordering": true,
        "info": false,
        "columnDefs": [
            { "orderable": false, "targets": [0, 7] }
        ]
    });

    // Handle select all checkbox
    $('#select-all').change(function() {
        $('.verification-checkbox:not(:disabled)').prop('checked', this.checked);
        updateBulkActionButtons();
    });

    // Handle individual checkboxes
    $(document).on('change', '.verification-checkbox', function() {
        updateBulkActionButtons();
        
        // Update select all checkbox
        const totalCheckboxes = $('.verification-checkbox:not(:disabled)').length;
        const checkedCheckboxes = $('.verification-checkbox:not(:disabled):checked').length;
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Update bulk action button states
    function updateBulkActionButtons() {
        const checkedCount = $('.verification-checkbox:checked').length;
        $('#bulk-approve-btn, #bulk-reject-btn').prop('disabled', checkedCount === 0);
    }

    // Bulk approve form submission
    $('#bulk-approve-form').submit(function(e) {
        e.preventDefault();
        
        const selectedIds = $('.verification-checkbox:checked').map(function() {
            return this.value;
        }).get();

        const comments = $('#bulk-approve-comments').val();

        $.ajax({
            url: '{{ route("admin.student-verifications.bulk-approve") }}',
            method: 'POST',
            data: {
                verification_ids: selectedIds,
                comments: comments,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#bulkApproveModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error!', response.message || 'An error occurred', 'error');
            }
        });
    });

    // Bulk reject form submission
    $('#bulk-reject-form').submit(function(e) {
        e.preventDefault();
        
        const selectedIds = $('.verification-checkbox:checked').map(function() {
            return this.value;
        }).get();

        const comments = $('#bulk-reject-comments').val();

        if (!comments.trim()) {
            Swal.fire('Error!', 'Please provide a reason for rejection', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("admin.student-verifications.bulk-reject") }}',
            method: 'POST',
            data: {
                verification_ids: selectedIds,
                comments: comments,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#bulkRejectModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error!', response.message || 'An error occurred', 'error');
            }
        });
    });

    // Single approve form submission
    $('#approve-form').submit(function(e) {
        e.preventDefault();
        
        const verificationId = $(this).data('verification-id');
        const comments = $('#approve-comments').val();

        $.ajax({
            url: `/admin/student-verifications/${verificationId}/approve`,
            method: 'POST',
            data: {
                comments: comments,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#approveModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error!', response.message || 'An error occurred', 'error');
            }
        });
    });

    // Single reject form submission
    $('#reject-form').submit(function(e) {
        e.preventDefault();
        
        const verificationId = $(this).data('verification-id');
        const comments = $('#reject-comments').val();

        if (!comments.trim()) {
            Swal.fire('Error!', 'Please provide a reason for rejection', 'error');
            return;
        }

        $.ajax({
            url: `/admin/student-verifications/${verificationId}/reject`,
            method: 'POST',
            data: {
                comments: comments,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#rejectModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error!', response.message || 'An error occurred', 'error');
            }
        });
    });
});

// Bulk action functions
function bulkApprove() {
    const selectedIds = $('.verification-checkbox:checked').map(function() {
        return this.value;
    }).get();

    if (selectedIds.length === 0) {
        Swal.fire('Warning!', 'Please select at least one verification to approve', 'warning');
        return;
    }

    // Show selected items in modal
    let listHtml = '<ul class="list-group">';
    $('.verification-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const studentName = row.find('td:nth-child(2) .font-weight-bold').text();
        const documentType = row.find('td:nth-child(3) .badge').text();
        listHtml += `<li class="list-group-item">${studentName} - ${documentType}</li>`;
    });
    listHtml += '</ul>';
    $('#bulk-approve-list').html(listHtml);

    $('#bulkApproveModal').modal('show');
}

function bulkReject() {
    const selectedIds = $('.verification-checkbox:checked').map(function() {
        return this.value;
    }).get();

    if (selectedIds.length === 0) {
        Swal.fire('Warning!', 'Please select at least one verification to reject', 'warning');
        return;
    }

    // Show selected items in modal
    let listHtml = '<ul class="list-group">';
    $('.verification-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const studentName = row.find('td:nth-child(2) .font-weight-bold').text();
        const documentType = row.find('td:nth-child(3) .badge').text();
        listHtml += `<li class="list-group-item">${studentName} - ${documentType}</li>`;
    });
    listHtml += '</ul>';
    $('#bulk-reject-list').html(listHtml);

    $('#bulkRejectModal').modal('show');
}

// Single action functions
function approveVerification(verificationId) {
    $('#approve-form').data('verification-id', verificationId);
    $('#approve-comments').val('');
    $('#approveModal').modal('show');
}

function rejectVerification(verificationId) {
    $('#reject-form').data('verification-id', verificationId);
    $('#reject-comments').val('');
    $('#rejectModal').modal('show');
}

function reprocessVerification(verificationId) {
    Swal.fire({
        title: 'Reprocess Verification?',
        text: 'This will restart the verification process for this document.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reprocess it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/student-verifications/${verificationId}/reprocess`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred', 'error');
                }
            });
        }
    });
}

function processPending() {
    Swal.fire({
        title: 'Process Pending Verifications?',
        text: 'This will process up to 10 pending verifications.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, process them!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("admin.student-verifications.process-pending") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response.message || 'An error occurred', 'error');
                }
            });
        }
    });
}

function refreshStats() {
    $.ajax({
        url: '{{ route("admin.student-verifications.statistics") }}',
        method: 'GET',
        success: function(response) {
            $('#total-verifications').text(response.total || 0);
            $('#pending-verifications').text(response.pending || 0);
            $('#verified-documents').text(response.verified || 0);
            $('#rejected-documents').text(response.rejected || 0);
            
            Swal.fire({
                title: 'Statistics Updated!',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        },
        error: function() {
            Swal.fire('Error!', 'Failed to refresh statistics', 'error');
        }
    });
}
</script>
@endpush