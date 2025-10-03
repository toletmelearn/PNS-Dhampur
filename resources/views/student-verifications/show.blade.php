@extends('layouts.app')

@section('title', 'Verification Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Verification Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('student-verifications.index') }}">My Verifications</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $verification->document_type_name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('student-verifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('student-verifications.download', $verification) }}" class="btn btn-info">
                <i class="fas fa-download"></i> Download
            </a>
            @if($verification->verification_status === 'rejected')
                <a href="{{ route('student-verifications.upload') }}" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload New
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Document Preview -->
        <div class="col-lg-8 mb-4">
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
                                <img src="{{ $fileUrl }}" class="img-fluid" style="max-height: 600px;" alt="Document Preview">
                            </div>
                        @elseif($fileExtension === 'pdf')
                            <!-- PDF Preview -->
                            <div class="text-center">
                                <embed src="{{ $fileUrl }}" type="application/pdf" width="100%" height="600px">
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
                            <p class="text-muted">The document file could not be located.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Verification Information -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Verification Status</h6>
                </div>
                <div class="card-body">
                    <!-- Status Badge -->
                    <div class="text-center mb-4">
                        @php
                            $statusConfig = match($verification->verification_status) {
                                'verified' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Verified'],
                                'rejected' => ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Rejected'],
                                'manual_review' => ['class' => 'warning', 'icon' => 'eye', 'text' => 'Under Review'],
                                'processing' => ['class' => 'info', 'icon' => 'spinner fa-spin', 'text' => 'Processing'],
                                default => ['class' => 'secondary', 'icon' => 'clock', 'text' => 'Pending']
                            };
                        @endphp
                        <div class="status-badge bg-{{ $statusConfig['class'] }} text-white p-4 rounded">
                            <i class="fas fa-{{ $statusConfig['icon'] }} fa-3x mb-2"></i>
                            <h4 class="mb-0">{{ $statusConfig['text'] }}</h4>
                        </div>
                    </div>

                    <!-- Document Information -->
                    <div class="mb-3">
                        <strong>Document Type:</strong>
                        <div class="mt-1">
                            <span class="badge badge-info badge-lg">{{ $verification->document_type_name }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Upload Date:</strong>
                        <div class="mt-1">{{ $verification->created_at->format('d/m/Y H:i:s') }}</div>
                        <small class="text-muted">{{ $verification->created_at->diffForHumans() }}</small>
                    </div>

                    @if($verification->file_size)
                        <div class="mb-3">
                            <strong>File Size:</strong>
                            <div class="mt-1">{{ $verification->file_size_formatted }}</div>
                        </div>
                    @endif

                    <!-- Confidence Score -->
                    @if($verification->confidence_score)
                        <div class="mb-3">
                            <strong>Verification Confidence:</strong>
                            <div class="mt-2">
                                <div class="progress" style="height: 25px;">
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
                        </div>
                    @endif

                    <!-- Processing Time -->
                    @if($verification->verification_duration)
                        <div class="mb-3">
                            <strong>Processing Time:</strong>
                            <div class="mt-1">{{ $verification->verification_duration }}</div>
                        </div>
                    @endif

                    <!-- Review Information -->
                    @if($verification->reviewed_by)
                        <div class="mb-3">
                            <strong>Reviewed By:</strong>
                            <div class="mt-1">{{ $verification->reviewer->name }}</div>
                        </div>

                        <div class="mb-3">
                            <strong>Review Date:</strong>
                            <div class="mt-1">{{ $verification->reviewed_at->format('d/m/Y H:i:s') }}</div>
                            <small class="text-muted">{{ $verification->reviewed_at->diffForHumans() }}</small>
                        </div>
                    @endif

                    <!-- Comments -->
                    @if($verification->reviewer_comments)
                        <div class="mb-3">
                            <strong>Comments:</strong>
                            <div class="mt-2">
                                <div class="alert alert-{{ $verification->verification_status === 'rejected' ? 'danger' : 'info' }}">
                                    {{ $verification->reviewer_comments }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Upload -->
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Document Uploaded</h6>
                                <p class="timeline-text">Document uploaded for verification</p>
                                <small class="text-muted">{{ $verification->created_at->format('d/m/Y H:i:s') }}</small>
                            </div>
                        </div>

                        <!-- Processing -->
                        @if($verification->verification_status !== 'pending')
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Processing Started</h6>
                                    <p class="timeline-text">Automated verification process initiated</p>
                                    <small class="text-muted">{{ $verification->created_at->addMinutes(1)->format('d/m/Y H:i:s') }}</small>
                                </div>
                            </div>
                        @endif

                        <!-- Manual Review -->
                        @if(in_array($verification->verification_status, ['manual_review', 'verified', 'rejected']))
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Manual Review</h6>
                                    <p class="timeline-text">Document sent for manual review</p>
                                    <small class="text-muted">{{ $verification->updated_at->format('d/m/Y H:i:s') }}</small>
                                </div>
                            </div>
                        @endif

                        <!-- Final Status -->
                        @if(in_array($verification->verification_status, ['verified', 'rejected']))
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{ $verification->verification_status === 'verified' ? 'success' : 'danger' }}"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">
                                        {{ $verification->verification_status === 'verified' ? 'Verified' : 'Rejected' }}
                                    </h6>
                                    <p class="timeline-text">
                                        Document {{ $verification->verification_status === 'verified' ? 'successfully verified' : 'rejected' }}
                                        @if($verification->reviewer_comments)
                                            <br><em>"{{ $verification->reviewer_comments }}"</em>
                                        @endif
                                    </p>
                                    @if($verification->reviewed_at)
                                        <small class="text-muted">{{ $verification->reviewed_at->format('d/m/Y H:i:s') }}</small>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('student-verifications.download', $verification) }}" 
                           class="btn btn-outline-info">
                            <i class="fas fa-download"></i> Download Document
                        </a>
                        
                        @if($verification->verification_status === 'rejected')
                            <a href="{{ route('student-verifications.upload') }}" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-upload"></i> Upload New Document
                            </a>
                        @endif
                        
                        <a href="{{ route('student-verifications.index') }}" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-list"></i> View All Verifications
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger" onclick="deleteVerification()">
                            <i class="fas fa-trash"></i> Delete Verification
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Extracted Data (if available) -->
    @if($verification->extracted_data)
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Extracted Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Field</th>
                                        <th>Extracted Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($verification->extracted_data as $key => $value)
                                        <tr>
                                            <td class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td>{{ $value ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-badge {
    border-radius: 15px !important;
}

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
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
    height: calc(100% + 25px);
    background-color: #e3e6f0;
}

.timeline-content {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 8px;
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

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endpush

@push('scripts')
<script>
function deleteVerification() {
    $('#deleteModal').modal('show');
}

function confirmDelete() {
    $.ajax({
        url: '{{ route("student-verifications.destroy", $verification) }}',
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
                window.location.href = '{{ route("student-verifications.index") }}';
            });
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire('Error!', response.message || 'An error occurred', 'error');
        }
    });
}
</script>
@endpush