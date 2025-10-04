@extends('layouts.app')

@section('title', 'Salary Components')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Salary Components</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('salary.index') }}">Salary</a></li>
                        <li class="breadcrumb-item active">Components</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Component Statistics -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-primary rounded-circle">
                                    <i class="mdi mdi-plus-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['total_earnings'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Earning Components</p>
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
                            <div class="avatar-sm">
                                <span class="avatar-title bg-danger rounded-circle">
                                    <i class="mdi mdi-minus-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['total_deductions'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Deduction Components</p>
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
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="mdi mdi-check-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['active_components'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Active Components</p>
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
                            <div class="avatar-sm">
                                <span class="avatar-title bg-info rounded-circle">
                                    <i class="mdi mdi-account-group"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['employees_using'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Employees Using</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Component Management -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-cog me-2"></i>
                            Salary Components
                        </h5>
                        <div class="card-header-actions">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary btn-sm" onclick="addComponent()">
                                    <i class="mdi mdi-plus me-1"></i>Add Component
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="importComponents()">
                                    <i class="mdi mdi-upload me-1"></i>Import
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportComponents()">
                                    <i class="mdi mdi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="filterType" onchange="filterComponents()">
                                <option value="">All Types</option>
                                <option value="earning">Earnings</option>
                                <option value="deduction">Deductions</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus" onchange="filterComponents()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterCalculation" onchange="filterComponents()">
                                <option value="">All Calculations</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                                <option value="formula">Formula Based</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchComponents" 
                                       placeholder="Search components..." onkeyup="filterComponents()">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Components Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="componentsTable">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </div>
                                    </th>
                                    <th>Component Name</th>
                                    <th>Type</th>
                                    <th>Calculation</th>
                                    <th>Default Value</th>
                                    <th>Tax Impact</th>
                                    <th>Status</th>
                                    <th>Usage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($components as $component)
                                <tr data-type="{{ $component['type'] }}" data-status="{{ $component['status'] }}" 
                                    data-calculation="{{ $component['calculation_type'] }}">
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input component-checkbox" type="checkbox" 
                                                   value="{{ $component['id'] }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <span class="avatar-title bg-{{ $component['type'] == 'earning' ? 'success' : 'danger' }} rounded-circle">
                                                    <i class="mdi mdi-{{ $component['type'] == 'earning' ? 'plus' : 'minus' }}-circle"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $component['name'] }}</h6>
                                                <small class="text-muted">{{ $component['code'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $component['type'] == 'earning' ? 'success' : 'danger' }}">
                                            {{ ucfirst($component['type']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($component['calculation_type']) }}
                                        </span>
                                        @if($component['calculation_type'] == 'percentage')
                                            <br><small class="text-muted">of {{ $component['percentage_of'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($component['calculation_type'] == 'fixed')
                                            ₹{{ number_format($component['default_amount'], 2) }}
                                        @elseif($component['calculation_type'] == 'percentage')
                                            {{ $component['default_percentage'] }}%
                                        @else
                                            <small class="text-muted">Formula based</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $component['taxable'] ? 'warning' : 'info' }}">
                                            {{ $component['taxable'] ? 'Taxable' : 'Non-taxable' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $component['status'] == 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($component['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $component['usage_count'] }} employees</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewComponent({{ $component['id'] }})" 
                                                    title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="editComponent({{ $component['id'] }})" 
                                                    title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="duplicateComponent({{ $component['id'] }})" 
                                                    title="Duplicate">
                                                <i class="mdi mdi-content-copy"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="toggleComponentStatus({{ $component['id'] }})" 
                                                    title="Toggle Status">
                                                <i class="mdi mdi-{{ $component['status'] == 'active' ? 'pause' : 'play' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteComponent({{ $component['id'] }})" 
                                                    title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="row mt-3" id="bulkActions" style="display: none;">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><strong id="selectedCount">0</strong> components selected</span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success" onclick="bulkActivate()">
                                            <i class="mdi mdi-check me-1"></i>Activate
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="bulkDeactivate()">
                                            <i class="mdi mdi-pause me-1"></i>Deactivate
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="bulkDelete()">
                                            <i class="mdi mdi-delete me-1"></i>Delete
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                                            <i class="mdi mdi-close me-1"></i>Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Component Templates -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-document-multiple me-2"></i>
                        Component Templates
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($templates as $template)
                        <div class="col-md-4 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $template['name'] }}</h6>
                                    <p class="card-text text-muted">{{ $template['description'] }}</p>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            {{ $template['components_count'] }} components
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="previewTemplate({{ $template['id'] }})">
                                            <i class="mdi mdi-eye me-1"></i>Preview
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="applyTemplate({{ $template['id'] }})">
                                            <i class="mdi mdi-check me-1"></i>Apply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Component Modal -->
<div class="modal fade" id="componentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="componentModalTitle">Add Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="componentForm">
                    <input type="hidden" id="componentId" name="id">
                    
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="componentName" class="form-label">Component Name *</label>
                            <input type="text" class="form-control" id="componentName" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="componentCode" class="form-label">Component Code *</label>
                            <input type="text" class="form-control" id="componentCode" name="code" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="componentType" class="form-label">Type *</label>
                            <select class="form-select" id="componentType" name="type" required onchange="toggleTypeFields()">
                                <option value="">Select Type</option>
                                <option value="earning">Earning</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="calculationType" class="form-label">Calculation Type *</label>
                            <select class="form-select" id="calculationType" name="calculation_type" required onchange="toggleCalculationFields()">
                                <option value="">Select Calculation</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                                <option value="formula">Formula Based</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Calculation Fields -->
                    <div class="row mb-3" id="fixedAmountField" style="display: none;">
                        <div class="col-md-6">
                            <label for="defaultAmount" class="form-label">Default Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="defaultAmount" name="default_amount" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3" id="percentageFields" style="display: none;">
                        <div class="col-md-6">
                            <label for="defaultPercentage" class="form-label">Default Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="defaultPercentage" name="default_percentage" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="percentageOf" class="form-label">Percentage Of</label>
                            <select class="form-select" id="percentageOf" name="percentage_of">
                                <option value="basic_salary">Basic Salary</option>
                                <option value="gross_salary">Gross Salary</option>
                                <option value="net_salary">Net Salary</option>
                                <option value="custom">Custom Component</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3" id="formulaField" style="display: none;">
                        <div class="col-12">
                            <label for="formula" class="form-label">Formula</label>
                            <textarea class="form-control" id="formula" name="formula" rows="3" 
                                      placeholder="e.g., (basic_salary * 0.12) + (hra * 0.05)"></textarea>
                            <small class="text-muted">Use component codes and mathematical operators. Available variables: basic_salary, hra, da, etc.</small>
                        </div>
                    </div>
                    
                    <!-- Tax and Compliance -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="taxable" name="taxable">
                                <label class="form-check-label" for="taxable">
                                    Taxable Component
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mandatory" name="mandatory">
                                <label class="form-check-label" for="mandatory">
                                    Mandatory for All Employees
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showInPayslip" name="show_in_payslip" checked>
                                <label class="form-check-label" for="showInPayslip">
                                    Show in Payslip
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Limits and Conditions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="minAmount" class="form-label">Minimum Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="minAmount" name="min_amount" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="maxAmount" class="form-label">Maximum Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="maxAmount" name="max_amount" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <!-- Applicable Conditions -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Applicable To</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <select class="form-select" id="applicableDepartments" name="applicable_departments[]" multiple>
                                        <option value="">All Departments</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Departments</small>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="applicableDesignations" name="applicable_designations[]" multiple>
                                        <option value="">All Designations</option>
                                        @foreach($designations as $designation)
                                            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Designations</small>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="applicableEmployeeTypes" name="applicable_employee_types[]" multiple>
                                        <option value="">All Types</option>
                                        <option value="permanent">Permanent</option>
                                        <option value="contract">Contract</option>
                                        <option value="temporary">Temporary</option>
                                        <option value="intern">Intern</option>
                                    </select>
                                    <small class="text-muted">Employee Types</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveComponent()">
                    <i class="mdi mdi-check me-1"></i>Save Component
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Component Details Modal -->
<div class="modal fade" id="componentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Component Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="componentDetailsContent">
                <!-- Component details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editComponentFromDetails()">
                    <i class="mdi mdi-pencil me-1"></i>Edit Component
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="templatePreviewContent">
                <!-- Template preview will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyTemplateFromPreview()">
                    <i class="mdi mdi-check me-1"></i>Apply Template
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Import Components Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Components</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="importFile" name="file" 
                               accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Supported formats: Excel (.xlsx, .xls), CSV (.csv)</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="updateExisting" name="update_existing">
                            <label class="form-check-label" for="updateExisting">
                                Update existing components
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="skipErrors" name="skip_errors" checked>
                            <label class="form-check-label" for="skipErrors">
                                Skip rows with errors
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6>Import Format:</h6>
                        <p class="mb-0">Download the <a href="{{ route('salary.components.template') }}" target="_blank">sample template</a> to see the required format.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processImport()">
                    <i class="mdi mdi-upload me-1"></i>Import
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.component-checkbox:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 0.875rem;
}

.card-header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.badge {
    font-size: 0.75rem;
}

.text-muted {
    color: #6c757d !important;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.modal-lg {
    max-width: 800px;
}

.modal-xl {
    max-width: 1140px;
}

@media (max-width: 768px) {
    .card-header-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 0.25rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
let currentComponentId = null;
let currentTemplateId = null;

$(document).ready(function() {
    // Initialize component checkboxes
    $('.component-checkbox').on('change', function() {
        updateBulkActions();
    });
    
    // Initialize select all checkbox
    $('#selectAll').on('change', function() {
        toggleSelectAll();
    });
});

function filterComponents() {
    const type = $('#filterType').val().toLowerCase();
    const status = $('#filterStatus').val().toLowerCase();
    const calculation = $('#filterCalculation').val().toLowerCase();
    const search = $('#searchComponents').val().toLowerCase();
    
    $('#componentsTable tbody tr').each(function() {
        const row = $(this);
        const rowType = row.data('type');
        const rowStatus = row.data('status');
        const rowCalculation = row.data('calculation');
        const rowText = row.text().toLowerCase();
        
        let show = true;
        
        if (type && rowType !== type) show = false;
        if (status && rowStatus !== status) show = false;
        if (calculation && rowCalculation !== calculation) show = false;
        if (search && !rowText.includes(search)) show = false;
        
        row.toggle(show);
    });
}

function addComponent() {
    currentComponentId = null;
    $('#componentModalTitle').text('Add Component');
    $('#componentForm')[0].reset();
    $('#componentId').val('');
    $('#componentModal').modal('show');
}

function editComponent(componentId) {
    currentComponentId = componentId;
    $('#componentModalTitle').text('Edit Component');
    
    // Load component data
    $.ajax({
        url: '{{ route("salary.components.show") }}',
        method: 'GET',
        data: { id: componentId },
        success: function(response) {
            if (response.success) {
                const component = response.data;
                
                // Fill form fields
                $('#componentId').val(component.id);
                $('#componentName').val(component.name);
                $('#componentCode').val(component.code);
                $('#componentType').val(component.type);
                $('#calculationType').val(component.calculation_type);
                $('#defaultAmount').val(component.default_amount);
                $('#defaultPercentage').val(component.default_percentage);
                $('#percentageOf').val(component.percentage_of);
                $('#formula').val(component.formula);
                $('#taxable').prop('checked', component.taxable);
                $('#mandatory').prop('checked', component.mandatory);
                $('#showInPayslip').prop('checked', component.show_in_payslip);
                $('#active').prop('checked', component.active);
                $('#minAmount').val(component.min_amount);
                $('#maxAmount').val(component.max_amount);
                $('#description').val(component.description);
                
                // Set applicable fields
                if (component.applicable_departments) {
                    $('#applicableDepartments').val(component.applicable_departments);
                }
                if (component.applicable_designations) {
                    $('#applicableDesignations').val(component.applicable_designations);
                }
                if (component.applicable_employee_types) {
                    $('#applicableEmployeeTypes').val(component.applicable_employee_types);
                }
                
                // Toggle fields based on type and calculation
                toggleTypeFields();
                toggleCalculationFields();
                
                $('#componentModal').modal('show');
            } else {
                toastr.error(response.message || 'Failed to load component data');
            }
        },
        error: function(xhr) {
            console.error('Failed to load component:', xhr);
            toastr.error('Failed to load component data');
        }
    });
}

function viewComponent(componentId) {
    $.ajax({
        url: '{{ route("salary.components.details") }}',
        method: 'GET',
        data: { id: componentId },
        success: function(response) {
            if (response.success) {
                $('#componentDetailsContent').html(response.html);
                currentComponentId = componentId;
                $('#componentDetailsModal').modal('show');
            } else {
                toastr.error(response.message || 'Failed to load component details');
            }
        },
        error: function(xhr) {
            console.error('Failed to load component details:', xhr);
            toastr.error('Failed to load component details');
        }
    });
}

function duplicateComponent(componentId) {
    if (confirm('Are you sure you want to duplicate this component?')) {
        $.ajax({
            url: '{{ route("salary.components.duplicate") }}',
            method: 'POST',
            data: { id: componentId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Component duplicated successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to duplicate component');
                }
            },
            error: function(xhr) {
                console.error('Failed to duplicate component:', xhr);
                toastr.error('Failed to duplicate component');
            }
        });
    }
}

function toggleComponentStatus(componentId) {
    $.ajax({
        url: '{{ route("salary.components.toggle-status") }}',
        method: 'POST',
        data: { id: componentId },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Component status updated successfully');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update component status');
            }
        },
        error: function(xhr) {
            console.error('Failed to toggle component status:', xhr);
            toastr.error('Failed to update component status');
        }
    });
}

function deleteComponent(componentId) {
    if (confirm('Are you sure you want to delete this component? This action cannot be undone.')) {
        $.ajax({
            url: '{{ route("salary.components.delete") }}',
            method: 'DELETE',
            data: { id: componentId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Component deleted successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to delete component');
                }
            },
            error: function(xhr) {
                console.error('Failed to delete component:', xhr);
                toastr.error('Failed to delete component');
            }
        });
    }
}

function saveComponent() {
    const formData = new FormData($('#componentForm')[0]);
    const url = currentComponentId ? 
        '{{ route("salary.components.update") }}' : 
        '{{ route("salary.components.store") }}';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Component saved successfully');
                $('#componentModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to save component');
            }
        },
        error: function(xhr) {
            console.error('Failed to save component:', xhr);
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    toastr.error(errors[key][0]);
                });
            } else {
                toastr.error('Failed to save component');
            }
        }
    });
}

