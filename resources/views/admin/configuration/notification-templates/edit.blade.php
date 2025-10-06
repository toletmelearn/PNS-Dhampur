@extends('layouts.app')

@section('title', 'Edit Notification Template')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Notification Template</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.notification-templates.index') }}">Notification Templates</a></li>
                        <li class="breadcrumb-item active">Edit Template</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Edit Notification Template</h5>
                        @if($template->is_system)
                            <span class="badge bg-warning">System Template</span>
                        @endif
                    </div>
                    
                    @if($template->is_system)
                        <div class="alert alert-warning">
                            <i class="mdi mdi-alert-circle me-2"></i>
                            This is a system template. Some fields may be restricted from editing to maintain system functionality.
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.configuration.notification-templates.update', $template) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $template->name) }}" 
                                           {{ $template->is_system ? 'readonly' : 'required' }}
                                           placeholder="e.g., Student Admission Confirmation">
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Template Slug <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                           id="slug" name="slug" value="{{ old('slug', $template->slug) }}" 
                                           readonly placeholder="student-admission-confirmation">
                                    <div class="form-text">Auto-generated from template name</div>
                                    @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Template Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" {{ $template->is_system ? 'disabled' : 'required' }}>
                                        <option value="">Select Type</option>
                                        @foreach(\App\Models\NotificationTemplate::TYPES as $key => $type)
                                            <option value="{{ $key }}" {{ old('type', $template->type) == $key ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($template->is_system)
                                        <input type="hidden" name="type" value="{{ $template->type }}">
                                    @endif
                                    @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" name="category" {{ $template->is_system ? 'disabled' : 'required' }}>
                                        <option value="">Select Category</option>
                                        @foreach(\App\Models\NotificationTemplate::CATEGORIES as $key => $category)
                                            <option value="{{ $key }}" {{ old('category', $template->category) == $key ? 'selected' : '' }}>
                                                {{ $category }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($template->is_system)
                                        <input type="hidden" name="category" value="{{ $template->category }}">
                                    @endif
                                    @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Email Subject (only for email templates) -->
                        <div class="mb-3" id="subjectField" style="{{ $template->type === 'email' ? '' : 'display: none;' }}">
                            <label for="subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" value="{{ old('subject', $template->subject) }}"
                                   placeholder="e.g., Welcome to {school_name}">
                            <div class="form-text">You can use variables like {student_name}, {school_name}, etc.</div>
                            @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Template Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('body') is-invalid @enderror" 
                                      id="body" name="body" rows="10" required
                                      placeholder="Enter your template content here...">{{ old('body', $template->body) }}</textarea>
                            <div class="form-text">
                                Use variables to personalize messages. Available variables are shown in the sidebar.
                            </div>
                            @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3"
                                      placeholder="Brief description of when this template is used">{{ old('description', $template->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Advanced Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Advanced Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="variables" class="form-label">Custom Variables</label>
                                            <input type="text" class="form-control @error('variables') is-invalid @enderror" 
                                                   id="variables" name="variables" 
                                                   value="{{ old('variables', is_array($template->variables) ? implode(',', $template->variables) : $template->variables) }}"
                                                   placeholder="variable1,variable2,variable3">
                                            <div class="form-text">Comma-separated list of additional variables (optional)</div>
                                            @error('variables')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="settings" class="form-label">Template Settings (JSON)</label>
                                            <textarea class="form-control @error('settings') is-invalid @enderror" 
                                                      id="settings" name="settings" rows="3"
                                                      placeholder='{"priority": "high", "retry_count": 3}'>{{ old('settings', is_array($template->settings) ? json_encode($template->settings, JSON_PRETTY_PRINT) : $template->settings) }}</textarea>
                                            <div class="form-text">Additional settings in JSON format (optional)</div>
                                            @error('settings')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Template
                                    </label>
                                    <div class="form-text">Only active templates can be used for sending notifications</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.configuration.notification-templates.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Back
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                                    <i class="mdi mdi-eye me-1"></i> Preview
                                </button>
                                <button type="button" class="btn btn-outline-info me-2" id="testBtn">
                                    <i class="mdi mdi-send me-1"></i> Send Test
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Update Template
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Template Statistics -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Template Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary mb-1">{{ $template->created_at->format('M d, Y') }}</h4>
                                <p class="text-muted mb-0">Created</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info mb-1">{{ $template->updated_at->format('M d, Y') }}</h4>
                                <p class="text-muted mb-0">Last Updated</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success mb-1">{{ $template->is_active ? 'Active' : 'Inactive' }}</h4>
                                <p class="text-muted mb-0">Status</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning mb-1">{{ count($template->getAvailableVariables()) }}</h4>
                                <p class="text-muted mb-0">Variables</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variables Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Available Variables</h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="variablesAccordion">
                        <!-- Student Variables -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="studentVariables">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapseStudent" aria-expanded="true">
                                    Student Variables
                                </button>
                            </h2>
                            <div id="collapseStudent" class="accordion-collapse collapse show" 
                                 data-bs-parent="#variablesAccordion">
                                <div class="accordion-body">
                                    <div class="variable-list">
                                        <div class="variable-item" data-variable="student_name">
                                            <code>{student_name}</code>
                                            <small class="text-muted d-block">Student full name</small>
                                        </div>
                                        <div class="variable-item" data-variable="student_id">
                                            <code>{student_id}</code>
                                            <small class="text-muted d-block">Student ID</small>
                                        </div>
                                        <div class="variable-item" data-variable="student_class">
                                            <code>{student_class}</code>
                                            <small class="text-muted d-block">Student class</small>
                                        </div>
                                        <div class="variable-item" data-variable="student_section">
                                            <code>{student_section}</code>
                                            <small class="text-muted d-block">Student section</small>
                                        </div>
                                        <div class="variable-item" data-variable="student_roll">
                                            <code>{student_roll}</code>
                                            <small class="text-muted d-block">Roll number</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Variables -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="parentVariables">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapseParent">
                                    Parent Variables
                                </button>
                            </h2>
                            <div id="collapseParent" class="accordion-collapse collapse" 
                                 data-bs-parent="#variablesAccordion">
                                <div class="accordion-body">
                                    <div class="variable-list">
                                        <div class="variable-item" data-variable="parent_name">
                                            <code>{parent_name}</code>
                                            <small class="text-muted d-block">Parent name</small>
                                        </div>
                                        <div class="variable-item" data-variable="parent_phone">
                                            <code>{parent_phone}</code>
                                            <small class="text-muted d-block">Parent phone</small>
                                        </div>
                                        <div class="variable-item" data-variable="parent_email">
                                            <code>{parent_email}</code>
                                            <small class="text-muted d-block">Parent email</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- School Variables -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="schoolVariables">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapseSchool">
                                    School Variables
                                </button>
                            </h2>
                            <div id="collapseSchool" class="accordion-collapse collapse" 
                                 data-bs-parent="#variablesAccordion">
                                <div class="accordion-body">
                                    <div class="variable-list">
                                        <div class="variable-item" data-variable="school_name">
                                            <code>{school_name}</code>
                                            <small class="text-muted d-block">School name</small>
                                        </div>
                                        <div class="variable-item" data-variable="school_address">
                                            <code>{school_address}</code>
                                            <small class="text-muted d-block">School address</small>
                                        </div>
                                        <div class="variable-item" data-variable="school_phone">
                                            <code>{school_phone}</code>
                                            <small class="text-muted d-block">School phone</small>
                                        </div>
                                        <div class="variable-item" data-variable="school_email">
                                            <code>{school_email}</code>
                                            <small class="text-muted d-block">School email</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Variables -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="systemVariables">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapseSystem">
                                    System Variables
                                </button>
                            </h2>
                            <div id="collapseSystem" class="accordion-collapse collapse" 
                                 data-bs-parent="#variablesAccordion">
                                <div class="accordion-body">
                                    <div class="variable-list">
                                        <div class="variable-item" data-variable="date">
                                            <code>{date}</code>
                                            <small class="text-muted d-block">Current date</small>
                                        </div>
                                        <div class="variable-item" data-variable="time">
                                            <code>{time}</code>
                                            <small class="text-muted d-block">Current time</small>
                                        </div>
                                        <div class="variable-item" data-variable="academic_year">
                                            <code>{academic_year}</code>
                                            <small class="text-muted d-block">Current academic year</small>
                                        </div>
                                        <div class="variable-item" data-variable="url">
                                            <code>{url}</code>
                                            <small class="text-muted d-block">System URL</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Usage -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Template Usage</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">This template can be used for:</p>
                    <ul class="list-unstyled">
                        <li><i class="mdi mdi-check text-success me-2"></i>{{ ucfirst($template->category) }} notifications</li>
                        <li><i class="mdi mdi-check text-success me-2"></i>{{ ucfirst($template->type) }} messages</li>
                        @if($template->is_system)
                            <li><i class="mdi mdi-alert text-warning me-2"></i>System automated messages</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Send Modal -->
<div class="modal fade" id="testModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testForm">
                    <div class="mb-3">
                        <label for="test_recipient" class="form-label">Recipient</label>
                        <input type="text" class="form-control" id="test_recipient" 
                               placeholder="{{ $template->type === 'email' ? 'Enter email address' : 'Enter phone number' }}" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        This will send a test message using sample data for variables.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendTestBtn">Send Test</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.variable-item {
    padding: 8px;
    margin-bottom: 8px;
    border: 1px solid #e3e6f0;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.variable-item:hover {
    background-color: #f8f9fc;
    border-color: #5a5c69;
}

.variable-item code {
    color: #5a5c69;
    font-weight: 600;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const typeSelect = document.getElementById('type');
    const subjectField = document.getElementById('subjectField');
    const bodyTextarea = document.getElementById('body');
    const previewBtn = document.getElementById('previewBtn');
    const testBtn = document.getElementById('testBtn');

    // Auto-generate slug from name (only if not system template)
    if (!nameInput.readOnly) {
        nameInput.addEventListener('input', function() {
            const slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            slugInput.value = slug;
        });
    }

    // Show/hide subject field based on type
    if (!typeSelect.disabled) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'email') {
                subjectField.style.display = 'block';
                document.getElementById('subject').required = true;
            } else {
                subjectField.style.display = 'none';
                document.getElementById('subject').required = false;
            }
        });
    }

    // Variable insertion
    document.querySelectorAll('.variable-item').forEach(item => {
        item.addEventListener('click', function() {
            const variable = '{' + this.dataset.variable + '}';
            insertAtCursor(bodyTextarea, variable);
        });
    });

    // Preview functionality
    previewBtn.addEventListener('click', function() {
        const type = typeSelect.value;
        const subject = document.getElementById('subject').value;
        const body = bodyTextarea.value;
        
        if (!body.trim()) {
            alert('Please enter template content first.');
            return;
        }

        let previewContent = '';
        
        if (type === 'email' && subject) {
            previewContent += `<div class="mb-3">
                <label class="form-label fw-bold">Subject:</label>
                <div class="border p-2 bg-light">${escapeHtml(subject)}</div>
            </div>`;
        }
        
        previewContent += `<div class="mb-3">
            <label class="form-label fw-bold">${type === 'email' ? 'Body' : 'Message'}:</label>
            <div class="border p-3" style="max-height: 300px; overflow-y: auto;">
                ${escapeHtml(body).replace(/\n/g, '<br>')}
            </div>
        </div>`;

        document.getElementById('previewContent').innerHTML = previewContent;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    });

    // Test send functionality
    testBtn.addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('testModal')).show();
    });

    document.getElementById('sendTestBtn').addEventListener('click', function() {
        const recipient = document.getElementById('test_recipient').value;
        if (!recipient.trim()) {
            alert('Please enter a recipient.');
            return;
        }

        // Here you would typically make an AJAX call to send the test
        alert('Test message sent to ' + recipient);
        bootstrap.Modal.getInstance(document.getElementById('testModal')).hide();
    });

    // JSON validation for settings
    document.getElementById('settings').addEventListener('blur', function() {
        if (this.value.trim()) {
            try {
                JSON.parse(this.value);
                this.classList.remove('is-invalid');
            } catch (e) {
                this.classList.add('is-invalid');
            }
        }
    });
});

function insertAtCursor(textarea, text) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    
    textarea.value = value.substring(0, start) + text + value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush