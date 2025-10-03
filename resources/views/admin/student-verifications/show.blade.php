@extends('layouts.app')

@section('title', 'Verification Details - ' . $verification->student->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Verification Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.student-verifications.index') }}">Student Verifications</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $verification->student->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('admin.student-verifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('admin.student-verifications.download', $verification) }}" class="btn btn-info">
                <i class="fas fa-download"></i> Download
            </a>
            @if(in_array($verification->verification_status, ['manual_review', 'pending']))
                <button type="button" class="btn btn-success" onclick="approveVerification()">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button type="button" class="btn btn-danger" onclick="rejectVerification()">
                    <i class="fas fa-times"></i> Reject
                </button>
            @endif
            <button type="button" class="btn btn-warning" onclick="reprocessVerification()">
                <i class="fas fa-redo"></i> Reprocess
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Document Preview -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Document Preview</h6>
                </div>
                <div class="card-body">
                    @if($verification->original_file_path && Storage::exists($verification->original_file_path))
                        @php
                            $fileExtension = strtolower(pathinfo($verification->original_file_path, PATHINFO_EXTENSION));
                            $fileUrl = Storage::url($verification->original_file_path);
                        @endphp
                        
                        @if(in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']))
                            <!-- Image Preview -->
                            <div class="text-center">
                                <img src="{{ $fileUrl }}" class="img-fluid" style="max-height: 500px;" alt="Document Preview">
                            </div>
                        @elseif($fileExtension === 'pdf')
                            <!-- PDF Preview -->
                            <div class="text-center">
                                <embed src="{{ $fileUrl }}" type="application/pdf" width="100%" height="500px">
                                <p class="mt-2">
                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> Open in New Tab
                                    </a>
                                </p>
                            </div>
                        @else
                            <!-- Other file types -->
                            <div class="text-center py-5">
                                <i class="fas fa-file fa-4x text-gray-300 mb-3"></i>
                                <h5 class="text-gray-600">Preview not available</h5>
                                <p class="text-muted">{{ strtoupper($fileExtension) }} files cannot be previewed directly.</p>
                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-download"></i> Download File
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                            <h5 class="text-gray-600">File not found</h5>
                            <p class="text-muted">The original document file could not be located.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Verification Information -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Verification Information</h6>
                </div>
                <div class="card-body">
                    <!-- Status -->
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Status:</strong></div>
                        <div class="col-sm-8">
                            @php
                                $statusClass = match($verification->verification_status) {
                                    'verified' => 'success',
                                    'rejected' => 'danger',
                                    'manual_review' => 'warning',
                                    'processing' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }} badge-lg">
                                {{ ucfirst(str_replace('_', ' ', $verification->verification_status)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Student Information -->
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Student:</strong></div>
                        <div class="col-sm-8">
                            <div>{{ $verification->student->name }}</div>
                            <small class="text-muted">{{ $verification->student->class?->name ?? 'N/A' }}</small>
                        </div>
                    </div>

                    <!-- Document Type -->
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Document Type:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge badge-info">{{ $verification->document_type_name }}</span>
                        </div>
                    </div>

                    <!-- Confidence Score -->
                    @if($verification->confidence_score)
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Confidence:</strong></div>
                            <div class="col-sm-8">
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 mr-2" style="height: 25px;">
                                        @php
                                            $confidenceClass = $verification->confidence_score >= 90 ? 'success' : 
                                                             ($verification->confidence_score >= 70 ? 'warning' : 'danger');
                                        @endphp
                                        <div class="progress-bar bg-{{ $confidenceClass }}" 
                                             style="width: {{ $verification->confidence_score }}%">
                                            {{ number_format($verification->confidence_score, 1) }}%
                                        </div>
                                    </div>
                                    <span class="badge badge-{{ $confidenceClass }}">
                                        {{ $verification->confidence_level }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Verification Method -->
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Method:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge badge-secondary">
                                {{ ucfirst(str_replace('_', ' ', $verification->verification_method)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Upload Information -->
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Uploaded By:</strong></div>
                        <div class="col-sm-8">{{ $verification->uploader->name ?? 'System' }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Upload Date:</strong></div>
                        <div class="col-sm-8">
                            {{ $verification->created_at->format('d/m/Y H:i:s') }}
                            <small class="text-muted d-block">{{ $verification->created_at->diffForHumans() }}</small>
                        </div>
                    </div>

                    <!-- File Information -->
                    @if($verification->file_size)
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>File Size:</strong></div>
                            <div class="col-sm-8">{{ $verification->file_size_formatted }}</div>
                        </div>
                    @endif

                    <!-- Processing Time -->
                    @if($verification->verification_duration)
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Processing Time:</strong></div>
                            <div class="col-sm-8">{{ $verification->verification_duration }}</div>
                        </div>
                    @endif

                    <!-- Review Information -->
                    @if($verification->reviewed_by)
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Reviewed By:</strong></div>
                            <div class="col-sm-8">{{ $verification->reviewer->name }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Review Date:</strong></div>
                            <div class="col-sm-8">
                                {{ $verification->reviewed_at->format('d/m/Y H:i:s') }}
                                <small class="text-muted d-block">{{ $verification->reviewed_at->diffForHumans() }}</small>
                            </div>
                        </div>

                        @if($verification->reviewer_comments)
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong>Comments:</strong></div>
                                <div class="col-sm-8">
                                    <div class="alert alert-info">
                                        {{ $verification->reviewer_comments }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Data Comparison -->
    @if($verification->extracted_data || $verification->verification_results)
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Data Comparison</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Extracted Data -->
                            <div class="col-md-6">
                                <h6 class="font-weight-bold text-info mb-3">
                                    <i class="fas fa-file-alt"></i> Extracted from Document
                                </h6>
                                @if($verification->extracted_data)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            @foreach($verification->extracted_data as $key => $value)
                                                <tr>
                                                    <td class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                    <td>{{ $value ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted">No data extracted from document.</p>
                                @endif
                            </div>

                            <!-- Student Data -->
                            <div class="col-md-6">
                                <h6 class="font-weight-bold text-success mb-3">
                                    <i class="fas fa-user"></i> Student Information
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <td class="font-weight-bold">Name</td>
                                            <td>{{ $verification->student->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Father Name</td>
                                            <td>{{ $verification->student->father_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Mother Name</td>
                                            <td>{{ $verification->student->mother_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Date of Birth</td>
                                            <td>{{ $verification->student->dob?->format('d/m/Y') ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Aadhaar</td>
                                            <td>{{ $verification->student->aadhaar ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Class</td>
                                            <td>{{ $verification->student->class?->name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Results -->
                        @if($verification->verification_results)
                            <div class="mt-4">
                                <h6 class="font-weight-bold text-warning mb-3">
                                    <i class="fas fa-search"></i> Verification Results
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Field</th>
                                                <th>Match Status</th>
                                                <th>Confidence</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($verification->verification_results as $field => $result)
                                                <tr>
                                                    <td class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                                    <td>
                                                        @if($result['match'] ?? false)
                                                            <span class="badge badge-success">Match</span>
                                                        @else
                                                            <span class="badge badge-danger">No Match</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(isset($result['confidence']))
                                                            <div class="progress" style="height: 20px;">
                                                                @php
                                                                    $confidence = $result['confidence'];
                                                                    $confidenceClass = $confidence >= 90 ? 'success' : 
                                                                                     ($confidence >= 70 ? 'warning' : 'danger');
                                                                @endphp
                                                                <div class="progress-bar bg-{{ $confidenceClass }}" 
                                                                     style="width: {{ $confidence }}%">
                                                                    {{ number_format($confidence, 1) }}%
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $result['notes'] ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Verification Log -->
    @if($verification->verification_log)
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Verification Log</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($verification->verification_log as $index => $logEntry)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">{{ $logEntry['step'] ?? 'Step ' . ($index + 1) }}</h6>
                                        <p class="timeline-text">{{ $logEntry['message'] ?? $logEntry }}</p>
                                        @if(isset($logEntry['timestamp']))
                                            <small class="text-muted">{{ $logEntry['timestamp'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Action Modals -->
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

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}
</style>
@endpush

@push('scripts')
<script>
function approveVerification() {
    $('#approve-comments').val('');
    $('#approveModal').modal('show');
}

function rejectVerification() {
    $('#reject-comments').val('');
    $('#rejectModal').modal('show');
}

function reprocessVerification() {
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
                url: '{{ route("admin.student-verifications.reprocess", $verification) }}',
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

$(document).ready(function() {
    // Approve form submission
    $('#approve-form').submit(function(e) {
        e.preventDefault();
        
        const comments = $('#approve-comments').val();

        $.ajax({
            url: '{{ route("admin.student-verifications.approve", $verification) }}',
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

    // Reject form submission
    $('#reject-form').submit(function(e) {
        e.preventDefault();
        
        const comments = $('#reject-comments').val();

        if (!comments.trim()) {
            Swal.fire('Error!', 'Please provide a reason for rejection', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("admin.student-verifications.reject", $verification) }}',
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
</script>
@endpush