@extends('layouts.app')

@section('title', 'Financial Management - PNS Dhampur')

@push('styles')
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .finance-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        background: white;
    }

    .finance-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .revenue-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .expense-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .profit-card {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        border: none;
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .pending-card {
        background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        border: none;
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        transform: translate(30px, -30px);
    }

    .fee-status-badge {
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .fee-paid {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }

    .fee-pending {
        background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        color: white;
    }

    .fee-overdue {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .chart-container {
        position: relative;
        height: 300px;
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .transaction-item {
        background: white;
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .transaction-item:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }

    .transaction-amount {
        font-size: 1.25rem;
        font-weight: 700;
    }

    .transaction-credit {
        color: #10b981;
    }

    .transaction-debit {
        color: #ef4444;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        border: none;
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .table-responsive {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem;
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-color: #f1f5f9;
    }

    .quick-action-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .quick-action-card:hover {
        border-color: #667eea;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }

    .quick-action-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
            padding: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">Financial Management</h1>
                    <p class="text-muted mb-0">Manage fees, salaries, expenses, and financial reports</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                        <i class="fas fa-plus me-2"></i>Add Transaction
                    </button>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#collectFeeModal">
                        <i class="fas fa-money-bill-wave me-2"></i>Collect Fee
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card revenue-card stats-card">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-chart-line fa-2x mb-3"></i>
                    <h3 class="mb-1">₹12,45,000</h3>
                    <p class="mb-0">Total Revenue</p>
                    <small class="opacity-75">+15% from last month</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card expense-card stats-card">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-chart-pie fa-2x mb-3"></i>
                    <h3 class="mb-1">₹8,75,000</h3>
                    <p class="mb-0">Total Expenses</p>
                    <small class="opacity-75">+8% from last month</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card profit-card stats-card">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-coins fa-2x mb-3"></i>
                    <h3 class="mb-1">₹3,70,000</h3>
                    <p class="mb-0">Net Profit</p>
                    <small class="opacity-75">+22% from last month</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card pending-card stats-card">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <h3 class="mb-1">₹2,15,000</h3>
                    <p class="mb-0">Pending Fees</p>
                    <small class="opacity-75">156 students</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Quick Actions</h5>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="quick-action-card" onclick="openFeeCollection()">
                <div class="quick-action-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h6 class="mb-1">Fee Collection</h6>
                <small class="text-muted">Collect student fees</small>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="quick-action-card" onclick="openSalaryManagement()">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h6 class="mb-1">Salary Management</h6>
                <small class="text-muted">Manage staff salaries</small>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="quick-action-card" onclick="openExpenseTracking()">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-receipt"></i>
                </div>
                <h6 class="mb-1">Expense Tracking</h6>
                <small class="text-muted">Track school expenses</small>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="quick-action-card" onclick="generateReports()">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h6 class="mb-1">Financial Reports</h6>
                <small class="text-muted">Generate reports</small>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="quick-action-card" onclick="openBudgetPlanning()">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-calculator"></i>
                </div>
                <h6 class="mb-1">Budget Planning</h6>
                <small class="text-muted">Plan annual budget</small>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="quick-action-card" onclick="openAuditTrail()">
                <div class="quick-action-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <i class="fas fa-search-dollar"></i>
                </div>
                <h6 class="mb-1">Audit Trail</h6>
                <small class="text-muted">Financial audit</small>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="chart-container">
                <h5 class="mb-3">Revenue vs Expenses</h5>
                <canvas id="revenueExpenseChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="chart-container">
                <h5 class="mb-3">Fee Collection Status</h5>
                <canvas id="feeStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card finance-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="transaction-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="quick-action-icon" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">Fee Collection - Class 10-A</h6>
                                    <small class="text-muted">Student: Rahul Sharma • Receipt: #FEE001</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="transaction-amount transaction-credit">+₹15,000</div>
                                <small class="text-muted">Today, 2:30 PM</small>
                            </div>
                        </div>
                    </div>

                    <div class="transaction-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="quick-action-icon" style="width: 50px; height: 50px; font-size: 1.2rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">Salary Payment - Teaching Staff</h6>
                                    <small class="text-muted">Teacher: Priya Sharma • Month: December</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="transaction-amount transaction-debit">-₹45,000</div>
                                <small class="text-muted">Yesterday, 11:00 AM</small>
                            </div>
                        </div>
                    </div>

                    <div class="transaction-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="quick-action-icon" style="width: 50px; height: 50px; font-size: 1.2rem; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-1">Utility Bill Payment</h6>
                                    <small class="text-muted">Electricity Bill • December 2024</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="transaction-amount transaction-debit">-₹12,500</div>
                                <small class="text-muted">2 days ago, 3:15 PM</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card finance-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Fee Defaulters</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Amit Kumar</h6>
                                <small class="text-muted">Class 9-A • Roll: 15</small>
                            </div>
                            <div class="text-end">
                                <span class="fee-status-badge fee-overdue">₹8,500</span>
                                <br><small class="text-muted">45 days overdue</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Sneha Patel</h6>
                                <small class="text-muted">Class 8-B • Roll: 22</small>
                            </div>
                            <div class="text-end">
                                <span class="fee-status-badge fee-pending">₹12,000</span>
                                <br><small class="text-muted">15 days overdue</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Ravi Singh</h6>
                                <small class="text-muted">Class 10-B • Roll: 8</small>
                            </div>
                            <div class="text-end">
                                <span class="fee-status-badge fee-overdue">₹15,000</span>
                                <br><small class="text-muted">60 days overdue</small>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-outline-primary btn-sm w-100">View All Defaulters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Collection Table -->
    <div class="row">
        <div class="col-12">
            <div class="card finance-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Fee Collection Records</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="feeCollectionTable">
                            <thead>
                                <tr>
                                    <th>Receipt No.</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#FEE001</strong></td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">Rahul Sharma</h6>
                                            <small class="text-muted">Roll: 15</small>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary">10-A</span></td>
                                    <td>Tuition Fee</td>
                                    <td><strong>₹15,000</strong></td>
                                    <td>
                                        <span class="badge bg-success">Cash</span>
                                    </td>
                                    <td>Dec 15, 2024</td>
                                    <td><span class="fee-status-badge fee-paid">Paid</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewReceipt('FEE001')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="printReceipt('FEE001')">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>#FEE002</strong></td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">Priya Gupta</h6>
                                            <small class="text-muted">Roll: 8</small>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary">9-B</span></td>
                                    <td>Annual Fee</td>
                                    <td><strong>₹25,000</strong></td>
                                    <td>
                                        <span class="badge bg-info">Online</span>
                                    </td>
                                    <td>Dec 14, 2024</td>
                                    <td><span class="fee-status-badge fee-paid">Paid</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewReceipt('FEE002')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="printReceipt('FEE002')">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Collect Fee Modal -->
<div class="modal fade" id="collectFeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Collect Student Fee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="collectFeeForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student *</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Select Student</option>
                                <option value="1">Rahul Sharma - 10-A (Roll: 15)</option>
                                <option value="2">Priya Gupta - 9-B (Roll: 8)</option>
                                <option value="3">Amit Kumar - 9-A (Roll: 15)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee Type *</label>
                            <select class="form-select" name="fee_type" required>
                                <option value="">Select Fee Type</option>
                                <option value="tuition">Tuition Fee</option>
                                <option value="annual">Annual Fee</option>
                                <option value="exam">Exam Fee</option>
                                <option value="transport">Transport Fee</option>
                                <option value="library">Library Fee</option>
                                <option value="sports">Sports Fee</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" class="form-control" name="amount" required min="0" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card Payment</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" placeholder="Transaction/Cheque Number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="collectFee()">Collect Fee</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Transaction</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTransactionForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Transaction Type *</label>
                        <select class="form-select" name="type" required>
                            <option value="">Select Type</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select Category</option>
                            <option value="salary">Staff Salary</option>
                            <option value="utilities">Utilities</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="supplies">Supplies</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount *</label>
                        <input type="number" class="form-control" name="amount" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="addTransaction()">Add Transaction</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#feeCollectionTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[6, 'desc']],
        columnDefs: [
            { orderable: false, targets: [8] }
        ]
    });

    // Initialize Charts
    initializeCharts();
});

function initializeCharts() {
    // Revenue vs Expenses Chart
    const revenueCtx = document.getElementById('revenueExpenseChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Revenue',
                data: [85000, 92000, 88000, 95000, 102000, 98000, 105000, 110000, 108000, 115000, 120000, 124500],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Expenses',
                data: [65000, 68000, 72000, 70000, 75000, 78000, 80000, 82000, 85000, 83000, 87000, 87500],
                borderColor: '#f093fb',
                backgroundColor: 'rgba(240, 147, 251, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Fee Status Chart
    const feeCtx = document.getElementById('feeStatusChart').getContext('2d');
    new Chart(feeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Pending', 'Overdue'],
            datasets: [{
                data: [75, 15, 10],
                backgroundColor: ['#43e97b', '#ffeaa7', '#f093fb'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Quick Action Functions
function openFeeCollection() {
    $('#collectFeeModal').modal('show');
}

function openSalaryManagement() {
    alert('Opening Salary Management...');
}

function openExpenseTracking() {
    alert('Opening Expense Tracking...');
}

function generateReports() {
    alert('Generating Financial Reports...');
}

function openBudgetPlanning() {
    alert('Opening Budget Planning...');
}

function openAuditTrail() {
    alert('Opening Audit Trail...');
}

// Form Functions
function collectFee() {
    var form = document.getElementById('collectFeeForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    var formData = new FormData(form);
    
    // Show loading state
    var submitBtn = event.target;
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    $.ajax({
        url: '/fees',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            // Show success message
            var alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Fee collected successfully! Receipt generated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.container-fluid').prepend(alertHtml);
            
            $('#collectFeeModal').modal('hide');
            form.reset();
            
            // Reload page after short delay to update statistics
            setTimeout(function() {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            var errorMessage = 'Error collecting fee';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join(', ');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            // Show error message
            var alertHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ${errorMessage}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.modal-body').prepend(alertHtml);
        },
        complete: function() {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function addTransaction() {
    if (!document.getElementById('addTransactionForm').checkValidity()) {
        document.getElementById('addTransactionForm').reportValidity();
        return;
    }

    var formData = {
        type: $('select[name="type"]').val(),
        category: $('select[name="category"]').val(),
        amount: $('input[name="amount"]').val(),
        description: $('input[name="description"]').val(),
        transaction_date: $('input[name="transaction_date"]').val()
    };

    $.ajax({
        type: 'POST',
        url: '/transactions',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert('Transaction added successfully!');
            $('#addTransactionModal').modal('hide');
            location.reload(); // Reload to update any lists or charts
        },
        error: function(xhr) {
            alert('Error adding transaction: ' + xhr.responseText);
        }
    });
}

function viewReceipt(receiptNo) {
    alert('Viewing receipt: ' + receiptNo);
}

function printReceipt(receiptNo) {
    alert('Printing receipt: ' + receiptNo);
}
</script>
@endpush