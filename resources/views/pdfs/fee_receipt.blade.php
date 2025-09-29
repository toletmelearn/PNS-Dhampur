<!DOCTYPE html>
<html>
<head>
    <title>Fee Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align:center; }
        .details { margin-top:20px; }
        .details td { padding:5px; }
        .footer { margin-top:50px; text-align:center; font-size:12px; color:gray; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $school }}</h2>
        <h4>Fee Receipt</h4>
    </div>

    <table class="details" width="100%" border="1" cellspacing="0" cellpadding="0">
        <tr>
            <td>Student Name</td>
            <td>{{ $student->name }}</td>
        </tr>
        <tr>
            <td>Admission No.</td>
            <td>{{ $student->admission_no }}</td>
        </tr>
        <tr>
            <td>Class</td>
            <td>{{ $student->classModel->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Paid Amount</td>
            <td>{{ number_format($paid,2) }}</td>
        </tr>
        <tr>
            <td>Payment Mode</td>
            <td>{{ ucfirst($payment_mode) }}</td>
        </tr>
        <tr>
            <td>Payment Date</td>
            <td>{{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>Fee Status</td>
            <td>{{ ucfirst($fee->status) }}</td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ \Carbon\Carbon::now()->format('d-m-Y H:i') }}
    </div>
</body>
</html>
