@extends('layouts.app')

@section('title', 'Create Assignment')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('assignments.index') }}">Assignments</a></li>
                    <li class="breadcrumb-item active">Create Assignment</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Create New Assignment</h2>
                    <p class="text-muted">Create and configure a new assignment for your students</p>
                </div>
                <div>
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Assignments
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="createAssignmentForm" action="{{ route('assignments.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Assignment Title *</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title') }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Assignment Type *</label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="homework" {{ old('type') == 'homework' ? 'selected' : '' }}>Homework</option>
                                        <option value="project" {{ old('type') == 'project' ? 'selected' : '' }}>Project</option>
                                        <option value="quiz" {{ old('type') == 'quiz' ? 'selected' : '' }}>Quiz</option>
                                        <option value="exam" {{ old('type') == 'exam' ? 'selected' : '' }}>Exam</option>
                                        <option value="lab" {{ old('type') == 'lab' ? 'selected' : '' }}>Lab Work</option>
                                        <option value="presentation" {{ old('type') == 'presentation' ? 'selected' : '' }}>Presentation</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Brief description of the assignment">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="instructions" class="form-label">Detailed Instructions</label>
                            <textarea class="form-control @error('instructions') is-invalid @enderror" 
                                      id="instructions" name="instructions" rows="6" 
                                      placeholder="Provide detailed instructions for students">{{ old('instructions') }}</textarea>
                            @error('instructions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class *</label>
                                    <select class="form-select @error('class_id') is-invalid @enderror" 
                                            id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject *</label>
                                    <select class="form-select @error('subject_id') is-invalid @enderror" 
                                            id="subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('subject_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="syllabus_id" class="form-label">Related Syllabus</label>
                                    <select class="form-select @error('syllabus_id') is-invalid @enderror" 
                                            id="syllabus_id" name="syllabus_id">
                                        <option value="">Select Syllabus (Optional)</option>
                                        @foreach($syllabi as $syllabus)
                                            <option value="{{ $syllabus->id }}" {{ old('syllabus_id') == $syllabus->id ? 'selected' : '' }}>
                                                {{ $syllabus->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('syllabus_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="difficulty" class="form-label">Difficulty Level</label>
                                    <select class="form-select @error('difficulty') is-invalid @enderror" 
                                            id="difficulty" name="difficulty">
                                        <option value="">Select Difficulty</option>
                                        <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                                        <option value="medium" {{ old('difficulty') == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
                                    </select>
                                    @error('difficulty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Assignment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="total_marks" class="form-label">Total Marks</label>
                                    <input type="number" class="form-control @error('total_marks') is-invalid @enderror" 
                                           id="total_marks" name="total_marks" value="{{ old('total_marks') }}" 
                                           min="0" step="0.5">
                                    @error('total_marks')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estimated_duration" class="form-label">Estimated Duration (minutes)</label>
                                    <input type="number" class="form-control @error('estimated_duration') is-invalid @enderror" 
                                           id="estimated_duration" name="estimated_duration" value="{{ old('estimated_duration') }}" 
                                           min="0">
                                    @error('estimated_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="submission_type" class="form-label">Submission Type</label>
                                    <select class="form-select @error('submission_type') is-invalid @enderror" 
                                            id="submission_type" name="submission_type">
                                        <option value="online" {{ old('submission_type') == 'online' ? 'selected' : '' }}>Online</option>
                                        <option value="offline" {{ old('submission_type') == 'offline' ? 'selected' : '' }}>Offline</option>
                                        <option value="both" {{ old('submission_type') == 'both' ? 'selected' : '' }}>Both</option>
                                    </select>
                                    @error('submission_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" name="due_date" value="{{ old('due_date') }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_time" class="form-label">Due Time</label>
                                    <input type="time" class="form-control @error('due_time') is-invalid @enderror" 
                                           id="due_time" name="due_time" value="{{ old('due_time') }}">
                                    @error('due_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control @error('tags') is-invalid @enderror" 
                                   id="tags" name="tags" value="{{ old('tags') }}" 
                                   placeholder="Enter tags separated by commas (e.g., algebra, equations, homework)">
                            <small class="text-muted">Tags help in organizing and searching assignments</small>
                            @error('tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Attachment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Attachment</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="attachment" class="form-label">Upload File</label>
                            <input type="file" class="form-control @error('attachment') is-invalid @enderror" 
                                   id="attachment" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar">
                            <small class="text-muted">
                                Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, RAR (Max: 10MB)
                            </small>
                            @error('attachment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div id="filePreview" class="mt-3" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-file"></i> <span id="fileName"></span>
                                <button type="button" class="btn-close float-end" onclick="removeFile()"></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Advanced Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="allow_late_submission" 
                                           name="allow_late_submission" value="1" {{ old('allow_late_submission') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_late_submission">
                                        Allow Late Submissions
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="latePenaltySection" style="display: none;">
                                    <label for="late_penalty_per_day" class="form-label">Late Penalty (% per day)</label>
                                    <input type="number" class="form-control" id="late_penalty_per_day" 
                                           name="late_penalty_per_day" value="{{ old('late_penalty_per_day', 5) }}" 
                                           min="0" max="100" step="0.5">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="visibility" class="form-label">Visibility</label>
                                    <select class="form-select" id="visibility" name="visibility">
                                        <option value="class" {{ old('visibility') == 'class' ? 'selected' : '' }}>Class Only</option>
                                        <option value="public" {{ old('visibility') == 'public' ? 'selected' : '' }}>Public</option>
                                        <option value="private" {{ old('visibility') == 'private' ? 'selected' : '' }}>Private</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="auto_grade" 
                                           name="auto_grade" value="1" {{ old('auto_grade') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_grade">
                                        Enable Auto-Grading (if applicable)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send_notification" 
                                   name="send_notification" value="1" {{ old('send_notification', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="send_notification">
                                Send notification to students when published
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Publishing Options -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Publishing Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="publish_option" 
                                   id="save_draft" value="draft" {{ old('publish_option', 'draft') == 'draft' ? 'checked' : '' }}>
                            <label class="form-check-label" for="save_draft">
                                <strong>Save as Draft</strong>
                                <br><small class="text-muted">Save without publishing to students</small>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="publish_option" 
                                   id="publish_now" value="publish" {{ old('publish_option') == 'publish' ? 'checked' : '' }}>
                            <label class="form-check-label" for="publish_now">
                                <strong>Publish Now</strong>
                                <br><small class="text-muted">Make immediately available to students</small>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="publish_option" 
                                   id="schedule_publish" value="schedule" {{ old('publish_option') == 'schedule' ? 'checked' : '' }}>
                            <label class="form-check-label" for="schedule_publish">
                                <strong>Schedule Publishing</strong>
                                <br><small class="text-muted">Publish at a specific date and time</small>
                            </label>
                        </div>
                        
                        <div id="scheduleSection" style="display: none;">
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="publish_date" class="form-label">Publish Date</label>
                                        <input type="date" class="form-control" id="publish_date" name="publish_date" 
                                               value="{{ old('publish_date') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="publish_time" class="form-label">Publish Time</label>
                                        <input type="time" class="form-control" id="publish_time" name="publish_time" 
                                               value="{{ old('publish_time') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-info" onclick="previewAssignment()">
                                <i class="fas fa-eye"></i> Preview Assignment
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="saveAsDraft()">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="loadTemplate()">
                                <i class="fas fa-file-alt"></i> Load Template
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Help & Tips -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tips for Creating Assignments</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <small>Use clear and specific titles</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <small>Provide detailed instructions</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <small>Set realistic due dates</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <small>Use tags for better organization</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-warning"></i>
                                <small>Include reference materials</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <div>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                        <button type="submit" name="action" value="publish" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Create & Publish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assignment Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.form-check-label strong {
    color: #495057;
}

.card-header h5 {
    color: #495057;
}

.btn-outline-info:hover {
    color: #fff;
}

#filePreview .alert {
    margin-bottom: 0;
}

.list-unstyled li {
    display: flex;
    align-items: center;
}

.list-unstyled li i {
    margin-right: 8px;
    width: 16px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Handle file upload preview
    $('#attachment').change(function() {
        const file = this.files[0];
        if (file) {
            $('#fileName').text(file.name);
            $('#filePreview').show();
        } else {
            $('#filePreview').hide();
        }
    });

    // Handle late submission checkbox
    $('#allow_late_submission').change(function() {
        if ($(this).is(':checked')) {
            $('#latePenaltySection').show();
        } else {
            $('#latePenaltySection').hide();
        }
    });

    // Handle publish option changes
    $('input[name="publish_option"]').change(function() {
        if ($(this).val() === 'schedule') {
            $('#scheduleSection').show();
        } else {
            $('#scheduleSection').hide();
        }
    });

    // Initialize based on old values
    if ($('#allow_late_submission').is(':checked')) {
        $('#latePenaltySection').show();
    }
    
    if ($('#schedule_publish').is(':checked')) {
        $('#scheduleSection').show();
    }

    // Form validation
    $('#createAssignmentForm').submit(function(e) {
        let isValid = true;
        let errors = [];

        // Check required fields
        if (!$('#title').val().trim()) {
            errors.push('Assignment title is required');
            isValid = false;
        }

        if (!$('#type').val()) {
            errors.push('Assignment type is required');
            isValid = false;
        }

        if (!$('#class_id').val()) {
            errors.push('Class selection is required');
            isValid = false;
        }

        if (!$('#subject_id').val()) {
            errors.push('Subject selection is required');
            isValid = false;
        }

        // Check due date if provided
        if ($('#due_date').val() && $('#due_time').val()) {
            const dueDateTime = new Date($('#due_date').val() + ' ' + $('#due_time').val());
            const now = new Date();
            
            if (dueDateTime <= now) {
                errors.push('Due date and time must be in the future');
                isValid = false;
            }
        }

        // Check schedule publishing
        if ($('#schedule_publish').is(':checked')) {
            if (!$('#publish_date').val() || !$('#publish_time').val()) {
                errors.push('Publish date and time are required for scheduled publishing');
                isValid = false;
            } else {
                const publishDateTime = new Date($('#publish_date').val() + ' ' + $('#publish_time').val());
                const now = new Date();
                
                if (publishDateTime <= now) {
                    errors.push('Publish date and time must be in the future');
                    isValid = false;
                }
            }
        }

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
        }
    });
});

function removeFile() {
    $('#attachment').val('');
    $('#filePreview').hide();
}

function previewAssignment() {
    // Collect form data
    const formData = {
        title: $('#title').val(),
        type: $('#type').val(),
        description: $('#description').val(),
        instructions: $('#instructions').val(),
        class_id: $('#class_id').val(),
        subject_id: $('#subject_id').val(),
        total_marks: $('#total_marks').val(),
        due_date: $('#due_date').val(),
        due_time: $('#due_time').val(),
        difficulty: $('#difficulty').val(),
        estimated_duration: $('#estimated_duration').val()
    };

    // Generate preview content
    let previewHtml = `
        <div class="assignment-preview">
            <h3>${formData.title || 'Untitled Assignment'}</h3>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Type:</strong> ${formData.type ? formData.type.charAt(0).toUpperCase() + formData.type.slice(1) : 'Not specified'}
                </div>
                <div class="col-md-6">
                    <strong>Difficulty:</strong> ${formData.difficulty ? formData.difficulty.charAt(0).toUpperCase() + formData.difficulty.slice(1) : 'Not specified'}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Total Marks:</strong> ${formData.total_marks || 'Not specified'}
                </div>
                <div class="col-md-6">
                    <strong>Duration:</strong> ${formData.estimated_duration ? formData.estimated_duration + ' minutes' : 'Not specified'}
                </div>
            </div>
            ${formData.due_date ? `<div class="mb-3"><strong>Due Date:</strong> ${formData.due_date} ${formData.due_time || ''}</div>` : ''}
            ${formData.description ? `<div class="mb-3"><strong>Description:</strong><br>${formData.description}</div>` : ''}
            ${formData.instructions ? `<div class="mb-3"><strong>Instructions:</strong><br><div class="bg-light p-3 rounded">${formData.instructions.replace(/\n/g, '<br>')}</div></div>` : ''}
        </div>
    `;

    $('#previewContent').html(previewHtml);
    $('#previewModal').modal('show');
}

function saveAsDraft() {
    $('input[name="publish_option"][value="draft"]').prop('checked', true);
    $('#createAssignmentForm').submit();
}

function loadTemplate() {
    // This would typically open a modal with predefined templates
    alert('Template loading feature will be implemented in the next phase');
}
</script>
@endpush