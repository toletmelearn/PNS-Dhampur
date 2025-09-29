<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Fee Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { width: 100%; margin-bottom: 10px; }
        .table { width:100%; border-collapse: collapse; margin-top:10px; }
        .table th, .table td { border: 1px solid #333; padding:8px; text-align:left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $school }}</h2>
        <p><strong>Fee Receipt</strong></p>
    </div>

    <div class="details">
        <strong>Student:</strong> {{ $student->name ?? 'N/A' }} <br>
        <strong>Admission No:</strong> {{ $student->admission_no ?? 'N/A' }} <br>
        <strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->toDateString() }}
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Fee Due</td>
                <td class="right">{{ number_format($fee->amount,2) }}</td>
            </tr>
            <tr>
                <td>Paid Amount</td>
                <td class="right">{{ number_format($paid,2) }}</td>
            </tr>
            <tr>
                <td>Balance</td>
                <td class="right">{{ number_format(max(0, $fee->amount - $fee->paid_amount),2) }}</td>
            </tr>
        </tbody>
    </table>

    <p>Payment Mode: {{ $payment_mode }}</p>

    <p style="margin-top:20px">This is a system generated receipt.</p>
</body>
</html>
