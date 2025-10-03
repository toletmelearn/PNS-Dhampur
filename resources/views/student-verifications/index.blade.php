@extends('layouts.app')

@section('title', 'My Document Verifications')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">My Document Verifications</h1>
            <p class="mb-0 text-muted">Track the status of your uploaded documents</p>
        </div>
        <a href="{{ route('student-verifications.upload') }}" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload New Document
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Documents
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-count">
                                {{ $verifications->count() }}
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Verified
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="verified-count">
                                {{ $verifications->where('verification_status', 'verified')->count() }}
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Under Review
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="review-count">
                                {{ $verifications->whereIn('verification_status', ['pending', 'processing', 'manual_review'])->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="rejected-count">
                                {{ $verifications->where('verification_status', 'rejected')->count() }}
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
            <h6 class="m-0 font-weight-bold text-primary">Filter Documents</h6>
        </div>
        <div class="card-body">
            <form id="filter-form" class="row">
                <div class="col-md-3 mb-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-control" id="status-filter" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="manual_review">Under Review</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="type-filter" class="form-label">Document Type</label>
                    <select class="form-control" id="type-filter" name="document_type">
                        <option value="">All Types</option>
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
                <div class="col-md-3 mb-3">
                    <label for="date-from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date-from" name="date_from">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="date-to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date-to" name="date_to">
                </div>
            </form>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Document Verifications</h6>
        </div>
        <div class="card-body">
            @if($verifications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="verifications-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>Status</th>
                                <th>Confidence</th>
                                <th>Upload Date</th>
                                <th>Review Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($verifications as $verification)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $typeIcon = match($verification->document_type) {
                                                    'birth_certificate' => 'fas fa-baby text-info',
                                                    'aadhaar_card' => 'fas fa-id-card text-primary',
                                                    'school_leaving_certificate', 'transfer_certificate' => 'fas fa-graduation-cap text-success',
                                                    'caste_certificate', 'income_certificate', 'domicile_certificate' => 'fas fa-certificate text-warning',
                                                    'passport' => 'fas fa-passport text-danger',
                                                    default => 'fas fa-file-alt text-secondary'
                                                };
                                            @endphp
                                            <i class="{{ $typeIcon }} mr-2"></i>
                                            <div>
                                                <div class="font-weight-bold">{{ $verification->document_type_name }}</div>
                                                @if($verification->file_size)
                                                    <small class="text-muted">{{ $verification->file_size_formatted }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $statusConfig = match($verification->verification_status) {
                                                'verified' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Verified'],
                                                'rejected' => ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Rejected'],
                                                'manual_review' => ['class' => 'warning', 'icon' => 'eye', 'text' => 'Under Review'],
                                                'processing' => ['class' => 'info', 'icon' => 'spinner fa-spin', 'text' => 'Processing'],
                                                default => ['class' => 'secondary', 'icon' => 'clock', 'text' => 'Pending']
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusConfig['class'] }}">
                                            <i class="fas fa-{{ $statusConfig['icon'] }} mr-1"></i>
                                            {{ $statusConfig['text'] }}
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
                                                <small class="text-muted">{{ $verification->confidence_level }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $verification->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $verification->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        @if($verification->reviewed_at)
                                            <div>{{ $verification->reviewed_at->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ $verification->reviewed_at->format('H:i:s') }}</small>
                                        @else
                                            <span class="text-muted">Not reviewed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('student-verifications.show', $verification) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('student-verifications.download', $verification) }}" 
                                               class="btn btn-sm btn-outline-info" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($verification->verification_status === 'rejected')
                                                <a href="{{ route('student-verifications.upload') }}" 
                                                   class="btn btn-sm btn-outline-success" title="Upload New">
                                                    <i class="fas fa-upload"></i>
                                                </a>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteVerification({{ $verification->id }})" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-upload fa-4x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No documents uploaded yet</h5>
                    <p class="text-muted">Upload your first document to get started with verification.</p>
                    <a href="{{ route('student-verifications.upload') }}" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Activity -->
    @if($recentActivity && $recentActivity->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($recentActivity as $activity)
                        <div class="timeline-item">
                            @php
                                $activityConfig = match($activity->verification_status) {
                                    'verified' => ['class' => 'success', 'icon' => 'check-circle'],
                                    'rejected' => ['class' => 'danger', 'icon' => 'times-circle'],
                                    'manual_review' => ['class' => 'warning', 'icon' => 'eye'],
                                    'processing' => ['class' => 'info', 'icon' => 'cog'],
                                    default => ['class' => 'primary', 'icon' => 'upload']
                                };
                            @endphp
                            <div class="timeline-marker bg-{{ $activityConfig['class'] }}"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">
                                    <i class="fas fa-{{ $activityConfig['icon'] }} mr-1"></i>
                                    {{ $activity->document_type_name }}
                                </h6>
                                <p class="timeline-text">
                                    Status: {{ ucfirst(str_replace('_', ' ', $activity->verification_status)) }}
                                    @if($activity->reviewer_comments)
                                        <br><em>"{{ Str::limit($activity->reviewer_comments, 100) }}"</em>
                                    @endif
                                </p>
                                <small class="text-muted">{{ $activity->updated_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Verification</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this verification?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="fas fa-trash"></i> Delete
                </button>
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

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -29px;
    top: 17px;
    width: 2px;
    height: calc(100% + 20px);
    background-color: #e3e6f0;
}

.timeline-content {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #4e73df;
}

.timeline-title {
    margin-bottom: 5px;
    color: #5a5c69;
    font-size: 14px;
}

.timeline-text {
    margin-bottom: 5px;
    color: #6e707e;
    font-size: 13px;
}

.progress {
    min-width: 80px;
}
</style>
@endpush

@push('scripts')
<script>
let deleteVerificationId = null;

$(document).ready(function() {
    // Initialize DataTable
    const table = $('#verifications-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[3, 'desc']], // Sort by upload date descending
        columnDefs: [
            { orderable: false, targets: [5] } // Disable sorting for actions column
        ]
    });

    // Filter functionality
    $('#filter-form select, #filter-form input').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const status = $('#status-filter').val();
        const type = $('#type-filter').val();
        const dateFrom = $('#date-from').val();
        const dateTo = $('#date-to').val();

        // Apply status filter
        if (status) {
            table.column(1).search(status, true, false);
        } else {
            table.column(1).search('');
        }

        // Apply type filter
        if (type) {
            table.column(0).search(type, true, false);
        } else {
            table.column(0).search('');
        }

        // Apply date filters (would need server-side implementation for full functionality)
        table.draw();
    }

    // Delete confirmation
    $('#confirm-delete-btn').on('click', function() {
        if (deleteVerificationId) {
            $.ajax({
                url: `/student-verifications/${deleteVerificationId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#deleteModal').modal('hide');
                    Swal.fire({
                        title: 'Deleted!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
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
});

function deleteVerification(id) {
    deleteVerificationId = id;
    $('#deleteModal').modal('show');
}

// Auto-refresh for processing documents
setInterval(function() {
    const processingElements = $('.badge:contains("Processing")');
    if (processingElements.length > 0) {
        // Refresh the page if there are processing documents
        location.reload();
    }
}, 30000); // Check every 30 seconds
</script>
@endpush