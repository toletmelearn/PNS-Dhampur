@extends('layouts.app')

@section('title', 'SR Register - Bulk Entry')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">SR Register - Bulk Entry</h2>
                    <p class="text-muted mb-0">Enter records for multiple students at once</p>
                </div>
                <div>
                    <a href="{{ route('sr-register.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Records
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Selection Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-cog me-2"></i>Selection Criteria
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('sr-register.bulk-entry') }}" id="selectionForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ $selectedClass == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $selectedSubject == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <input type="text" name="academic_year" id="academic_year" class="form-control" 
                               value="{{ $academicYear }}" placeholder="2024" required>
                    </div>
                    <div class="col-md-2">
                        <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term" id="term" class="form-select" required>
                            <option value="1" {{ $term == '1' ? 'selected' : '' }}>First Term</option>
                            <option value="2" {{ $term == '2' ? 'selected' : '' }}>Second Term</option>
                            <option value="3" {{ $term == '3' ? 'selected' : '' }}>Third Term</option>
                            <option value="annual" {{ $term == 'annual' ? 'selected' : '' }}>Annual</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">
                            <i class="fas fa-search me-2"></i>Load Students
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($students->count() > 0)
        <!-- Bulk Entry Form -->
        <form method="POST" action="{{ route('sr-register.store') }}" id="bulkEntryForm">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass }}">
            <input type="hidden" name="subject_id" value="{{ $selectedSubject }}">
            <input type="hidden" name="academic_year" value="{{ $academicYear }}">
            <input type="hidden" name="term" value="{{ $term }}">

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Student Records
                            <span class="badge bg-primary ms-2">{{ $students->count() }} students</span>
                        </h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="fillAllAttendance()">
                                <i class="fas fa-fill me-1"></i>Fill All Attendance
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="calculateAllGrades()">
                                <i class="fas fa-calculator me-1"></i>Calculate All Grades
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">Student</th>
                                    <th rowspan="2" class="align-middle">Attendance %</th>
                                    <th colspan="4" class="text-center">Marks</th>
                                    <th rowspan="2" class="align-middle">Total</th>
                                    <th rowspan="2" class="align-middle">Grade</th>
                                    <th rowspan="2" class="align-middle">Conduct</th>
                                    <th rowspan="2" class="align-middle">Remarks</th>
                                </tr>
                                <tr>
                                    <th>Theory</th>
                                    <th>Practical</th>
                                    <th>Internal</th>
                                    <th>Project</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    @php
                                        $existingRecord = $existingRecords->get($student->id);
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="records[{{ $index }}][student_id]" value="{{ $student->id }}">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-white fw-bold">{{ substr($student->name, 0, 1) }}</span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $student->name }}</div>
                                                    <small class="text-muted">{{ $student->admission_number }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="records[{{ $index }}][attendance_percentage]" 
                                                   class="form-control form-control-sm attendance-input" 
                                                   min="0" max="100" step="0.1"
                                                   value="{{ $existingRecord->attendance_percentage ?? '' }}"
                                                   placeholder="85.5">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="records[{{ $index }}][theory_marks]" 
                                                   class="form-control form-control-sm marks-input" 
                                                   min="0" max="100" step="0.1"
                                                   value="{{ $existingRecord->theory_marks ?? '' }}"
                                                   data-row="{{ $index }}"
                                                   placeholder="85">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="records[{{ $index }}][practical_marks]" 
                                                   class="form-control form-control-sm marks-input" 
                                                   min="0" max="100" step="0.1"
                                                   value="{{ $existingRecord->practical_marks ?? '' }}"
                                                   data-row="{{ $index }}"
                                                   placeholder="90">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="records[{{ $index }}][internal_assessment]" 
                                                   class="form-control form-control-sm marks-input" 
                                                   min="0" max="100" step="0.1"
                                                   value="{{ $existingRecord->internal_assessment ?? '' }}"
                                                   data-row="{{ $index }}"
                                                   placeholder="88">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="records[{{ $index }}][project_marks]" 
                                                   class="form-control form-control-sm marks-input" 
                                                   min="0" max="100" step="0.1"
                                                   value="{{ $existingRecord->project_marks ?? '' }}"
                                                   data-row="{{ $index }}"
                                                   placeholder="92">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="records[{{ $index }}][total_marks]" 
                                                   class="form-control form-control-sm total-marks" 
                                                   min="0" max="500" step="0.1"
                                                   value="{{ $existingRecord->total_marks ?? '' }}"
                                                   data-row="{{ $index }}"
                                                   readonly>
                                        </td>
                                        <td>
                                            <select name="records[{{ $index }}][grade]" 
                                                    class="form-select form-select-sm grade-select" 
                                                    data-row="{{ $index }}">
                                                <option value="">-</option>
                                                @foreach(['A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F'] as $grade)
                                                    <option value="{{ $grade }}" 
                                                            {{ ($existingRecord->grade ?? '') == $grade ? 'selected' : '' }}>
                                                        {{ $grade }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="records[{{ $index }}][conduct_grade]" 
                                                    class="form-select form-select-sm">
                                                <option value="">-</option>
                                                <option value="A" {{ ($existingRecord->conduct_grade ?? '') == 'A' ? 'selected' : '' }}>A</option>
                                                <option value="B" {{ ($existingRecord->conduct_grade ?? '') == 'B' ? 'selected' : '' }}>B</option>
                                                <option value="C" {{ ($existingRecord->conduct_grade ?? '') == 'C' ? 'selected' : '' }}>C</option>
                                                <option value="D" {{ ($existingRecord->conduct_grade ?? '') == 'D' ? 'selected' : '' }}>D</option>
                                                <option value="E" {{ ($existingRecord->conduct_grade ?? '') == 'E' ? 'selected' : '' }}>E</option>
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="records[{{ $index }}][remarks]" 
                                                      class="form-control form-control-sm" 
                                                      rows="1" 
                                                      placeholder="Optional remarks...">{{ $existingRecord->remarks ?? '' }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Fill in the data for each student. Total marks and grades will be calculated automatically.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Reset Form
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Save All Records
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @elseif($selectedClass && $selectedSubject)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Students Found</h5>
                <p class="text-muted">No students are enrolled in the selected class.</p>
            </div>
        </div>
    @endif
</div>

<!-- Fill All Attendance Modal -->
<div class="modal fade" id="fillAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fill All Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="bulkAttendance" class="form-label">Attendance Percentage</label>
                    <input type="number" id="bulkAttendance" class="form-control" 
                           min="0" max="100" step="0.1" placeholder="85.5">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="applyBulkAttendance()">Apply to All</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .form-control-sm, .form-select-sm {
        font-size: 0.875rem;
    }
    
    .marks-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .total-marks {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    
    .grade-select option {
        font-weight: bold;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-calculate total marks and grades
        document.querySelectorAll('.marks-input').forEach(input => {
            input.addEventListener('input', function() {
                calculateRowTotal(this.dataset.row);
            });
        });
        
        // Auto-submit selection form when changed
        document.querySelectorAll('#class_id, #subject_id').forEach(select => {
            select.addEventListener('change', function() {
                if (document.getElementById('class_id').value && document.getElementById('subject_id').value) {
                    document.getElementById('selectionForm').submit();
                }
            });
        });
    });
    
    function calculateRowTotal(rowIndex) {
        const theory = parseFloat(document.querySelector(`input[name="records[${rowIndex}][theory_marks]"]`).value) || 0;
        const practical = parseFloat(document.querySelector(`input[name="records[${rowIndex}][practical_marks]"]`).value) || 0;
        const internal = parseFloat(document.querySelector(`input[name="records[${rowIndex}][internal_assessment]"]`).value) || 0;
        const project = parseFloat(document.querySelector(`input[name="records[${rowIndex}][project_marks]"]`).value) || 0;
        
        const total = theory + practical + internal + project;
        
        // Update total marks
        const totalInput = document.querySelector(`input[name="records[${rowIndex}][total_marks]"]`);
        totalInput.value = total > 0 ? total.toFixed(1) : '';
        
        // Auto-calculate grade
        const gradeSelect = document.querySelector(`select[name="records[${rowIndex}][grade]"]`);
        if (total > 0) {
            let grade = 'F';
            if (total >= 90) grade = 'A+';
            else if (total >= 80) grade = 'A';
            else if (total >= 70) grade = 'B+';
            else if (total >= 60) grade = 'B';
            else if (total >= 50) grade = 'C+';
            else if (total >= 40) grade = 'C';
            else if (total >= 33) grade = 'D';
            
            gradeSelect.value = grade;
        } else {
            gradeSelect.value = '';
        }
    }
    
    function calculateAllGrades() {
        document.querySelectorAll('.marks-input').forEach(input => {
            if (input.dataset.row) {
                calculateRowTotal(input.dataset.row);
            }
        });
        
        // Show success message
        showToast('Grades calculated for all students', 'success');
    }
    
    function fillAllAttendance() {
        const modal = new bootstrap.Modal(document.getElementById('fillAttendanceModal'));
        modal.show();
    }
    
    function applyBulkAttendance() {
        const attendance = document.getElementById('bulkAttendance').value;
        
        if (attendance) {
            document.querySelectorAll('.attendance-input').forEach(input => {
                input.value = attendance;
            });
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('fillAttendanceModal')).hide();
            
            // Show success message
            showToast(`Attendance set to ${attendance}% for all students`, 'success');
        }
    }
    
    function resetForm() {
        if (confirm('Are you sure you want to reset all entered data?')) {
            document.getElementById('bulkEntryForm').reset();
            showToast('Form has been reset', 'info');
        }
    }
    
    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3000);
    }
    
    // Form validation before submit
    document.getElementById('bulkEntryForm')?.addEventListener('submit', function(e) {
        const hasData = Array.from(document.querySelectorAll('.marks-input, .attendance-input')).some(input => input.value.trim() !== '');
        
        if (!hasData) {
            e.preventDefault();
            alert('Please enter at least some data before saving.');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    });
</script>
@endpush