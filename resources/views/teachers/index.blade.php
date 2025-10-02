@extends('layouts.app')

@section('title', 'Teacher Management - PNS Dhampur')

@push('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
    .teacher-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        background: white;
    }

    .teacher-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .teacher-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: bold;
        margin: 0 auto 1rem;
    }

    .teacher-profile-card {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 15px;
        margin-bottom: 1rem;
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 15px;
        color: white;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        transform: translate(30px, -30px);
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
    }

    .department-badge {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .experience-badge {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .qualification-tag {
        background: #f1f5f9;
        color: #475569;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        margin: 0.25rem;
        display: inline-block;
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

    .class-assignment-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .class-assignment-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    @media (max-width: 768px) {
        .teacher-profile-card {
            padding: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
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
                    <h1 class="h2 mb-1">Teacher Management</h1>
                    <p class="text-muted mb-0">Manage teaching staff, assignments, and professional development</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignClassModal">
                        <i class="fas fa-chalkboard me-2"></i>Assign Classes
                    </button>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class="fas fa-plus me-2"></i>Add New Teacher
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-chalkboard-teacher fa-2x mb-3"></i>
                    <h3 class="mb-1">89</h3>
                    <p class="mb-0">Total Teachers</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-user-check fa-2x mb-3"></i>
                    <h3 class="mb-1">82</h3>
                    <p class="mb-0">Active Teachers</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-graduation-cap fa-2x mb-3"></i>
                    <h3 class="mb-1">67</h3>
                    <p class="mb-0">Class Teachers</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-award fa-2x mb-3"></i>
                    <h3 class="mb-1">15</h3>
                    <p class="mb-0">Senior Faculty</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Cards Grid -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="teacher-card">
                <div class="teacher-profile-card">
                    <div class="teacher-avatar">DR</div>
                    <h5 class="mb-1">Dr. Rajesh Kumar</h5>
                    <p class="text-muted mb-2">Principal & Mathematics</p>
                    <div class="department-badge mb-2">Mathematics Department</div>
                    <div class="experience-badge">25+ Years Experience</div>
                </div>
                <div class="p-3">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <h6 class="mb-0">5</h6>
                            <small class="text-muted">Classes</small>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0">156</h6>
                            <small class="text-muted">Students</small>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0">98%</h6>
                            <small class="text-muted">Attendance</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="qualification-tag">M.Sc Mathematics</div>
                        <div class="qualification-tag">B.Ed</div>
                        <div class="qualification-tag">Ph.D</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="viewTeacher(1)">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-outline-warning flex-fill" onclick="editTeacher(1)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="teacher-card">
                <div class="teacher-profile-card">
                    <div class="teacher-avatar" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">PS</div>
                    <h5 class="mb-1">Priya Sharma</h5>
                    <p class="text-muted mb-2">English Literature</p>
                    <div class="department-badge mb-2" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">English Department</div>
                    <div class="experience-badge">12 Years Experience</div>
                </div>
                <div class="p-3">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <h6 class="mb-0">4</h6>
                            <small class="text-muted">Classes</small>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0">128</h6>
                            <small class="text-muted">Students</small>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0">95%</h6>
                            <small class="text-muted">Attendance</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="qualification-tag">M.A English</div>
                        <div class="qualification-tag">B.Ed</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="viewTeacher(2)">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-outline-warning flex-fill" onclick="editTeacher(2)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="teacher-card">
                <div class="teacher-profile-card">
                    <div class="teacher-avatar" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">AG</div>
                    <h5 class="mb-1">Amit Gupta</h5>
                    <p class="text-muted mb-2">Physics & Chemistry</p>
                    <div class="department-badge mb-2" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">Science Department</div>
                    <div class="experience-badge">8 Years Experience</div>
                </div>
                <div class="p-3">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <h6 class="mb-0">6</h6>
                            <small class="text-muted">Classes</small>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0">189</h6>
                            <small class="text-muted">Students</small>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0">97%</h6>
                            <small class="text-muted">Attendance</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="qualification-tag">M.Sc Physics</div>
                        <div class="qualification-tag">B.Ed</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="viewTeacher(3)">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-outline-warning flex-fill" onclick="editTeacher(3)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teachers Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">All Teachers</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="teachersTable">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Department</th>
                                    <th>Classes Assigned</th>
                                    <th>Experience</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="teacher-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">DR</div>
                                            <div>
                                                <h6 class="mb-0">Dr. Rajesh Kumar</h6>
                                                <small class="text-muted">EMP001 • Principal</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="department-badge">Mathematics</span></td>
                                    <td>
                                        <div>
                                            <span class="badge bg-primary me-1">10-A</span>
                                            <span class="badge bg-primary me-1">10-B</span>
                                            <span class="badge bg-primary">11-A</span>
                                        </div>
                                    </td>
                                    <td><span class="experience-badge">25+ Years</span></td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">+91 98765 43210</div>
                                            <small class="text-muted">rajesh@pnsdhampur.edu</small>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewTeacher(1)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editTeacher(1)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="assignClasses(1)">
                                                <i class="fas fa-chalkboard"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="teacher-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">PS</div>
                                            <div>
                                                <h6 class="mb-0">Priya Sharma</h6>
                                                <small class="text-muted">EMP002 • Senior Teacher</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="department-badge" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">English</span></td>
                                    <td>
                                        <div>
                                            <span class="badge bg-primary me-1">9-A</span>
                                            <span class="badge bg-primary me-1">9-B</span>
                                            <span class="badge bg-primary">10-A</span>
                                        </div>
                                    </td>
                                    <td><span class="experience-badge">12 Years</span></td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold">+91 87654 32109</div>
                                            <small class="text-muted">priya@pnsdhampur.edu</small>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewTeacher(2)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editTeacher(2)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="assignClasses(2)">
                                                <i class="fas fa-chalkboard"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Teacher</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTeacherForm">
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
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" name="employee_id">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department *</label>
                            <select class="form-select" name="department" required>
                                <option value="">Select Department</option>
                                <option value="mathematics">Mathematics</option>
                                <option value="english">English</option>
                                <option value="science">Science</option>
                                <option value="social_studies">Social Studies</option>
                                <option value="hindi">Hindi</option>
                                <option value="computer_science">Computer Science</option>
                                <option value="physical_education">Physical Education</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Qualification *</label>
                            <input type="text" class="form-control" name="qualification" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Experience (Years)</label>
                            <input type="number" class="form-control" name="experience" min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number *</label>
                            <input type="tel" class="form-control" name="contact_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Joining</label>
                            <input type="date" class="form-control" name="joining_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salary</label>
                            <input type="number" class="form-control" name="salary" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document *</label>
                        <input type="file" class="form-control" name="document" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveTeacher()">Save Teacher</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Classes Modal -->
<div class="modal fade" id="assignClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Classes to Teacher</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignClassForm">
                    <div class="mb-3">
                        <label class="form-label">Select Teacher *</label>
                        <select class="form-select" name="teacher_id" required>
                            <option value="">Choose Teacher</option>
                            <option value="1">Dr. Rajesh Kumar - Mathematics</option>
                            <option value="2">Priya Sharma - English</option>
                            <option value="3">Amit Gupta - Science</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Available Classes</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="class-assignment-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1-A" id="class1A">
                                        <label class="form-check-label" for="class1A">
                                            <strong>Class 1-A</strong>
                                            <br><small class="text-muted">35 students</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="class-assignment-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1-B" id="class1B">
                                        <label class="form-check-label" for="class1B">
                                            <strong>Class 1-B</strong>
                                            <br><small class="text-muted">32 students</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="class-assignment-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="2-A" id="class2A">
                                        <label class="form-check-label" for="class2A">
                                            <strong>Class 2-A</strong>
                                            <br><small class="text-muted">38 students</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="class-assignment-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="2-B" id="class2B">
                                        <label class="form-check-label" for="class2B">
                                            <strong>Class 2-B</strong>
                                            <br><small class="text-muted">36 students</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveClassAssignment()">Assign Classes</button>
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
$(document).ready(function() {
    // Initialize DataTable
    $('#teachersTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});

function viewTeacher(id) {
    // Fetch teacher details via AJAX
    $.ajax({
        url: '/teachers/' + id,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            // Create a modal to display teacher details
            var modalHtml = `
                <div class="modal fade" id="viewTeacherModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Teacher Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> ${response.user.name}</p>
                                        <p><strong>Email:</strong> ${response.user.email}</p>
                                        <p><strong>Phone:</strong> ${response.user.phone || 'N/A'}</p>
                                        <p><strong>Employee ID:</strong> ${response.employee_id || 'N/A'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Department:</strong> ${response.department || 'N/A'}</p>
                                        <p><strong>Qualification:</strong> ${response.qualification || 'N/A'}</p>
                                        <p><strong>Experience:</strong> ${response.experience_years || 0} years</p>
                                        <p><strong>Salary:</strong> ₹${response.salary || 'N/A'}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <p><strong>Address:</strong> ${response.address || 'N/A'}</p>
                                        <p><strong>Joining Date:</strong> ${response.joining_date || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#viewTeacherModal').remove();
            
            // Add modal to body and show
            $('body').append(modalHtml);
            $('#viewTeacherModal').modal('show');
        },
        error: function(xhr) {
            alert('Error fetching teacher details: ' + (xhr.responseJSON?.message || 'Unknown error'));
        }
    });
}

function editTeacher(id) {
    // Fetch teacher details for editing
    $.ajax({
        url: '/teachers/' + id,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            // Populate edit form with teacher data
            var modalHtml = `
                <div class="modal fade" id="editTeacherModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Teacher</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editTeacherForm">
                                    <input type="hidden" name="_method" value="PUT">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" class="form-control" name="first_name" value="${response.user.name.split(' ')[0]}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" name="last_name" value="${response.user.name.split(' ').slice(1).join(' ')}" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Employee ID</label>
                                            <input type="text" class="form-control" name="employee_id" value="${response.employee_id || ''}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Department *</label>
                                            <select class="form-select" name="department" required>
                                                <option value="">Select Department</option>
                                                <option value="Mathematics" ${response.department === 'Mathematics' ? 'selected' : ''}>Mathematics</option>
                                                <option value="Science" ${response.department === 'Science' ? 'selected' : ''}>Science</option>
                                                <option value="English" ${response.department === 'English' ? 'selected' : ''}>English</option>
                                                <option value="Hindi" ${response.department === 'Hindi' ? 'selected' : ''}>Hindi</option>
                                                <option value="Social Studies" ${response.department === 'Social Studies' ? 'selected' : ''}>Social Studies</option>
                                                <option value="Physical Education" ${response.department === 'Physical Education' ? 'selected' : ''}>Physical Education</option>
                                                <option value="Art" ${response.department === 'Art' ? 'selected' : ''}>Art</option>
                                                <option value="Music" ${response.department === 'Music' ? 'selected' : ''}>Music</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Qualification *</label>
                                            <input type="text" class="form-control" name="qualification" value="${response.qualification || ''}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Experience (Years)</label>
                                            <input type="number" class="form-control" name="experience" value="${response.experience_years || 0}" min="0">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Contact Number *</label>
                                            <input type="tel" class="form-control" name="contact_number" value="${response.user.phone || ''}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email" value="${response.user.email}" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Salary</label>
                                            <input type="number" class="form-control" name="salary" value="${response.salary || ''}" min="0">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Joining Date</label>
                                            <input type="date" class="form-control" name="joining_date" value="${response.joining_date || ''}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3">${response.address || ''}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Upload Document</label>
                                        <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max: 2MB)</small>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary-custom" onclick="updateTeacher(${id})">Update Teacher</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#editTeacherModal').remove();
            
            // Add modal to body and show
            $('body').append(modalHtml);
            $('#editTeacherModal').modal('show');
        },
        error: function(xhr) {
            alert('Error fetching teacher details: ' + (xhr.responseJSON?.message || 'Unknown error'));
        }
    });
}

function assignClasses(id) {
    $('#assignClassModal').modal('show');
}

function saveTeacher() {
    var form = document.getElementById('addTeacherForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    var formData = new FormData(form);
    
    // Show loading state
    var submitBtn = event.target;
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    $.ajax({
        url: '/teachers',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            // Show success message
            var alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Teacher added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.container-fluid').prepend(alertHtml);
            
            $('#addTeacherModal').modal('hide');
            form.reset();
            
            // Reload page after short delay to show new teacher
            setTimeout(function() {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            var errorMessage = 'Error adding teacher';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join(', ');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            // Show error message
            var alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ${errorMessage}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.modal-body').prepend(alertHtml);
        },
        complete: function() {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function updateTeacher(id) {
    var form = document.getElementById('editTeacherForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    var formData = new FormData(form);
    
    // Show loading state
    var submitBtn = event.target;
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;
    
    $.ajax({
        url: '/teachers/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            // Show success message
            var alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Teacher updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.container-fluid').prepend(alertHtml);
            
            $('#editTeacherModal').modal('hide');
            
            // Reload page after short delay to show updated teacher
            setTimeout(function() {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            var errorMessage = 'Error updating teacher';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join(', ');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            // Show error message
            var alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ${errorMessage}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.modal-body').prepend(alertHtml);
        },
        complete: function() {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function deleteTeacher(id) {
    if (!confirm('Are you sure you want to delete this teacher? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: '/teachers/' + id,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            // Show success message
            var alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Teacher deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.container-fluid').prepend(alertHtml);
            
            // Reload page after short delay
            setTimeout(function() {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            var errorMessage = 'Error deleting teacher';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            alert(errorMessage);
        }
    });
}

function saveClassAssignment() {
    var formData = new FormData(document.getElementById('assignClassForm'));
    
    if (!document.getElementById('assignClassForm').checkValidity()) {
        document.getElementById('assignClassForm').reportValidity();
        return;
    }
    
    alert('Classes assigned successfully!');
    $('#assignClassModal').modal('hide');
    document.getElementById('assignClassForm').reset();
}
</script>
@endpush