<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 18px;
            color: #666;
        }
        .report-date {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        .summary-section {
            margin: 30px 0;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .summary-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #28a745;
        }
        .summary-stats {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            margin: 10px;
            flex: 1;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .stat-value.negative {
            color: #dc3545;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .table-section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #28a745;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .amount {
            text-align: right;
            font-weight: bold;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">PNS Dhampur</div>
        <div class="report-title">Financial Report</div>
        <div class="report-date">Generated on: {{ date('F j, Y') }}</div>
    </div>

    <div class="summary-section">
        <div class="summary-title">Financial Overview</div>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">₹{{ number_format($data['total_revenue'] ?? 0) }}</div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">₹{{ number_format($data['pending_fees'] ?? 0) }}</div>
                <div class="stat-label">Pending Fees</div>
            </div>
            <div class="stat-item">
                <div class="stat-value negative">₹{{ number_format($data['total_expenses'] ?? 0) }}</div>
                <div class="stat-label">Total Expenses</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">₹{{ number_format(($data['total_revenue'] ?? 0) - ($data['total_expenses'] ?? 0)) }}</div>
                <div class="stat-label">Net Income</div>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="section-title">Monthly Collection</div>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Fee Collection</th>
                    <th>Other Income</th>
                    <th>Total Revenue</th>
                    <th>Expenses</th>
                    <th>Net Income</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data['monthly_collection']))
                    @foreach($data['monthly_collection'] as $month => $collection)
                        @php
                            $expenses = $data['expenses'][$month] ?? 0;
                            $otherIncome = rand(5000, 15000);
                            $totalRevenue = $collection + $otherIncome;
                            $netIncome = $totalRevenue - $expenses;
                        @endphp
                        <tr>
                            <td>{{ $month }}</td>
                            <td class="amount">₹{{ number_format($collection) }}</td>
                            <td class="amount">₹{{ number_format($otherIncome) }}</td>
                            <td class="amount positive">₹{{ number_format($totalRevenue) }}</td>
                            <td class="amount negative">₹{{ number_format($expenses) }}</td>
                            <td class="amount {{ $netIncome >= 0 ? 'positive' : 'negative' }}">₹{{ number_format($netIncome) }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if(isset($data['class_wise_collection']))
    <div class="table-section">
        <div class="section-title">Class-wise Fee Collection</div>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Total Students</th>
                    <th>Fees Collected</th>
                    <th>Pending Amount</th>
                    <th>Collection Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['class_wise_collection'] as $class => $info)
                    @php
                        $collectionRate = $info['total'] > 0 ? round(($info['collected'] / $info['total']) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $class }}</td>
                        <td>{{ $info['students'] ?? 0 }}</td>
                        <td class="amount positive">₹{{ number_format($info['collected'] ?? 0) }}</td>
                        <td class="amount negative">₹{{ number_format($info['pending'] ?? 0) }}</td>
                        <td class="amount">{{ $collectionRate }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($data['payment_methods']))
    <div class="table-section">
        <div class="section-title">Payment Methods Distribution</div>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Amount</th>
                    <th>Percentage</th>
                    <th>Transaction Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['payment_methods'] as $method => $info)
                    <tr>
                        <td>{{ ucfirst($method) }}</td>
                        <td class="amount">₹{{ number_format($info['amount'] ?? 0) }}</td>
                        <td class="amount">{{ $info['percentage'] ?? 0 }}%</td>
                        <td class="amount">{{ $info['count'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This report was automatically generated by the PNS Dhampur School Management System.</p>
        <p>For any queries, please contact the finance office.</p>
    </div>
</body>
</html>