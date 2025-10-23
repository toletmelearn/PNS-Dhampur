<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} .box{border:1px solid #ddd;padding:12px;border-radius:6px;} </style>
</head>
<body>
    <h1>Receipt {{ $receipt->receipt_number }}</h1>
    <div class="box">
        <p>Issued At: {{ $receipt->issued_at?->format('Y-m-d H:i') }}</p>
        <p>Student: {{ $receipt->studentFee->student->name ?? '—' }}</p>
        <p>Item: {{ $receipt->studentFee->item_name }}</p>
        <p>Amount: ₹ {{ number_format($receipt->transaction->amount ?? $receipt->studentFee->paid_amount, 2) }}</p>
        <p>Transaction ID: {{ $receipt->transaction->transaction_id ?? '—' }}</p>
    </div>

    <p style="margin-top:16px;"><a href="{{ route('student-fees.show', $receipt->studentFee) }}">Back to Fee</a></p>
</body>
</html>