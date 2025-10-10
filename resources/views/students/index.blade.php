@extends('layouts.app')

@section('title', 'Student Management - PNS Dhampur')

@push('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
<style>
    .student-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
    }

    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 15px;
        color: white;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
    }

    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: none;
    }

    .advanced-search-panel {
        background: #f8fafc;
        border-radius: 15px;
        border: 2px dashed #e2e8f0;
        transition: all 0.3s ease;
    }

    .advanced-search-panel.show {
        border-color: #667eea;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .saved-search-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .saved-search-item:hover {
        border-color: #667eea;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .search-stats {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border-radius: 10px;
        padding: 1rem;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .table-responsive {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .badge-status {
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .action-buttons .btn {
        margin: 0 0.25rem;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    .search-box {
        border-radius: 25px;
        border: 2px solid #e2e8f0;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
    }

    .search-box:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .export-dropdown {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        border: none;
        color: white;
    }

    .filter-tag {
        background: #667eea;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
        margin: 0.25rem;
        display: inline-block;
    }

    .filter-tag .remove-filter {
        margin-left: 0.5rem;
        cursor: pointer;
        opacity: 0.8;
    }

    .filter-tag .remove-filter:hover {
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">Student Management</h1>
                    <p class="text-muted mb-0">Manage student records with advanced search and filtering</p>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn export-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportStudents('csv')">
                                <i class="fas fa-file-csv me-2"></i>Export as CSV
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportStudents('excel')">
                                <i class="fas fa-file-excel me-2"></i>Export as Excel
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportStudents('pdf')">
                                <i class="fas fa-file-pdf me-2"></i>Export as PDF
                            </a></li>
                        </ul>
                    </div>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus me-2"></i>Add New Student
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Statistics -->
    @if(isset($searchStats))
    <div class="row mb-4">
        <div class="col-12">
            <div class="search-stats">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4 class="mb-0">{{ $searchStats['total_found'] ?? 0 }}</h4>
                        <small>Students Found</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="mb-0">{{ $searchStats['active_count'] ?? 0 }}</h4>
                        <small>Active Students</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="mb-0">{{ $searchStats['classes_count'] ?? 0 }}</h4>
                        <small>Classes Represented</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="mb-0">{{ number_format($searchStats['search_time'] ?? 0, 2) }}s</h4>
                        <small>Search Time</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Basic Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-body">
                    <form id="searchForm" method="GET">
                        <div class="row align-items-center">
                            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control search-box border-start-0" 
                                           name="search" placeholder="Search by name, admission no, or Aadhaar..." 
                                           value="{{ request('search') }}" id="searchInput">
                                    <div class="search-suggestions" id="searchSuggestions"></div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                                <select class="form-select" name="class_id" id="classFilter">
                                    <option value="">All Classes</option>
                                    @if(isset($filterOptions['classes']))
                                        @foreach($filterOptions['classes'] as $class)
                                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}{{ $class->section ? '-' . $class->section : '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                                <select class="form-select" name="status" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                                <select class="form-select" name="gender" id="genderFilter">
                                    <option value="">All Gender</option>
                                    <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleAdvancedSearch()">
                                        <i class="fas fa-sliders-h"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="clearFilters()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Active Filters Display -->
                        <div id="activeFilters" class="mt-3" style="display: none;">
                            <div class="d-flex flex-wrap align-items-center">
                                <span class="me-2 text-muted">Active Filters:</span>
                                <div id="filterTags"></div>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearAllFilters()">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Search Panel -->
    <div class="row mb-4" id="advancedSearchPanel" style="display: none;">
        <div class="col-12">
            <div class="advanced-search-panel p-4">
                <h5 class="mb-3"><i class="fas fa-search-plus me-2"></i>Advanced Search</h5>
                <form id="advancedSearchForm">
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Age Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="age_min" placeholder="Min" 
                                           value="{{ request('age_min') }}" min="0" max="100">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="age_max" placeholder="Max" 
                                           value="{{ request('age_max') }}" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Date of Birth Range</label>
                            <input type="text" class="form-control" name="dob_range" id="dobRange" 
                                   placeholder="Select date range" readonly>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Admission Date Range</label>
                            <input type="text" class="form-control" name="admission_range" id="admissionRange" 
                                   placeholder="Select date range" readonly>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Verification Status</label>
                            <select class="form-select" name="verification_status">
                                <option value="">All</option>
                                <option value="verified" {{ request('verification_status') == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="pending" {{ request('verification_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="rejected" {{ request('verification_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Father's Name</label>
                            <input type="text" class="form-control" name="father_name" 
                                   value="{{ request('father_name') }}" placeholder="Search by father's name">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" class="form-control" name="mother_name" 
                                   value="{{ request('mother_name') }}" placeholder="Search by mother's name">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" name="contact_number" 
                                   value="{{ request('contact_number') }}" placeholder="Search by contact">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="{{ request('email') }}" placeholder="Search by email">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Has Aadhaar</label>
                            <select class="form-select" name="has_aadhaar">
                                <option value="">All</option>
                                <option value="1" {{ request('has_aadhaar') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('has_aadhaar') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Has Documents</label>
                            <select class="form-select" name="has_documents">
                                <option value="">All</option>
                                <option value="1" {{ request('has_documents') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('has_documents') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" name="sort_by">
                                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                                <option value="admission_no" {{ request('sort_by') == 'admission_no' ? 'selected' : '' }}>Admission No</option>
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date Added</option>
                                <option value="dob" {{ request('sort_by') == 'dob' ? 'selected' : '' }}>Date of Birth</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="form-label">Sort Order</label>
                            <select class="form-select" name="sort_order">
                                <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetAdvancedSearch()">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="saveCurrentSearch()">
                                    <i class="fas fa-save me-2"></i>Save Search
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Saved Searches -->
    @if(isset($savedSearches) && $savedSearches->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bookmark me-2"></i>Saved Searches</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($savedSearches as $savedSearch)
                        <div class="col-md-6 col-lg-4 mb-2">
                            <div class="saved-search-item" onclick="applySavedSearch({{ $savedSearch->id }})">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $savedSearch->name }}</strong>
                                        @if($savedSearch->description)
                                            <br><small class="text-muted">{{ $savedSearch->description }}</small>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-1">
                                        @if($savedSearch->is_default)
                                            <span class="badge bg-primary">Default</span>
                                        @endif
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSavedSearch({{ $savedSearch->id }}, event)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Bulk Operations Panel -->
    <div class="row mb-3" id="bulkOperationsPanel" style="display: none;">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-check-square me-2"></i>
                                <span id="selectedCount">0</span> students selected
                            </h6>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="showBulkAttendanceModal()">
                                <i class="fas fa-calendar-check me-1"></i>Mark Attendance
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="showBulkFeeModal()">
                                <i class="fas fa-money-bill me-1"></i>Collect Fees
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="showBulkDocumentModal()">
                                <i class="fas fa-upload me-1"></i>Upload Documents
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="showBulkStatusModal()">
                                <i class="fas fa-edit me-1"></i>Update Status
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                                <i class="fas fa-times me-1"></i>Clear Selection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="studentsTable">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                            <label class="form-check-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>Student</th>
                                    <th>Roll No.</th>
                                    <th>Class</th>
                                    <th>Parent Contact</th>
                                    <th>Status</th>
                                    <th>Verification</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results ?? [] as $student)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input student-checkbox" type="checkbox" 
                                                       value="{{ $student->id }}" onchange="updateBulkOperations()">
                                                <label class="form-check-label"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="student-avatar me-3">{{ strtoupper(substr($student->name, 0, 2)) }}</div>
                                                <div>
                                                    <h6 class="mb-0">{{ $student->name }}</h6>
                                                    <small class="text-muted">ID: {{ $student->admission_no }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="fw-semibold">{{ $student->roll_number ?? 'N/A' }}</span></td>
                                        <td>
                                            @if($student->classModel)
                                                <span class="badge bg-info">{{ $student->classModel->name }}{{ $student->classModel->section ? '-' . $student->classModel->section : '' }}</span>
                                            @else
                                                <span class="text-muted">Not Assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($student->contact_number)
                                                <span class="fw-semibold">{{ $student->contact_number }}</span>
                                                @if($student->attendance_total_count > 0)
                                                    <br><small class="text-muted">
                                                        Attendance: {{ $student->attendance_present_count }}/{{ $student->attendance_total_count }}
                                                        ({{ round(($student->attendance_present_count / $student->attendance_total_count) * 100, 1) }}%)
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($student->status)
                                                @case('active')
                                                    <span class="badge badge-status bg-success">Active</span>
                                                    @break
                                                @case('inactive')
                                                    <span class="badge badge-status bg-warning">Inactive</span>
                                                    @break
                                                @case('left')
                                                    <span class="badge badge-status bg-danger">Left</span>
                                                    @break
                                                @case('alumni')
                                                    <span class="badge badge-status bg-info">Alumni</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-status bg-secondary">Unknown</span>
                                            @endswitch
                                            @if($student->fees_total_sum > 0)
                                                <br><small class="text-muted">
                                                    Fees: ₹{{ number_format($student->fees_paid_sum ?? 0) }}/₹{{ number_format($student->fees_total_sum) }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($student->verification_status ?? 'pending')
                                                @case('verified')
                                                    <span class="badge bg-success">Verified</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">Unknown</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewStudent({{ $student->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" onclick="editStudent({{ $student->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent({{ $student->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-3x mb-3"></i>
                                                <h5>No students found</h5>
                                                <p>Try adjusting your search criteria or add new students.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(isset($results) && method_exists($results, 'links'))
                        <div class="d-flex justify-content-center mt-4">
                            {{ $results->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Search Modal -->
<div class="modal fade" id="saveSearchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Save Current Search</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="saveSearchForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Search Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_public" id="isPublic">
                            <label class="form-check-label" for="isPublic">
                                Make this search public (visible to other users)
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" id="isDefault">
                            <label class="form-check-label" for="isDefault">
                                Set as my default search
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Search</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize date range pickers
    $('#dobRange, #admissionRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear'
        }
    });

    $('#dobRange, #admissionRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });

    $('#dobRange, #admissionRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // Search suggestions
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                fetchSearchSuggestions(query);
            }, 300);
        } else {
            $('#searchSuggestions').hide();
        }
    });

    // Auto-submit form on filter change
    $('#searchForm select').on('change', function() {
        $('#searchForm').submit();
    });

    // Update active filters display
    updateActiveFilters();
});

function toggleAdvancedSearch() {
    const panel = $('#advancedSearchPanel');
    const isVisible = panel.is(':visible');
    
    if (isVisible) {
        panel.slideUp();
        $('.advanced-search-panel').removeClass('show');
    } else {
        panel.slideDown();
        $('.advanced-search-panel').addClass('show');
    }
}

function fetchSearchSuggestions(query) {
    $.ajax({
        url: '{{ route("students.search-suggestions") }}',
        method: 'GET',
        data: { query: query },
        success: function(suggestions) {
            displaySearchSuggestions(suggestions);
        }
    });
}

function displaySearchSuggestions(suggestions) {
    const container = $('#searchSuggestions');
    container.empty();
    
    if (suggestions.length > 0) {
        const list = $('<div class="list-group position-absolute w-100" style="z-index: 1000;"></div>');
        
        suggestions.forEach(suggestion => {
            const item = $(`<a href="#" class="list-group-item list-group-item-action">${suggestion}</a>`);
            item.on('click', function(e) {
                e.preventDefault();
                $('#searchInput').val(suggestion);
                container.hide();
                $('#searchForm').submit();
            });
            list.append(item);
        });
        
        container.append(list).show();
    } else {
        container.hide();
    }
}

function updateActiveFilters() {
    const filters = [];
    const form = $('#searchForm');
    
    // Check each filter field
    form.find('input, select').each(function() {
        const $field = $(this);
        const value = $field.val();
        const name = $field.attr('name');
        
        if (value && name !== '_token') {
            let label = $field.prev('label').text() || $field.closest('.col-lg-2, .col-md-6').find('label').text() || name;
            if (!label) {
                label = $field.attr('placeholder') || name;
            }
            
            filters.push({
                name: name,
                label: label,
                value: value,
                display: `${label}: ${value}`
            });
        }
    });
    
    const container = $('#filterTags');
    container.empty();
    
    if (filters.length > 0) {
        filters.forEach(filter => {
            const tag = $(`
                <span class="filter-tag">
                    ${filter.display}
                    <span class="remove-filter" onclick="removeFilter('${filter.name}')">&times;</span>
                </span>
            `);
            container.append(tag);
        });
        $('#activeFilters').show();
    } else {
        $('#activeFilters').hide();
    }
}

function removeFilter(fieldName) {
    $(`[name="${fieldName}"]`).val('');
    $('#searchForm').submit();
}

function clearFilters() {
    $('#searchForm')[0].reset();
    $('#searchForm').submit();
}

function clearAllFilters() {
    $('#searchForm')[0].reset();
    $('#advancedSearchForm')[0].reset();
    window.location.href = '{{ route("students.index") }}';
}

function resetAdvancedSearch() {
    $('#advancedSearchForm')[0].reset();
}

function saveCurrentSearch() {
    $('#saveSearchModal').modal('show');
}

function applySavedSearch(searchId) {
    window.location.href = `{{ route("students.index") }}?saved_search_id=${searchId}`;
}

function deleteSavedSearch(searchId, event) {
    event.stopPropagation();
    
    if (confirm('Are you sure you want to delete this saved search?')) {
        $.ajax({
            url: `{{ route("students.saved-searches.destroy", ":id") }}`.replace(':id', searchId),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Failed to delete saved search');
            }
        });
    }
}

function exportStudents(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export_format', format);
    
    const url = '{{ route("students.advanced-search") }}?' + params.toString();
    window.open(url, '_blank');
}

// Save search form submission
$('#saveSearchForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Add current search parameters
    const searchParams = new URLSearchParams(window.location.search);
    for (const [key, value] of searchParams) {
        formData.append(key, value);
    }
    
    $.ajax({
        url: '{{ route("students.save-search") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#saveSearchModal').modal('hide');
            location.reload();
        },
        error: function() {
            alert('Failed to save search');
        }
    });
});

// Advanced search form submission
$('#advancedSearchForm').on('submit', function(e) {
    e.preventDefault();
    
    // Combine basic and advanced search parameters
    const basicForm = $('#searchForm');
    const advancedForm = $('#advancedSearchForm');
    
    const params = new URLSearchParams();
    
    // Add basic search parameters
    basicForm.find('input, select').each(function() {
        const $field = $(this);
        const value = $field.val();
        if (value) {
            params.append($field.attr('name'), value);
        }
    });
    
    // Add advanced search parameters
    advancedForm.find('input, select').each(function() {
        const $field = $(this);
        const value = $field.val();
        if (value) {
            params.append($field.attr('name'), value);
        }
    });
    
    window.location.href = '{{ route("students.index") }}?' + params.toString();
});

function viewStudent(id) {
    window.location.href = `{{ route("students.show", ":id") }}`.replace(':id', id);
}

function editStudent(id) {
    window.location.href = `{{ route("students.edit", ":id") }}`.replace(':id', id);
}

function deleteStudent(id) {
    if (confirm('Are you sure you want to delete this student?')) {
        $.ajax({
            url: `{{ route("students.destroy", ":id") }}`.replace(':id', id),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Failed to delete student');
            }
        });
    }
}

// Bulk Operations JavaScript
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkOperations();
}

function updateBulkOperations() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    const count = checkboxes.length;
    const panel = document.getElementById('bulkOperationsPanel');
    const countSpan = document.getElementById('selectedCount');
    
    countSpan.textContent = count;
    
    if (count > 0) {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.student-checkbox');
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
    selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    updateBulkOperations();
}

function getSelectedStudentIds() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function showBulkAttendanceModal() {
    const selectedIds = getSelectedStudentIds();
    if (selectedIds.length === 0) {
        alert('Please select students first');
        return;
    }
    $('#bulkAttendanceModal').modal('show');
}

function showBulkFeeModal() {
    const selectedIds = getSelectedStudentIds();
    if (selectedIds.length === 0) {
        alert('Please select students first');
        return;
    }
    $('#bulkFeeModal').modal('show');
}

function showBulkDocumentModal() {
    const selectedIds = getSelectedStudentIds();
    if (selectedIds.length === 0) {
        alert('Please select students first');
        return;
    }
    $('#bulkDocumentModal').modal('show');
}

function showBulkStatusModal() {
    const selectedIds = getSelectedStudentIds();
    if (selectedIds.length === 0) {
        alert('Please select students first');
        return;
    }
    $('#bulkStatusModal').modal('show');
}

function processBulkAttendance() {
    const selectedIds = getSelectedStudentIds();
    const date = document.getElementById('attendanceDate').value;
    const status = document.getElementById('attendanceStatus').value;
    
    if (!date || !status) {
        alert('Please fill all required fields');
        return;
    }
    
    $.ajax({
        url: '{{ route("students.bulk-attendance") }}',
        method: 'POST',
        data: {
            student_ids: selectedIds,
            date: date,
            status: status,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#bulkAttendanceModal').modal('hide');
            alert('Attendance marked successfully for ' + selectedIds.length + ' students');
            clearSelection();
        },
        error: function() {
            alert('Failed to mark attendance');
        }
    });
}

function processBulkFeeCollection() {
    const selectedIds = getSelectedStudentIds();
    const amount = document.getElementById('feeAmount').value;
    const type = document.getElementById('feeType').value;
    const dueDate = document.getElementById('feeDueDate').value;
    
    if (!amount || !type) {
        alert('Please fill all required fields');
        return;
    }
    
    $.ajax({
        url: '{{ route("students.bulk-fee-collection") }}',
        method: 'POST',
        data: {
            student_ids: selectedIds,
            amount: amount,
            fee_type: type,
            due_date: dueDate,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#bulkFeeModal').modal('hide');
            alert('Fee collection processed for ' + selectedIds.length + ' students');
            clearSelection();
        },
        error: function() {
            alert('Failed to process fee collection');
        }
    });
}

function processBulkDocumentUpload() {
    const selectedIds = getSelectedStudentIds();
    const formData = new FormData();
    const fileInput = document.getElementById('bulkDocumentFile');
    const documentType = document.getElementById('documentType').value;
    
    if (!fileInput.files[0] || !documentType) {
        alert('Please select a file and document type');
        return;
    }
    
    formData.append('file', fileInput.files[0]);
    formData.append('document_type', documentType);
    formData.append('student_ids', JSON.stringify(selectedIds));
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    
    $.ajax({
        url: '{{ route("students.bulk-document-upload") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#bulkDocumentModal').modal('hide');
            alert('Document uploaded for ' + selectedIds.length + ' students');
            clearSelection();
        },
        error: function() {
            alert('Failed to upload document');
        }
    });
}

function processBulkStatusUpdate() {
    const selectedIds = getSelectedStudentIds();
    const status = document.getElementById('bulkStatus').value;
    const reason = document.getElementById('statusReason').value;
    
    if (!status) {
        alert('Please select a status');
        return;
    }
    
    $.ajax({
        url: '{{ route("students.bulk-status-update") }}',
        method: 'POST',
        data: {
            student_ids: selectedIds,
            status: status,
            reason: reason,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#bulkStatusModal').modal('hide');
            alert('Status updated for ' + selectedIds.length + ' students');
            clearSelection();
            location.reload();
        },
        error: function() {
            alert('Failed to update status');
        }
    });
}
</script>

<!-- Bulk Attendance Modal -->
<div class="modal fade" id="bulkAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Bulk Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="attendanceDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendanceDate" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label for="attendanceStatus" class="form-label">Status</label>
                        <select class="form-select" id="attendanceStatus">
                            <option value="">Select Status</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processBulkAttendance()">Mark Attendance</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Fee Collection Modal -->
<div class="modal fade" id="bulkFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Fee Collection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="feeType" class="form-label">Fee Type</label>
                        <select class="form-select" id="feeType">
                            <option value="">Select Fee Type</option>
                            <option value="tuition">Tuition Fee</option>
                            <option value="admission">Admission Fee</option>
                            <option value="examination">Examination Fee</option>
                            <option value="library">Library Fee</option>
                            <option value="transport">Transport Fee</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feeAmount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="feeAmount" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="feeDueDate" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="feeDueDate">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="processBulkFeeCollection()">Process Fee Collection</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Document Upload Modal -->
<div class="modal fade" id="bulkDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Document Upload</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="documentType" class="form-label">Document Type</label>
                        <select class="form-select" id="documentType">
                            <option value="">Select Document Type</option>
                            <option value="birth_certificate">Birth Certificate</option>
                            <option value="aadhaar">Aadhaar Card</option>
                            <option value="photo">Photograph</option>
                            <option value="transfer_certificate">Transfer Certificate</option>
                            <option value="medical_certificate">Medical Certificate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="bulkDocumentFile" class="form-label">Document File</label>
                        <input type="file" class="form-control" id="bulkDocumentFile" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">This document will be uploaded for all selected students.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="processBulkDocumentUpload()">Upload Document</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Status Update Modal -->
<div class="modal fade" id="bulkStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Status Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="bulkStatus" class="form-label">New Status</label>
                        <select class="form-select" id="bulkStatus">
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="left">Left</option>
                            <option value="alumni">Alumni</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusReason" class="form-label">Reason (Optional)</label>
                        <textarea class="form-control" id="statusReason" rows="3" placeholder="Enter reason for status change"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="processBulkStatusUpdate()">Update Status</button>
            </div>
        </div>
    </div>
</div>

@endpush