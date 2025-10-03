<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - {{ $period->format('F Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 10px;
            color: #666;
        }
        
        .slip-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
            background-color: #f5f5f5;
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .employee-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            padding: 5px;
            border: 1px solid #ddd;
            width: 25%;
        }
        
        .info-label {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .salary-table th,
        .salary-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .salary-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .net-salary {
            font-size: 14px;
            font-weight: bold;
            background-color: #e8f5e8;
        }
        
        .summary-section {
            margin-top: 20px;
            padding: 15px;
            border: 2px solid #333;
            background-color: #f9f9f9;
        }
        
        .summary-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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
            font-size: 10px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-details">
            {{ $company['address'] }}<br>
            Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}
        </div>
    </div>

    <!-- Salary Slip Title -->
    <div class="slip-title">
        SALARY SLIP FOR {{ strtoupper($period->format('F Y')) }}
    </div>

    <!-- Employee Information -->
    <div class="employee-info">
        <div class="info-row">
            <div class="info-cell info-label">Employee Name:</div>
            <div class="info-cell">{{ $employee->name }}</div>
            <div class="info-cell info-label">Employee ID:</div>
            <div class="info-cell">{{ $employee->employee_code ?? $employee->id }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Designation:</div>
            <div class="info-cell">{{ $employee->designation ?? 'N/A' }}</div>
            <div class="info-cell info-label">Department:</div>
            <div class="info-cell">{{ $employee->department ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Pay Period:</div>
            <div class="info-cell">{{ $period->format('F Y') }}</div>
            <div class="info-cell info-label">Pay Date:</div>
            <div class="info-cell">{{ $period->endOfMonth()->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">PAN:</div>
            <div class="info-cell">{{ $employee->pan_number ?? 'N/A' }}</div>
            <div class="info-cell info-label">PF Number:</div>
            <div class="info-cell">{{ $employee->pf_number ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Salary Details -->
    <table class="salary-table">
        <thead>
            <tr>
                <th style="width: 50%;">EARNINGS</th>
                <th style="width: 20%;">AMOUNT (₹)</th>
                <th style="width: 50%;">DEDUCTIONS</th>
                <th style="width: 20%;">AMOUNT (₹)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Basic Salary -->
            <tr>
                <td>Basic Salary</td>
                <td class="amount">{{ number_format($salary_data['basic_salary'], 2) }}</td>
                <td>Provident Fund (PF)</td>
                <td class="amount">{{ number_format($salary_data['statutory_deductions']['pf'] ?? 0, 2) }}</td>
            </tr>

            <!-- Allowances -->
            @php
                $allowanceKeys = array_keys($salary_data['allowances']);
                $deductionKeys = array_keys($salary_data['statutory_deductions']);
                $voluntaryKeys = array_keys($salary_data['voluntary_deductions']);
                $allDeductions = array_merge($deductionKeys, $voluntaryKeys);
                $maxRows = max(count($allowanceKeys), count($allDeductions));
            @endphp

            @for($i = 0; $i < $maxRows; $i++)
                <tr>
                    <!-- Earnings Column -->
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

                    <!-- Deductions Column -->
                    <td>
                        @if(isset($allDeductions[$i]))
                            @php
                                $deductionKey = $allDeductions[$i];
                                $deductionName = match($deductionKey) {
                                    'esi' => 'Employee State Insurance (ESI)',
                                    'professional_tax' => 'Professional Tax',
                                    'tds' => 'Tax Deducted at Source (TDS)',
                                    default => ucwords(str_replace('_', ' ', $deductionKey))
                                };
                            @endphp
                            {{ $deductionName }}
                        @endif
                    </td>
                    <td class="amount">
                        @if(isset($allDeductions[$i]))
                            @php
                                $amount = $salary_data['statutory_deductions'][$allDeductions[$i]] ?? 
                                         $salary_data['voluntary_deductions'][$allDeductions[$i]] ?? 0;
                            @endphp
                            {{ number_format($amount, 2) }}
                        @endif
                    </td>
                </tr>
            @endfor

            <!-- Totals -->
            <tr class="total-row">
                <td><strong>GROSS EARNINGS</strong></td>
                <td class="amount"><strong>{{ number_format($salary_data['gross_salary'], 2) }}</strong></td>
                <td><strong>TOTAL DEDUCTIONS</strong></td>
                <td class="amount"><strong>{{ number_format($salary_data['total_deductions'], 2) }}</strong></td>
            </tr>

            <!-- Net Salary -->
            <tr class="net-salary">
                <td colspan="2"><strong>NET SALARY</strong></td>
                <td colspan="2" class="amount"><strong>₹ {{ number_format($salary_data['net_salary'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-title">SALARY SUMMARY</div>
        <div class="summary-row">
            <span>Gross Salary:</span>
            <span>₹ {{ number_format($salary_data['gross_salary'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Deductions:</span>
            <span>₹ {{ number_format($salary_data['total_deductions'], 2) }}</span>
        </div>
        <div class="summary-row" style="border-top: 1px solid #333; padding-top: 5px; font-weight: bold;">
            <span>Net Pay:</span>
            <span>₹ {{ number_format($salary_data['net_salary'], 2) }}</span>
        </div>
        <div style="margin-top: 10px; font-size: 11px; text-align: center;">
            <strong>Net Pay in Words:</strong> 
            {{ ucwords(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($salary_data['net_salary'])) }} Rupees Only
        </div>
    </div>

    <!-- Employer Contributions (if any) -->
    @if(!empty($salary_data['employer_contributions']))
    <div style="margin-top: 15px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <div style="font-weight: bold; margin-bottom: 8px;">EMPLOYER CONTRIBUTIONS:</div>
        @foreach($salary_data['employer_contributions'] as $type => $amount)
            @if($amount > 0)
            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                <span>{{ ucwords(str_replace('_', ' ', $type)) }}:</span>
                <span>₹ {{ number_format($amount, 2) }}</span>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-cell">
            <div class="signature-line">
                Employee Signature
            </div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">
                Authorized Signatory<br>
                {{ $company['name'] }}
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated salary slip and does not require a signature.</p>
        <p>Generated on: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Note:</strong> Please verify all details and contact HR for any discrepancies.</p>
    </div>
</body>
</html>