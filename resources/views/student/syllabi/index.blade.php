@extends('layouts.app')

@section('title', 'Syllabi')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Syllabi</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Course Syllabi</h1>
            <p class="mb-0 text-muted">Access your course syllabi and curriculum information</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="exportSyllabi('pdf')">
                <i class="fas fa-file-pdf me-1"></i>Export PDF
            </button>
            <button class="btn btn-outline-success" onclick="exportSyllabi('excel')">
                <i class="fas fa-file-excel me-1"></i>Export Excel
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
                                Total Syllabi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
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
                                Downloaded
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['downloaded'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-download fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Current Semester
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['current_semester'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                                Recently Updated
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['recent_updates'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter & Search</h6>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search syllabi..." value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject">
                            <option value="">All Subjects</option>
                            @foreach($subjects ?? [] as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class">
                            <option value="">All Classes</option>
                            @foreach($classes ?? [] as $class)
                                <option value="{{ $class->id }}" {{ request('class') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester">
                            <option value="">All Semesters</option>
                            <option value="1" {{ request('semester') == '1' ? 'selected' : '' }}>Semester 1</option>
                            <option value="2" {{ request('semester') == '2' ? 'selected' : '' }}>Semester 2</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Title</option>
                            <option value="subject" {{ request('sort') == 'subject' ? 'selected' : '' }}>Subject</option>
                            <option value="updated_at" {{ request('sort') == 'updated_at' ? 'selected' : '' }}>Last Updated</option>
                            <option value="downloads" {{ request('sort') == 'downloads' ? 'selected' : '' }}>Downloads</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-undo me-1"></i>Reset Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Syllabi List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Available Syllabi</h6>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="toggleView('grid')" id="gridViewBtn">
                    <i class="fas fa-th-large"></i>
                </button>
                <button class="btn btn-sm btn-primary" onclick="toggleView('list')" id="listViewBtn">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(isset($syllabi) && $syllabi->count() > 0)
                <!-- List View -->
                <div id="listView" class="table-responsive">
                    <table class="table table-bordered" id="syllabusTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Semester</th>
                                <th>Teacher</th>
                                <th>Last Updated</th>
                                <th>Downloads</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($syllabi as $syllabus)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            <div>
                                                <div class="fw-bold">{{ $syllabus->title }}</div>
                                                @if($syllabus->description)
                                                    <small class="text-muted">{{ Str::limit($syllabus->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $syllabus->subject->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $syllabus->class->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($syllabus->semester)
                                            <span class="badge bg-info">Semester {{ $syllabus->semester }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $syllabus->teacher->name ?? 'N/A' }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $syllabus->updated_at ? $syllabus->updated_at->format('M d, Y') : 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $syllabus->download_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('student.syllabi.show', $syllabus) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('student.syllabi.download', $syllabus) }}" 
                                               class="btn btn-sm btn-outline-success" title="Download" target="_blank">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Grid View -->
                <div id="gridView" class="row" style="display: none;">
                    @foreach($syllabi as $syllabus)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1">{{ Str::limit($syllabus->title, 30) }}</h6>
                                            <p class="text-muted small mb-2">{{ $syllabus->subject->name ?? 'N/A' }}</p>
                                        </div>
                                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                    </div>
                                    
                                    @if($syllabus->description)
                                        <p class="card-text small text-muted mb-3">
                                            {{ Str::limit($syllabus->description, 80) }}
                                        </p>
                                    @endif
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <small class="text-muted d-block">Class</small>
                                            <span class="fw-bold">{{ $syllabus->class->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Semester</small>
                                            <span class="fw-bold">{{ $syllabus->semester ?? '-' }}</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">Downloads</small>
                                            <span class="fw-bold">{{ $syllabus->download_count ?? 0 }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Teacher: {{ $syllabus->teacher->name ?? 'N/A' }}</small><br>
                                        <small class="text-muted">Updated: {{ $syllabus->updated_at ? $syllabus->updated_at->format('M d, Y') : 'N/A' }}</small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('student.syllabi.show', $syllabus) }}" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                        <a href="{{ route('student.syllabi.download', $syllabus) }}" 
                                           class="btn btn-outline-success btn-sm" target="_blank">
                                            <i class="fas fa-download me-1"></i>Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($syllabi->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $syllabi->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Syllabi Found</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'subject', 'class', 'semester']))
                            No syllabi match your current filters. Try adjusting your search criteria.
                        @else
                            No syllabi are available at the moment.
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'subject', 'class', 'semester']))
                        <button class="btn btn-outline-primary" onclick="resetFilters()">
                            <i class="fas fa-undo me-1"></i>Clear Filters
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Quick Access -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recently Viewed</h6>
                </div>
                <div class="card-body">
                    @if(isset($recentlyViewed) && $recentlyViewed->count() > 0)
                        @foreach($recentlyViewed as $syllabus)
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-file-pdf text-danger me-3"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ Str::limit($syllabus->title, 30) }}</div>
                                    <small class="text-muted">{{ $syllabus->subject->name ?? 'N/A' }}</small>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('student.syllabi.show', $syllabus) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('student.syllabi.download', $syllabus) }}" 
                                       class="btn btn-sm btn-outline-success" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No recently viewed syllabi.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Most Downloaded</h6>
                </div>
                <div class="card-body">
                    @if(isset($mostDownloaded) && $mostDownloaded->count() > 0)
                        @foreach($mostDownloaded as $syllabus)
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-file-pdf text-danger me-3"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ Str::limit($syllabus->title, 30) }}</div>
                                    <small class="text-muted">{{ $syllabus->subject->name ?? 'N/A' }}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary">{{ $syllabus->download_count ?? 0 }}</span>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('student.syllabi.show', $syllabus) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('student.syllabi.download', $syllabus) }}" 
                                           class="btn btn-sm btn-outline-success" target="_blank">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No download statistics available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fc;
}

