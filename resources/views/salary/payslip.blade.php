@extends('layouts.app')

@section('title', 'Payslip Generation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Payslip Generation</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('salary.index') }}">Salary</a></li>
                        <li class="breadcrumb-item active">Payslip</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Payslip Generation Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-document me-2"></i>
                        Generate Payslip
                    </h5>
                </div>
                <div class="card-body">
                    <form id="payslipForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" data-department="{{ $employee->department }}" 
                                            data-designation="{{ $employee->designation }}" data-salary="{{ $employee->basic_salary }}">
                                        {{ $employee->name }} ({{ $employee->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="pay_period" class="form-label">Pay Period</label>
                            <input type="month" class="form-control" id="pay_period" name="pay_period" 
                                   value="{{ date('Y-m') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label for="salary_type" class="form-label">Salary Type</label>
                            <select class="form-select" id="salary_type" name="salary_type" required>
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="daily">Daily</option>
                                <option value="hourly">Hourly</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payslip_template" class="form-label">Template</label>
                            <select class="form-select" id="payslip_template" name="template">
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="summary">Summary</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-file-document-outline me-1"></i>Generate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Information -->
    <div class="row" id="employeeInfo" style="display: none;">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Employee Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-2"><strong>Name:</strong></p>
                            <p class="mb-2"><strong>ID:</strong></p>
                            <p class="mb-2"><strong>Department:</strong></p>
                            <p class="mb-2"><strong>Designation:</strong></p>
                            <p class="mb-0"><strong>Join Date:</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2" id="empName">-</p>
                            <p class="mb-2" id="empId">-</p>
                            <p class="mb-2" id="empDepartment">-</p>
                            <p class="mb-2" id="empDesignation">-</p>
                            <p class="mb-0" id="empJoinDate">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Attendance Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-2"><strong>Working Days:</strong></p>
                            <p class="mb-2"><strong>Present:</strong></p>
                            <p class="mb-2"><strong>Absent:</strong></p>
                            <p class="mb-2"><strong>Late Days:</strong></p>
                            <p class="mb-0"><strong>Overtime Hours:</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2" id="workingDays">-</p>
                            <p class="mb-2 text-success" id="presentDays">-</p>
                            <p class="mb-2 text-danger" id="absentDays">-</p>
                            <p class="mb-2 text-warning" id="lateDays">-</p>
                            <p class="mb-0 text-info" id="overtimeHours">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Salary Overview</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-2"><strong>Basic Salary:</strong></p>
                            <p class="mb-2"><strong>Gross Salary:</strong></p>
                            <p class="mb-2"><strong>Deductions:</strong></p>
                            <p class="mb-0"><strong>Net Salary:</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2" id="basicSalary">₹0</p>
                            <p class="mb-2 text-success" id="grossSalary">₹0</p>
                            <p class="mb-2 text-danger" id="totalDeductions">₹0</p>
                            <p class="mb-0 text-primary fw-bold" id="netSalary">₹0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payslip Preview -->
    <div class="row" id="payslipPreview" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-document me-2"></i>
                        Payslip Preview
                    </h5>
                    <div class="card-header-actions">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadPayslip('pdf')">
                                <i class="mdi mdi-file-pdf me-1"></i>Download PDF
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="downloadPayslip('excel')">
                                <i class="mdi mdi-file-excel me-1"></i>Download Excel
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="emailPayslip()">
                                <i class="mdi mdi-email me-1"></i>Email
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printPayslip()">
                                <i class="mdi mdi-printer me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="payslipContent" class="payslip-content">
                        <!-- Payslip content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Payslip Generation -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-multiple me-2"></i>
                        Bulk Payslip Generation
                    </h5>
                </div>
                <div class="card-body">
                    <form id="bulkPayslipForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="bulk_department" class="form-label">Department</label>
                            <select class="form-select" id="bulk_department" name="department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="bulk_designation" class="form-label">Designation</label>
                            <select class="form-select" id="bulk_designation" name="designation">
                                <option value="">All Designations</option>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="bulk_pay_period" class="form-label">Pay Period</label>
                            <input type="month" class="form-control" id="bulk_pay_period" name="pay_period" 
                                   value="{{ date('Y-m') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label for="bulk_template" class="form-label">Template</label>
                            <select class="form-select" id="bulk_template" name="template">
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="summary">Summary</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="mdi mdi-file-multiple me-1"></i>Generate All
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="auto_email" name="auto_email">
                            <label class="form-check-label" for="auto_email">
                                Auto-email to employees
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="save_records" name="save_records" checked>
                            <label class="form-check-label" for="save_records">
                                Save payslip records
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="generate_summary" name="generate_summary">
                            <label class="form-check-label" for="generate_summary">
                                Generate department summary
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payslips -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-history me-2"></i>
                        Recent Payslips
                    </h5>
                    <div class="card-header-actions">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="searchPayslips" placeholder="Search payslips...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="payslipsTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Pay Period</th>
                                    <th>Gross Salary</th>
                                    <th>Deductions</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th>Generated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPayslips as $payslip)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <span class="avatar-title bg-primary rounded-circle">
                                                    {{ substr($payslip['employee_name'], 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $payslip['employee_name'] }}</h6>
                                                <small class="text-muted">{{ $payslip['employee_id'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $payslip['pay_period'] }}</td>
                                    <td class="text-success">₹{{ number_format($payslip['gross_salary'], 2) }}</td>
                                    <td class="text-danger">₹{{ number_format($payslip['total_deductions'], 2) }}</td>
                                    <td class="text-primary fw-bold">₹{{ number_format($payslip['net_salary'], 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $payslip['status'] == 'generated' ? 'success' : ($payslip['status'] == 'sent' ? 'info' : 'warning') }}">
                                            {{ ucfirst($payslip['status']) }}
                                        </span>
                                    </td>
                                    <td>{{ $payslip['created_at'] }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewPayslip({{ $payslip['id'] }})" 
                                                    title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="downloadPayslipById({{ $payslip['id'] }}, 'pdf')" 
                                                    title="Download PDF">
                                                <i class="mdi mdi-file-pdf"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" 
                                                    onclick="emailPayslipById({{ $payslip['id'] }})" 
                                                    title="Email">
                                                <i class="mdi mdi-email"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deletePayslip({{ $payslip['id'] }})" 
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Payslip Modal -->
<div class="modal fade" id="emailPayslipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Payslip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="emailPayslipForm">
                    <div class="mb-3">
                        <label for="email_to" class="form-label">To</label>
                        <input type="email" class="form-control" id="email_to" name="to" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_cc" class="form-label">CC</label>
                        <input type="email" class="form-control" id="email_cc" name="cc" 
                               placeholder="Multiple emails separated by commas">
                    </div>
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="email_subject" name="subject" 
                               value="Your Payslip for {{ date('F Y') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_message" class="form-label">Message</label>
                        <textarea class="form-control" id="email_message" name="message" rows="4" required>Dear Employee,

Please find attached your payslip for the month of {{ date('F Y') }}.

If you have any questions regarding your salary, please contact the HR department.

Best regards,
HR Department</textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email_password_protect" name="password_protect">
                            <label class="form-check-label" for="email_password_protect">
                                Password protect PDF (Employee ID will be used as password)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processEmailPayslip()">
                    <i class="mdi mdi-email me-1"></i>Send Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Generation Progress Modal -->
<div class="modal fade" id="bulkProgressModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Payslip Generation</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Progress</span>
                        <span id="progressText">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <h5 id="processedCount">0</h5>
                            <small class="text-muted">Processed</small>
                        </div>
                        <div class="col-4">
                            <h5 id="successCount">0</h5>
                            <small class="text-success">Success</small>
                        </div>
                        <div class="col-4">
                            <h5 id="errorCount">0</h5>
                            <small class="text-danger">Errors</small>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Status:</label>
                    <div id="currentStatus" class="text-muted">Initializing...</div>
                </div>
                <div id="errorList" class="alert alert-danger" style="display: none;">
                    <h6>Errors:</h6>
                    <ul id="errorItems"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelBulkGeneration" onclick="cancelBulkGeneration()">Cancel</button>
                <button type="button" class="btn btn-primary" id="closeBulkModal" onclick="closeBulkModal()" style="display: none;">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.payslip-content {
    background: white;
    padding: 30px;
    border: 1px solid #ddd;
    font-family: 'Arial', sans-serif;
}

.payslip-header {
    text-align: center;
    border-bottom: 2px solid #333;
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.payslip-header h2 {
    margin: 0;
    color: #333;
}

.payslip-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.payslip-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.payslip-table th,
.payslip-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.payslip-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.payslip-summary {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}

.payslip-footer {
    margin-top: 30px;
    text-align: center;
    font-size: 12px;
    color: #666;
}

@media print {
    .card-header,
    .btn,
    .breadcrumb {
        display: none !important;
    }
    
    .payslip-content {
        border: none;
        padding: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
let currentPayslipData = null;
let bulkGenerationInProgress = false;

$(document).ready(function() {
    // Form submissions
    $('#payslipForm').on('submit', function(e) {
        e.preventDefault();
        generatePayslip();
    });
    
    $('#bulkPayslipForm').on('submit', function(e) {
        e.preventDefault();
        generateBulkPayslips();
    });
    
    // Employee selection change
    $('#employee_id').on('change', function() {
        const employeeId = $(this).val();
        if (employeeId) {
            loadEmployeeInfo(employeeId);
        } else {
            $('#employeeInfo').hide();
        }
    });
    
    // Search functionality
    $('#searchPayslips').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#payslipsTable tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
});

function generatePayslip() {
    const formData = $('#payslipForm').serialize();
    
    // Show loading
    $('#payslipPreview').show();
    $('#payslipContent').html('<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Generating payslip...</p></div>');
    
    $.ajax({
        url: '{{ route("salary.payslip.generate") }}',
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                currentPayslipData = response.data;
                displayPayslip(response.data);
                updateEmployeeSalaryInfo(response.data);
            } else {
                toastr.error(response.message || 'Failed to generate payslip');
                $('#payslipPreview').hide();
            }
        },
        error: function(xhr) {
            console.error('Failed to generate payslip:', xhr);
            toastr.error('Failed to generate payslip');
            $('#payslipPreview').hide();
        }
    });
}

function loadEmployeeInfo(employeeId) {
    const payPeriod = $('#pay_period').val();
    
    $.ajax({
        url: '{{ route("salary.employee.info") }}',
        method: 'GET',
        data: { 
            employee_id: employeeId,
            pay_period: payPeriod
        },
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update employee details
                $('#empName').text(data.employee.name);
                $('#empId').text(data.employee.employee_id);
                $('#empDepartment').text(data.employee.department);
                $('#empDesignation').text(data.employee.designation);
                $('#empJoinDate').text(data.employee.join_date);
                
                // Update attendance summary
                $('#workingDays').text(data.attendance.working_days);
                $('#presentDays').text(data.attendance.present_days);
                $('#absentDays').text(data.attendance.absent_days);
                $('#lateDays').text(data.attendance.late_days);
                $('#overtimeHours').text(data.attendance.overtime_hours);
                
                // Update salary overview
                $('#basicSalary').text('₹' + formatNumber(data.salary.basic_salary));
                $('#grossSalary').text('₹' + formatNumber(data.salary.gross_salary));
                $('#totalDeductions').text('₹' + formatNumber(data.salary.total_deductions));
                $('#netSalary').text('₹' + formatNumber(data.salary.net_salary));
                
                $('#employeeInfo').show();
            } else {
                toastr.error(response.message || 'Failed to load employee information');
            }
        },
        error: function(xhr) {
            console.error('Failed to load employee info:', xhr);
            toastr.error('Failed to load employee information');
        }
    });
}

function displayPayslip(data) {
    const payslipHtml = generatePayslipHTML(data);
    $('#payslipContent').html(payslipHtml);
}

function generatePayslipHTML(data) {
    return `
        <div class="payslip-header">
            <h2>${data.company.name}</h2>
            <p>${data.company.address}</p>
            <h4>SALARY SLIP</h4>
            <p>For the month of ${data.pay_period}</p>
        </div>
        
        <div class="payslip-info">
            <div>
                <strong>Employee Name:</strong> ${data.employee.name}<br>
                <strong>Employee ID:</strong> ${data.employee.employee_id}<br>
                <strong>Department:</strong> ${data.employee.department}<br>
                <strong>Designation:</strong> ${data.employee.designation}
            </div>
            <div>
                <strong>Pay Period:</strong> ${data.pay_period}<br>
                <strong>Working Days:</strong> ${data.attendance.working_days}<br>
                <strong>Present Days:</strong> ${data.attendance.present_days}<br>
                <strong>LOP Days:</strong> ${data.attendance.lop_days}
            </div>
        </div>
        
        <table class="payslip-table">
            <thead>
                <tr>
                    <th colspan="2">EARNINGS</th>
                    <th colspan="2">DEDUCTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Particulars</strong></td>
                    <td><strong>Amount (₹)</strong></td>
                    <td><strong>Particulars</strong></td>
                    <td><strong>Amount (₹)</strong></td>
                </tr>
                ${generateEarningsDeductionsRows(data.earnings, data.deductions)}
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td>TOTAL EARNINGS</td>
                    <td>₹${formatNumber(data.totals.gross_salary)}</td>
                    <td>TOTAL DEDUCTIONS</td>
                    <td>₹${formatNumber(data.totals.total_deductions)}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="payslip-summary">
            <div class="row">
                <div class="col-6">
                    <strong>Gross Salary: ₹${formatNumber(data.totals.gross_salary)}</strong>
                </div>
                <div class="col-6 text-end">
                    <strong>Total Deductions: ₹${formatNumber(data.totals.total_deductions)}</strong>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <h4><strong>NET SALARY: ₹${formatNumber(data.totals.net_salary)}</strong></h4>
                    <p><em>In Words: ${data.totals.net_salary_words}</em></p>
                </div>
            </div>
        </div>
        
        <div class="payslip-footer">
            <p>This is a computer-generated payslip and does not require a signature.</p>
            <p>Generated on: ${new Date().toLocaleString()}</p>
        </div>
    `;
}

function generateEarningsDeductionsRows(earnings, deductions) {
    const maxRows = Math.max(earnings.length, deductions.length);
    let rows = '';
    
    for (let i = 0; i < maxRows; i++) {
        const earning = earnings[i] || { name: '', amount: '' };
        const deduction = deductions[i] || { name: '', amount: '' };
        
        rows += `
            <tr>
                <td>${earning.name}</td>
                <td>${earning.amount ? '₹' + formatNumber(earning.amount) : ''}</td>
                <td>${deduction.name}</td>
                <td>${deduction.amount ? '₹' + formatNumber(deduction.amount) : ''}</td>
            </tr>
        `;
    }
    
    return rows;
}

function updateEmployeeSalaryInfo(data) {
    $('#basicSalary').text('₹' + formatNumber(data.earnings.find(e => e.name === 'Basic Salary')?.amount || 0));
    $('#grossSalary').text('₹' + formatNumber(data.totals.gross_salary));
    $('#totalDeductions').text('₹' + formatNumber(data.totals.total_deductions));
    $('#netSalary').text('₹' + formatNumber(data.totals.net_salary));
}

function downloadPayslip(format) {
    if (!currentPayslipData) {
        toastr.error('No payslip data to download');
        return;
    }
    
    const formData = $('#payslipForm').serialize() + '&format=' + format;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = '{{ route("salary.payslip.download") }}?' + formData;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.success('Download started');
}

function emailPayslip() {
    if (!currentPayslipData) {
        toastr.error('No payslip data to email');
        return;
    }
    
    // Pre-fill email form
    $('#email_to').val(currentPayslipData.employee.email);
    $('#email_subject').val(`Payslip for ${currentPayslipData.pay_period} - ${currentPayslipData.employee.name}`);
    
    $('#emailPayslipModal').modal('show');
}

function processEmailPayslip() {
    const formData = new FormData($('#emailPayslipForm')[0]);
    formData.append('payslip_data', JSON.stringify(currentPayslipData));
    
    $.ajax({
        url: '{{ route("salary.payslip.email") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Payslip emailed successfully');
                $('#emailPayslipModal').modal('hide');
            } else {
                toastr.error(response.message || 'Failed to email payslip');
            }
        },
        error: function(xhr) {
            console.error('Failed to email payslip:', xhr);
            toastr.error('Failed to email payslip');
        }
    });
}

function printPayslip() {
    if (!currentPayslipData) {
        toastr.error('No payslip data to print');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payslip - ${currentPayslipData.employee.name}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .payslip-content { max-width: 800px; margin: 0 auto; }
                .payslip-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .payslip-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .payslip-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .payslip-table th, .payslip-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .payslip-table th { background-color: #f8f9fa; font-weight: bold; }
                .payslip-summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; }
                .payslip-footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="payslip-content">
                ${generatePayslipHTML(currentPayslipData)}
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

function generateBulkPayslips() {
    if (bulkGenerationInProgress) {
        toastr.warning('Bulk generation already in progress');
        return;
    }
    
    const formData = $('#bulkPayslipForm').serialize();
    
    // Show progress modal
    $('#bulkProgressModal').modal('show');
    bulkGenerationInProgress = true;
    
    // Reset progress
    updateBulkProgress(0, 0, 0, 0, 'Initializing bulk generation...');
    
    $.ajax({
        url: '{{ route("salary.payslip.bulk-generate") }}',
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Start polling for progress
                pollBulkProgress(response.batch_id);
            } else {
                toastr.error(response.message || 'Failed to start bulk generation');
                closeBulkModal();
            }
        },
        error: function(xhr) {
            console.error('Failed to start bulk generation:', xhr);
            toastr.error('Failed to start bulk generation');
            closeBulkModal();
        }
    });
}

function pollBulkProgress(batchId) {
    const pollInterval = setInterval(function() {
        $.ajax({
            url: '{{ route("salary.payslip.bulk-progress") }}',
            method: 'GET',
            data: { batch_id: batchId },
            success: function(response) {
                if (response.success) {
                    const progress = response.data;
                    updateBulkProgress(
                        progress.processed,
                        progress.success,
                        progress.errors,
                        progress.total,
                        progress.status
                    );
                    
                    if (progress.errors > 0) {
                        displayBulkErrors(progress.error_list);
                    }
                    
                    if (progress.completed) {
                        clearInterval(pollInterval);
                        bulkGenerationInProgress = false;
                        $('#cancelBulkGeneration').hide();
                        $('#closeBulkModal').show();
                        
                        if (progress.success > 0) {
                            toastr.success(`Bulk generation completed. ${progress.success} payslips generated successfully.`);
                        }
                    }
                }
            },
            error: function(xhr) {
                console.error('Failed to get bulk progress:', xhr);
                clearInterval(pollInterval);
                closeBulkModal();
            }
        });
    }, 2000); // Poll every 2 seconds
}

function updateBulkProgress(processed, success, errors, total, status) {
    const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
    
    $('#progressBar').css('width', percentage + '%');
    $('#progressText').text(percentage + '%');
    $('#processedCount').text(processed);
    $('#successCount').text(success);
    $('#errorCount').text(errors);
    $('#currentStatus').text(status);
}

function displayBulkErrors(errorList) {
    if (errorList && errorList.length > 0) {
        const errorItems = errorList.map(error => `<li>${error}</li>`).join('');
        $('#errorItems').html(errorItems);
        $('#errorList').show();
    }
}

function cancelBulkGeneration() {
    if (confirm('Are you sure you want to cancel the bulk generation?')) {
        // Implementation for canceling bulk generation
        bulkGenerationInProgress = false;
        closeBulkModal();
    }
}

function closeBulkModal() {
    bulkGenerationInProgress = false;
    $('#bulkProgressModal').modal('hide');
    $('#cancelBulkGeneration').show();
    $('#closeBulkModal').hide();
    $('#errorList').hide();
}

function viewPayslip(payslipId) {
    $.ajax({
        url: '{{ route("salary.payslip.view") }}',
        method: 'GET',
        data: { id: payslipId },
        success: function(response) {
            if (response.success) {
                currentPayslipData = response.data;
                displayPayslip(response.data);
                $('#payslipPreview').show();
                
                // Scroll to preview
                $('html, body').animate({
                    scrollTop: $('#payslipPreview').offset().top - 100
                }, 500);
            } else {
                toastr.error(response.message || 'Failed to load payslip');
            }
        },
        error: function(xhr) {
            console.error('Failed to load payslip:', xhr);
            toastr.error('Failed to load payslip');
        }
    });
}

function downloadPayslipById(payslipId, format) {
    const link = document.createElement('a');
    link.href = '{{ route("salary.payslip.download-by-id") }}?id=' + payslipId + '&format=' + format;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toastr.success('Download started');
}

function emailPayslipById(payslipId) {
    $.ajax({
        url: '{{ route("salary.payslip.email-by-id") }}',
        method: 'POST',
        data: { id: payslipId },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Payslip emailed successfully');
            } else {
                toastr.error(response.message || 'Failed to email payslip');
            }
        },
        error: function(xhr) {
            console.error('Failed to email payslip:', xhr);
            toastr.error('Failed to email payslip');
        }
    });
}

function deletePayslip(payslipId) {
    if (confirm('Are you sure you want to delete this payslip?')) {
        $.ajax({
            url: '{{ route("salary.payslip.delete") }}',
            method: 'DELETE',
            data: { id: payslipId },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Payslip deleted successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to delete payslip');
                }
            },
            error: function(xhr) {
                console.error('Failed to delete payslip:', xhr);
                toastr.error('Failed to delete payslip');
            }
        });
    }
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>
@endpush