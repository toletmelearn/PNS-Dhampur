@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">System Settings</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item active">System Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h5 class="card-title">System Settings</h5>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end">
                                <a href="{{ route('admin.configuration.system-settings.create') }}" class="btn btn-primary">
                                    <i class="mdi mdi-plus me-1"></i> Add Setting
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#all-settings" role="tab">
                                <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                <span class="d-none d-sm-block">All Settings</span>
                            </a>
                        </li>
                        @foreach($categories as $category)
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#{{ Str::slug($category) }}" role="tab">
                                <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                <span class="d-none d-sm-block">{{ ucfirst($category) }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    <div class="tab-content p-3 text-muted">
                        <!-- All Settings Tab -->
                        <div class="tab-pane active" id="all-settings" role="tabpanel">
                            @include('admin.configuration.system-settings.partials.settings-table', ['settings' => $settings])
                        </div>

                        <!-- Category Tabs -->
                        @foreach($categories as $category)
                        <div class="tab-pane" id="{{ Str::slug($category) }}" role="tabpanel">
                            @include('admin.configuration.system-settings.partials.settings-table', ['settings' => $settings->where('category', $category)])
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Setting Modal -->
<div class="modal fade" id="editSettingModal" tabindex="-1" aria-labelledby="editSettingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSettingModalLabel">Edit Setting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSettingForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_label" class="form-label">Label</label>
                        <input type="text" class="form-control" id="edit_label" name="label" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2" readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_value" class="form-label">Value</label>
                        <div id="edit_value_container">
                            <!-- Dynamic input will be inserted here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit setting modal
    const editModal = document.getElementById('editSettingModal');
    const editForm = document.getElementById('editSettingForm');
    const editValueContainer = document.getElementById('edit_value_container');

    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const settingId = button.getAttribute('data-id');
        const settingKey = button.getAttribute('data-key');
        const settingLabel = button.getAttribute('data-label');
        const settingDescription = button.getAttribute('data-description');
        const settingValue = button.getAttribute('data-value');
        const settingType = button.getAttribute('data-type');

        // Update form action
        editForm.action = `/admin/configuration/system-settings/${settingId}`;

        // Update form fields
        document.getElementById('edit_label').value = settingLabel;
        document.getElementById('edit_description').value = settingDescription;

        // Create appropriate input based on type
        let inputHtml = '';
        switch(settingType) {
            case 'boolean':
                inputHtml = `
                    <select class="form-select" name="value" required>
                        <option value="1" ${settingValue == '1' ? 'selected' : ''}>Yes</option>
                        <option value="0" ${settingValue == '0' ? 'selected' : ''}>No</option>
                    </select>
                `;
                break;
            case 'integer':
                inputHtml = `<input type="number" class="form-control" name="value" value="${settingValue}" required>`;
                break;
            case 'decimal':
                inputHtml = `<input type="number" step="0.01" class="form-control" name="value" value="${settingValue}" required>`;
                break;
            case 'json':
                inputHtml = `<textarea class="form-control" name="value" rows="5" required>${settingValue}</textarea>`;
                break;
            default:
                inputHtml = `<input type="text" class="form-control" name="value" value="${settingValue}" required>`;
        }

        editValueContainer.innerHTML = inputHtml;
    });

    // Delete confirmation
    window.confirmDelete = function(settingId, settingKey) {
        if (confirm(`Are you sure you want to delete the setting "${settingKey}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/configuration/system-settings/${settingId}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
});
</script>
@endpush