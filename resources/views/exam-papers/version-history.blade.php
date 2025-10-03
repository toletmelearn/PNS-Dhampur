@extends('layouts.app')

@section('title', 'Version History - ' . $examPaper->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">
                                <i class="fas fa-history mr-2"></i>Version History
                            </h3>
                            <p class="text-muted mb-0">{{ $examPaper->title }} ({{ $examPaper->paper_code }})</p>
                        </div>
                        <div>
                            <a href="{{ route('exam-papers.show', $examPaper) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Back to Paper
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Version Timeline -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-code-branch mr-2"></i>Version Timeline
                    </h4>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($versions as $version)
                            <div class="time-label">
                                <span class="bg-{{ $version->status === 'approved' ? 'success' : ($version->status === 'rejected' ? 'danger' : 'primary') }}">
                                    {{ $version->created_at->format('M d, Y') }}
                                </span>
                            </div>
                            <div>
                                <i class="fas fa-code-branch bg-{{ $version->status === 'approved' ? 'success' : ($version->status === 'rejected' ? 'danger' : 'primary') }}"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> {{ $version->created_at->format('H:i') }}
                                    </span>
                                    <h3 class="timeline-header">
                                        <strong>Version {{ $version->version_number }}</strong>
                                        @if($version->id === $examPaper->current_version_id)
                                            <span class="badge badge-primary ml-2">Current</span>
                                        @endif
                                        <span class="badge badge-{{ $version->status === 'approved' ? 'success' : ($version->status === 'rejected' ? 'danger' : 'warning') }} ml-2">
                                            {{ ucfirst($version->status) }}
                                        </span>
                                    </h3>
                                    <div class="timeline-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Created by:</strong> {{ $version->creator->name }}<br>
                                                <strong>Status:</strong> {{ ucfirst($version->status) }}<br>
                                                <strong>Difficulty:</strong> {{ ucfirst($version->difficulty_level) }}<br>
                                                @if($version->change_summary)
                                                    <strong>Changes:</strong> {{ $version->change_summary }}<br>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                @if($version->approver)
                                                    <strong>Approved by:</strong> {{ $version->approver->name }}<br>
                                                    <strong>Approved at:</strong> {{ $version->approved_at?->format('M d, Y H:i') }}<br>
                                                @endif
                                                @if($version->rejection_reason)
                                                    <strong>Rejection reason:</strong> {{ $version->rejection_reason }}<br>
                                                @endif
                                                <strong>Checksum:</strong> <code>{{ substr($version->checksum, 0, 16) }}...</code>
                                            </div>
                                        </div>
                                        
                                        @if($version->approval_comments)
                                            <div class="mt-2">
                                                <strong>Approval Comments:</strong>
                                                <div class="alert alert-info mt-1">
                                                    {{ $version->approval_comments }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="timeline-footer">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('exam-papers.download-version', $version) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-download mr-1"></i>Download
                                            </a>
                                            @if($version->id !== $examPaper->current_version_id && auth()->user()->can('manage-exam-papers'))
                                                <form method="POST" action="{{ route('exam-papers.restore-version', [$examPaper, $version]) }}" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('Are you sure you want to restore this version?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-undo mr-1"></i>Restore
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('exam-papers.compare-versions', [$examPaper, $version->id, $examPaper->current_version_id]) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-exchange-alt mr-1"></i>Compare
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No versions found for this exam paper.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Version Statistics -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-code-branch"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Versions</span>
                            <span class="info-box-number">{{ $versions->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Approved</span>
                            <span class="info-box-number">{{ $versions->where('status', 'approved')->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pending</span>
                            <span class="info-box-number">{{ $versions->where('status', 'pending')->count() }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Rejected</span>
                            <span class="info-box-number">{{ $versions->where('status', 'rejected')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 30px;
    width: 4px;
    background: #dee2e6;
}

.timeline > li {
    position: relative;
    margin-right: 10px;
    margin-bottom: 15px;
}

.timeline > li:before,
.timeline > li:after {
    content: " ";
    display: table;
}

.timeline > li:after {
    clear: both;
}

.timeline > li > .timeline-item {
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    border-radius: 3px;
    margin-top: 0;
    background: #fff;
    color: #444;
    margin-left: 60px;
    margin-right: 15px;
    padding: 0;
    position: relative;
}

.timeline > li > .timeline-item > .time {
    color: #999;
    float: right;
    padding: 10px;
    font-size: 12px;
}

.timeline > li > .timeline-item > .timeline-header {
    margin: 0;
    color: #555;
    border-bottom: 1px solid #f4f4f4;
    padding: 10px;
    font-size: 16px;
    line-height: 1.1;
}

.timeline > li > .timeline-item > .timeline-body,
.timeline > li > .timeline-item > .timeline-footer {
    padding: 10px;
}

.timeline > li > .fa,
.timeline > li > .fas,
.timeline > li > .far,
.timeline > li > .fab,
.timeline > li > .fal,
.timeline > li > .fad,
.timeline > li > .glyphicon,
.timeline > li > .ion {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #666;
    background: #d2d6de;
    border-radius: 50%;
    text-align: center;
    left: 18px;
    top: 0;
}

.timeline > .time-label > span {
    font-weight: 600;
    color: #fff;
    border-radius: 4px;
    display: inline-block;
    padding: 5px;
}
</style>
@endpush
@endsection