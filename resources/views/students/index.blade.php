@extends('layouts.app')

@section('title', 'Student Management - PNS Dhampur')

@push('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
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

    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        border: none;
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

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .action-buttons .btn {
            margin: 0;
        }
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
                    <p class="text-muted mb-0">Manage student records, enrollment, and academic information</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add New Student
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ $students->total() }}</h3>
                    <p class="mb-0">Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-center">
                    <i class="fas fa-user-plus fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ \App\Models\Student::whereMonth('created_at', now()->month)->count() }}</h3>
                    <p class="mb-0">New This Month</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-center">
                    <i class="fas fa-graduation-cap fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ \App\Models\Student::where('status', 'active')->count() }}</h3>
                    <p class="mb-0">Active Students</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-3"></i>
                    <h3 class="mb-1">{{ $classes->count() }}</h3>
                    <p class="mb-0">Total Classes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control search-box border-start-0" 
                                       placeholder="Search students..." id="studentSearch"
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <select class="form-select" id="classFilter">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}{{ $class->section ? '-' . $class->section : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                                <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                            <select class="form-select" id="genderFilter">
                                <option value="">All Gender</option>
                                <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-12">
                            <button class="btn btn-outline-primary w-100" onclick="clearFilters()">
                                <i class="fas fa-times me-2"></i>Clear Filters
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
                                    <th>Student</th>
                                    <th>Roll No.</th>
                                    <th>Class</th>
                                    <th>Parent Contact</th>
                                    <th>Status</th>
                                    <th>Fees Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
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
                                                <span class="badge bg-primary">{{ $student->classModel->name }}{{ $student->classModel->section ? '-' . $student->classModel->section : '' }}</span>
                                            @else
                                                <span class="badge bg-secondary">Not Assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold">{{ $student->father_name ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ $student->contact_number ?? 'No contact' }}</small>
                                            </div>
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
                                        </td>
                                        <td><span class="badge badge-status bg-secondary">N/A</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewStudent({{ $student->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editStudent({{ $student->id }})">
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
                                        <td colspan="7" class="text-center py-4">
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
                    @if($students->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} results
                            </div>
                            <div>
                                {{ $students->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control" name="date_of_birth" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            &lt;div class=&quot;form-group&quot;&gt;
                            &lt;label for=&quot;gender&quot;&gt;Gender&lt;/label&gt;
                            &lt;select class=&quot;form-control&quot; id=&quot;gender&quot;&gt;
                            &lt;option value=&quot;male&quot;&gt;Male&lt;/option&gt;
                            &lt;option value=&quot;female&quot;&gt;Female&lt;/option&gt;
                            &lt;option value=&quot;other&quot;&gt;Other&lt;/option&gt;
                            &lt;/select&gt;
                            &lt;/div&gt;
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class *</label>
                            <select class="form-select" name="class" required>
                                <option value="">Select Class</option>
                                <option value="1">Class 1</option>
                                <option value="2">Class 2</option>
                                <option value="3">Class 3</option>
                                <option value="4">Class 4</option>
                                <option value="5">Class 5</option>
                                <option value="6">Class 6</option>
                                <option value="7">Class 7</option>
                                <option value="8">Class 8</option>
                                <option value="9">Class 9</option>
                                <option value="10">Class 10</option>
                                <option value="11">Class 11</option>
                                <option value="12">Class 12</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Roll Number</label>
                            <input type="text" class="form-control" name="roll_number">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Father's Name *</label>
                            <input type="text" class="form-control" name="father_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" class="form-control" name="mother_name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number *</label>
                            <input type="tel" class="form-control" name="contact_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveStudent()">Save Student</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
// Enhanced search and filter functionality
$(document).ready(function() {
    // Search functionality
    $('#studentSearch').on('keyup', function() {
        performSearch();
    });

    // Filter functionality
    $('#classFilter, #statusFilter, #genderFilter').on('change', function() {
        applyFilters();
    });
});

function performSearch() {
    const searchTerm = $('#studentSearch').val();
    const currentUrl = new URL(window.location);
    
    if (searchTerm) {
        currentUrl.searchParams.set('search', searchTerm);
    } else {
        currentUrl.searchParams.delete('search');
    }
    
    // Reset to first page when searching
    currentUrl.searchParams.delete('page');
    
    window.location.href = currentUrl.toString();
}

function applyFilters() {
    const currentUrl = new URL(window.location);
    
    // Get filter values
    const classFilter = $('#classFilter').val();
    const statusFilter = $('#statusFilter').val();
    const genderFilter = $('#genderFilter').val();
    
    // Update URL parameters
    if (classFilter) {
        currentUrl.searchParams.set('class_id', classFilter);
    } else {
        currentUrl.searchParams.delete('class_id');
    }
    
    if (statusFilter) {
        currentUrl.searchParams.set('status', statusFilter);
    } else {
        currentUrl.searchParams.delete('status');
    }
    
    if (genderFilter) {
        currentUrl.searchParams.set('gender', genderFilter);
    } else {
        currentUrl.searchParams.delete('gender');
    }
    
    // Reset to first page when filtering
    currentUrl.searchParams.delete('page');
    
    window.location.href = currentUrl.toString();
}

function clearFilters() {
    const currentUrl = new URL(window.location);
    
    // Clear all filter parameters
    currentUrl.searchParams.delete('search');
    currentUrl.searchParams.delete('class_id');
    currentUrl.searchParams.delete('status');
    currentUrl.searchParams.delete('gender');
    currentUrl.searchParams.delete('page');
    
    window.location.href = currentUrl.toString();
}

function viewStudent(id) {
    // Implement view student functionality
    alert('View student with ID: ' + id);
}

function editStudent(id) {
    // Implement edit student functionality
    alert('Edit student with ID: ' + id);
}

function deleteStudent(id) {
    if (confirm('Are you sure you want to delete this student?')) {
        // Implement delete student functionality
        alert('Delete student with ID: ' + id);
    }
}

function saveStudent() {
    var formData = {
        first_name: $('#firstName').val(),
        last_name: $('#lastName').val(),
        date_of_birth: $('#dob').val(),
        gender: $('#gender').val(),
        'class': $('#class').val(),
        roll_number: $('#rollNumber').val(),
        father_name: $('#fatherName').val(),
        mother_name: $('#motherName').val(),
        contact_number: $('#contact').val(),
        email: $('#email').val(),
        address: $('#address').val(),
    };
    $.ajax({
        type: 'POST',
        url: '/students',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Student saved:', response);
            $('#addStudentModal').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            console.log('Error:', xhr.responseText);
            alert('Error saving student: ' + xhr.responseText);
        }
    });
}
</script>
@endpush