@extends('layouts.app')

@section('title', 'Payroll Calculations')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Payroll Calculations</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('salary.index') }}">Salary</a></li>
                        <li class="breadcrumb-item active">Calculations</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Calculation Overview -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-primary rounded-circle">
                                    <i class="mdi mdi-account-group"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ $stats['total_employees'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Total Employees</p>
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
                                    <i class="mdi mdi-currency-inr"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">₹{{ number_format($stats['total_gross'] ?? 0, 2) }}</h5>
                            <p class="text-muted mb-0">Total Gross Salary</p>
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
                            <h5 class="mb-1">₹{{ number_format($stats['total_deductions'] ?? 0, 2) }}</h5>
                            <p class="text-muted mb-0">Total Deductions</p>
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
                                    <i class="mdi mdi-cash-multiple"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">₹{{ number_format($stats['total_net'] ?? 0, 2) }}</h5>
                            <p class="text-muted mb-0">Total Net Salary</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Processing -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-calculator me-2"></i>
                            Payroll Processing
                        </h5>
                        <div class="card-header-actions">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary btn-sm" onclick="processPayroll()">
                                    <i class="mdi mdi-play me-1"></i>Process Payroll
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="validateCalculations()">
                                    <i class="mdi mdi-check-circle me-1"></i>Validate
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="previewCalculations()">
                                    <i class="mdi mdi-eye me-1"></i>Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Processing Filters -->
                    <form id="payrollForm">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="payPeriod" class="form-label">Pay Period *</label>
                                <input type="month" class="form-control" id="payPeriod" name="pay_period" 
                                       value="{{ date('Y-m') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="employeeType" class="form-label">Employee Type</label>
                                <select class="form-select" id="employeeType" name="employee_type">
                                    <option value="">All Types</option>
                                    <option value="permanent">Permanent</option>
                                    <option value="contract">Contract</option>
                                    <option value="temporary">Temporary</option>
                                    <option value="intern">Intern</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="specificEmployees" class="form-label">Specific Employees</label>
                                <select class="form-select" id="specificEmployees" name="specific_employees[]" multiple>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->employee_id }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave empty to process all employees</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Processing Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeAttendance" name="include_attendance" checked>
                                    <label class="form-check-label" for="includeAttendance">
                                        Include Attendance Calculations
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeOvertime" name="include_overtime" checked>
                                    <label class="form-check-label" for="includeOvertime">
                                        Include Overtime Calculations
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeTax" name="include_tax" checked>
                                    <label class="form-check-label" for="includeTax">
                                        Include Tax Calculations
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="autoApprove" name="auto_approve">
                                    <label class="form-check-label" for="autoApprove">
                                        Auto-approve Calculations
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Processing Progress -->
                    <div id="processingProgress" style="display: none;">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>Processing Payroll...</strong>
                                    <div class="progress mt-2">
                                        <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar">
                                            <span id="progressText">0%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted" id="progressStatus">Initializing...</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Processing Results -->
                    <div id="processingResults" style="display: none;">
                        <div class="alert alert-success">
                            <h6>Processing Completed Successfully!</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong id="processedCount">0</strong> employees processed
                                </div>
                                <div class="col-md-3">
                                    <strong id="successCount">0</strong> successful
                                </div>
                                <div class="col-md-3">
                                    <strong id="errorCount">0</strong> errors
                                </div>
                                <div class="col-md-3">
                                    <strong id="totalAmount">₹0</strong> total amount
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadReport()">
                                    <i class="mdi mdi-download me-1"></i>Download Report
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="viewResults()">
                                    <i class="mdi mdi-eye me-1"></i>View Results
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-cog me-2"></i>
                        Calculation Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form id="calculationSettings">
                        <div class="mb-3">
                            <label for="workingDays" class="form-label">Working Days per Month</label>
                            <input type="number" class="form-control" id="workingDays" name="working_days" 
                                   value="{{ $settings['working_days'] ?? 26 }}" min="1" max="31">
                        </div>
                        
                        <div class="mb-3">
                            <label for="workingHours" class="form-label">Working Hours per Day</label>
                            <input type="number" class="form-control" id="workingHours" name="working_hours" 
                                   value="{{ $settings['working_hours'] ?? 8 }}" min="1" max="24" step="0.5">
                        </div>
                        
                        <div class="mb-3">
                            <label for="overtimeRate" class="form-label">Overtime Rate Multiplier</label>
                            <input type="number" class="form-control" id="overtimeRate" name="overtime_rate" 
                                   value="{{ $settings['overtime_rate'] ?? 1.5 }}" min="1" step="0.1">
                        </div>
                        
                        <div class="mb-3">
                            <label for="lateDeduction" class="form-label">Late Arrival Deduction (per hour)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="lateDeduction" name="late_deduction" 
                                       value="{{ $settings['late_deduction'] ?? 50 }}" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="absentDeduction" class="form-label">Absent Day Deduction</label>
                            <select class="form-select" id="absentDeduction" name="absent_deduction">
                                <option value="full_day" {{ ($settings['absent_deduction'] ?? 'full_day') == 'full_day' ? 'selected' : '' }}>
                                    Full Day Salary
                                </option>
                                <option value="basic_only" {{ ($settings['absent_deduction'] ?? '') == 'basic_only' ? 'selected' : '' }}>
                                    Basic Salary Only
                                </option>
                                <option value="custom" {{ ($settings['absent_deduction'] ?? '') == 'custom' ? 'selected' : '' }}>
                                    Custom Amount
                                </option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="roundSalary" name="round_salary" 
                                       {{ ($settings['round_salary'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="roundSalary">
                                    Round Final Salary
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="prorateSalary" name="prorate_salary" 
                                       {{ ($settings['prorate_salary'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="prorateSalary">
                                    Prorate Salary for Partial Months
                                </label>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveSettings()">
                            <i class="mdi mdi-check me-1"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-lightning-bolt me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="recalculateAll()">
                            <i class="mdi mdi-refresh me-1"></i>Recalculate All
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="generatePayslips()">
                            <i class="mdi mdi-file-document me-1"></i>Generate Payslips
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="exportCalculations()">
                            <i class="mdi mdi-download me-1"></i>Export Calculations
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="auditTrail()">
                            <i class="mdi mdi-history me-1"></i>Audit Trail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Calculations -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="mdi mdi-history me-2"></i>
                            Recent Calculations
                        </h5>
                        <div class="card-header-actions">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="refreshCalculations()">
                                    <i class="mdi mdi-refresh"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="filterCalculations()">
                                    <i class="mdi mdi-filter"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3" id="calculationFilters" style="display: none;">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterPeriod" onchange="applyFilters()">
                                <option value="">All Periods</option>
                                @for($i = 0; $i < 12; $i++)
                                    @php $date = now()->subMonths($i)->format('Y-m') @endphp
                                    <option value="{{ $date }}">{{ now()->subMonths($i)->format('F Y') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterStatus" onchange="applyFilters()">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterDepartment" onchange="applyFilters()">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="searchCalculations" 
                                       placeholder="Search..." onkeyup="applyFilters()">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calculations Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="calculationsTable">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Department</th>
                                    <th>Employees</th>
                                    <th>Gross Amount</th>
                                    <th>Deductions</th>
                                    <th>Net Amount</th>
                                    <th>Status</th>
                                    <th>Processed By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentCalculations as $calculation)
                                <tr data-period="{{ $calculation['period'] }}" data-status="{{ $calculation['status'] }}" 
                                    data-department="{{ $calculation['department_id'] }}">
                                    <td>
                                        <strong>{{ \Carbon\Carbon::parse($calculation['period'])->format('F Y') }}</strong>
                                    </td>
                                    <td>{{ $calculation['department_name'] ?? 'All Departments' }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $calculation['employee_count'] }}</span>
                                    </td>
                                    <td>₹{{ number_format($calculation['gross_amount'], 2) }}</td>
                                    <td>₹{{ number_format($calculation['deduction_amount'], 2) }}</td>
                                    <td>₹{{ number_format($calculation['net_amount'], 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $calculation['status'] == 'completed' ? 'success' : 
                                            ($calculation['status'] == 'processing' ? 'warning' : 
                                            ($calculation['status'] == 'approved' ? 'info' : 'secondary')) 
                                        }}">
                                            {{ ucfirst($calculation['status']) }}
                                        </span>
                                    </td>
                                    <td>{{ $calculation['processed_by'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($calculation['created_at'])->format('d M Y, H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewCalculation({{ $calculation['id'] }})" 
                                                    title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            @if($calculation['status'] == 'completed')
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="approveCalculation({{ $calculation['id'] }})" 
                                                    title="Approve">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="downloadCalculation({{ $calculation['id'] }})" 
                                                    title="Download">
                                                <i class="mdi mdi-download"></i>
                                            </button>
                                            @if(in_array($calculation['status'], ['pending', 'processing']))
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="cancelCalculation({{ $calculation['id'] }})" 
                                                    title="Cancel">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $recentCalculations->firstItem() ?? 0 }} to {{ $recentCalculations->lastItem() ?? 0 }} 
                            of {{ $recentCalculations->total() ?? 0 }} results
                        </div>
                        <div>
                            {{ $recentCalculations->links() ?? '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calculation Details Modal -->
<div class="modal fade" id="calculationDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calculation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="calculationDetailsContent">
                <!-- Calculation details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="exportCalculationDetails()">
                    <i class="mdi mdi-download me-1"></i>Export Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calculation Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="proceedWithCalculation()">
                    <i class="mdi mdi-play me-1"></i>Proceed with Calculation
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
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

.progress {
    height: 1rem;
}

.progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.badge {
    font-size: 0.75rem;
}

.text-muted {
    color: #6c757d !important;
}

.alert {
    border: 1px solid transparent;
    border-radius: 0.375rem;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
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
    
    .d-grid {
        gap: 0.25rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
let currentCalculationId = null;
let processingInterval = null;

$(document).ready(function() {
    // Initialize form elements
    initializeSelects();
    
    // Check for ongoing calculations
    checkOngoingCalculations();
});

function initializeSelects() {
    // Initialize multi-select for specific employees
    if ($('#specificEmployees').length) {
        $('#specificEmployees').select2({
            placeholder: 'Select specific employees (optional)',
            allowClear: true,
            width: '100%'
        });
    }
}

function processPayroll() {
    if (!validateForm()) {
        return;
    }
    
    const formData = new FormData($('#payrollForm')[0]);
    
    // Show processing UI
    $('#processingProgress').show();
    $('#processingResults').hide();
    updateProgress(0, 'Initializing payroll processing...');
    
    $.ajax({
        url: '{{ route("salary.calculations.process") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                currentCalculationId = response.calculation_id;
                
                // Start monitoring progress
                processingInterval = setInterval(checkProgress, 2000);
                
                toastr.success('Payroll processing started successfully');
            } else {
                $('#processingProgress').hide();
                toastr.error(response.message || 'Failed to start payroll processing');
            }
        },
        error: function(xhr) {
            $('#processingProgress').hide();
            console.error('Failed to process payroll:', xhr);
            
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    toastr.error(errors[key][0]);
                });
            } else {
                toastr.error('Failed to process payroll');
            }
        }
    });
}

function checkProgress() {
    if (!currentCalculationId) {
        return;
    }
    
    $.ajax({
        url: '{{ route("salary.calculations.progress") }}',
        method: 'GET',
        data: { calculation_id: currentCalculationId },
        success: function(response) {
            if (response.success) {
                const progress = response.data;
                
                updateProgress(progress.percentage, progress.status);
                
                if (progress.completed) {
                    clearInterval(processingInterval);
                    showResults(progress);
                }
            }
        },
        error: function(xhr) {
            console.error('Failed to check progress:', xhr);
            clearInterval(processingInterval);
            $('#processingProgress').hide();
            toastr.error('Failed to check processing progress');
        }
    });
}

function updateProgress(percentage, status) {
    $('#progressBar').css('width', percentage + '%');
    $('#progressText').text(Math.round(percentage) + '%');
    $('#progressStatus').text(status);
}

function showResults(results) {
    $('#processingProgress').hide();
    $('#processingResults').show();
    
    $('#processedCount').text(results.processed_count || 0);
    $('#successCount').text(results.success_count || 0);
    $('#errorCount').text(results.error_count || 0);
    $('#totalAmount').text('₹' + (results.total_amount ? number_format(results.total_amount, 2) : '0'));
    
    // Refresh the calculations table
    refreshCalculations();
}

function validateForm() {
    const payPeriod = $('#payPeriod').val();
    
    if (!payPeriod) {
        toastr.error('Please select a pay period');
        return false;
    }
    
    return true;
}

function validateCalculations() {
    const formData = new FormData($('#payrollForm')[0]);
    
    $.ajax({
        url: '{{ route("salary.calculations.validate") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                if (response.data.errors && response.data.errors.length > 0) {
                    let errorMessage = 'Validation found ' + response.data.errors.length + ' issues:\n';
                    response.data.errors.slice(0, 5).forEach(error => {
                        errorMessage += '- ' + error + '\n';
                    });
                    if (response.data.errors.length > 5) {
                        errorMessage += '... and ' + (response.data.errors.length - 5) + ' more';
                    }
                    toastr.warning(errorMessage);
                } else {
                    toastr.success('Validation passed successfully. Ready to process payroll.');
                }
            } else {
                toastr.error(response.message || 'Validation failed');
            }
        },
        error: function(xhr) {
            console.error('Validation failed:', xhr);
            toastr.error('Failed to validate calculations');
        }
    });
}

function previewCalculations() {
    const formData = new FormData($('#payrollForm')[0]);
    
    $.ajax({
        url: '{{ route("salary.calculations.preview") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                $('#previewContent').html(response.html);
                $('#previewModal').modal('show');
            } else {
                toastr.error(response.message || 'Failed to generate preview');
            }
        },
        error: function(xhr) {
            console.error('Failed to generate preview:', xhr);
            toastr.error('Failed to generate preview');
        }
    });
}

function proceedWithCalculation() {
    $('#previewModal').modal('hide');
    processPayroll();
}

function saveSettings() {
    const formData = new FormData($('#calculationSettings')[0]);
    
    $.ajax({
        url: '{{ route("salary.calculations.save-settings") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Settings saved successfully');
            } else {
                toastr.error(response.message || 'Failed to save settings');
            }
        },
        error: function(xhr) {
            console.error('Failed to save settings:', xhr);
            toastr.error('Failed to save settings');
        }
    });
}

function recalculateAll() {
    if (confirm('Are you sure you want to recalculate all payroll data? This may take some time.')) {
        $.ajax({
            url: '{{ route("salary.calculations.recalculate-all") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Recalculation started successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to start recalculation');
                }
            },
            error: function(xhr) {
                console.error('Failed to recalculate:', xhr);
                toastr.error('Failed to start recalculation');
            }
        });
    }
}

