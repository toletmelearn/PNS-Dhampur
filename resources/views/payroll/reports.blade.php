@extends('layouts.app')

@section('title', 'Payroll Reports')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Payroll Reports</h2>
                    <p class="text-muted">Generate comprehensive payroll reports and analytics</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" id="generateReportBtn">
                        <i class="fas fa-chart-bar me-2"></i>Generate Report
                    </button>
                    <button class="btn btn-success" id="exportReportBtn">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Report Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="reportType" class="form-label">Report Type</label>
                        <select class="form-select" id="reportType">
                            <option value="monthly_summary">Monthly Summary</option>
                            <option value="employee_wise">Employee Wise</option>
                            <option value="department_wise">Department Wise</option>
                            <option value="deduction_summary">Deduction Summary</option>
                            <option value="statutory_report">Statutory Report</option>
                            <option value="annual_summary">Annual Summary</option>
                            <option value="comparison_report">Comparison Report</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="reportMonth" class="form-label">Month</label>
                        <input type="month" class="form-control" id="reportMonth" value="{{ date('Y-m') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="reportYear" class="form-label">Year</label>
                        <select class="form-select" id="reportYear">
                            @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="departmentFilter" class="form-label">Department</label>
                        <select class="form-select" id="departmentFilter">
                            <option value="">All Departments</option>
                            <option value="teaching">Teaching</option>
                            <option value="non_teaching">Non-Teaching</option>
                            <option value="administration">Administration</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="employeeFilter" class="form-label">Employee</label>
                        <select class="form-select" id="employeeFilter">
                            <option value="">All Employees</option>
                            <!-- Will be populated via AJAX -->
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="exportFormat" class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Include Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeDeductions" checked>
                            <label class="form-check-label" for="includeDeductions">Include Deductions</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeAllowances" checked>
                            <label class="form-check-label" for="includeAllowances">Include Allowances</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Additional Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeCharts">
                            <label class="form-check-label" for="includeCharts">Include Charts</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="detailedBreakdown">
                            <label class="form-check-label" for="detailedBreakdown">Detailed Breakdown</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Payroll</h6>
                            <h3 class="mb-0" id="totalPayroll">₹0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-rupee-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Employees</h6>
                            <h3 class="mb-0" id="totalEmployees">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Deductions</h6>
                            <h3 class="mb-0" id="totalDeductions">₹0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-minus-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Net Payable</h6>
                            <h3 class="mb-0" id="netPayable">₹0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hand-holding-usd fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Report Results</h5>
        </div>
        <div class="card-body">
            <div id="reportContent">
                <div class="text-center py-5">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Select report parameters and click "Generate Report" to view results</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mt-4" id="chartsSection" style="display: none;">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Payroll Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="payrollTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Department Wise Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.opacity-75 {
    opacity: 0.75;
}

.report-table {
    font-size: 0.9rem;
}

.report-table th {
    background-color: #e9ecef;
    font-weight: 600;
}

