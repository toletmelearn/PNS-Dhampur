<!DOCTYPE html>
<html>
<head>
    <title>Student Fee Detail</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} .btn{padding:6px 10px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;} </style>
</head>
<body>
    <h1>Fee: {{ $studentFee->item_name }}</h1>
    <p>Student: {{ $studentFee->student->name ?? '—' }}</p>
    <p>Due Date: {{ $studentFee->due_date?->format('Y-m-d') }}</p>
    <p>Amount: {{ number_format($studentFee->amount, 2) }}</p>
    <p>Status: {{ ucfirst($studentFee->status) }}</p>

    @if($studentFee->status !== 'paid')
        <p><a class="btn" href="{{ route('fees.payment.checkout', $studentFee) }}">Proceed to Payment</a></p>
    @endif

    <p style="margin-top:16px;"><a href="{{ route('student-fees.index') }}">Back to Student Fees</a></p>
</body>
</html>