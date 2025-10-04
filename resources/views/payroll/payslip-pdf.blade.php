<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $employee->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .payslip-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #2c3e50;
            padding: 0;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .payslip-title {
            background: #34495e;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        
        .employee-info {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .employee-row {
            display: table-row;
        }
        
        .employee-cell {
            display: table-cell;
            padding: 8px 15px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        .employee-cell:first-child {
            background: #f8f9fa;
            font-weight: bold;
            width: 25%;
        }
        
        .employee-cell:nth-child(3) {
            background: #f8f9fa;
            font-weight: bold;
            width: 25%;
        }
        
        .salary-details {
            margin-top: 20px;
        }
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .salary-table th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        
        .salary-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .salary-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .total-row {
            background: #e8f4fd !important;
            font-weight: bold;
            border-top: 2px solid #3498db;
        }
        
        .net-salary-row {
            background: #d5f4e6 !important;
            font-weight: bold;
            font-size: 14px;
            border-top: 3px solid #27ae60;
        }
        
        .summary-section {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
        }
        
        .summary-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-cell {
            display: table-cell;
            padding: 5px 10px;
            width: 50%;
        }
        
        .footer {
            margin-top: 30px;
            padding: 15px;
            background: #2c3e50;
            color: white;
            text-align: center;
            font-size: 10px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        
        .signature-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        @media print {
            body { margin: 0; }
            .payslip-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="company-details">
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}
            </div>
        </div>
        
        <!-- Payslip Title -->
        <div class="payslip-title">
            SALARY SLIP FOR {{ strtoupper($period['month']) }}
        </div>
        
        <!-- Employee Information -->
        <div class="employee-info">
            <div class="employee-row">
                <div class="employee-cell">Employee Name:</div>
                <div class="employee-cell">{{ $employee->name }}</div>
                <div class="employee-cell">Employee ID:</div>
                <div class="employee-cell">{{ $employee->employee_id ?? 'N/A' }}</div>
            </div>
            <div class="employee-row">
                <div class="employee-cell">Designation:</div>
                <div class="employee-cell">{{ $employee->designation ?? 'Teacher' }}</div>
                <div class="employee-cell">Department:</div>
                <div class="employee-cell">{{ $employee->department ?? 'Academic' }}</div>
            </div>
            <div class="employee-row">
                <div class="employee-cell">Date of Joining:</div>
                <div class="employee-cell">{{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d/m/Y') : 'N/A' }}</div>
                <div class="employee-cell">Pay Period:</div>
                <div class="employee-cell">{{ $period['month'] }}</div>
            </div>
            @if(isset($salary_data['leave_details']))
            <div class="employee-row">
                <div class="employee-cell">Working Days:</div>
                <div class="employee-cell">{{ $salary_data['leave_details']['total_working_days'] }}</div>
                <div class="employee-cell">Present Days:</div>
                <div class="employee-cell">{{ $salary_data['leave_details']['present_days'] }}</div>
            </div>
            @endif
        </div>
        
        <!-- Salary Details -->
        <div class="salary-details">
            <table class="salary-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">EARNINGS</th>
                        <th style="width: 20%;">AMOUNT (₹)</th>
                        <th style="width: 30%;">DEDUCTIONS</th>
                        <th style="width: 20%;">AMOUNT (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Basic Salary -->
                    <tr>
                        <td>Basic Salary</td>
                        <td class="amount">{{ number_format($salary_data['basic_salary'], 2) }}</td>
                        <td>Provident Fund (PF)</td>
                        <td class="amount">{{ number_format($salary_data['statutory_deductions']['pf'], 2) }}</td>
                    </tr>
                    
                    <!-- Allowances -->
                    @php
                        $allowanceKeys = array_keys($salary_data['allowances']);
                        $deductionKeys = array_keys($salary_data['statutory_deductions']);
                        $maxRows = max(count($allowanceKeys), count($deductionKeys));
                    @endphp
                    
                    @for($i = 0; $i < $maxRows - 1; $i++)
                        <tr>
                            <td>
                                @if(isset($allowanceKeys[$i]))
                                    {{ ucwords(str_replace('_', ' ', $allowanceKeys[$i])) }}
                                @endif
                            </td>
                            <td class="amount">
                                @if(isset($allowanceKeys[$i]))
                                    {{ number_format($salary_data['allowances'][$allowanceKeys[$i]], 2) }}
                                @endif
                            </td>
                            <td>
                                @if(isset($deductionKeys[$i + 1]))
                                    @php
                                        $deductionName = match($deductionKeys[$i + 1]) {
                                            'esi' => 'Employee State Insurance (ESI)',
                                            'professional_tax' => 'Professional Tax',
                                            'tds' => 'Tax Deducted at Source (TDS)',
                                            default => ucwords(str_replace('_', ' ', $deductionKeys[$i + 1]))
                                        };
                                    @endphp
                                    {{ $deductionName }}
                                @endif
                            </td>
                            <td class="amount">
                                @if(isset($deductionKeys[$i + 1]))
                                    {{ number_format($salary_data['statutory_deductions'][$deductionKeys[$i + 1]], 2) }}
                                @endif
                            </td>
                        </tr>
                    @endfor
                    
                    <!-- Leave Deduction if applicable -->
                    @if(isset($salary_data['leave_details']) && $salary_data['leave_details']['leave_deduction'] > 0)
                        <tr>
                            <td></td>
                            <td class="amount"></td>
                            <td>Leave Deduction ({{ $salary_data['leave_details']['absent_days'] }} days)</td>
                            <td class="amount">{{ number_format($salary_data['leave_details']['leave_deduction'], 2) }}</td>
                        </tr>
                    @endif
                    
                    <!-- Totals -->
                    <tr class="total-row">
                        <td><strong>GROSS EARNINGS</strong></td>
                        <td class="amount"><strong>{{ number_format($salary_data['gross_salary'], 2) }}</strong></td>
                        <td><strong>TOTAL DEDUCTIONS</strong></td>
                        <td class="amount"><strong>{{ number_format($salary_data['total_deductions'], 2) }}</strong></td>
                    </tr>
                    
                    <!-- Net Salary -->
                    <tr class="net-salary-row">
                        <td colspan="3"><strong>NET SALARY</strong></td>
                        <td class="amount"><strong>{{ number_format($salary_data['net_salary'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-title">EMPLOYER CONTRIBUTIONS</div>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-cell">Employer PF Contribution:</div>
                    <div class="summary-cell">₹{{ number_format($salary_data['employer_contributions']['pf'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell">Employer ESI Contribution:</div>
                    <div class="summary-cell">₹{{ number_format($salary_data['employer_contributions']['esi'], 2) }}</div>
                </div>
            </div>
        </div>
        
        <!-- Net Salary in Words -->
        <div class="summary-section">
            <div class="summary-title">NET SALARY IN WORDS</div>
            <div style="font-style: italic; font-size: 13px;">
                {{ ucwords(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($salary_data['net_salary'])) }} Rupees Only
            </div>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-cell">
                <div class="signature-line">Employee Signature</div>
            </div>
            <div class="signature-cell">
                <div class="signature-line">Authorized Signatory</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>This is a computer-generated payslip and does not require a signature.</div>
            <div>Generated on: {{ $generated_at }}</div>
        </div>
    </div>
</body>
</html>