function generatePayslips() {
    window.location.href = '{{ route("salary.payslip.index") }}';
}

function exportCalculations() {
    const link = document.createElement('a');
    link.href = '{{ route("salary.calculations.export") }}';
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.success('Export started');
}

function auditTrail() {
    window.open('{{ route("salary.calculations.audit") }}', '_blank');
}

function refreshCalculations() {
    location.reload();
}

function filterCalculations() {
    $('#calculationFilters').toggle();
}

function applyFilters() {
    const period = $('#filterPeriod').val().toLowerCase();
    const status = $('#filterStatus').val().toLowerCase();
    const department = $('#filterDepartment').val();
    const search = $('#searchCalculations').val().toLowerCase();
    
    $('#calculationsTable tbody tr').each(function() {
        const row = $(this);
        const rowPeriod = row.data('period');
        const rowStatus = row.data('status');
        const rowDepartment = row.data('department');
        const rowText = row.text().toLowerCase();
        
        let show = true;
        
        if (period && rowPeriod !== period) show = false;
        if (status && rowStatus !== status) show = false;
        if (department && rowDepartment != department) show = false;
        if (search && !rowText.includes(search)) show = false;
        
        row.toggle(show);
    });
}

function viewCalculation(calculationId) {
    $.ajax({
        url: '{{ route("salary.calculations.details") }}',
        method: 'GET',
        data: { id: calculationId },
        success: function(response) {
            if (response.success) {
                $('#calculationDetailsContent').html(response.html);
                currentCalculationId = calculationId;
                $('#calculationDetailsModal').modal('show');
            } else {
                toastr.error(response.message || 'Failed to load calculation details');
            }
        },
        error: function(xhr) {
            console.error('Failed to load calculation details:', xhr);
            toastr.error('Failed to load calculation details');
        }
    });
}

