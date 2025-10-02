@extends('layouts.app')

@section('title', 'Fee Details')

@section('content')
<div class="container-fluid">
    <style>
        .fee-show-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .content-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn-back {
            background: #6c757d;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }

        .btn-action {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-primary-custom {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-success-custom {
            background: #28a745;
            color: white;
        }

        .btn-success-custom:hover {
            background: #218838;
            transform: translateY(-2px);
            color: white;
        }

        .btn-warning-custom {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning-custom:hover {
            background: #e0a800;
            transform: translateY(-2px);
            color: #212529;
        }

        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-partial {
            background: #fff3cd;
            color: #856404;
        }

        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .amount-highlight {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .amount-paid {
            color: #28a745;
        }

        .amount-pending {
            color: #dc3545;
        }

        .overdue-indicator {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .payment-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .payment-history-table th,
        .payment-history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .payment-history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .payment-history-table tr:hover {
            background: #f8f9fa;
        }

        .no-payments {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 30px;
        }

        .student-card {
            background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-right: 20px;
        }

        .student-info {
            display: flex;
            align-items: center;
        }

        .student-details h4 {
            margin: 0;
            color: #2c3e50;
            font-weight: 700;
        }

        .student-details p {
            margin: 5px 0 0 0;
            color: #6c757d;
        }

        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .btn-close {
            filter: invert(1);
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .cheque-fields {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .receipt-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>

    <div class="fee-show-container">
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-receipt me-3"></i>Fee Details
                </h1>
                <div class="header-actions">
                    <a href="{{ route('fees.index') }}" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Fees
                    </a>
                    @if($fee->status !== 'paid')
                        <button type="button" class="btn-action btn-success-custom" onclick="recordPayment({{ $fee->id }})">
                            <i class="fas fa-credit-card"></i> Record Payment
                        </button>
                    @endif
                    <a href="{{ route('fees.edit', $fee->id) }}" class="btn-action btn-warning-custom">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @if($fee->payments && $fee->payments->count() > 0)
                        <a href="{{ route('fees.receipt', $fee->id) }}" class="btn-action btn-primary-custom" target="_blank">
                            <i class="fas fa-download"></i> Download Receipt
                        </a>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            <!-- Student Information -->
            <div class="student-card">
                <div class="student-info">
                    <div class="student-avatar">
                        {{ strtoupper(substr($fee->student->user->name, 0, 1)) }}
                    </div>
                    <div class="student-details">
                        <h4>{{ $fee->student->user->name }}</h4>
                        <p><i class="fas fa-envelope"></i> {{ $fee->student->user->email }}</p>
                        <p><i class="fas fa-graduation-cap"></i> Class: {{ $fee->student->classModel->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Fee Information -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Fee Information
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Fee Type</span>
                        <span class="info-value">{{ ucfirst($fee->fee_type) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Academic Year</span>
                        <span class="info-value">{{ $fee->academic_year }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Month</span>
                        <span class="info-value">{{ $fee->month ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Due Date</span>
                        <span class="info-value">
                            {{ $fee->due_date->format('d M Y') }}
                            @if($fee->is_overdue)
                                <span class="overdue-indicator">OVERDUE</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ $fee->status }}">{{ ucfirst($fee->status) }}</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created Date</span>
                        <span class="info-value">{{ $fee->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>

            <!-- Amount Details -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-calculator"></i>
                    Amount Details
                </h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Base Amount</span>
                        <span class="info-value amount-highlight">₹{{ number_format($fee->amount, 2) }}</span>
                    </div>
                    @if($fee->late_fee > 0)
                        <div class="info-item">
                            <span class="info-label">Late Fee</span>
                            <span class="info-value">₹{{ number_format($fee->late_fee, 2) }}</span>
                        </div>
                    @endif
                    @if($fee->discount > 0)
                        <div class="info-item">
                            <span class="info-label">Discount</span>
                            <span class="info-value">-₹{{ number_format($fee->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="info-item">
                        <span class="info-label">Total Amount</span>
                        <span class="info-value amount-highlight">₹{{ number_format($fee->amount + ($fee->late_fee ?? 0) - ($fee->discount ?? 0), 2) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Paid Amount</span>
                        <span class="info-value amount-highlight amount-paid">₹{{ number_format($fee->paid_amount, 2) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Remaining Amount</span>
                        <span class="info-value amount-highlight amount-pending">₹{{ number_format($fee->remaining_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="info-section">
                <h3 class="section-title">
                    <i class="fas fa-history"></i>
                    Payment History
                </h3>
                
                @if($fee->payments && $fee->payments->count() > 0)
                    <table class="payment-history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Mode</th>
                                <th>Transaction ID</th>
                                <th>Receipt</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fee->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td class="amount-paid">₹{{ number_format($payment->amount_paid, 2) }}</td>
                                    <td>{{ ucfirst($payment->payment_mode) }}</td>
                                    <td>{{ $payment->transaction_id ?? '-' }}</td>
                                    <td>
                                        @if($payment->receipt_no)
                                            <a href="{{ route('fees.receipt', $fee->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-download"></i> {{ $payment->receipt_no }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $payment->remarks ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-payments">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <p>No payments recorded for this fee yet.</p>
                    </div>
                @endif
            </div>

            <!-- Remarks -->
            @if($fee->remarks)
                <div class="info-section">
                    <h3 class="section-title">
                        <i class="fas fa-sticky-note"></i>
                        Remarks
                    </h3>
                    <p class="info-value">{{ $fee->remarks }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card me-2"></i>Record Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="paymentForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount to Pay <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                                    <small class="text-muted">Maximum: ₹<span id="maxAmount">0.00</span></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                    <select name="payment_mode" class="form-select" required>
                                        <option value="">Select Payment Mode</option>
                                        <option value="cash">Cash</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="online">Online Transfer</option>
                                        <option value="card">Card Payment</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Transaction ID</label>
                                    <input type="text" name="transaction_id" class="form-control" placeholder="Enter transaction ID">
                                </div>
                            </div>
                        </div>

                        <!-- Cheque Fields (Hidden by default) -->
                        <div class="cheque-fields" id="chequeFields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cheque Number</label>
                                        <input type="text" name="cheque_number" class="form-control" placeholder="Enter cheque number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control" placeholder="Enter bank name">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function recordPayment(feeId) {
            // Set form action
            document.getElementById('paymentForm').action = `/fees/${feeId}/pay`;
            
            // Set maximum amount
            const remainingAmount = {{ $fee->remaining_amount }};
            document.getElementById('maxAmount').textContent = remainingAmount.toFixed(2);
            document.querySelector('input[name="amount"]').max = remainingAmount;
            document.querySelector('input[name="amount"]').value = remainingAmount;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
        }

        // Show/hide cheque fields based on payment mode
        document.querySelector('select[name="payment_mode"]').addEventListener('change', function() {
            const chequeFields = document.getElementById('chequeFields');
            if (this.value === 'cheque') {
                chequeFields.style.display = 'block';
            } else {
                chequeFields.style.display = 'none';
            }
        });

        // Form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const amount = parseFloat(document.querySelector('input[name="amount"]').value);
            const maxAmount = {{ $fee->remaining_amount }};
            
            if (amount > maxAmount) {
                e.preventDefault();
                alert(`Payment amount cannot exceed remaining amount of ₹${maxAmount.toFixed(2)}`);
                return false;
            }
            
            if (amount <= 0) {
                e.preventDefault();
                alert('Payment amount must be greater than 0');
                return false;
            }
        });
    </script>
</div>
@endsection