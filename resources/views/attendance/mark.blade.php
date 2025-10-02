@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Mark Attendance</h2>
                    <p class="text-muted mb-0">Mark individual student attendance for the selected class and date</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Overview
                    </a>
                    <a href="{{ route('attendance.bulk-mark') }}" class="btn btn-success">
                        <i class="fas fa-users me-2"></i>Bulk Mark
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Selection Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="selectionForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="{{ request('date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" 
                                            {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Load Students
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="markAllPresent()" 
                                    id="markAllBtn" style="display: none;">
                                <i class="fas fa-check-double me-1"></i>Mark All Present
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List -->
    <div class="row" id="studentsSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Students List</h5>
                    <div>
                        <span class="badge bg-primary me-2">Total: <span id="totalCount">0</span></span>
                        <span class="badge bg-success me-2">Present: <span id="presentCount">0</span></span>
                        <span class="badge bg-danger me-2">Absent: <span id="absentCount">0</span></span>
                        <span class="badge bg-warning">Late: <span id="lateCount">0</span></span>
                    </div>
                </div>
                <div class="card-body">
                    <form id="attendanceForm">
                        <input type="hidden" id="form_date" name="date">
                        <input type="hidden" id="form_class_id" name="class_id">
                        
                        <div id="studentsContainer">
                            <!-- Students will be loaded here via AJAX -->
                        </div>
                        
                        <div class="text-center mt-4" id="saveSection" style="display: none;">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="row" id="loadingSpinner" style="display: none;">
        <div class="col-12">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading students...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let studentsData = [];

    // Handle selection form submission
    document.getElementById('selectionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const date = document.getElementById('date').value;
        const classId = document.getElementById('class_id').value;
        
        if (!date || !classId) {
            alert('Please select both date and class');
            return;
        }
        
        loadStudents(date, classId);
    });

    // Load students via AJAX
    function loadStudents(date, classId) {
        // Show loading spinner
        document.getElementById('studentsSection').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
        
        // Set form hidden fields
        document.getElementById('form_date').value = date;
        document.getElementById('form_class_id').value = classId;
        
        fetch(`{{ route('attendance.ajax.students-by-class-date') }}?date=${date}&class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    studentsData = data.students;
                    renderStudents(data.students);
                    updateCounts();
                    
                    // Show students section and hide loading
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('studentsSection').style.display = 'block';
                    document.getElementById('markAllBtn').style.display = 'inline-block';
                    document.getElementById('saveSection').style.display = 'block';
                } else {
                    alert('Error loading students: ' + data.message);
                    document.getElementById('loadingSpinner').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading students. Please try again.');
                document.getElementById('loadingSpinner').style.display = 'none';
            });
    }

    // Render students list
    function renderStudents(students) {
        const container = document.getElementById('studentsContainer');
        
        if (students.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No active students found</h5>
                    <p class="text-muted">There are no active students in the selected class.</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="row">';
        
        students.forEach((student, index) => {
            const currentStatus = student.attendance_status || 'present';
            
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card student-card" data-student-id="${student.id}">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-md me-3">
                                    <div class="avatar-title bg-light text-primary rounded-circle">
                                        ${student.name.charAt(0).toUpperCase()}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${student.name}</h6>
                                    <small class="text-muted">Adm. No: ${student.admission_no}</small>
                                </div>
                            </div>
                            
                            <div class="attendance-buttons">
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendance_${student.id}" 
                                           id="present_${student.id}" value="present" 
                                           ${currentStatus === 'present' ? 'checked' : ''}>
                                    <label class="btn btn-outline-success" for="present_${student.id}">
                                        <i class="fas fa-check me-1"></i>Present
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="attendance_${student.id}" 
                                           id="absent_${student.id}" value="absent"
                                           ${currentStatus === 'absent' ? 'checked' : ''}>
                                    <label class="btn btn-outline-danger" for="absent_${student.id}">
                                        <i class="fas fa-times me-1"></i>Absent
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="attendance_${student.id}" 
                                           id="late_${student.id}" value="late"
                                           ${currentStatus === 'late' ? 'checked' : ''}>
                                    <label class="btn btn-outline-warning" for="late_${student.id}">
                                        <i class="fas fa-clock me-1"></i>Late
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Add event listeners to radio buttons
        document.querySelectorAll('input[type="radio"][name^="attendance_"]').forEach(radio => {
            radio.addEventListener('change', updateCounts);
        });
    }

    // Update attendance counts
    function updateCounts() {
        const total = studentsData.length;
        let present = 0, absent = 0, late = 0;
        
        studentsData.forEach(student => {
            const selectedRadio = document.querySelector(`input[name="attendance_${student.id}"]:checked`);
            if (selectedRadio) {
                const status = selectedRadio.value;
                if (status === 'present') present++;
                else if (status === 'absent') absent++;
                else if (status === 'late') late++;
            }
        });
        
        document.getElementById('totalCount').textContent = total;
        document.getElementById('presentCount').textContent = present;
        document.getElementById('absentCount').textContent = absent;
        document.getElementById('lateCount').textContent = late;
    }

    // Mark all students as present
    function markAllPresent() {
        studentsData.forEach(student => {
            const presentRadio = document.getElementById(`present_${student.id}`);
            if (presentRadio) {
                presentRadio.checked = true;
            }
        });
        updateCounts();
    }

    // Handle attendance form submission
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('date', document.getElementById('form_date').value);
        formData.append('class_id', document.getElementById('form_class_id').value);
        
        // Collect attendance data
        const attendanceData = [];
        studentsData.forEach(student => {
            const selectedRadio = document.querySelector(`input[name="attendance_${student.id}"]:checked`);
            if (selectedRadio) {
                attendanceData.push({
                    student_id: student.id,
                    status: selectedRadio.value
                });
            }
        });
        
        formData.append('attendance_data', JSON.stringify(attendanceData));
        
        // Show loading state
        const submitBtn = document.querySelector('#attendanceForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        submitBtn.disabled = true;
        
        fetch('{{ route("attendance.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Attendance saved successfully!');
                window.location.href = '{{ route("attendance.index") }}';
            } else {
                alert('Error saving attendance: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving attendance. Please try again.');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Auto-load students if date and class are already selected
    document.addEventListener('DOMContentLoaded', function() {
        const date = document.getElementById('date').value;
        const classId = document.getElementById('class_id').value;
        
        if (date && classId) {
            loadStudents(date, classId);
        }
    });
</script>
@endsection

@section('styles')
<style>
    .avatar-md {
        width: 50px;
        height: 50px;
    }
    
    .avatar-title {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.2rem;
    }
    
    .student-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    
    .student-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
    
    .btn-check:checked + .btn-outline-success {
        background-color: #198754;
        border-color: #198754;
        color: white;
    }
    
    .btn-check:checked + .btn-outline-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }
    
    .btn-check:checked + .btn-outline-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #000;
    }
    
    .attendance-buttons .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .spinner-border {
        width: 3rem;
        height: 3rem;
    }
</style>
@endsection