function toggleTypeFields() {
    const type = $('#componentType').val();
    // Add any type-specific field toggles here if needed
}

function toggleCalculationFields() {
    const calculationType = $('#calculationType').val();
    
    // Hide all calculation fields
    $('#fixedAmountField').hide();
    $('#percentageFields').hide();
    $('#formulaField').hide();
    
    // Show relevant fields
    if (calculationType === 'fixed') {
        $('#fixedAmountField').show();
    } else if (calculationType === 'percentage') {
        $('#percentageFields').show();
    } else if (calculationType === 'formula') {
        $('#formulaField').show();
    }
}

function toggleSelectAll() {
    const isChecked = $('#selectAll').is(':checked');
    $('.component-checkbox').prop('checked', isChecked);
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCount = $('.component-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkActions').show();
    } else {
        $('#bulkActions').hide();
    }
    
    // Update select all checkbox state
    const totalCheckboxes = $('.component-checkbox').length;
    if (selectedCount === 0) {
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
    } else if (selectedCount === totalCheckboxes) {
        $('#selectAll').prop('indeterminate', false).prop('checked', true);
    } else {
        $('#selectAll').prop('indeterminate', true);
    }
}

function getSelectedComponents() {
    const selected = [];
    $('.component-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function bulkActivate() {
    const selected = getSelectedComponents();
    if (selected.length === 0) {
        toastr.warning('Please select components to activate');
        return;
    }
    
    if (confirm(`Are you sure you want to activate ${selected.length} components?`)) {
        bulkAction('activate', selected);
    }
}

function bulkDeactivate() {
    const selected = getSelectedComponents();
    if (selected.length === 0) {
        toastr.warning('Please select components to deactivate');
        return;
    }
    
    if (confirm(`Are you sure you want to deactivate ${selected.length} components?`)) {
        bulkAction('deactivate', selected);
    }
}

function bulkDelete() {
    const selected = getSelectedComponents();
    if (selected.length === 0) {
        toastr.warning('Please select components to delete');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selected.length} components? This action cannot be undone.`)) {
        bulkAction('delete', selected);
    }
}

function bulkAction(action, componentIds) {
    $.ajax({
        url: '{{ route("salary.components.bulk-action") }}',
        method: 'POST',
        data: {
            action: action,
            component_ids: componentIds
        },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Bulk action completed successfully');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to perform bulk action');
            }
        },
        error: function(xhr) {
            console.error('Failed to perform bulk action:', xhr);
            toastr.error('Failed to perform bulk action');
        }
    });
}

function clearSelection() {
    $('.component-checkbox').prop('checked', false);
    $('#selectAll').prop('checked', false).prop('indeterminate', false);
    updateBulkActions();
}

function previewTemplate(templateId) {
    $.ajax({
        url: '{{ route("salary.components.template-preview") }}',
        method: 'GET',
        data: { id: templateId },
        success: function(response) {
            if (response.success) {
                $('#templatePreviewContent').html(response.html);
                currentTemplateId = templateId;
                $('#templatePreviewModal').modal('show');
            } else {
                toastr.error(response.message || 'Failed to load template preview');
            }
        },
        error: function(xhr) {
            console.error('Failed to load template preview:', xhr);
            toastr.error('Failed to load template preview');
        }
    });
}

function applyTemplate(templateId) {
    if (confirm('Are you sure you want to apply this template? This will add all template components to your system.')) {
        $.ajax({
            url: '{{ route("salary.components.apply-template") }}',
            method: 'POST',
            data: { id: templateId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Template applied successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to apply template');
                }
            },
            error: function(xhr) {
                console.error('Failed to apply template:', xhr);
                toastr.error('Failed to apply template');
            }
        });
    }
}

function applyTemplateFromPreview() {
    if (currentTemplateId) {
        applyTemplate(currentTemplateId);
        $('#templatePreviewModal').modal('hide');
    }
}

function editComponentFromDetails() {
    if (currentComponentId) {
        $('#componentDetailsModal').modal('hide');
        editComponent(currentComponentId);
    }
}

function importComponents() {
    $('#importModal').modal('show');
}

function processImport() {
    const formData = new FormData($('#importForm')[0]);
    
    $.ajax({
        url: '{{ route("salary.components.import") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Components imported successfully');
                $('#importModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to import components');
            }
        },
        error: function(xhr) {
            console.error('Failed to import components:', xhr);
            toastr.error('Failed to import components');
        }
    });
}

function exportComponents() {
    const link = document.createElement('a');
    link.href = '{{ route("salary.components.export") }}';
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.success('Export started');
}
</script>
@endpush