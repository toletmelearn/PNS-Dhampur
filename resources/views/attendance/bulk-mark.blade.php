@extends('layouts.app')

@section('title', 'Bulk Mark Attendance')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">
                                <i class="fas fa-users-cog me-2"></i>
                                Bulk Mark Attendance
                            </h2>
                            <p class="mb-0 opacity-75">Mark attendance for entire class efficiently</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('attendance.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>
                                Back to Attendance
                            </a>
                            <a href="{{ route('attendance.reports') }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-chart-bar me-1"></i>
                                Reports
                            </a>
                            <a href="{{ route('attendance.analytics') }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-analytics me-1"></i>
                                Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Selection Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter text-primary me-2"></i>
                        Select Class & Date
                    </h5>
                </div>
                <div class="card-body">
                    <form id="classSelectionForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="attendance_date" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt text-primary me-1"></i>
                                Date
                            </label>
                            <input type="date" class="form-control form-control-lg" id="attendance_date" 
                                   name="attendance_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="class_id" class="form-label fw-bold">
                                <i class="fas fa-school text-primary me-1"></i>
                                Class
                            </label>
                            <select class="form-select form-select-lg" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-lg w-100" onclick="loadStudents()">
                                <i class="fas fa-search me-2"></i>
                                Load Students
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="row mb-4" id="quickActionsSection" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success btn-sm w-100" onclick="markAllAs('present')">
                                <i class="fas fa-check-circle me-1"></i>
                                All Present
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="markAllAs('absent')">
                                <i class="fas fa-times-circle me-1"></i>
                                All Absent
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-warning btn-sm w-100" onclick="markAllAs('late')">
                                <i class="fas fa-clock me-1"></i>
                                All Late
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-info btn-sm w-100" onclick="markAllAs('excused')">
                                <i class="fas fa-user-check me-1"></i>
                                All Excused
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-secondary btn-sm w-100" onclick="clearAll()">
                                <i class="fas fa-eraser me-1"></i>
                                Clear All
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveAttendance()">
                                <i class="fas fa-save me-1"></i>
                                Save All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List Section -->
    <div class="row mb-4" id="studentsSection" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users text-primary me-2"></i>
                        Students List
                        <span class="badge bg-primary ms-2" id="studentCount">0</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshStudents()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Refresh
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="previewChanges()">
                            <i class="fas fa-eye me-1"></i>
                            Preview Changes
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" class="form-check-input" id="selectAllStudents" onchange="toggleAllStudents()">
                                    </th>
                                    <th width="10%">Photo</th>
                                    <th width="25%">Student Details</th>
                                    <th width="15%">Current Status</th>
                                    <th width="20%">New Status</th>
                                    <th width="15%">Late Minutes</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                        <h6>No students loaded</h6>
                                        <p class="mb-0">Select a class and date to load students</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="row mb-4" id="summarySection" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-primary" id="totalStudents">0</span>
                                <small class="text-muted">Total Students</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-success" id="presentCount">0</span>
                                <small class="text-muted">Present</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-danger" id="absentCount">0</span>
                                <small class="text-muted">Absent</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-warning" id="lateCount">0</span>
                                <small class="text-muted">Late</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-info" id="excusedCount">0</span>
                                <small class="text-muted">Excused</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex flex-column">
                                <span class="fs-4 fw-bold text-secondary" id="pendingCount">0</span>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Bulk Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action will mark attendance for all students in the selected class.
                    Any existing attendance records for this date will be updated.
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <strong>Date:</strong>
                        <div id="confirm_date" class="text-muted"></div>
                    </div>
                    <div class="col-6">
                        <strong>Class:</strong>
                        <div id="confirm_class" class="text-muted"></div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Status:</strong>
                        <div id="confirm_status" class="text-muted"></div>
                    </div>
                    <div class="col-6">
                        <strong>Students:</strong>
                        <div id="confirm_count" class="text-muted"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmBulkMark()">
                    <i class="fas fa-check me-2"></i>Confirm & Mark
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Initialize all attendance modules
    const notifications = new AttendanceNotifications();
    const validator = new AttendanceValidator();
    const accessibility = new AttendanceAccessibility();
    const performance = new AttendancePerformance();
    const loading = new AttendanceLoading();
    
    // Initialize modules
    validator.init();
    accessibility.init();
    performance.init();
    
    // Store instances globally for other scripts to use
    window.attendanceModules = {
        notifications,
        validator,
        accessibility,
        performance,
        loading
    };
    
    let studentsData = [];
    let currentClassId = null;
    let currentDate = null;
    let hasUnsavedChanges = false;
    let isLoading = false;

    // Load students for selected class and date
    function loadStudents() {
        const date = document.getElementById('attendance_date').value;
        const classId = document.getElementById('class_id').value;
        
        if (!date || !classId) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please select both date and class'
            });
            return;
        }

        currentDate = date;
        currentClassId = classId;
        
        // Show loading
        Swal.fire({
            title: 'Loading Students...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Simulate API call to fetch students
        setTimeout(() => {
            // Mock data - replace with actual API call
            studentsData = generateMockStudents(20);
            renderStudentsTable();
            updateSummary();
            
            // Show sections
            document.getElementById('quickActionsSection').style.display = 'block';
            document.getElementById('studentsSection').style.display = 'block';
            document.getElementById('summarySection').style.display = 'block';
            
            Swal.close();
        }, 1000);
    }

    // Generate mock student data
    function generateMockStudents(count) {
        const students = [];
        const statuses = ['present', 'absent', 'late', 'excused', null];
        const names = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'David Brown', 'Lisa Davis', 'Tom Anderson', 'Emma Taylor'];
        
        for (let i = 1; i <= count; i++) {
            students.push({
                id: i,
                name: names[Math.floor(Math.random() * names.length)] + ' ' + i,
                admission_no: 'ADM' + String(i).padStart(4, '0'),
                father_name: 'Father ' + i,
                photo: `https://ui-avatars.com/api/?name=${encodeURIComponent('Student ' + i)}&background=random`,
                current_status: Math.random() > 0.3 ? statuses[Math.floor(Math.random() * 4)] : null,
                new_status: null,
                late_minutes: 0,
                selected: false
            });
        }
        return students;
    }

    // Render students table
    function renderStudentsTable() {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';
        
        studentsData.forEach((student, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input student-checkbox" 
                           data-student-id="${student.id}" onchange="toggleStudent(${student.id})">
                </td>
                <td>
                    <img src="${student.photo}" alt="${student.name}" 
                         class="rounded-circle" width="40" height="40">
                </td>
                <td>
                    <div>
                        <strong>${student.name}</strong>
                        <br>
                        <small class="text-muted">Adm: ${student.admission_no}</small>
                        <br>
                        <small class="text-muted">Father: ${student.father_name}</small>
                    </div>
                </td>
                <td>
                    ${student.current_status ? getStatusBadge(student.current_status) : '<span class="badge bg-secondary">Not Marked</span>'}
                </td>
                <td>
                    <select class="form-select form-select-sm" onchange="updateStudentStatus(${student.id}, this.value)">
                        <option value="">Select Status</option>
                        <option value="present" ${student.new_status === 'present' ? 'selected' : ''}>Present</option>
                        <option value="absent" ${student.new_status === 'absent' ? 'selected' : ''}>Absent</option>
                        <option value="late" ${student.new_status === 'late' ? 'selected' : ''}>Late</option>
                        <option value="excused" ${student.new_status === 'excused' ? 'selected' : ''}>Excused</option>
                        <option value="sick" ${student.new_status === 'sick' ? 'selected' : ''}>Sick</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           value="${student.late_minutes}" min="0" max="300"
                           ${student.new_status !== 'late' ? 'disabled' : ''}
                           onchange="updateLateMinutes(${student.id}, this.value)">
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                onclick="viewStudentDetails(${student.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="resetStudent(${student.id})" title="Reset">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        document.getElementById('studentCount').textContent = studentsData.length;
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        const badges = {
            'present': '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Present</span>',
            'absent': '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Absent</span>',
            'late': '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Late</span>',
            'excused': '<span class="badge bg-info"><i class="fas fa-user-check me-1"></i>Excused</span>',
            'sick': '<span class="badge bg-secondary"><i class="fas fa-thermometer me-1"></i>Sick</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    // Quick action functions
    function markAllAs(status) {
        studentsData.forEach(student => {
            student.new_status = status;
            if (status !== 'late') {
                student.late_minutes = 0;
            }
        });
        renderStudentsTable();
        updateSummary();
    }

    function clearAll() {
        studentsData.forEach(student => {
            student.new_status = null;
            student.late_minutes = 0;
            student.selected = false;
        });
        renderStudentsTable();
        updateSummary();
        document.getElementById('selectAllStudents').checked = false;
    }

    // Toggle functions
    function toggleAllStudents() {
        const selectAll = document.getElementById('selectAllStudents').checked;
        studentsData.forEach(student => {
            student.selected = selectAll;
        });
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.checked = selectAll;
        });
    }

    function toggleStudent(studentId) {
        const student = studentsData.find(s => s.id === studentId);
        if (student) {
            student.selected = !student.selected;
        }
        
        // Update select all checkbox
        const allSelected = studentsData.every(s => s.selected);
        const someSelected = studentsData.some(s => s.selected);
        const selectAllCheckbox = document.getElementById('selectAllStudents');
        selectAllCheckbox.checked = allSelected;
        selectAllCheckbox.indeterminate = someSelected && !allSelected;
    }

    // Update student status
    function updateStudentStatus(studentId, status) {
        const student = studentsData.find(s => s.id === studentId);
        if (student) {
            student.new_status = status;
            if (status !== 'late') {
                student.late_minutes = 0;
            }
        }
        renderStudentsTable();
        updateSummary();
    }

    // Update late minutes
    function updateLateMinutes(studentId, minutes) {
        const student = studentsData.find(s => s.id === studentId);
        if (student) {
            student.late_minutes = parseInt(minutes) || 0;
        }
    }

    // Reset student
    function resetStudent(studentId) {
        const student = studentsData.find(s => s.id === studentId);
        if (student) {
            student.new_status = null;
            student.late_minutes = 0;
            student.selected = false;
        }
        renderStudentsTable();
        updateSummary();
    }

    // Update summary
    function updateSummary() {
        const total = studentsData.length;
        const present = studentsData.filter(s => s.new_status === 'present').length;
        const absent = studentsData.filter(s => s.new_status === 'absent').length;
        const late = studentsData.filter(s => s.new_status === 'late').length;
        const excused = studentsData.filter(s => s.new_status === 'excused').length;
        const pending = studentsData.filter(s => !s.new_status).length;
        
        document.getElementById('totalStudents').textContent = total;
        document.getElementById('presentCount').textContent = present;
        document.getElementById('absentCount').textContent = absent;
        document.getElementById('lateCount').textContent = late;
        document.getElementById('excusedCount').textContent = excused;
        document.getElementById('pendingCount').textContent = pending;
    }

    // Save attendance
    function saveAttendance() {
        const pendingStudents = studentsData.filter(s => !s.new_status);
        
        if (pendingStudents.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Attendance',
                text: `${pendingStudents.length} students still need attendance status. Continue anyway?`,
                showCancelButton: true,
                confirmButtonText: 'Yes, Save',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    processSaveAttendance();
                }
            });
        } else {
            processSaveAttendance();
        }
    }

    // Process save attendance
    function processSaveAttendance() {
        Swal.fire({
            title: 'Saving Attendance...',
            text: 'Please wait while we save the attendance records.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Simulate API call
        setTimeout(() => {
            const savedCount = studentsData.filter(s => s.new_status).length;
            
            Swal.fire({
                icon: 'success',
                title: 'Attendance Saved!',
                text: `Successfully saved attendance for ${savedCount} students.`,
                confirmButtonText: 'OK'
            }).then(() => {
                // Reset form
                studentsData = [];
                document.getElementById('quickActionsSection').style.display = 'none';
                document.getElementById('studentsSection').style.display = 'none';
                document.getElementById('summarySection').style.display = 'none';
                document.getElementById('classSelectionForm').reset();
            });
        }, 2000);
    }

    // Other utility functions
    function refreshStudents() {
        if (currentDate && currentClassId) {
            loadStudents();
        }
    }

    function previewChanges() {
        const changedStudents = studentsData.filter(s => s.new_status);
        
        if (changedStudents.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'No Changes',
                text: 'No attendance changes to preview.'
            });
            return;
        }
        
        let previewHtml = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Student</th><th>Status</th><th>Late Minutes</th></tr></thead><tbody>';
        
        changedStudents.forEach(student => {
            previewHtml += `
                <tr>
                    <td>${student.name}</td>
                    <td>${getStatusBadge(student.new_status)}</td>
                    <td>${student.late_minutes || 0} min</td>
                </tr>
            `;
        });
        
        previewHtml += '</tbody></table></div>';
        
        Swal.fire({
            title: 'Preview Changes',
            html: previewHtml,
            width: '600px',
            confirmButtonText: 'Close'
        });
    }

    function viewStudentDetails(studentId) {
        const student = studentsData.find(s => s.id === studentId);
        if (!student) return;
        
        Swal.fire({
            title: 'Student Details',
            html: `
                <div class="text-center mb-3">
                    <img src="${student.photo}" alt="${student.name}" class="rounded-circle mb-2" width="80" height="80">
                    <h5>${student.name}</h5>
                    <p class="text-muted">Admission No: ${student.admission_no}</p>
                    <p class="text-muted">Father: ${student.father_name}</p>
                </div>
                <div class="row">
                    <div class="col-6">
                        <strong>Current Status:</strong><br>
                        ${student.current_status ? getStatusBadge(student.current_status) : '<span class="badge bg-secondary">Not Marked</span>'}
                    </div>
                    <div class="col-6">
                        <strong>New Status:</strong><br>
                        ${student.new_status ? getStatusBadge(student.new_status) : '<span class="badge bg-secondary">Not Set</span>'}
                    </div>
                </div>
            `,
            confirmButtonText: 'Close'
        });
    }

    // Enhanced validation with detailed error messages
    function validateForm() {
        const errors = [];
        
        if (!currentDate) {
            errors.push('Please select a date');
        }
        
        if (!currentClassId) {
            errors.push('Please select a class');
        }
        
        // Check if date is in the future
        const selectedDate = new Date(currentDate);
        const today = new Date();
        today.setHours(23, 59, 59, 999);
        
        if (selectedDate > today) {
            errors.push('Cannot mark attendance for future dates');
        }
        
        // Check if date is too old (more than 30 days)
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        if (selectedDate < thirtyDaysAgo) {
            errors.push('Cannot mark attendance for dates older than 30 days');
        }
        
        return errors;
    }

    // Enhanced loadStudents function with better error handling
    function loadStudents() {
        const errors = validateForm();
        
        if (errors.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<ul style="text-align: left;">' + errors.map(error => `<li>${error}</li>`).join('') + '</ul>',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        const date = document.getElementById('attendanceDate').value;
        const classId = document.getElementById('classSelect').value;

        currentDate = date;
        currentClassId = classId;
        
        // Show loading with progress
        let progress = 0;
        const loadingInterval = setInterval(() => {
            progress += 10;
            if (progress <= 100) {
                Swal.update({
                    html: `
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading students... ${progress}%</p>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: ${progress}%" 
                                     aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    `
                });
            }
        }, 100);
        
        Swal.fire({
            title: 'Loading Students',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading students... 0%</p>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%" 
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });
        
        // Simulate API call with error handling
        setTimeout(() => {
            clearInterval(loadingInterval);
            
            try {
                // Simulate potential API errors
                const randomError = Math.random();
                if (randomError < 0.1) { // 10% chance of network error
                    throw new Error('Network connection failed. Please check your internet connection.');
                }
                if (randomError < 0.15) { // 5% chance of server error
                    throw new Error('Server is temporarily unavailable. Please try again later.');
                }
                if (randomError < 0.2) { // 5% chance of no students found
                    throw new Error('No students found for the selected class and date.');
                }
                
                // Mock data - replace with actual API call
                studentsData = generateMockStudents(20);
                
                if (studentsData.length === 0) {
                    throw new Error('No students found for the selected class.');
                }
                
                renderStudentsTable();
                updateSummary();
                
                // Show sections with animation
                const sections = ['quickActionsSection', 'studentsSection', 'summarySection'];
                sections.forEach((sectionId, index) => {
                    setTimeout(() => {
                        const section = document.getElementById(sectionId);
                        section.style.display = 'block';
                        section.style.opacity = '0';
                        section.style.transform = 'translateY(20px)';
                        
                        setTimeout(() => {
                            section.style.transition = 'all 0.3s ease';
                            section.style.opacity = '1';
                            section.style.transform = 'translateY(0)';
                        }, 50);
                    }, index * 100);
                });
                
                Swal.fire({
                    icon: 'success',
                    title: 'Students Loaded Successfully',
                    text: `Found ${studentsData.length} students`,
                    timer: 2000,
                    showConfirmButton: false
                });
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Students',
                    text: error.message,
                    confirmButtonColor: '#dc3545',
                    footer: '<small>If the problem persists, please contact the system administrator.</small>'
                });
            }
        }, 1000);
    }

    // Enhanced student status update with validation
    function updateStudentStatus(studentId, status) {
        try {
            const student = studentsData.find(s => s.id === studentId);
            if (!student) {
                throw new Error('Student not found');
            }
            
            // Validate status
            const validStatuses = ['present', 'absent', 'late', 'excused', 'sick'];
            if (status && !validStatuses.includes(status)) {
                throw new Error('Invalid status selected');
            }
            
            student.new_status = status;
            
            // Handle late minutes field
            const lateMinutesInput = document.querySelector(`input[onchange*="${studentId}"]`);
            if (lateMinutesInput) {
                if (status === 'late') {
                    lateMinutesInput.disabled = false;
                    lateMinutesInput.focus();
                    // Set default late minutes if not set
                    if (student.late_minutes === 0) {
                        student.late_minutes = 15;
                        lateMinutesInput.value = 15;
                    }
                } else {
                    lateMinutesInput.disabled = true;
                    student.late_minutes = 0;
                    lateMinutesInput.value = 0;
                }
            }
            
            updateSummary();
            
            // Show brief success feedback
            const row = lateMinutesInput?.closest('tr');
            if (row) {
                row.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 1000);
            }
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: error.message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }

    // Enhanced late minutes update with validation
    function updateLateMinutes(studentId, minutes) {
        try {
            const student = studentsData.find(s => s.id === studentId);
            if (!student) {
                throw new Error('Student not found');
            }
            
            // Validate minutes
            const minutesNum = parseInt(minutes);
            if (isNaN(minutesNum) || minutesNum < 0 || minutesNum > 300) {
                throw new Error('Late minutes must be between 0 and 300');
            }
            
            student.late_minutes = minutesNum;
            updateSummary();
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Input',
                text: error.message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            
            // Reset to previous valid value
            const input = event.target;
            const student = studentsData.find(s => s.id === studentId);
            if (student) {
                input.value = student.late_minutes;
            }
        }
    }

    // Enhanced save attendance with comprehensive validation
    function saveAttendance() {
        try {
            // Validate form data
            const errors = validateForm();
            if (errors.length > 0) {
                throw new Error(errors.join(', '));
            }
            
            // Check if any students are loaded
            if (!studentsData || studentsData.length === 0) {
                throw new Error('No students loaded. Please load students first.');
            }
            
            // Count students with status changes
            const studentsWithChanges = studentsData.filter(s => s.new_status);
            
            if (studentsWithChanges.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Changes Made',
                    text: 'Please mark attendance for at least one student before saving.',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }
            
            // Check for incomplete late entries
            const incompleteLatEntries = studentsWithChanges.filter(s => 
                s.new_status === 'late' && (!s.late_minutes || s.late_minutes === 0)
            );
            
            if (incompleteLatEntries.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Late Entries',
                    html: `
                        <p>The following students are marked as late but don't have late minutes specified:</p>
                        <ul style="text-align: left; margin: 10px 0;">
                            ${incompleteLatEntries.map(s => `<li>${s.name}</li>`).join('')}
                        </ul>
                        <p>Would you like to continue anyway?</p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    cancelButtonText: 'Fix Entries',
                    confirmButtonColor: '#ffc107'
                }).then((result) => {
                    if (result.isConfirmed) {
                        processSaveAttendance(studentsWithChanges);
                    }
                });
                return;
            }
            
            // Show confirmation dialog
            Swal.fire({
                icon: 'question',
                title: 'Confirm Attendance Submission',
                html: `
                    <div class="text-start">
                        <p><strong>Date:</strong> ${currentDate}</p>
                        <p><strong>Class:</strong> ${document.getElementById('classSelect').selectedOptions[0]?.text}</p>
                        <p><strong>Students to update:</strong> ${studentsWithChanges.length}</p>
                        <hr>
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="badge bg-success fs-6">${studentsWithChanges.filter(s => s.new_status === 'present').length}</div>
                                <small class="d-block">Present</small>
                            </div>
                            <div class="col-3">
                                <div class="badge bg-danger fs-6">${studentsWithChanges.filter(s => s.new_status === 'absent').length}</div>
                                <small class="d-block">Absent</small>
                            </div>
                            <div class="col-3">
                                <div class="badge bg-warning fs-6">${studentsWithChanges.filter(s => s.new_status === 'late').length}</div>
                                <small class="d-block">Late</small>
                            </div>
                            <div class="col-3">
                                <div class="badge bg-info fs-6">${studentsWithChanges.filter(s => s.new_status === 'excused').length}</div>
                                <small class="d-block">Excused</small>
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save Attendance',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    processSaveAttendance(studentsWithChanges);
                }
            });
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: error.message,
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Process save attendance with enhanced error handling
    function processSaveAttendance(studentsWithChanges) {
        let progress = 0;
        const totalSteps = studentsWithChanges.length;
        
        // Show progress dialog
        Swal.fire({
            title: 'Saving Attendance',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Saving...</span>
                    </div>
                    <p>Saving attendance records... <span id="progress-text">0/${totalSteps}</span></p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" id="save-progress"></div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });
        
        // Simulate saving with progress updates
        const saveInterval = setInterval(() => {
            progress++;
            const percentage = (progress / totalSteps) * 100;
            
            document.getElementById('progress-text').textContent = `${progress}/${totalSteps}`;
            document.getElementById('save-progress').style.width = `${percentage}%`;
            
            if (progress >= totalSteps) {
                clearInterval(saveInterval);
                
                // Simulate potential save errors
                const randomError = Math.random();
                if (randomError < 0.05) { // 5% chance of save error
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: 'Failed to save attendance records. Please try again.',
                        confirmButtonColor: '#dc3545',
                        footer: '<small>Error Code: ATT_SAVE_001</small>'
                    });
                    return;
                }
                
                // Success
                Swal.fire({
                    icon: 'success',
                    title: 'Attendance Saved Successfully!',
                    html: `
                        <p>Successfully saved attendance for <strong>${studentsWithChanges.length}</strong> students.</p>
                        <small class="text-muted">Date: ${currentDate}</small>
                    `,
                    confirmButtonText: 'Continue',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    // Reset form
                    resetForm();
                });
            }
        }, 100);
    }

    // Enhanced form reset
    function resetForm() {
        try {
            // Clear form fields
            document.getElementById('attendanceDate').value = '';
            document.getElementById('classSelect').value = '';
            
            // Hide sections
            const sections = ['quickActionsSection', 'studentsSection', 'summarySection'];
            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (section) {
                    section.style.display = 'none';
                }
            });
            
            // Clear data
            studentsData = [];
            currentDate = null;
            currentClassId = null;
            
            // Clear table
            const tbody = document.getElementById('studentsTableBody');
            if (tbody) {
                tbody.innerHTML = '';
            }
            
            // Reset summary
            updateSummary();
            
            // Show success message
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            toast.fire({
                icon: 'info',
                title: 'Form reset successfully'
            });
            
        } catch (error) {
            console.error('Error resetting form:', error);
        }
    }

    function previewBulkMark() {
        if (currentDate && currentClassId) {
            loadStudents();
        }
    }
</script>
        while (tbody.children.length > 5) {
            tbody.removeChild(tbody.lastChild);
        }
    }

    // Auto-preview when form changes
    document.getElementById('date').addEventListener('change', function() {
        if (this.value && document.getElementById('class_id').value) {
            previewBulkMark();
        }
    });

    document.getElementById('class_id').addEventListener('change', function() {
        if (this.value && document.getElementById('date').value) {
            previewBulkMark();
        }
    });

    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (document.getElementById('date').value && document.getElementById('class_id').value) {
                previewBulkMark();
            }
        });
    });
</script>
@endsection

@section('styles')
<style>
    .form-check-label {
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: all 0.3s ease;
    }
    
    .form-check-input:checked + .form-check-label {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    
    .alert {
        border-radius: 0.5rem;
    }
    
    .table th {
        font-weight: 600;
        border-top: none;
        background-color: #f8f9fa;
    }
    
    .btn {
        border-radius: 0.375rem;
    }
    
    .modal-content {
        border-radius: 0.5rem;
    }
</style>
@endsection