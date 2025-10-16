<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #f7f7f7; }
        .container { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .header { padding: 20px 24px; border-bottom: 1px solid #eee; }
        .content { padding: 24px; }
        h1 { margin: 0; font-size: 22px; }
        p { color: #444; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #e8f5e9; color: #256029; }
        .alert-info { background: #e3f2fd; color: #0d47a1; }
        .alert-danger { background: #ffebee; color: #b71c1c; }
        .form-row { display: flex; gap: 12px; }
        input[type="email"] { flex: 1; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #1976d2; color: #fff; border: 0; padding: 10px 14px; border-radius: 6px; cursor: pointer; }
        button:hover { background: #125ea7; }
        .muted { color: #666; font-size: 13px; }
        .footer { padding: 16px 24px; border-top: 1px solid #eee; font-size: 13px; color: #666; }
    </style>
    @vite(['resources/js/app.js'])
    @csrf
    @php($user = auth()->user())
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Email Verification Required</h1>
    </div>
    <div class="content">
        <p>To activate your account, please verify your email address.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @auth
            <p class="muted">We sent a verification link to <strong>{{ $user->email }}</strong>. If you didn’t receive it, resend below.</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit">Resend Verification Email</button>
            </form>
        @else
            <p class="muted">Enter your account email to receive a verification link.</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <div class="form-row">
                    <input type="email" name="email" placeholder="Your email" required>
                    <button type="submit">Send Verification Email</button>
                </div>
            </form>
        @endauth
    </div>
    <div class="footer">
        <span class="muted">Having trouble? Contact support.</span>
    </div>
</div>
</body>
</html>