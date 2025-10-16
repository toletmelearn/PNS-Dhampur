<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - PNS Dhampur</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        p {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #666;
        }
        
        .maintenance-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        
        .countdown {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }
        
        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .contact-info a {
            color: #667eea;
            text-decoration: none;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo pulse">PNS</div>
        
        <h1>🚧 Maintenance in Progress</h1>
        
        <p>We're currently performing scheduled maintenance to improve your experience. Please check back shortly.</p>
        
        <div class="maintenance-info">
            <strong>Estimated Completion:</strong><br>
            {{ $retryAfter ?? '30 minutes' }}
        </div>
        
        <div class="countdown" id="countdown">Checking...</div>
        
        <p>We apologize for any inconvenience. Our team is working hard to complete the maintenance as quickly as possible.</p>
        
        <div class="contact-info">
            <strong>Need immediate assistance?</strong><br>
            Email: <a href="mailto:support@pns-dhampur.edu.in">support@pns-dhampur.edu.in</a><br>
            Phone: +91-XXXX-XXXXXX
        </div>
    </div>

    <script>
        function updateCountdown() {
            const countdownElement = document.getElementById('countdown');
            const retryTime = {{ $retryAfter ?? 30 }} * 60; // minutes to seconds
            const now = Math.floor(Date.now() / 1000);
            
            if (retryTime > now) {
                const remaining = retryTime - now;
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                
                countdownElement.textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                countdownElement.textContent = 'Maintenance Complete!';
                countdownElement.style.color = '#28a745';
            }
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
</body>
</html>