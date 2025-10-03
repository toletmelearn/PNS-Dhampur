@extends('layouts.app')

@section('title', $syllabus->title . ' - Syllabus')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('student.syllabi') }}">Syllabi</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($syllabus->title, 30) }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">{{ $syllabus->title }}</h1>
            <p class="mb-0 text-muted">{{ $syllabus->subject->name ?? 'N/A' }} - {{ $syllabus->class->name ?? 'N/A' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.syllabi') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Syllabi
            </a>
            <button class="btn btn-outline-info" onclick="printSyllabus()">
                <i class="fas fa-print me-1"></i>Print
            </button>
            <a href="{{ route('student.syllabi.download', $syllabus) }}" 
               class="btn btn-success" target="_blank" onclick="trackDownload()">
                <i class="fas fa-download me-1"></i>Download
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Syllabus Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Syllabus Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">Subject:</label>
                                <span class="info-value">
                                    <span class="badge bg-primary">{{ $syllabus->subject->name ?? 'N/A' }}</span>
                                </span>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Class:</label>
                                <span class="info-value">{{ $syllabus->class->name ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Semester:</label>
                                <span class="info-value">
                                    @if($syllabus->semester)
                                        <span class="badge bg-info">Semester {{ $syllabus->semester }}</span>
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Academic Year:</label>
                                <span class="info-value">{{ $syllabus->academic_year ?? 'Current' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">Teacher:</label>
                                <span class="info-value">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie me-2 text-muted"></i>
                                        {{ $syllabus->teacher->name ?? 'N/A' }}
                                    </div>
                                </span>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Created:</label>
                                <span class="info-value">
                                    {{ $syllabus->created_at ? $syllabus->created_at->format('M d, Y g:i A') : 'N/A' }}
                                </span>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Last Updated:</label>
                                <span class="info-value">
                                    {{ $syllabus->updated_at ? $syllabus->updated_at->format('M d, Y g:i A') : 'N/A' }}
                                </span>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">File Size:</label>
                                <span class="info-value">
                                    @if($syllabus->file_path && Storage::exists($syllabus->file_path))
                                        {{ number_format(Storage::size($syllabus->file_path) / 1024 / 1024, 2) }} MB
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($syllabus->description)
                        <div class="mb-4">
                            <label class="info-label">Description:</label>
                            <div class="description-content p-3 bg-light rounded">
                                {!! nl2br(e($syllabus->description)) !!}
                            </div>
                        </div>
                    @endif

                    @if($syllabus->objectives)
                        <div class="mb-4">
                            <label class="info-label">Learning Objectives:</label>
                            <div class="objectives-content p-3 bg-light rounded">
                                {!! nl2br(e($syllabus->objectives)) !!}
                            </div>
                        </div>
                    @endif

                    @if($syllabus->topics && is_array($syllabus->topics))
                        <div class="mb-4">
                            <label class="info-label">Topics Covered:</label>
                            <div class="topics-list">
                                @foreach($syllabus->topics as $index => $topic)
                                    <div class="topic-item d-flex align-items-start mb-2">
                                        <span class="topic-number me-3">{{ $index + 1 }}.</span>
                                        <span class="topic-text">{{ $topic }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($syllabus->assessment_methods)
                        <div class="mb-4">
                            <label class="info-label">Assessment Methods:</label>
                            <div class="assessment-content p-3 bg-light rounded">
                                {!! nl2br(e($syllabus->assessment_methods)) !!}
                            </div>
                        </div>
                    @endif

                    @if($syllabus->grading_criteria)
                        <div class="mb-4">
                            <label class="info-label">Grading Criteria:</label>
                            <div class="grading-content p-3 bg-light rounded">
                                {!! nl2br(e($syllabus->grading_criteria)) !!}
                            </div>
                        </div>
                    @endif

                    @if($syllabus->required_materials)
                        <div class="mb-4">
                            <label class="info-label">Required Materials:</label>
                            <div class="materials-content p-3 bg-light rounded">
                                {!! nl2br(e($syllabus->required_materials)) !!}
                            </div>
                        </div>
                    @endif

                    @if($syllabus->references && is_array($syllabus->references))
                        <div class="mb-4">
                            <label class="info-label">References:</label>
                            <div class="references-list">
                                @foreach($syllabus->references as $index => $reference)
                                    <div class="reference-item d-flex align-items-start mb-2">
                                        <span class="reference-number me-3">{{ $index + 1 }}.</span>
                                        <span class="reference-text">{{ $reference }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- File Preview -->
            @if($syllabus->file_path && Storage::exists($syllabus->file_path))
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">File Preview</h6>
                    </div>
                    <div class="card-body">
                        <div class="file-preview-container">
                            @php
                                $fileExtension = pathinfo($syllabus->file_path, PATHINFO_EXTENSION);
                            @endphp
                            
                            @if(in_array(strtolower($fileExtension), ['pdf']))
                                <div class="pdf-preview">
                                    <iframe src="{{ route('student.syllabi.download', $syllabus) }}" 
                                            width="100%" height="600px" 
                                            style="border: 1px solid #ddd; border-radius: 0.375rem;">
                                        <p>Your browser does not support PDFs. 
                                           <a href="{{ route('student.syllabi.download', $syllabus) }}" target="_blank">Download the PDF</a>
                                        </p>
                                    </iframe>
                                </div>
                            @elseif(in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif']))
                                <div class="image-preview text-center">
                                    <img src="{{ route('student.syllabi.download', $syllabus) }}" 
                                         class="img-fluid rounded" 
                                         alt="Syllabus Image"
                                         style="max-height: 600px;">
                                </div>
                            @else
                                <div class="file-info text-center py-5">
                                    <i class="fas fa-file fa-4x text-muted mb-3"></i>
                                    <h5>{{ basename($syllabus->file_path) }}</h5>
                                    <p class="text-muted">Preview not available for this file type.</p>
                                    <a href="{{ route('student.syllabi.download', $syllabus) }}" 
                                       class="btn btn-primary" target="_blank">
                                        <i class="fas fa-download me-1"></i>Download File
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Related Assignments -->
            @if(isset($relatedAssignments) && $relatedAssignments->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Related Assignments</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Assignment</th>
                                        <th>Type</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($relatedAssignments as $assignment)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $assignment->title }}</div>
                                                <small class="text-muted">{{ Str::limit($assignment->description, 50) }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($assignment->type) }}</span>
                                            </td>
                                            <td>
                                                @if($assignment->due_date)
                                                    <span class="text-{{ $assignment->isOverdue() ? 'danger' : 'dark' }}">
                                                        {{ $assignment->due_date->format('M d, Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">No due date</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $submission = $assignment->submissions()->where('student_id', auth()->id())->first();
                                                @endphp
                                                @if($submission)
                                                    @if($submission->is_graded)
                                                        <span class="badge bg-success">Graded</span>
                                                    @else
                                                        <span class="badge bg-info">Submitted</span>
                                                    @endif
                                                @elseif($assignment->isOverdue())
                                                    <span class="badge bg-danger">Overdue</span>
                                                @else
                                                    <span class="badge bg-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('student.assignments.show', $assignment) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('student.syllabi.download', $syllabus) }}" 
                           class="btn btn-success" target="_blank" onclick="trackDownload()">
                            <i class="fas fa-download me-1"></i>Download Syllabus
                        </a>
                        <button class="btn btn-outline-info" onclick="printSyllabus()">
                            <i class="fas fa-print me-1"></i>Print Syllabus
                        </button>
                        <button class="btn btn-outline-secondary" onclick="shareSyllabus()">
                            <i class="fas fa-share me-1"></i>Share
                        </button>
                        <a href="{{ route('student.syllabi') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i>All Syllabi
                        </a>
                        @if($syllabus->teacher && $syllabus->teacher->email)
                            <a href="mailto:{{ $syllabus->teacher->email }}" class="btn btn-outline-warning">
                                <i class="fas fa-envelope me-1"></i>Contact Teacher
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                        <span class="stat-label">
                            <i class="fas fa-eye text-info me-2"></i>Views
                        </span>
                        <span class="stat-value badge bg-info">{{ $syllabus->view_count ?? 0 }}</span>
                    </div>
                    <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                        <span class="stat-label">
                            <i class="fas fa-download text-success me-2"></i>Downloads
                        </span>
                        <span class="stat-value badge bg-success">{{ $syllabus->download_count ?? 0 }}</span>
                    </div>
                    <div class="stat-item d-flex justify-content-between align-items-center mb-3">
                        <span class="stat-label">
                            <i class="fas fa-calendar text-warning me-2"></i>Last Updated
                        </span>
                        <span class="stat-value text-muted small">
                            {{ $syllabus->updated_at ? $syllabus->updated_at->diffForHumans() : 'N/A' }}
                        </span>
                    </div>
                    <div class="stat-item d-flex justify-content-between align-items-center">
                        <span class="stat-label">
                            <i class="fas fa-file text-primary me-2"></i>File Type
                        </span>
                        <span class="stat-value badge bg-primary">
                            {{ strtoupper(pathinfo($syllabus->file_path ?? '', PATHINFO_EXTENSION)) ?: 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Related Syllabi -->
            @if(isset($relatedSyllabi) && $relatedSyllabi->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Related Syllabi</h6>
                    </div>
                    <div class="card-body">
                        @foreach($relatedSyllabi as $related)
                            <div class="related-item d-flex align-items-center mb-3">
                                <i class="fas fa-file-pdf text-danger me-3"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ Str::limit($related->title, 25) }}</div>
                                    <small class="text-muted">{{ $related->subject->name ?? 'N/A' }}</small>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('student.syllabi.show', $related) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('student.syllabi.download', $related) }}" 
                                       class="btn btn-sm btn-outline-success" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Help & Support -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Need Help?</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="fas fa-question-circle me-1"></i>How to Use Syllabi
                        </button>
                        @if($syllabus->teacher && $syllabus->teacher->email)
                            <a href="mailto:{{ $syllabus->teacher->email }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-envelope me-1"></i>Contact Teacher
                            </a>
                        @endif
                        <a href="#" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-life-ring me-1"></i>Technical Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Share Syllabus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="shareUrl" class="form-label">Share URL:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareUrl" 
                               value="{{ route('student.syllabi.show', $syllabus) }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="shareViaEmail()">
                        <i class="fas fa-envelope me-1"></i>Share via Email
                    </button>
                    <button class="btn btn-success" onclick="shareViaWhatsApp()">
                        <i class="fab fa-whatsapp me-1"></i>Share via WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">How to Use Syllabi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Understanding Your Syllabus:</h6>
                <ul>
                    <li>Review learning objectives and topics covered</li>
                    <li>Check assessment methods and grading criteria</li>
                    <li>Note required materials and references</li>
                    <li>Download for offline access</li>
                </ul>
                
                <h6 class="mt-3">Tips:</h6>
                <ul>
                    <li>Print important syllabi for quick reference</li>
                    <li>Check for updates regularly</li>
                    <li>Contact your teacher for clarifications</li>
                    <li>Use related assignments to plan your studies</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.info-label {
    font-weight: 600;
    color: #5a5c69;
    display: inline-block;
    min-width: 120px;
    margin-bottom: 0.25rem;
}

.info-value {
    color: #3a3b45;
}

.info-item {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 0.75rem;
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.description-content,
.objectives-content,
.assessment-content,
.grading-content,
.materials-content {
    line-height: 1.6;
    font-size: 0.95rem;
}

.topic-number,
.reference-number {
    font-weight: 600;
    color: #5a5c69;
    min-width: 30px;
}

.topic-text,
.reference-text {
    flex: 1;
    line-height: 1.5;
}

.stat-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e3e6f0;
}

.stat-item:last-child {
    border-bottom: none;
}

.related-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e3e6f0;
}

.related-item:last-child {
    border-bottom: none;
}

.file-preview-container {
    background: #f8f9fc;
    border-radius: 0.375rem;
    padding: 1rem;
}

.pdf-preview iframe {
    background: white;
}

@media print {
    .btn, .card-header, nav, .d-sm-flex {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .col-lg-4 {
        display: none !important;
    }
    
    .col-lg-8 {
        width: 100% !important;
        max-width: 100% !important;
    }
}

@media (max-width: 768px) {
    .d-sm-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .info-label {
        min-width: auto;
        display: block;
        margin-bottom: 0.25rem;
    }
    
    .d-flex.gap-2 {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Track view
    trackView();
    
    // Auto-refresh statistics
    setInterval(refreshStats, 300000); // Every 5 minutes
});

function trackView() {
    $.ajax({
        url: '{{ route("student.syllabi.track-view") }}',
        method: 'POST',
        data: {
            syllabus_id: {{ $syllabus->id }},
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // View tracked successfully
        }
    });
}

function trackDownload() {
    $.ajax({
        url: '{{ route("student.syllabi.track-download") }}',
        method: 'POST',
        data: {
            syllabus_id: {{ $syllabus->id }},
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // Download tracked successfully
            refreshStats();
        }
    });
}

function refreshStats() {
    $.ajax({
        url: '{{ route("student.syllabi.stats", $syllabus) }}',
        method: 'GET',
        success: function(response) {
            if (response.view_count !== undefined) {
                $('.stat-item:first .stat-value').text(response.view_count);
            }
            if (response.download_count !== undefined) {
                $('.stat-item:nth-child(2) .stat-value').text(response.download_count);
            }
        }
    });
}

function printSyllabus() {
    window.print();
}

function shareSyllabus() {
    $('#shareModal').modal('show');
}

function copyToClipboard() {
    const shareUrl = document.getElementById('shareUrl');
    shareUrl.select();
    shareUrl.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showAlert('success', 'URL copied to clipboard!');
    } catch (err) {
        showAlert('error', 'Failed to copy URL');
    }
}

function shareViaEmail() {
    const subject = encodeURIComponent('Syllabus: {{ $syllabus->title }}');
    const body = encodeURIComponent(`Check out this syllabus: {{ route('student.syllabi.show', $syllabus) }}`);
    window.open(`mailto:?subject=${subject}&body=${body}`);
}

function shareViaWhatsApp() {
    const text = encodeURIComponent(`Check out this syllabus: {{ $syllabus->title }} - {{ route('student.syllabi.show', $syllabus) }}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 3000);
}

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl/Cmd + P for print
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 80) {
        e.preventDefault();
        printSyllabus();
    }
    
    // Ctrl/Cmd + D for download
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 68) {
        e.preventDefault();
        window.open('{{ route("student.syllabi.download", $syllabus) }}', '_blank');
        trackDownload();
    }
    
    // Ctrl/Cmd + S for share
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
        e.preventDefault();
        shareSyllabus();
    }
});

// Lazy load iframe
$(document).ready(function() {
    const iframe = $('iframe');
    if (iframe.length) {
        iframe.on('load', function() {
            $(this).fadeIn();
        });
    }
});
</script>
@endpush