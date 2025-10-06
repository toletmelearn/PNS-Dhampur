@extends('layouts.app')

@section('title', 'Holiday Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Holiday Management</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item active">Holidays</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="mdi mdi-calendar-multiple font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Total Holidays</h6>
                            <b>{{ $holidays->count() }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success">
                                <span class="avatar-title">
                                    <i class="mdi mdi-calendar-today font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">This Month</h6>
                            <b>{{ $holidays->filter(function($holiday) { 
                                return $holiday->start_date->month == now()->month && $holiday->start_date->year == now()->year; 
                            })->count() }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title">
                                    <i class="mdi mdi-calendar-clock font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Upcoming</h6>
                            <b>{{ $holidays->filter(function($holiday) { 
                                return $holiday->start_date->isFuture(); 
                            })->count() }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="mdi mdi-repeat font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Recurring</h6>
                            <b>{{ $holidays->where('is_recurring', true)->count() }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Holidays & Events</h5>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="mdi mdi-upload me-1"></i> Import Holidays
                            </button>
                            <a href="{{ route('admin.configuration.holidays.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-1"></i> Add Holiday
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="academicYearFilter">
                                <option value="">All Academic Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ request('academic_year') == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="typeFilter">
                                <option value="">All Types</option>
                                @foreach(\App\Models\Holiday::TYPES as $key => $type)
                                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\Holiday::CATEGORIES as $key => $category)
                                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search holidays..." 
                                       value="{{ request('search') }}">
                                <button class="btn btn-outline-secondary" type="button" onclick="applyFilters()">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Holidays Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Holiday Name</th>
                                    <th>Date Range</th>
                                    <th>Duration</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Academic Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $holiday)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($holiday->color)
                                                <div class="me-2" style="width: 12px; height: 12px; background-color: {{ $holiday->color }}; border-radius: 50%;"></div>
                                            @endif
                                            <div>
                                                <strong>{{ $holiday->name }}</strong>
                                                @if($holiday->is_recurring)
                                                    <i class="mdi mdi-repeat text-info ms-1" title="Recurring Holiday"></i>
                                                @endif
                                                @if($holiday->description)
                                                    <br><small class="text-muted">{{ Str::limit($holiday->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $holiday->getFormattedStartDate() }}</strong>
                                            @if($holiday->start_date->format('Y-m-d') !== $holiday->end_date->format('Y-m-d'))
                                                <br><small class="text-muted">to {{ $holiday->getFormattedEndDate() }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $holiday->getDurationInDays() }} day(s)</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ \App\Models\Holiday::TYPES[$holiday->type] ?? $holiday->type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ \App\Models\Holiday::CATEGORIES[$holiday->category] ?? $holiday->category }}</span>
                                    </td>
                                    <td>
                                        @if($holiday->academicYear)
                                            <span class="text-muted">{{ $holiday->academicYear->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($holiday->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.configuration.holidays.edit', $holiday) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if($holiday->is_recurring)
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="showRecurrenceDetails({{ $holiday->id }})" title="View Recurrence">
                                                <i class="mdi mdi-repeat"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteHoliday({{ $holiday->id }})" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="mdi mdi-calendar-remove font-24 d-block mb-2"></i>
                                            No holidays found. <a href="{{ route('admin.configuration.holidays.create') }}">Add your first holiday</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($holidays->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $holidays->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Holidays Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Holidays</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.configuration.holidays.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                            <option value="">Select Academic Year</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="import_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv" required>
                        <div class="form-text">
                            Upload a CSV file with columns: name, start_date, end_date, type, category, description
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <a href="{{ route('admin.configuration.holidays.template') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="mdi mdi-download me-1"></i> Download Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import Holidays</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this holiday?</p>
                <p class="text-danger"><i class="mdi mdi-alert-circle me-1"></i> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Holiday</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function applyFilters() {
    const params = new URLSearchParams();
    
    const academicYear = document.getElementById('academicYearFilter').value;
    const type = document.getElementById('typeFilter').value;
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    if (academicYear) params.append('academic_year', academicYear);
    if (type) params.append('type', type);
    if (category) params.append('category', category);
    if (status) params.append('status', status);
    if (search) params.append('search', search);
    
    window.location.href = '{{ route("admin.configuration.holidays.index") }}?' + params.toString();
}

function deleteHoliday(holidayId) {
    const form = document.getElementById('deleteForm');
    form.action = `/admin/configuration/holidays/${holidayId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function showRecurrenceDetails(holidayId) {
    // Implementation for showing recurrence details
    // This could open a modal with recurrence pattern information
    alert('Recurrence details for holiday ID: ' + holidayId);
}

// Auto-apply filters on change
document.addEventListener('DOMContentLoaded', function() {
    ['academicYearFilter', 'typeFilter', 'categoryFilter', 'statusFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', applyFilters);
    });
    
    // Search on Enter key
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
});
</script>
@endpush