function approveCalculation(calculationId) {
    if (confirm('Are you sure you want to approve this calculation?')) {
        $.ajax({
            url: '{{ route("salary.calculations.approve") }}',
            method: 'POST',
            data: { id: calculationId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Calculation approved successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to approve calculation');
                }
            },
            error: function(xhr) {
                console.error('Failed to approve calculation:', xhr);
                toastr.error('Failed to approve calculation');
            }
        });
    }
}

function downloadCalculation(calculationId) {
    const link = document.createElement('a');
    link.href = '{{ route("salary.calculations.download") }}?id=' + calculationId;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.success('Download started');
}

function cancelCalculation(calculationId) {
    if (confirm('Are you sure you want to cancel this calculation?')) {
        $.ajax({
            url: '{{ route("salary.calculations.cancel") }}',
            method: 'POST',
            data: { id: calculationId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Calculation cancelled successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to cancel calculation');
                }
            },
            error: function(xhr) {
                console.error('Failed to cancel calculation:', xhr);
                toastr.error('Failed to cancel calculation');
            }
        });
    }
}

function exportCalculationDetails() {
    if (currentCalculationId) {
        downloadCalculation(currentCalculationId);
    }
}

function checkOngoingCalculations() {
    $.ajax({
        url: '{{ route("salary.calculations.ongoing") }}',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                const ongoing = response.data[0];
                currentCalculationId = ongoing.id;
                
                $('#processingProgress').show();
                updateProgress(ongoing.progress || 0, ongoing.status || 'Processing...');
                
                // Start monitoring progress
                processingInterval = setInterval(checkProgress, 2000);
            }
        },
        error: function(xhr) {
            console.error('Failed to check ongoing calculations:', xhr);
        }
    });
}

function downloadReport() {
    if (currentCalculationId) {
        downloadCalculation(currentCalculationId);
    }
}

function viewResults() {
    if (currentCalculationId) {
        viewCalculation(currentCalculationId);
    }
}

// Utility function for number formatting
function number_format(number, decimals, dec_point, thousands_sep) {
    decimals = decimals || 0;
    number = parseFloat(number);
    
    if (!isFinite(number) || (!number && number !== 0)) {
        return '';
    }
    
    dec_point = dec_point || '.';
    thousands_sep = thousands_sep || ',';
    
    const parts = number.toFixed(decimals).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);
    
    return parts.join(dec_point);
}
</script>
@endpush