.badge {
    font-size: 0.75em;
}

#gridView .card {
    transition: all 0.3s ease;
}

#gridView .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.text-muted {
    color: #6c757d !important;
}

@media (max-width: 768px) {
    .d-sm-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable for list view
    if ($('#syllabusTable').length) {
        $('#syllabusTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "searching": false, // We have custom search
            "info": true,
            "lengthChange": false,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": -1 } // Disable ordering on Actions column
            ]
        });
    }
    
    // Auto-submit form on filter change
    $('#subject, #class, #semester, #sort').on('change', function() {
        $('#filterForm').submit();
    });
    
    // Search with debounce
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 500);
    });
});

function toggleView(viewType) {
    if (viewType === 'grid') {
        $('#listView').hide();
        $('#gridView').show();
        $('#listViewBtn').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#gridViewBtn').removeClass('btn-outline-primary').addClass('btn-primary');
        localStorage.setItem('syllabi_view', 'grid');
    } else {
        $('#gridView').hide();
        $('#listView').show();
        $('#gridViewBtn').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#listViewBtn').removeClass('btn-outline-primary').addClass('btn-primary');
        localStorage.setItem('syllabi_view', 'list');
    }
}

function resetFilters() {
    $('#search').val('');
    $('#subject').val('');
    $('#class').val('');
    $('#semester').val('');
    $('#sort').val('title');
    $('#filterForm').submit();
}

function clearSearch() {
    $('#search').val('');
    $('#filterForm').submit();
}

function exportSyllabi(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    const url = '{{ route("student.syllabi.export") }}?' + params.toString();
    window.open(url, '_blank');
}

// Restore view preference
$(document).ready(function() {
    const savedView = localStorage.getItem('syllabi_view');
    if (savedView) {
        toggleView(savedView);
    }
});

// Track syllabus views
function trackView(syllabusId) {
    $.ajax({
        url: '{{ route("student.syllabi.track-view") }}',
        method: 'POST',
        data: {
            syllabus_id: syllabusId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // View tracked successfully
        }
    });
}

// Add click tracking to view links
$(document).on('click', 'a[href*="syllabi"][href*="show"]', function() {
    const href = $(this).attr('href');
    const syllabusId = href.split('/').pop();
    if (syllabusId && !isNaN(syllabusId)) {
        trackView(syllabusId);
    }
});

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl/Cmd + K for search focus
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
        e.preventDefault();
        $('#search').focus();
    }
    
    // Ctrl/Cmd + G for grid view
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 71) {
        e.preventDefault();
        toggleView('grid');
    }
    
    // Ctrl/Cmd + L for list view
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 76) {
        e.preventDefault();
        toggleView('list');
    }
});

// Tooltip initialization
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush