<!DOCTYPE html>
<html>
<head>
    <title>Student Fees</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;} th{background:#f7f7f7;} .btn{padding:6px 10px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;} </style>
</head>
<body>
    <h1>Student Fees</h1>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Item</th>
                <th>Due</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fees as $fee)
                <tr>
                    <td>{{ $fee->student->name ?? '—' }}</td>
                    <td>{{ $fee->item_name }}</td>
                    <td>{{ $fee->due_date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($fee->amount, 2) }}</td>
                    <td>{{ ucfirst($fee->status) }}</td>
                    <td>
                        <a class="btn" href="{{ route('student-fees.show', $fee) }}">View</a>
                        @if($fee->status !== 'paid')
                            <a class="btn" href="{{ route('fees.payment.checkout', $fee) }}">Pay</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:12px;">{{ $fees->links() }}</p>

    <p style="margin-top:20px;">
        <a href="{{ route('fee-structures.index') }}">Manage Fee Structures</a>
    </p>
</body>
</html>