@extends('layouts.app')

@section('title', 'Admit Card Generation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-id-card mr-2"></i>
                        Admit Card Generation
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="exam_select" class="form-label">Select Exam</label>
                            <select id="exam_select" class="form-control select2">
                                <option value="">Choose an exam...</option>
                                @foreach($exams as $exam)
                                    <option value="{{ $exam->id }}" data-class-id="{{ $exam->class_id }}">
                                        {{ $exam->subject }} - {{ $exam->class->name }} {{ $exam->class->section }} 
                                        ({{ $exam->exam_date->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="class_select" class="form-label">Filter by Class (Optional)</label>
                            <select id="class_select" class="form-control select2">
                                <option value="">All classes for selected exam</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->section }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" id="load_students" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Load Students
                                </button>
                                <button type="button" id="bulk_generate" class="btn btn-success" disabled>
                                    <i class="fas fa-file-pdf"></i> Generate All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div id="students_section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Students List</h5>
                            <div>
                                <button type="button" id="select_all" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-check-square"></i> Select All
                                </button>
                                <button type="button" id="deselect_all" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-square"></i> Deselect All
                                </button>
                                <button type="button" id="generate_selected" class="btn btn-sm btn-success" disabled>
                                    <i class="fas fa-file-pdf"></i> Generate Selected
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="students_table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="master_checkbox">
                                        </th>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Father's Name</th>
                                        <th>Status</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="students_tbody">
                                    <!-- Students will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Exam Details -->
                    <div id="exam_details" class="mt-4" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Exam Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Subject:</strong> <span id="exam_subject"></span></p>
                                        <p><strong>Class:</strong> <span id="exam_class"></span></p>
                                        <p><strong>Date:</strong> <span id="exam_date"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Time:</strong> <span id="exam_time"></span></p>
                                        <p><strong>Duration:</strong> <span id="exam_duration"></span></p>
                                        <p><strong>Total Marks:</strong> <span id="exam_marks"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admit Card Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="preview_content">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="download_preview" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let currentExam = null;
    let currentStudents = [];

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Load students when exam is selected
    $('#load_students').click(function() {
        const examId = $('#exam_select').val();
        if (!examId) {
            Swal.fire('Error', 'Please select an exam first', 'error');
            return;
        }

        loadStudents(examId);
    });

    // Master checkbox functionality
    $('#master_checkbox').change(function() {
        const isChecked = $(this).is(':checked');
        $('.student-checkbox').prop('checked', isChecked);
        updateGenerateButton();
    });

    // Select/Deselect all buttons
    $('#select_all').click(function() {
        $('.student-checkbox').prop('checked', true);
        $('#master_checkbox').prop('checked', true);
        updateGenerateButton();
    });

    $('#deselect_all').click(function() {
        $('.student-checkbox').prop('checked', false);
        $('#master_checkbox').prop('checked', false);
        updateGenerateButton();
    });

    // Generate selected admit cards
    $('#generate_selected').click(function() {
        const selectedStudents = getSelectedStudents();
        if (selectedStudents.length === 0) {
            Swal.fire('Error', 'Please select at least one student', 'error');
            return;
        }

        generateAdmitCards(selectedStudents);
    });

    // Bulk generate all admit cards
    $('#bulk_generate').click(function() {
        const examId = $('#exam_select').val();
        const classId = $('#class_select').val();
        
        if (!examId) {
            Swal.fire('Error', 'Please select an exam first', 'error');
            return;
        }

        generateAdmitCards(null, classId);
    });

    function loadStudents(examId) {
        $.ajax({
            url: `/admit-cards/exam-students/${examId}`,
            method: 'GET',
            beforeSend: function() {
                $('#load_students').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            },
            success: function(response) {
                if (response.success) {
                    currentExam = response.data.exam;
                    currentStudents = response.data.students;
                    
                    displayStudents(response.data.students);
                    displayExamDetails(response.data.exam);
                    
                    $('#students_section').show();
                    $('#exam_details').show();
                    $('#bulk_generate').prop('disabled', false);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error', response?.message || 'Failed to load students', 'error');
            },
            complete: function() {
                $('#load_students').prop('disabled', false).html('<i class="fas fa-search"></i> Load Students');
            }
        });
    }

    function displayStudents(students) {
        const tbody = $('#students_tbody');
        tbody.empty();

        students.forEach(student => {
            const row = `
                <tr>
                    <td>
                        <input type="checkbox" class="student-checkbox" value="${student.id}">
                    </td>
                    <td>${student.admission_no}</td>
                    <td>${student.name}</td>
                    <td>${student.class.name} - ${student.class.section}</td>
                    <td>${student.father_name}</td>
                    <td>
                        <span class="badge badge-success">Verified</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info preview-btn" data-student-id="${student.id}">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-sm btn-primary download-single-btn" data-student-id="${student.id}">
                            <i class="fas fa-download"></i> Download
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Bind events for individual actions
        $('.student-checkbox').change(updateGenerateButton);
        $('.preview-btn').click(function() {
            const studentId = $(this).data('student-id');
            previewAdmitCard(studentId);
        });
        $('.download-single-btn').click(function() {
            const studentId = $(this).data('student-id');
            downloadSingleAdmitCard(studentId);
        });
    }

    function displayExamDetails(exam) {
        $('#exam_subject').text(exam.subject);
        $('#exam_class').text(`${exam.class.name} - ${exam.class.section}`);
        $('#exam_date').text(new Date(exam.exam_date).toLocaleDateString());
        $('#exam_time').text(`${exam.start_time} - ${exam.end_time}`);
        $('#exam_duration').text(`${exam.duration} minutes`);
        $('#exam_marks').text(exam.total_marks);
    }

    function updateGenerateButton() {
        const selectedCount = $('.student-checkbox:checked').length;
        $('#generate_selected').prop('disabled', selectedCount === 0);
        
        if (selectedCount > 0) {
            $('#generate_selected').html(`<i class="fas fa-file-pdf"></i> Generate Selected (${selectedCount})`);
        } else {
            $('#generate_selected').html('<i class="fas fa-file-pdf"></i> Generate Selected');
        }
    }

    function getSelectedStudents() {
        const selected = [];
        $('.student-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        return selected;
    }

    function generateAdmitCards(studentIds = null, classId = null) {
        const examId = $('#exam_select').val();
        const data = { exam_id: examId };
        
        if (studentIds) {
            data.student_ids = studentIds;
        }
        if (classId) {
            data.class_id = classId;
        }

        // Create a form and submit for PDF download
        const form = $('<form>', {
            method: 'POST',
            action: '/admit-cards/download-pdf'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: '_token',
            value: $('meta[name="csrf-token"]').attr('content')
        }));

        Object.keys(data).forEach(key => {
            if (Array.isArray(data[key])) {
                data[key].forEach(value => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: `${key}[]`,
                        value: value
                    }));
                });
            } else {
                form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: data[key]
                }));
            }
        });

        $('body').append(form);
        form.submit();
        form.remove();

        Swal.fire('Success', 'Admit cards are being generated and will download shortly', 'success');
    }

    function previewAdmitCard(studentId) {
        const examId = $('#exam_select').val();
        
        $.ajax({
            url: '/admit-cards/preview',
            method: 'POST',
            data: {
                exam_id: examId,
                student_id: studentId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.data);
                    $('#previewModal').modal('show');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error', response?.message || 'Failed to generate preview', 'error');
            }
        });
    }

    function displayPreview(data) {
        const admitCard = data.admit_card;
        const exam = data.exam;
        
        const previewHtml = `
            <div class="admit-card-preview">
                <div class="text-center mb-4">
                    <h4>${data.school_info.name}</h4>
                    <p>${data.school_info.address}</p>
                    <h5 class="text-primary">ADMIT CARD</h5>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td><strong>Admit Card No:</strong></td><td>${admitCard.admit_card_no}</td></tr>
                            <tr><td><strong>Student Name:</strong></td><td>${admitCard.student_name}</td></tr>
                            <tr><td><strong>Admission No:</strong></td><td>${admitCard.admission_no}</td></tr>
                            <tr><td><strong>Class:</strong></td><td>${admitCard.class}</td></tr>
                            <tr><td><strong>Father's Name:</strong></td><td>${admitCard.father_name}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td><strong>Subject:</strong></td><td>${admitCard.exam_subject}</td></tr>
                            <tr><td><strong>Exam Date:</strong></td><td>${new Date(admitCard.exam_date).toLocaleDateString()}</td></tr>
                            <tr><td><strong>Exam Time:</strong></td><td>${admitCard.exam_time}</td></tr>
                            <tr><td><strong>Duration:</strong></td><td>${admitCard.exam_duration}</td></tr>
                            <tr><td><strong>Total Marks:</strong></td><td>${admitCard.total_marks}</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Instructions:</h6>
                    <ul class="small">
                        ${admitCard.instructions.map(instruction => `<li>${instruction}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
        
        $('#preview_content').html(previewHtml);
        
        // Set download button data
        $('#download_preview').data('exam-id', exam.id).data('student-id', admitCard.student_id);
    }

    function downloadSingleAdmitCard(studentId) {
        const examId = $('#exam_select').val();
        window.open(`/admit-cards/download-single/${examId}/${studentId}`, '_blank');
    }

    // Download from preview modal
    $('#download_preview').click(function() {
        const examId = $(this).data('exam-id');
        const studentId = $(this).data('student-id');
        downloadSingleAdmitCard(studentId);
        $('#previewModal').modal('hide');
    });
});
</script>
@endpush