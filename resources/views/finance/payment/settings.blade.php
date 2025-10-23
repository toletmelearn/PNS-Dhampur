<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway Settings</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style> body{font-family:system-ui,Arial,sans-serif;margin:20px;} label{display:block;margin-top:8px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;} th{background:#f7f7f7;} </style>
</head>
<body>
    <h1>Payment Gateway Settings</h1>

    <form action="{{ route('payment.settings.store') }}" method="POST">
        @csrf
        <label>Provider
            <select name="provider" required>
                <option value="razorpay">Razorpay</option>
                <option value="stripe">Stripe</option>
            </select>
        </label>
        <label>Mode
            <select name="mode" required>
                <option value="test">Test</option>
                <option value="live">Live</option>
            </select>
        </label>
        <label>API Key
            <input type="text" name="api_key" />
        </label>
        <label>API Secret
            <input type="text" name="api_secret" />
        </label>
        <label><input type="checkbox" name="is_active" value="1" /> Set as Active</label>
        <button type="submit" style="margin-top:12px;">Save Settings</button>
    </form>

    <h2 style="margin-top:20px;">Existing Configurations</h2>
    <table>
        <thead>
            <tr>
                <th>Provider</th>
                <th>Mode</th>
                <th>Active</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach($configs as $cfg)
            <tr>
                <td>{{ ucfirst($cfg->provider) }}</td>
                <td>{{ ucfirst($cfg->mode) }}</td>
                <td>{{ $cfg->is_active ? 'Yes' : 'No' }}</td>
                <td>{{ $cfg->updated_at?->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:16px;"><a href="{{ route('fees.index') }}">Back to Fees</a></p>
</body>
</html>