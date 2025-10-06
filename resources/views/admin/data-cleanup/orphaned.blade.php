@extends('layouts.admin')

@section('title', 'Orphaned Student Records')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-times mr-2"></i>
                        Orphaned Student Records
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.data-cleanup.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-ban"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(count($orphanedData) > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Found <strong>{{ count($orphanedData) }}</strong> orphaned student records that need attention.
                        </div>

                        <!-- Bulk Actions -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form action="{{ route('admin.data-cleanup.fix-orphaned') }}" method="POST" 
                                      onsubmit="return confirm('Are you sure you want to perform this action on all orphaned records?')">
                                    @csrf
                                    <div class="input-group">
                                        <select name="action" class="form-control" required>
                                            <option value="">Select Action</option>
                                            <option value="set_inactive">Set as Inactive</option>
                                            <option value="delete">Delete Records</option>
                                            <option value="assign_default_class">Assign to Default Class</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-tools mr-1"></i>
                                                Apply to All
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-info" onclick="exportOrphanedData()">
                                    <i class="fas fa-download mr-1"></i>
                                    Export Data
                                </button>
                            </div>
                        </div>

                        <!-- Orphaned Records Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="orphanedTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Admission No</th>
                                        <th>Aadhaar</th>
                                        <th>Class ID</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orphanedData as $student)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="student-checkbox" value="{{ $student->id }}">
                                            </td>
                                            <td>{{ $student->id }}</td>
                                            <td>
                                                <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                                @if($student->email)
                                                    <br><small class="text-muted">{{ $student->email }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $student->admission_no }}</span>
                                            </td>
                                            <td>
                                                @if($student->aadhaar)
                                                    <code>{{ substr($student->aadhaar, 0, 4) }}****{{ substr($student->aadhaar, -4) }}</code>
                                                @else
                                                    <span class="text-muted">Not provided</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-danger">{{ $student->class_id ?? 'NULL' }}</span>
                                            </td>
                                            <td>
                                                @if($student->status === 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @elseif($student->status === 'inactive')
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @elseif($student->status === 'graduated')
                                                    <span class="badge badge-info">Graduated</span>
                                                @elseif($student->status === 'transferred')
                                                    <span class="badge badge-warning">Transferred</span>
                                                @else
                                                    <span class="badge badge-dark">{{ ucfirst($student->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $student->created_at->format('Y-m-d H:i') }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info btn-sm" 
                                                            onclick="viewStudent({{ $student->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="assignClass({{ $student->id }})">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="deleteStudent({{ $student->id }})"
                                                            title="Delete Student">
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
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i>
                            Great! No orphaned student records found. Your data is clean.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Student details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Class Modal -->
<div class="modal fade" id="assignClassModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Class</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="assignClassForm">
                <div class="modal-body">
                    <input type="hidden" id="studentId" name="student_id">
                    <div class="form-group">
                        <label for="classSelect">Select Class:</label>
                        <select class="form-control" id="classSelect" name="class_id" required>
                            <option value="">Choose a class...</option>
                            <!-- Classes will be loaded dynamically -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Class</button>
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
    $('#orphanedTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[7, 'desc']], // Sort by created_at desc
        columnDefs: [
            { orderable: false, targets: [0, 8] } // Disable sorting for checkbox and actions
        ]
    });

    // Select all checkbox functionality
    $('#selectAll').change(function() {
        $('.student-checkbox').prop('checked', this.checked);
    });

    // Update select all when individual checkboxes change
    $('.student-checkbox').change(function() {
        const total = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });
});

function viewStudent(studentId) {
    $('#studentDetails').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#studentModal').modal('show');
    
    // Load student details via AJAX
    fetch(`/admin/students/${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                $('#studentDetails').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td>${student.first_name} ${student.last_name}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${student.email || 'Not provided'}</td></tr>
                                <tr><td><strong>Phone:</strong></td><td>${student.phone || 'Not provided'}</td></tr>
                                <tr><td><strong>Date of Birth:</strong></td><td>${student.date_of_birth || 'Not provided'}</td></tr>
                                <tr><td><strong>Gender:</strong></td><td>${student.gender || 'Not provided'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Academic Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Admission No:</strong></td><td>${student.admission_no}</td></tr>
                                <tr><td><strong>Class ID:</strong></td><td><span class="badge badge-danger">${student.class_id || 'NULL'}</span></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusColor(student.status)}">${student.status}</span></td></tr>
                                <tr><td><strong>Aadhaar:</strong></td><td>${student.aadhaar ? student.aadhaar.substring(0,4) + '****' + student.aadhaar.substring(8) : 'Not provided'}</td></tr>
                                <tr><td><strong>Created:</strong></td><td>${new Date(student.created_at).toLocaleString()}</td></tr>
                            </table>
                        </div>
                    </div>
                `);
            } else {
                $('#studentDetails').html('<div class="alert alert-danger">Error loading student details.</div>');
            }
        })
        .catch(error => {
            $('#studentDetails').html('<div class="alert alert-danger">Error loading student details.</div>');
        });
}

function assignClass(studentId) {
    $('#studentId').val(studentId);
    
    // Load available classes
    fetch('/admin/classes/active')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let options = '<option value="">Choose a class...</option>';
                data.classes.forEach(cls => {
                    options += `<option value="${cls.id}">${cls.name} - ${cls.section}</option>`;
                });
                $('#classSelect').html(options);
            }
        });
    
    $('#assignClassModal').modal('show');
}

$('#assignClassForm').submit(function(e) {
    e.preventDefault();
    
    const studentId = $('#studentId').val();
    const classId = $('#classSelect').val();
    
    if (!classId) {
        alert('Please select a class');
        return;
    }
    
    // Submit assignment
    fetch(`/admin/students/${studentId}/assign-class`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        body: JSON.stringify({ class_id: classId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#assignClassModal').modal('hide');
            location.reload();
        } else {
            alert('Error assigning class: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error assigning class');
    });
});

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        fetch(`/admin/students/${studentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting student: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting student');
        });
    }
}

function exportOrphanedData() {
    const selectedIds = $('.student-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    let url = '/admin/data-cleanup/export-orphaned';
    if (selectedIds.length > 0) {
        url += '?ids=' + selectedIds.join(',');
    }
    
    window.open(url, '_blank');
}

function getStatusColor(status) {
    switch(status) {
        case 'active': return 'success';
        case 'inactive': return 'secondary';
        case 'graduated': return 'info';
        case 'transferred': return 'warning';
        default: return 'dark';
    }
}
</script>
@endpush