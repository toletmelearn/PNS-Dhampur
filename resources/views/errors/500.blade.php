<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - PNS Dhampur</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 550px;
            width: 90%;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 150px;
            height: 150px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #ff6b6b;
            margin: 0;
            line-height: 1;
            opacity: 0.1;
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 0;
        }
        
        .content {
            position: relative;
            z-index: 1;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: #ff6b6b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 32px;
            font-weight: 600;
        }
        
        p {
            margin-bottom: 25px;
            line-height: 1.6;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .error-details {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .error-details summary {
            cursor: pointer;
            font-weight: 600;
            color: #e53e3e;
            margin-bottom: 10px;
        }
        
        .error-details pre {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            overflow-x: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            color: #4a5568;
            margin: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #ff6b6b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #ee5a52;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #ff6b6b;
            border-color: #ff6b6b;
        }
        
        .btn-outline:hover {
            background: #ff6b6b;
            color: white;
            transform: translateY(-2px);
        }
        
        .technical-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #718096;
        }
        
        .technical-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .support-contact {
            background: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .support-contact h3 {
            color: #3182ce;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .support-contact a {
            color: #3182ce;
            text-decoration: none;
        }
        
        .support-contact a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .error-code {
                font-size: 80px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .logo {
            animation: shake 0.5s ease-in-out;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">500</div>
        
        <div class="content">
            <div class="logo">⚠️</div>
            
            <h1>Internal Server Error</h1>
            
            <p>Something went wrong on our end. Our technical team has been notified and is working to fix the issue.</p>
            
            <div class="error-details">
                <summary>Technical Details (For Support Team)</summary>
                <pre>Error: {{ $exception->getMessage() ?? 'Unknown error occurred' }}
Time: {{ now()->toISOString() }}
URL: {{ request()->fullUrl() }}
Method: {{ request()->method() }}
IP: {{ request()->ip() }}
User Agent: {{ request()->userAgent() }}</pre>
            </div>
            
            <div class="action-buttons">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    🏠 Go Home
                </a>
                <a href="javascript:location.reload()" class="btn btn-outline">
                    🔄 Refresh Page
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    ↩️ Go Back
                </a>
            </div>
            
            <div class="support-contact">
                <h3>Need Immediate Help?</h3>
                <p>📧 Email: <a href="mailto:support@pns-dhampur.edu.in">support@pns-dhampur.edu.in</a></p>
                <p>📞 Phone: +91-XXXX-XXXXXX</p>
                <p>🕒 Support Hours: 24/7</p>
            </div>
            
            <div class="technical-info">
                <p><strong>Reference ID:</strong> {{ uniqid() }}</p>
                <p><strong>Server Time:</strong> {{ now()->format('Y-m-d H:i:s T') }}</p>
                <p><strong>Request ID:</strong> {{ request()->header('X-Request-ID', 'N/A') }}</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh after 30 seconds if user is still on the page
        setTimeout(() => {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
        
        // Log error details to console for debugging
        console.error('Server Error Details:', {
            url: '{{ request()->fullUrl() }}',
            method: '{{ request()->method() }}',
            timestamp: '{{ now()->toISOString() }}',
            referenceId: '{{ uniqid() }}'
        });
    </script>
</body>
</html>