<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - PNS Dhampur</title>
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
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 500px;
            width: 90%;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
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
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 32px;
            font-weight: 600;
        }
        
        p {
            margin-bottom: 30px;
            line-height: 1.6;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .search-box {
            margin: 30px 0;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e6ed;
            border-radius: 50px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-button {
            position: absolute;
            right: 5px;
            top: 5px;
            background: #667eea;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .search-button:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            color: #667eea;
            border-color: #667eea;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .suggestions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
        
        .suggestions h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .suggestion-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .suggestion-links a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .suggestion-links a:hover {
            color: #5a6fd8;
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
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .logo {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        
        <div class="content">
            <div class="logo">PNS</div>
            
            <h1>Oops! Page Not Found</h1>
            
            <p>The page you're looking for seems to have gone missing. Don't worry, let's get you back on track.</p>
            
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search our website..." id="searchInput">
                <button class="search-button" onclick="handleSearch()">Search</button>
            </div>
            
            <div class="action-buttons">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    🏠 Go Home
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    ↩️ Go Back
                </a>
                <a href="{{ url('/contact') }}" class="btn btn-outline">
                    📞 Contact Support
                </a>
            </div>
            
            <div class="suggestions">
                <h3>Popular Pages:</h3>
                <div class="suggestion-links">
                    <a href="{{ url('/students') }}">Student Portal</a>
                    <a href="{{ url('/teachers') }}">Teacher Dashboard</a>
                    <a href="{{ url('/attendance') }}">Attendance System</a>
                    <a href="{{ url('/exam-results') }}">Exam Results</a>
                    <a href="{{ url('/fee-payment') }}">Fee Payment</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = `{{ url('/search') }}?q=${encodeURIComponent(query)}`;
            }
        }
        
        // Allow Enter key to trigger search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
        
        // Focus search input on load
        document.getElementById('searchInput').focus();
    </script>
</body>
</html>