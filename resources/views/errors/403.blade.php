<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - PNS Dhampur</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            background: rgba(240, 147, 251, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #f093fb;
            margin: 0;
            line-height: 1;
            opacity: 0.1;
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
        }
        
        .content {
            position: relative;
            z-index: 1;
            margin-top: 60px;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        
        h1 {
            font-size: 32px;
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .subtitle {
            font-size: 18px;
            color: #7f8c8d;
            margin: 0 0 30px 0;
            line-height: 1.5;
        }
        
        .description {
            font-size: 16px;
            color: #95a5a6;
            margin: 0 0 40px 0;
            line-height: 1.6;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(240, 147, 251, 0.3);
        }
        
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-secondary:hover {
            background: #d5dbdb;
            transform: translateY(-2px);
        }
        
        .error-details {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #f093fb;
        }
        
        .error-details h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .error-details p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .contact-info {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
            border-radius: 10px;
        }
        
        .contact-info h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 18px;
        }
        
        .contact-info p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .error-code {
                font-size: 80px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: 16px;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">403</div>
        
        <div class="content">
            <div class="icon">
                🔒
            </div>
            
            <h1>Access Forbidden</h1>
            <p class="subtitle">You don't have permission to access this resource</p>
            <p class="description">
                The page or resource you're trying to access requires special permissions that your current account doesn't have. 
                Please contact your administrator if you believe this is an error.
            </p>
            
            <div class="actions">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    🏠 Go Home
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    ← Go Back
                </a>
            </div>
            
            <div class="error-details">
                <h3>Error Details</h3>
                <p><strong>Error Code:</strong> 403 - Forbidden</p>
                <p><strong>Timestamp:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
                @if(request()->user())
                    <p><strong>User:</strong> {{ request()->user()->name ?? 'Unknown' }}</p>
                @endif
                <p><strong>Requested URL:</strong> {{ request()->fullUrl() }}</p>
            </div>
            
            <div class="contact-info">
                <h3>Need Help?</h3>
                <p><strong>School Office:</strong> Contact your system administrator</p>
                <p><strong>Technical Support:</strong> IT Department</p>
                <p><strong>Email:</strong> support@pnsdhampur.edu.in</p>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
        
        // Add CSS animation for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>