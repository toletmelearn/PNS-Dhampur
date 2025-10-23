<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} label{display:block;margin-top:8px;} .box{border:1px solid #ddd;padding:12px;border-radius:6px;} </style>
</head>
<body>
    <h1>Payment Checkout</h1>
    <div class="box">
        <p>Student: {{ $studentFee->student->name ?? '—' }}</p>
        <p>Fee Item: {{ $studentFee->item_name }}</p>
        <p>Amount Due: ₹ {{ number_format(($studentFee->amount - $studentFee->paid_amount), 2) }}</p>
        <p>Gateway: {{ $gateway->provider }} ({{ strtoupper($gateway->mode) }})</p>
    </div>

    <form action="{{ route('fees.payment.callback') }}" method="POST" style="margin-top:12px;">
        @csrf
        <input type="hidden" name="student_fee_id" value="{{ $studentFee->id }}" />
        <input type="hidden" name="amount" value="{{ $studentFee->amount - $studentFee->paid_amount }}" />
        <label>Transaction ID (mock)
            <input type="text" name="transaction_id" value="TXN{{ time() }}" />
        </label>
        <label>Status
            <select name="status">
                <option value="success">Success</option>
                <option value="failed">Failed</option>
            </select>
        </label>
        <button type="submit">Submit Payment Result</button>
    </form>

    <p style="margin-top:16px;"><a href="{{ route('student-fees.show', $studentFee) }}">Back</a></p>
</body>
</html>