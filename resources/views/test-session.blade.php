<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Security Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background-color: #0056b3; }
        .danger { background-color: #dc3545; }
        .danger:hover { background-color: #c82333; }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Session Security Test Page</h1>
    
    <div class="card">
        <h2>Current Session Information</h2>
        <div id="session-info" class="status info">Loading session information...</div>
        <button onclick="loadSessionInfo()">Refresh Session Info</button>
    </div>

    <div class="card">
        <h2>Session Timeout Test</h2>
        <div id="timeout-status" class="status info">Checking timeout status...</div>
        <button onclick="checkTimeoutWarning()">Check Timeout Warning</button>
        <button onclick="extendSession()">Extend Session</button>
    </div>

    <div class="card">
        <h2>Session Policies</h2>
        <div id="policies-info" class="status info">Loading policies...</div>
        <button onclick="loadPolicies()">Load Session Policies</button>
    </div>

    <div class="card">
        <h2>Manual Tests</h2>
        <button onclick="forceLogout()" class="danger">Force Logout</button>
        <button onclick="testSessionSecurity()">Test Security Settings</button>
        <div id="test-results" class="status info" style="display: none;"></div>
    </div>

    <div class="card">
        <h2>Session Activity Log</h2>
        <div id="activity-log">
            <pre id="log-content">Session activity will be logged here...</pre>
        </div>
        <button onclick="clearLog()">Clear Log</button>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            const logContent = document.getElementById('log-content');
            logContent.textContent += `[${timestamp}] ${message}\n`;
            logContent.scrollTop = logContent.scrollHeight;
        }

        function clearLog() {
            document.getElementById('log-content').textContent = 'Session activity will be logged here...\n';
        }

        async function makeRequest(url, method = 'GET', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                };
                
                if (data) {
                    options.body = JSON.stringify(data);
                }
                
                const response = await fetch(url, options);
                const result = await response.json();
                
                log(`${method} ${url} - Status: ${response.status}`);
                return { success: response.ok, data: result, status: response.status };
            } catch (error) {
                log(`Error: ${error.message}`);
                return { success: false, error: error.message };
            }
        }

        async function loadSessionInfo() {
            const result = await makeRequest('/api/session/info');
            const infoDiv = document.getElementById('session-info');
            
            if (result.success) {
                infoDiv.className = 'status success';
                infoDiv.innerHTML = `
                    <strong>Session Information:</strong><br>
                    Role: ${result.data.role || 'N/A'}<br>
                    Timeout: ${result.data.session_timeout_minutes || 'N/A'} minutes<br>
                    Last Activity: ${result.data.last_activity || 'N/A'}<br>
                    Expires At: ${result.data.expires_at || 'N/A'}<br>
                    Time Remaining: ${result.data.time_remaining_minutes || 'N/A'} minutes
                `;
            } else {
                infoDiv.className = 'status error';
                infoDiv.textContent = `Error: ${result.error || 'Failed to load session info'}`;
            }
        }

        async function checkTimeoutWarning() {
            const result = await makeRequest('/api/session/timeout-warning');
            const statusDiv = document.getElementById('timeout-status');
            
            if (result.success) {
                if (result.data.warning) {
                    statusDiv.className = 'status warning';
                    statusDiv.innerHTML = `
                        <strong>⚠️ Session Timeout Warning!</strong><br>
                        Time Remaining: ${result.data.time_remaining} minutes<br>
                        Your session will expire soon. Please extend it to continue.
                    `;
                } else {
                    statusDiv.className = 'status success';
                    statusDiv.textContent = '✅ Session is active. No timeout warning.';
                }
            } else {
                statusDiv.className = 'status error';
                statusDiv.textContent = `Error: ${result.error || 'Failed to check timeout'}`;
            }
        }

        async function extendSession() {
            const result = await makeRequest('/api/session/extend', 'POST');
            const statusDiv = document.getElementById('timeout-status');
            
            if (result.success) {
                statusDiv.className = 'status success';
                statusDiv.innerHTML = `
                    <strong>✅ Session Extended!</strong><br>
                    New Last Activity: ${result.data.last_activity}<br>
                    Timeout: ${result.data.timeout_minutes} minutes<br>
                    Role: ${result.data.user_role}
                `;
                log('Session extended successfully');
            } else {
                statusDiv.className = 'status error';
                statusDiv.textContent = `Error: ${result.error || 'Failed to extend session'}`;
            }
        }

        async function loadPolicies() {
            const result = await makeRequest('/api/session/policies');
            const policiesDiv = document.getElementById('policies-info');
            
            if (result.success) {
                policiesDiv.className = 'status success';
                let html = '<strong>Session Policies:</strong><br><br>';
                
                for (const [role, policy] of Object.entries(result.data.policies)) {
                    html += `<strong>${role}:</strong> ${policy.timeout_minutes}min, `;
                    html += `Security: ${policy.security_level}, `;
                    html += `Expire on close: ${policy.expire_on_close ? 'Yes' : 'No'}<br>`;
                }
                
                policiesDiv.innerHTML = html;
            } else {
                policiesDiv.className = 'status error';
                policiesDiv.textContent = `Error: ${result.error || 'Failed to load policies'}`;
            }
        }

        async function forceLogout() {
            if (confirm('Are you sure you want to force logout? This will end your session.')) {
                const result = await makeRequest('/api/session/logout', 'POST');
                
                if (result.success) {
                    alert('Logged out successfully. You will be redirected to login.');
                    window.location.href = '/login';
                } else {
                    alert(`Logout failed: ${result.error || 'Unknown error'}`);
                }
            }
        }

        function testSessionSecurity() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.style.display = 'block';
            resultsDiv.className = 'status info';
            
            let results = '<strong>Session Security Test Results:</strong><br><br>';
            
            // Check cookie settings
            const cookies = document.cookie.split(';');
            const sessionCookie = cookies.find(cookie => cookie.trim().startsWith('laravel_session='));
            
            if (sessionCookie) {
                results += '✅ Session cookie found<br>';
            } else {
                results += '❌ Session cookie not found<br>';
            }
            
            // Check HTTPS (in production)
            if (location.protocol === 'https:') {
                results += '✅ HTTPS connection<br>';
            } else {
                results += '⚠️ HTTP connection (HTTPS recommended for production)<br>';
            }
            
            // Check if session storage is working
            try {
                sessionStorage.setItem('test', 'value');
                sessionStorage.removeItem('test');
                results += '✅ Session storage available<br>';
            } catch (e) {
                results += '❌ Session storage not available<br>';
            }
            
            results += '<br>Manual verification needed for:<br>';
            results += '• HTTP-only cookies (check browser dev tools)<br>';
            results += '• Secure cookies (in HTTPS production)<br>';
            results += '• SameSite=Strict setting<br>';
            results += '• Session expiration on browser close<br>';
            
            resultsDiv.innerHTML = results;
            log('Session security test completed');
        }

        // Auto-load session info on page load
        document.addEventListener('DOMContentLoaded', function() {
            log('Session security test page loaded');
            loadSessionInfo();
            checkTimeoutWarning();
            loadPolicies();
        });

        // Auto-refresh session info every 30 seconds
        setInterval(() => {
            loadSessionInfo();
            checkTimeoutWarning();
        }, 30000);
    </script>
</body>
</html>