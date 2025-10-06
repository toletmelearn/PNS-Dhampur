@extends('layouts.app')

@section('title', 'Notification Templates')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Notification Templates</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item active">Notification Templates</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="mdi mdi-email-outline font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Total Templates</h6>
                            <p class="text-muted mb-0">{{ $templates->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success">
                                <span class="avatar-title">
                                    <i class="mdi mdi-email font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Email Templates</h6>
                            <p class="text-muted mb-0">{{ $templates->where('type', 'email')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="mdi mdi-message-text font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">SMS Templates</h6>
                            <p class="text-muted mb-0">{{ $templates->where('type', 'sms')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title">
                                    <i class="mdi mdi-cog font-20"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">System Templates</h6>
                            <p class="text-muted mb-0">{{ $templates->where('is_system', true)->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Notification Templates</h5>
                        <div>
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#variablesModal">
                                <i class="mdi mdi-code-braces me-1"></i> View Variables
                            </button>
                            <a href="{{ route('admin.configuration.notification-templates.create') }}" class="btn btn-primary">
                                <i class="mdi mdi-plus me-1"></i> Add Template
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="typeFilter">
                                <option value="">All Types</option>
                                @foreach(\App\Models\NotificationTemplate::TYPES as $key => $type)
                                    <option value="{{ $key }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\NotificationTemplate::CATEGORIES as $key => $category)
                                    <option value="{{ $key }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="system">System Templates</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchFilter" placeholder="Search templates...">
                        </div>
                    </div>

                    <!-- Templates Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="templatesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Template</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Variables</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                <tr data-type="{{ $template->type }}" data-category="{{ $template->category }}" 
                                    data-status="{{ $template->is_active ? 'active' : 'inactive' }}{{ $template->is_system ? ' system' : '' }}">
                                    <td>
                                        <div>
                                            <h6 class="mb-1">{{ $template->name }}</h6>
                                            <small class="text-muted">{{ $template->slug }}</small>
                                            @if($template->is_system)
                                                <span class="badge bg-warning ms-2">System</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $template->type === 'email' ? 'primary' : 'info' }}">
                                            <i class="mdi mdi-{{ $template->type === 'email' ? 'email' : 'message-text' }} me-1"></i>
                                            {{ strtoupper($template->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-capitalize">{{ str_replace('_', ' ', $template->category) }}</span>
                                    </td>
                                    <td>
                                        @if($template->variables && count($template->variables) > 0)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="popover" 
                                                    data-bs-content="{{ implode(', ', array_map(function($var) { return '{' . $var . '}'; }, $template->variables)) }}"
                                                    title="Available Variables">
                                                {{ count($template->variables) }} variables
                                            </button>
                                        @else
                                            <span class="text-muted">No variables</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $template->is_active ? 'success' : 'danger' }}">
                                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $template->updated_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#previewModal{{ $template->id }}">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <a href="{{ route('admin.configuration.notification-templates.edit', $template) }}" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if(!$template->is_system)
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteTemplate({{ $template->id }})">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="mdi mdi-email-outline font-24 d-block mb-2"></i>
                                            No notification templates found
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modals -->
@foreach($templates as $template)
<div class="modal fade" id="previewModal{{ $template->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview: {{ $template->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($template->type === 'email' && $template->subject)
                <div class="mb-3">
                    <label class="form-label fw-bold">Subject:</label>
                    <div class="border p-2 bg-light">{{ $template->subject }}</div>
                </div>
                @endif
                
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ $template->type === 'email' ? 'Body' : 'Message' }}:</label>
                    <div class="border p-3" style="max-height: 300px; overflow-y: auto;">
                        {!! nl2br(e($template->body)) !!}
                    </div>
                </div>
                
                @if($template->variables && count($template->variables) > 0)
                <div class="mb-3">
                    <label class="form-label fw-bold">Available Variables:</label>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($template->variables as $variable)
                            <span class="badge bg-secondary">{{{ $variable }}}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('admin.configuration.notification-templates.edit', $template) }}" class="btn btn-primary">
                    Edit Template
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Variables Reference Modal -->
<div class="modal fade" id="variablesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Available Template Variables</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Student Variables</h6>
                        <ul class="list-unstyled">
                            <li><code>{student_name}</code> - Student full name</li>
                            <li><code>{student_id}</code> - Student ID</li>
                            <li><code>{student_class}</code> - Student class</li>
                            <li><code>{student_section}</code> - Student section</li>
                            <li><code>{student_roll}</code> - Roll number</li>
                        </ul>
                        
                        <h6>Parent Variables</h6>
                        <ul class="list-unstyled">
                            <li><code>{parent_name}</code> - Parent name</li>
                            <li><code>{parent_phone}</code> - Parent phone</li>
                            <li><code>{parent_email}</code> - Parent email</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>School Variables</h6>
                        <ul class="list-unstyled">
                            <li><code>{school_name}</code> - School name</li>
                            <li><code>{school_address}</code> - School address</li>
                            <li><code>{school_phone}</code> - School phone</li>
                            <li><code>{school_email}</code> - School email</li>
                        </ul>
                        
                        <h6>System Variables</h6>
                        <ul class="list-unstyled">
                            <li><code>{date}</code> - Current date</li>
                            <li><code>{time}</code> - Current time</li>
                            <li><code>{academic_year}</code> - Current academic year</li>
                            <li><code>{url}</code> - System URL</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="mdi mdi-information me-1"></i>
                    <strong>Usage:</strong> Use variables in your templates by wrapping them in curly braces, e.g., <code>{student_name}</code>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Filter functionality
    const typeFilter = document.getElementById('typeFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchFilter = document.getElementById('searchFilter');
    const tableRows = document.querySelectorAll('#templatesTable tbody tr');

    function applyFilters() {
        const typeValue = typeFilter.value.toLowerCase();
        const categoryValue = categoryFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const searchValue = searchFilter.value.toLowerCase();

        tableRows.forEach(row => {
            if (row.cells.length === 1) return; // Skip empty row

            const type = row.dataset.type || '';
            const category = row.dataset.category || '';
            const status = row.dataset.status || '';
            const text = row.textContent.toLowerCase();

            const typeMatch = !typeValue || type.includes(typeValue);
            const categoryMatch = !categoryValue || category.includes(categoryValue);
            const statusMatch = !statusValue || status.includes(statusValue);
            const searchMatch = !searchValue || text.includes(searchValue);

            if (typeMatch && categoryMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide empty message
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none' && row.cells.length > 1);
        const emptyRow = document.querySelector('#templatesTable tbody tr td[colspan="7"]');
        if (emptyRow) {
            emptyRow.parentElement.style.display = visibleRows.length === 0 ? '' : 'none';
        }
    }

    typeFilter.addEventListener('change', applyFilters);
    categoryFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    searchFilter.addEventListener('input', applyFilters);
});

function deleteTemplate(templateId) {
    if (confirm('Are you sure you want to delete this notification template? This action cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/configuration/notification-templates/${templateId}`;
        form.submit();
    }
}
</script>
@endpush