.summary-row {
    background-color: #f8f9fa;
    font-weight: 600;
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    let payrollTrendChart, departmentChart;

    // Load initial data
    loadEmployees();
    loadQuickStats();

    // Generate Report
    $('#generateReportBtn').on('click', function() {
        generateReport();
    });

    // Export Report
    $('#exportReportBtn').on('click', function() {
        exportReport();
    });

    // Report type change handler
    $('#reportType').on('change', function() {
        let reportType = $(this).val();
        
        // Show/hide relevant filters based on report type
        if (reportType === 'employee_wise') {
            $('#employeeFilter').closest('.col-md-3').show();
        } else {
            $('#employeeFilter').closest('.col-md-3').hide();
        }
        
        if (reportType === 'department_wise') {
            $('#departmentFilter').closest('.col-md-2').show();
        } else if (reportType !== 'employee_wise') {
            $('#departmentFilter').closest('.col-md-2').show();
        }
    });

    function generateReport() {
        let reportType = $('#reportType').val();
        let reportMonth = $('#reportMonth').val();
        let reportYear = $('#reportYear').val();
        let department = $('#departmentFilter').val();
        let employee = $('#employeeFilter').val();
        let includeDeductions = $('#includeDeductions').is(':checked');
        let includeAllowances = $('#includeAllowances').is(':checked');
        let includeCharts = $('#includeCharts').is(':checked');
        let detailedBreakdown = $('#detailedBreakdown').is(':checked');

        // Show loading
        $('#reportContent').html(`
            <div class="text-center py-5">
                <div class="loading-spinner"></div>
                <h5 class="text-muted mt-3">Generating report...</h5>
            </div>
        `);

        $.ajax({
            url: '{{ route("payroll.reports.generate") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                report_type: reportType,
                month: reportMonth,
                year: reportYear,
                department: department,
                employee: employee,
                include_deductions: includeDeductions,
                include_allowances: includeAllowances,
                include_charts: includeCharts,
                detailed_breakdown: detailedBreakdown
            },
            success: function(response) {
                displayReport(response);
                
                if (includeCharts && response.chart_data) {
                    displayCharts(response.chart_data);
                }
                
                loadQuickStats();
            },
            error: function(xhr) {
                $('#reportContent').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h5 class="text-danger">Error generating report</h5>
                        <p class="text-muted">${xhr.responseJSON?.message || 'Please try again later'}</p>
                    </div>
                `);
            }
        });
    }

    function displayReport(data) {
        let html = '';
        
        if (data.report_type === 'monthly_summary') {
            html = generateMonthlySummaryReport(data);
        } else if (data.report_type === 'employee_wise') {
            html = generateEmployeeWiseReport(data);
        } else if (data.report_type === 'department_wise') {
            html = generateDepartmentWiseReport(data);
        } else if (data.report_type === 'deduction_summary') {
            html = generateDeductionSummaryReport(data);
        } else if (data.report_type === 'statutory_report') {
            html = generateStatutoryReport(data);
        } else if (data.report_type === 'annual_summary') {
            html = generateAnnualSummaryReport(data);
        } else if (data.report_type === 'comparison_report') {
            html = generateComparisonReport(data);
        }
        
        $('#reportContent').html(html);
    }

    function generateMonthlySummaryReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Monthly Payroll Summary - ${data.period}</h4>
                <p class="text-muted">Generated on ${new Date().toLocaleDateString('en-IN')}</p>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Gross Salary</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        let totalGross = 0, totalDeductions = 0, totalNet = 0;
        
        data.employees.forEach(function(employee) {
            totalGross += parseFloat(employee.gross_salary);
            totalDeductions += parseFloat(employee.total_deductions);
            totalNet += parseFloat(employee.net_salary);
            
            html += `
                <tr>
                    <td>${employee.name}</td>
                    <td>${employee.department || 'N/A'}</td>
                    <td>₹${parseFloat(employee.basic_salary).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.total_allowances).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.gross_salary).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.total_deductions).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.net_salary).toLocaleString('en-IN')}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <th colspan="4">Total</th>
                            <th>₹${totalGross.toLocaleString('en-IN')}</th>
                            <th>₹${totalDeductions.toLocaleString('en-IN')}</th>
                            <th>₹${totalNet.toLocaleString('en-IN')}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        return html;
    }

    function generateEmployeeWiseReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Employee Wise Payroll Report - ${data.period}</h4>
                <p class="text-muted">Employee: ${data.employee_name}</p>
            </div>
        `;
        
        if (data.salary_details) {
            html += `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Earnings</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><td>Basic Salary</td><td class="text-end">₹${parseFloat(data.salary_details.basic_salary).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>HRA</td><td class="text-end">₹${parseFloat(data.salary_details.hra || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>DA</td><td class="text-end">₹${parseFloat(data.salary_details.da || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>TA</td><td class="text-end">₹${parseFloat(data.salary_details.ta || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>Medical Allowance</td><td class="text-end">₹${parseFloat(data.salary_details.medical_allowance || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>Special Allowance</td><td class="text-end">₹${parseFloat(data.salary_details.special_allowance || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr class="fw-bold"><td>Gross Salary</td><td class="text-end">₹${parseFloat(data.salary_details.gross_salary).toLocaleString('en-IN')}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Deductions</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><td>PF</td><td class="text-end">₹${parseFloat(data.salary_details.pf_deduction || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>ESI</td><td class="text-end">₹${parseFloat(data.salary_details.esi_deduction || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>Professional Tax</td><td class="text-end">₹${parseFloat(data.salary_details.professional_tax || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>TDS</td><td class="text-end">₹${parseFloat(data.salary_details.tds || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr><td>Other Deductions</td><td class="text-end">₹${parseFloat(data.salary_details.other_deductions || 0).toLocaleString('en-IN')}</td></tr>
                                    <tr class="fw-bold"><td>Total Deductions</td><td class="text-end">₹${parseFloat(data.salary_details.total_deductions).toLocaleString('en-IN')}</td></tr>
                                    <tr class="fw-bold text-success"><td>Net Salary</td><td class="text-end">₹${parseFloat(data.salary_details.net_salary).toLocaleString('en-IN')}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        return html;
    }

    function generateDepartmentWiseReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Department Wise Payroll Report - ${data.period}</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Employees</th>
                            <th>Total Basic</th>
                            <th>Total Allowances</th>
                            <th>Total Gross</th>
                            <th>Total Deductions</th>
                            <th>Total Net</th>
                            <th>Average Salary</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        let grandTotal = { employees: 0, basic: 0, allowances: 0, gross: 0, deductions: 0, net: 0 };
        
        data.departments.forEach(function(dept) {
            grandTotal.employees += parseInt(dept.employee_count);
            grandTotal.basic += parseFloat(dept.total_basic);
            grandTotal.allowances += parseFloat(dept.total_allowances);
            grandTotal.gross += parseFloat(dept.total_gross);
            grandTotal.deductions += parseFloat(dept.total_deductions);
            grandTotal.net += parseFloat(dept.total_net);
            
            html += `
                <tr>
                    <td>${dept.department}</td>
                    <td>${dept.employee_count}</td>
                    <td>₹${parseFloat(dept.total_basic).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(dept.total_allowances).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(dept.total_gross).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(dept.total_deductions).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(dept.total_net).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(dept.average_salary).toLocaleString('en-IN')}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <th>Total</th>
                            <th>${grandTotal.employees}</th>
                            <th>₹${grandTotal.basic.toLocaleString('en-IN')}</th>
                            <th>₹${grandTotal.allowances.toLocaleString('en-IN')}</th>
                            <th>₹${grandTotal.gross.toLocaleString('en-IN')}</th>
                            <th>₹${grandTotal.deductions.toLocaleString('en-IN')}</th>
                            <th>₹${grandTotal.net.toLocaleString('en-IN')}</th>
                            <th>₹${(grandTotal.net / grandTotal.employees).toLocaleString('en-IN')}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        return html;
    }

    function generateDeductionSummaryReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Deduction Summary Report - ${data.period}</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr>
                            <th>Deduction Type</th>
                            <th>Number of Employees</th>
                            <th>Total Amount</th>
                            <th>Average Amount</th>
                            <th>Percentage of Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        let totalAmount = 0;
        data.deductions.forEach(function(deduction) {
            totalAmount += parseFloat(deduction.total_amount);
        });
        
        data.deductions.forEach(function(deduction) {
            let percentage = totalAmount > 0 ? ((parseFloat(deduction.total_amount) / totalAmount) * 100).toFixed(2) : 0;
            
            html += `
                <tr>
                    <td>${deduction.deduction_type.charAt(0).toUpperCase() + deduction.deduction_type.slice(1)}</td>
                    <td>${deduction.employee_count}</td>
                    <td>₹${parseFloat(deduction.total_amount).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(deduction.average_amount).toLocaleString('en-IN')}</td>
                    <td>${percentage}%</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <th>Total</th>
                            <th>-</th>
                            <th>₹${totalAmount.toLocaleString('en-IN')}</th>
                            <th>-</th>
                            <th>100%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        return html;
    }

    function generateStatutoryReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Statutory Deductions Report - ${data.period}</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>PAN</th>
                            <th>PF Number</th>
                            <th>ESI Number</th>
                            <th>PF Deduction</th>
                            <th>ESI Deduction</th>
                            <th>Professional Tax</th>
                            <th>TDS</th>
                            <th>Total Statutory</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        let totals = { pf: 0, esi: 0, pt: 0, tds: 0, total: 0 };
        
        data.employees.forEach(function(employee) {
            totals.pf += parseFloat(employee.pf_deduction || 0);
            totals.esi += parseFloat(employee.esi_deduction || 0);
            totals.pt += parseFloat(employee.professional_tax || 0);
            totals.tds += parseFloat(employee.tds || 0);
            totals.total += parseFloat(employee.total_statutory || 0);
            
            html += `
                <tr>
                    <td>${employee.name}</td>
                    <td>${employee.pan_number || 'N/A'}</td>
                    <td>${employee.pf_number || 'N/A'}</td>
                    <td>${employee.esi_number || 'N/A'}</td>
                    <td>₹${parseFloat(employee.pf_deduction || 0).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.esi_deduction || 0).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.professional_tax || 0).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.tds || 0).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(employee.total_statutory || 0).toLocaleString('en-IN')}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <th colspan="4">Total</th>
                            <th>₹${totals.pf.toLocaleString('en-IN')}</th>
                            <th>₹${totals.esi.toLocaleString('en-IN')}</th>
                            <th>₹${totals.pt.toLocaleString('en-IN')}</th>
                            <th>₹${totals.tds.toLocaleString('en-IN')}</th>
                            <th>₹${totals.total.toLocaleString('en-IN')}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        return html;
    }

    function generateAnnualSummaryReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Annual Payroll Summary - ${data.year}</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Employees</th>
                            <th>Gross Payroll</th>
                            <th>Total Deductions</th>
                            <th>Net Payroll</th>
                            <th>Average Salary</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        let yearTotals = { employees: 0, gross: 0, deductions: 0, net: 0 };
        
        data.months.forEach(function(month) {
            yearTotals.employees += parseInt(month.employee_count);
            yearTotals.gross += parseFloat(month.gross_payroll);
            yearTotals.deductions += parseFloat(month.total_deductions);
            yearTotals.net += parseFloat(month.net_payroll);
            
            html += `
                <tr>
                    <td>${month.month_name}</td>
                    <td>${month.employee_count}</td>
                    <td>₹${parseFloat(month.gross_payroll).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(month.total_deductions).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(month.net_payroll).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(month.average_salary).toLocaleString('en-IN')}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <th>Total</th>
                            <th>${(yearTotals.employees / data.months.length).toFixed(0)} (Avg)</th>
                            <th>₹${yearTotals.gross.toLocaleString('en-IN')}</th>
                            <th>₹${yearTotals.deductions.toLocaleString('en-IN')}</th>
                            <th>₹${yearTotals.net.toLocaleString('en-IN')}</th>
                            <th>₹${(yearTotals.net / yearTotals.employees * data.months.length).toLocaleString('en-IN')}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        return html;
    }

    function generateComparisonReport(data) {
        let html = `
            <div class="report-header mb-4">
                <h4>Payroll Comparison Report</h4>
                <p class="text-muted">Comparing ${data.period1} vs ${data.period2}</p>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>${data.period1}</th>
                            <th>${data.period2}</th>
                            <th>Difference</th>
                            <th>% Change</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.comparisons.forEach(function(comparison) {
            let difference = parseFloat(comparison.period2_value) - parseFloat(comparison.period1_value);
            let percentChange = parseFloat(comparison.period1_value) > 0 ? 
                ((difference / parseFloat(comparison.period1_value)) * 100).toFixed(2) : 0;
            let changeClass = difference >= 0 ? 'text-success' : 'text-danger';
            let changeIcon = difference >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
            
            html += `
                <tr>
                    <td>${comparison.metric}</td>
                    <td>₹${parseFloat(comparison.period1_value).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(comparison.period2_value).toLocaleString('en-IN')}</td>
                    <td class="${changeClass}">
                        <i class="fas ${changeIcon}"></i>
                        ₹${Math.abs(difference).toLocaleString('en-IN')}
                    </td>
                    <td class="${changeClass}">
                        <i class="fas ${changeIcon}"></i>
                        ${Math.abs(percentChange)}%
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        return html;
    }

    function displayCharts(chartData) {
        $('#chartsSection').show();
        
        // Payroll Trend Chart
        if (payrollTrendChart) {
            payrollTrendChart.destroy();
        }
        
        const trendCtx = document.getElementById('payrollTrendChart').getContext('2d');
        payrollTrendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: chartData.trend.labels,
                datasets: [{
                    label: 'Gross Payroll',
                    data: chartData.trend.gross,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Net Payroll',
                    data: chartData.trend.net,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
        
        // Department Chart
        if (departmentChart) {
            departmentChart.destroy();
        }
        
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        departmentChart = new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: chartData.departments.labels,
                datasets: [{
                    data: chartData.departments.data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function exportReport() {
        let reportType = $('#reportType').val();
        let reportMonth = $('#reportMonth').val();
        let reportYear = $('#reportYear').val();
        let department = $('#departmentFilter').val();
        let employee = $('#employeeFilter').val();
        let exportFormat = $('#exportFormat').val();
        let includeDeductions = $('#includeDeductions').is(':checked');
        let includeAllowances = $('#includeAllowances').is(':checked');
        let includeCharts = $('#includeCharts').is(':checked');
        let detailedBreakdown = $('#detailedBreakdown').is(':checked');

        // Create form and submit
        let form = $('<form>', {
            method: 'POST',
            action: '{{ route("payroll.reports.export") }}'
        });
        
        form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        form.append($('<input>', { type: 'hidden', name: 'report_type', value: reportType }));
        form.append($('<input>', { type: 'hidden', name: 'month', value: reportMonth }));
        form.append($('<input>', { type: 'hidden', name: 'year', value: reportYear }));
        form.append($('<input>', { type: 'hidden', name: 'department', value: department }));
        form.append($('<input>', { type: 'hidden', name: 'employee', value: employee }));
        form.append($('<input>', { type: 'hidden', name: 'format', value: exportFormat }));
        form.append($('<input>', { type: 'hidden', name: 'include_deductions', value: includeDeductions }));
        form.append($('<input>', { type: 'hidden', name: 'include_allowances', value: includeAllowances }));
        form.append($('<input>', { type: 'hidden', name: 'include_charts', value: includeCharts }));
        form.append($('<input>', { type: 'hidden', name: 'detailed_breakdown', value: detailedBreakdown }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }

    function loadEmployees() {
        $.get('{{ route("payroll.api.employees") }}', function(data) {
            let options = '<option value="">All Employees</option>';
            data.forEach(function(employee) {
                options += `<option value="${employee.id}">${employee.name} (${employee.employee_id || 'N/A'})</option>`;
            });
            $('#employeeFilter').html(options);
        });
    }

    function loadQuickStats() {
        let month = $('#reportMonth').val();
        let year = $('#reportYear').val();
        
        $.get('{{ route("payroll.api.statistics") }}', { month: month, year: year }, function(data) {
            $('#totalPayroll').text('₹' + (data.total_payroll || 0).toLocaleString('en-IN'));
            $('#totalEmployees').text(data.total_employees || 0);
            $('#totalDeductions').text('₹' + (data.total_deductions || 0).toLocaleString('en-IN'));
            $('#netPayable').text('₹' + (data.net_payable || 0).toLocaleString('en-IN'));
        });
    }
});
</script>
@endsection