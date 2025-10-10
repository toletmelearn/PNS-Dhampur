@extends('layouts.app')

@section('title', 'Payroll Deductions Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Payroll Deductions Management</h2>
                    <p class="text-muted">Manage employee deductions, loans, advances, and statutory deductions</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deductionModal">
                    <i class="fas fa-plus me-2"></i>Add New Deduction
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Deductions</h6>
                            <h3 class="mb-0" id="totalDeductions">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-minus-circle fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Pending Approvals</h6>
                            <h3 class="mb-0" id="pendingApprovals">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Active Loans</h6>
                            <h3 class="mb-0" id="activeLoans">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hand-holding-usd fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Monthly Deductions</h6>
                            <h3 class="mb-0" id="monthlyDeductions">₹0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-rupee-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <label for="typeFilter" class="form-label">Deduction Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="statutory">Statutory</option>
                        <option value="voluntary">Voluntary</option>
                        <option value="disciplinary">Disciplinary</option>
                        <option value="advance">Advance</option>
                        <option value="loan">Loan</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="employeeFilter" class="form-label">Employee</label>
                    <select class="form-select" id="employeeFilter">
                        <option value="">All Employees</option>
                        <!-- Will be populated via AJAX -->
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="monthFilter" class="form-label">Month</label>
                    <input type="month" class="form-control" id="monthFilter">
                </div>
                <div class="col-md-2">
                    <label for="amountFilter" class="form-label">Min Amount</label>
                    <input type="number" class="form-control" id="amountFilter" placeholder="Min amount">
                </div>
                <div class="col-md-2">
                    <label for="searchFilter" class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="Search deductions...">
                </div>
            </div>
        </div>
    </div>

    <!-- Deductions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Payroll Deductions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="deductionsTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Effective Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Deduction Modal -->
<div class="modal fade" id="deductionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add/Edit Deduction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deductionForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employeeId" class="form-label">Employee</label>
                                <select class="form-select" id="employeeId" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deductionType" class="form-label">Deduction Type</label>
                                <select class="form-select" id="deductionType" name="deduction_type" required>
                                    <option value="">Select Type</option>
                                    <option value="statutory">Statutory</option>
                                    <option value="voluntary">Voluntary</option>
                                    <option value="disciplinary">Disciplinary</option>
                                    <option value="advance">Advance</option>
                                    <option value="loan">Loan</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="calculationMethod" class="form-label">Calculation Method</label>
                                <select class="form-select" id="calculationMethod" name="calculation_method">
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage_gross">Percentage of Gross</option>
                                    <option value="percentage_basic">Percentage of Basic</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rate" class="form-label">Rate (%)</label>
                                <input type="number" class="form-control" id="rate" name="rate" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="effectiveFrom" class="form-label">Effective From</label>
                                <input type="date" class="form-control" id="effectiveFrom" name="effective_from" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="effectiveTo" class="form-label">Effective To (Optional)</label>
                                <input type="date" class="form-control" id="effectiveTo" name="effective_to">
                            </div>
                        </div>
                    </div>

                    <!-- Loan/Advance Specific Fields -->
                    <div id="loanAdvanceFields" style="display: none;">
                        <h6 class="mb-3">Loan/Advance Details</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="totalAmount" class="form-label">Total Amount</label>
                                    <input type="number" class="form-control" id="totalAmount" name="loan_advance_details[total_amount]" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="installments" class="form-label">Number of Installments</label>
                                    <input type="number" class="form-control" id="installments" name="loan_advance_details[installments]">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="interestRate" class="form-label">Interest Rate (%)</label>
                                    <input type="number" class="form-control" id="interestRate" name="loan_advance_details[interest_rate]" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statutory Specific Fields -->
                    <div id="statutoryFields" style="display: none;">
                        <h6 class="mb-3">Statutory Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="panNumber" class="form-label">PAN Number</label>
                                    <input type="text" class="form-control" id="panNumber" name="statutory_details[pan_number]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pfNumber" class="form-label">PF Number</label>
                                    <input type="text" class="form-control" id="pfNumber" name="statutory_details[pf_number]">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="esiNumber" class="form-label">ESI Number</label>
                                    <input type="text" class="form-control" id="esiNumber" name="statutory_details[esi_number]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="uanNumber" class="form-label">UAN Number</label>
                                    <input type="text" class="form-control" id="uanNumber" name="statutory_details[uan_number]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="isRecurring" class="form-label">Recurring</label>
                                <select class="form-select" id="isRecurring" name="is_recurring">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Deduction</button>
                </div>
            </form>
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

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.modal-lg {
    max-width: 900px;
}

.opacity-75 {
    opacity: 0.75;
}
</style>

<script>
$(document).ready(function() {
    let deductionsTable;
    let editingDeductionId = null;

    // Initialize DataTable
    deductionsTable = $('#deductionsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("payroll.deductions") }}',
            type: 'GET'
        },
        columns: [
            { data: 'employee_name', name: 'employee_name' },
            { 
                data: 'deduction_type', 
                name: 'deduction_type',
                render: function(data) {
                    let badgeClass = {
                        'statutory': 'bg-primary',
                        'voluntary': 'bg-success',
                        'disciplinary': 'bg-danger',
                        'advance': 'bg-warning',
                        'loan': 'bg-info',
                        'other': 'bg-secondary'
                    };
                    return `<span class="badge ${badgeClass[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { data: 'description', name: 'description' },
            { 
                data: 'amount', 
                name: 'amount',
                render: function(data) {
                    return '₹' + parseFloat(data).toLocaleString('en-IN');
                }
            },
            { 
                data: 'payroll_period', 
                name: 'payroll_period',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('en-IN', { year: 'numeric', month: 'long' }) : '-';
                }
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    let badgeClass = {
                        'pending': 'bg-warning',
                        'approved': 'bg-info',
                        'active': 'bg-success',
                        'completed': 'bg-primary',
                        'cancelled': 'bg-danger'
                    };
                    return `<span class="badge ${badgeClass[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'effective_from', 
                name: 'effective_from',
                render: function(data) {
                    return new Date(data).toLocaleDateString('en-IN');
                }
            },
            { 
                data: 'id', 
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let actions = `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-deduction" data-id="${data}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info view-deduction" data-id="${data}">
                                <i class="fas fa-eye"></i>
                            </button>
                    `;
                    
                    if (row.status === 'pending') {
                        actions += `
                            <button type="button" class="btn btn-sm btn-outline-success approve-deduction" data-id="${data}">
                                <i class="fas fa-check"></i>
                            </button>
                        `;
                    }
                    
                    actions += `
                            <button type="button" class="btn btn-sm btn-outline-danger delete-deduction" data-id="${data}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    
                    return actions;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Load initial data
    loadStatistics();
    loadEmployees();

    // Filter handlers
    $('#typeFilter, #statusFilter, #employeeFilter, #monthFilter, #amountFilter, #searchFilter').on('change keyup', function() {
        deductionsTable.draw();
    });

    // Deduction type change handler
    $('#deductionType').on('change', function() {
        let type = $(this).val();
        
        if (type === 'loan' || type === 'advance') {
            $('#loanAdvanceFields').show();
        } else {
            $('#loanAdvanceFields').hide();
        }
        
        if (type === 'statutory') {
            $('#statutoryFields').show();
        } else {
            $('#statutoryFields').hide();
        }
    });

    // Form submission
    $('#deductionForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let url = editingDeductionId ? 
            `{{ route('payroll.deductions.update', '') }}/${editingDeductionId}` : 
            '{{ route("payroll.deductions.store") }}';
        
        if (editingDeductionId) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#deductionModal').modal('hide');
                deductionsTable.ajax.reload();
                loadStatistics();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Deduction saved successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                let errors = xhr.responseJSON?.errors || {};
                let errorMessage = 'Please check the form for errors.';
                
                if (Object.keys(errors).length > 0) {
                    errorMessage = Object.values(errors)[0][0];
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        });
    });

    // Edit deduction
    $(document).on('click', '.edit-deduction', function() {
        editingDeductionId = $(this).data('id');
        
        // Load deduction data and populate form
        $.get(`{{ route('payroll.deductions') }}/${editingDeductionId}`, function(data) {
            $('#employeeId').val(data.employee_id);
            $('#deductionType').val(data.deduction_type).trigger('change');
            $('#description').val(data.description);
            $('#amount').val(data.amount);
            $('#calculationMethod').val(data.calculation_method);
            $('#rate').val(data.rate);
            $('#effectiveFrom').val(data.effective_from);
            $('#effectiveTo').val(data.effective_to);
            $('#priority').val(data.priority);
            $('#isRecurring').val(data.is_recurring ? '1' : '0');
            $('#remarks').val(data.remarks);
            
            // Populate loan/advance details
            if (data.loan_advance_details) {
                $('#totalAmount').val(data.loan_advance_details.total_amount);
                $('#installments').val(data.loan_advance_details.installments);
                $('#interestRate').val(data.loan_advance_details.interest_rate);
            }
            
            // Populate statutory details
            if (data.statutory_details) {
                $('#panNumber').val(data.statutory_details.pan_number);
                $('#pfNumber').val(data.statutory_details.pf_number);
                $('#esiNumber').val(data.statutory_details.esi_number);
                $('#uanNumber').val(data.statutory_details.uan_number);
            }
            
            $('#deductionModal').modal('show');
        });
    });

    // Approve deduction
    $(document).on('click', '.approve-deduction', function() {
        let deductionId = $(this).data('id');
        
        Swal.fire({
            title: 'Approve Deduction?',
            text: 'This will approve the deduction and make it active.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('payroll.deductions') }}/${deductionId}/approve`,
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        deductionsTable.ajax.reload();
                        loadStatistics();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: 'Deduction has been approved.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to approve deduction.'
                        });
                    }
                });
            }
        });
    });

    // Delete deduction
    $(document).on('click', '.delete-deduction', function() {
        let deductionId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will permanently delete the deduction.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('payroll.deductions.destroy', '') }}/${deductionId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        deductionsTable.ajax.reload();
                        loadStatistics();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Deduction has been deleted.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to delete deduction.'
                        });
                    }
                });
            }
        });
    });

    // Reset form when modal is hidden
    $('#deductionModal').on('hidden.bs.modal', function() {
        $('#deductionForm')[0].reset();
        $('#loanAdvanceFields, #statutoryFields').hide();
        editingDeductionId = null;
    });

    function loadStatistics() {
        $.get('{{ route("payroll.api.statistics") }}', function(data) {
            $('#totalDeductions').text(data.total_deductions || 0);
            $('#pendingApprovals').text(data.pending_approvals || 0);
            $('#activeLoans').text(data.active_loans || 0);
            $('#monthlyDeductions').text('₹' + (data.monthly_deductions || 0).toLocaleString('en-IN'));
        });
    }

    function loadEmployees() {
        $.get('{{ route("payroll.api.employees") }}', function(data) {
            let options = '<option value="">Select Employee</option>';
            data.forEach(function(employee) {
                options += `<option value="${employee.id}">${employee.name} (${employee.employee_id || 'N/A'})</option>`;
            });
            $('#employeeId, #employeeFilter').html(options);
        });
    }
});
</script>
@endsection