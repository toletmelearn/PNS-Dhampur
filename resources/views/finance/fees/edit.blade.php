@extends('layouts.app')

@section('title', 'Edit Fee Record')

@section('content')
<div class="container-fluid">
    <style>
        .fee-edit-container {
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
            max-width: 1000px;
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

        .form-section {
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }

        .required {
            color: #e74c3c;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary-custom {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary-custom:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f8f9fa;
        }

        .student-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }

        .amount-calculation {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .calculation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .calculation-row:last-child {
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .invalid-feedback {
            display: block;
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #e74c3c;
        }

        .payment-history {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .payment-item:last-child {
            border-bottom: none;
        }

        .payment-amount {
            font-weight: 600;
            color: #28a745;
        }

        .payment-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>

    <div class="fee-edit-container">
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-edit me-3"></i>Edit Fee Record
                </h1>
                <a href="{{ route('fees.index') }}" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Fees
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('fees.update', $fee->id) }}" method="POST" id="feeForm">
                @csrf
                @method('PUT')

                <!-- Student Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-graduate"></i>
                        Student Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Student <span class="required">*</span></label>
                                <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                                    <option value="">Select Student</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" 
                                                data-class="{{ $student->classModel->name ?? 'N/A' }}"
                                                data-email="{{ $student->user->email }}"
                                                {{ (old('student_id', $fee->student_id) == $student->id) ? 'selected' : '' }}>
                                            {{ $student->user->name }} ({{ $student->classModel->name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', $fee->student->class_id ?? '') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="student-info" id="studentInfo">
                        <h6><i class="fas fa-info-circle"></i> Student Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Class:</strong> <span id="studentClass">{{ $fee->student->classModel->name ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong> <span id="studentEmail">{{ $fee->student->user->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fee Details -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-money-bill-wave"></i>
                        Fee Details
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Fee Type <span class="required">*</span></label>
                                <select name="fee_type" class="form-select @error('fee_type') is-invalid @enderror" required>
                                    <option value="">Select Fee Type</option>
                                    <option value="tuition" {{ old('fee_type', $fee->fee_type) == 'tuition' ? 'selected' : '' }}>Tuition Fee</option>
                                    <option value="admission" {{ old('fee_type', $fee->fee_type) == 'admission' ? 'selected' : '' }}>Admission Fee</option>
                                    <option value="exam" {{ old('fee_type', $fee->fee_type) == 'exam' ? 'selected' : '' }}>Exam Fee</option>
                                    <option value="transport" {{ old('fee_type', $fee->fee_type) == 'transport' ? 'selected' : '' }}>Transport Fee</option>
                                    <option value="library" {{ old('fee_type', $fee->fee_type) == 'library' ? 'selected' : '' }}>Library Fee</option>
                                    <option value="other" {{ old('fee_type', $fee->fee_type) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('fee_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Base Amount <span class="required">*</span></label>
                                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" 
                                       step="0.01" min="0" value="{{ old('amount', $fee->amount) }}" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Due Date <span class="required">*</span></label>
                                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                                       value="{{ old('due_date', $fee->due_date->format('Y-m-d')) }}" required>
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Academic Year <span class="required">*</span></label>
                                <input type="text" name="academic_year" class="form-control @error('academic_year') is-invalid @enderror" 
                                       placeholder="e.g., 2023-24" value="{{ old('academic_year', $fee->academic_year) }}" required>
                                @error('academic_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Month</label>
                                <select name="month" class="form-select @error('month') is-invalid @enderror">
                                    <option value="">Select Month</option>
                                    <option value="January" {{ old('month', $fee->month) == 'January' ? 'selected' : '' }}>January</option>
                                    <option value="February" {{ old('month', $fee->month) == 'February' ? 'selected' : '' }}>February</option>
                                    <option value="March" {{ old('month', $fee->month) == 'March' ? 'selected' : '' }}>March</option>
                                    <option value="April" {{ old('month', $fee->month) == 'April' ? 'selected' : '' }}>April</option>
                                    <option value="May" {{ old('month', $fee->month) == 'May' ? 'selected' : '' }}>May</option>
                                    <option value="June" {{ old('month', $fee->month) == 'June' ? 'selected' : '' }}>June</option>
                                    <option value="July" {{ old('month', $fee->month) == 'July' ? 'selected' : '' }}>July</option>
                                    <option value="August" {{ old('month', $fee->month) == 'August' ? 'selected' : '' }}>August</option>
                                    <option value="September" {{ old('month', $fee->month) == 'September' ? 'selected' : '' }}>September</option>
                                    <option value="October" {{ old('month', $fee->month) == 'October' ? 'selected' : '' }}>October</option>
                                    <option value="November" {{ old('month', $fee->month) == 'November' ? 'selected' : '' }}>November</option>
                                    <option value="December" {{ old('month', $fee->month) == 'December' ? 'selected' : '' }}>December</option>
                                </select>
                                @error('month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Late Fee</label>
                                <input type="number" name="late_fee" class="form-control @error('late_fee') is-invalid @enderror" 
                                       step="0.01" min="0" value="{{ old('late_fee', $fee->late_fee ?? 0) }}">
                                @error('late_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Discount</label>
                                <input type="number" name="discount" class="form-control @error('discount') is-invalid @enderror" 
                                       step="0.01" min="0" value="{{ old('discount', $fee->discount ?? 0) }}">
                                @error('discount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="unpaid" {{ old('status', $fee->status) == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="partial" {{ old('status', $fee->status) == 'partial' ? 'selected' : '' }}>Partial</option>
                                    <option value="paid" {{ old('status', $fee->status) == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Amount Calculation -->
                    <div class="amount-calculation">
                        <h6><i class="fas fa-calculator"></i> Amount Calculation</h6>
                        <div class="calculation-row">
                            <span>Base Amount:</span>
                            <span id="baseAmount">₹{{ number_format($fee->amount, 2) }}</span>
                        </div>
                        <div class="calculation-row">
                            <span>Late Fee:</span>
                            <span id="lateFeeAmount">₹{{ number_format($fee->late_fee ?? 0, 2) }}</span>
                        </div>
                        <div class="calculation-row">
                            <span>Discount:</span>
                            <span id="discountAmount">-₹{{ number_format($fee->discount ?? 0, 2) }}</span>
                        </div>
                        <div class="calculation-row">
                            <span>Final Amount:</span>
                            <span id="finalAmount">₹{{ number_format($fee->amount + ($fee->late_fee ?? 0) - ($fee->discount ?? 0), 2) }}</span>
                        </div>
                    </div>

                    <!-- Payment History -->
                    @if($fee->payments && $fee->payments->count() > 0)
                        <div class="payment-history">
                            <h6><i class="fas fa-history"></i> Payment History</h6>
                            @foreach($fee->payments as $payment)
                                <div class="payment-item">
                                    <div>
                                        <div class="payment-amount">₹{{ number_format($payment->amount_paid, 2) }}</div>
                                        <div class="payment-date">{{ $payment->payment_date->format('d M Y') }} - {{ ucfirst($payment->payment_mode) }}</div>
                                    </div>
                                    <div>
                                        @if($payment->transaction_id)
                                            <small class="text-muted">{{ $payment->transaction_id }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="payment-item">
                                <div>
                                    <strong>Total Paid: ₹{{ number_format($fee->paid_amount, 2) }}</strong>
                                </div>
                                <div>
                                    <strong>Remaining: ₹{{ number_format($fee->remaining_amount, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" 
                                  rows="3" placeholder="Enter any additional remarks...">{{ old('remarks', $fee->remarks) }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="{{ route('fees.index') }}" class="btn-secondary-custom">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary-custom">
                        <i class="fas fa-save"></i> Update Fee Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show student info when student is selected
        document.querySelector('select[name="student_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const studentInfo = document.getElementById('studentInfo');
            
            if (this.value) {
                document.getElementById('studentClass').textContent = selectedOption.dataset.class || 'N/A';
                document.getElementById('studentEmail').textContent = selectedOption.dataset.email || 'N/A';
                studentInfo.style.display = 'block';
            } else {
                studentInfo.style.display = 'none';
            }
        });

        // Calculate final amount
        function calculateAmount() {
            const baseAmount = parseFloat(document.querySelector('input[name="amount"]').value) || 0;
            const lateFee = parseFloat(document.querySelector('input[name="late_fee"]').value) || 0;
            const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
            
            const finalAmount = baseAmount + lateFee - discount;
            
            document.getElementById('baseAmount').textContent = `₹${baseAmount.toFixed(2)}`;
            document.getElementById('lateFeeAmount').textContent = `₹${lateFee.toFixed(2)}`;
            document.getElementById('discountAmount').textContent = `-₹${discount.toFixed(2)}`;
            document.getElementById('finalAmount').textContent = `₹${finalAmount.toFixed(2)}`;
        }

        // Add event listeners for amount calculation
        document.querySelector('input[name="amount"]').addEventListener('input', calculateAmount);
        document.querySelector('input[name="late_fee"]').addEventListener('input', calculateAmount);
        document.querySelector('input[name="discount"]').addEventListener('input', calculateAmount);

        // Filter students by class
        document.querySelector('select[name="class_id"]').addEventListener('change', function() {
            const classId = this.value;
            const studentSelect = document.querySelector('select[name="student_id"]');
            const allOptions = studentSelect.querySelectorAll('option');
            
            allOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                    return;
                }
                
                if (!classId) {
                    option.style.display = 'block';
                } else {
                    // This is a simple filter - in a real application, you'd want to 
                    // make an AJAX call to get filtered students
                    option.style.display = 'block';
                }
            });
        });

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateAmount();
        });

        // Form validation
        document.getElementById('feeForm').addEventListener('submit', function(e) {
            const studentId = document.querySelector('select[name="student_id"]').value;
            const feeType = document.querySelector('select[name="fee_type"]').value;
            const amount = document.querySelector('input[name="amount"]').value;
            const dueDate = document.querySelector('input[name="due_date"]').value;
            const academicYear = document.querySelector('input[name="academic_year"]').value;
            
            if (!studentId || !feeType || !amount || !dueDate || !academicYear) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (parseFloat(amount) <= 0) {
                e.preventDefault();
                alert('Amount must be greater than 0.');
                return false;
            }
        });
    </script>
</div>
@endsection