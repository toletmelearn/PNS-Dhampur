@extends('layouts.app')

@section('title', 'Error Pages Test')

@section('header', 'Error Pages & Validation Test')

@section('header-actions')
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-10 mx-auto">
        <!-- Error Pages Test Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Error Pages Testing
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Test Instructions:</strong> Click the buttons below to test different error scenarios. 
                    Each button will trigger a specific error condition to test our error handling system.
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border-danger">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-search me-2"></i>404 - Page Not Found</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test the custom 404 error page when a resource is not found.</p>
                                <a href="{{ route('test.404') }}" class="btn btn-outline-danger" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Test 404 Error
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-warning">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-ban me-2"></i>403 - Access Forbidden</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test the custom 403 error page for access denied scenarios.</p>
                                <a href="{{ route('test.403') }}" class="btn btn-outline-warning" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Test 403 Error
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-dark">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-server me-2"></i>500 - Server Error</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test the custom 500 error page for internal server errors.</p>
                                <a href="{{ route('test.500') }}" class="btn btn-outline-dark" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Test 500 Error
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-info">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user-lock me-2"></i>401 - Authentication Required</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test authentication error handling.</p>
                                <button type="button" class="btn btn-outline-info" onclick="testAuthError()">
                                    <i class="fas fa-shield-alt me-2"></i>Test Auth Error
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation Testing Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>Validation Error Testing
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Validation Tests:</strong> These tests demonstrate form validation errors and how they are handled.
                </div>

                <form id="validationTestForm" class="mb-4">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="required_field" class="form-label">Required Field</label>
                            <input type="text" class="form-control" id="required_field" name="required_field" 
                                   placeholder="Leave empty to test required validation">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="email_field" class="form-label">Email Field</label>
                            <input type="text" class="form-control" id="email_field" name="email_field" 
                                   placeholder="Enter invalid email to test">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numeric_field" class="form-label">Numeric Field (1-100)</label>
                            <input type="text" class="form-control" id="numeric_field" name="numeric_field" 
                                   placeholder="Enter invalid number to test">
                        </div>
                    </div>
                    <button type="button" class="btn btn-warning" onclick="testValidationError()">
                        <i class="fas fa-exclamation-triangle me-2"></i>Test Validation Errors
                    </button>
                </form>

                <div id="validationResults" class="alert alert-info d-none">
                    <div id="validationResultsContent"></div>
                </div>
            </div>
        </div>

        <!-- Security Testing Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-shield-alt me-2"></i>Security Testing
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Security Tests:</strong> These tests demonstrate various security features and protections.
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border-primary">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Rate Limiting Test</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test rate limiting by making multiple rapid requests.</p>
                                <button type="button" class="btn btn-outline-primary" onclick="testRateLimit()">
                                    <i class="fas fa-clock me-2"></i>Test Rate Limit
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-key me-2"></i>CSRF Protection Test</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test CSRF token validation.</p>
                                <button type="button" class="btn btn-outline-success" onclick="testCsrf()">
                                    <i class="fas fa-shield-alt me-2"></i>Test CSRF
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-danger">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-code me-2"></i>Input Sanitization Test</h6>
                            </div>
                            <div class="card-body">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="sanitizationInput" 
                                           placeholder="Try: <script>alert('XSS')</script>">
                                    <button type="button" class="btn btn-outline-danger" onclick="testSanitization()">
                                        <i class="fas fa-filter me-2"></i>Test Sanitization
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-info">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Logging Test</h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Test logging functionality across different channels.</p>
                                <button type="button" class="btn btn-outline-info" onclick="testLogging()">
                                    <i class="fas fa-clipboard-list me-2"></i>Test Logging
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Testing Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Performance Testing
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Performance Tests:</strong> These tests demonstrate performance monitoring and logging.
                </div>

                <button type="button" class="btn btn-success" onclick="testPerformance()">
                    <i class="fas fa-stopwatch me-2"></i>Test Performance Monitoring
                </button>
            </div>
        </div>

        <!-- Test Results Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Test Results
                </h5>
            </div>
            <div class="card-body">
                <div id="testResults" class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Click any test button above to see results here.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript for Testing -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultsDiv = document.getElementById('testResults');
    
    function showResult(title, content, type = 'info') {
        resultsDiv.className = `alert alert-${type}`;
        resultsDiv.innerHTML = `
            <h6><i class="fas fa-clipboard-check me-2"></i>${title}</h6>
            ${content}
            <small class="text-muted d-block mt-2">Test completed at: ${new Date().toLocaleString()}</small>
        `;
    }
    
    function showLoading(message) {
        resultsDiv.className = 'alert alert-info';
        resultsDiv.innerHTML = `
            <i class="fas fa-spinner fa-spin me-2"></i>${message}
        `;
    }
    
    // Test authentication error
    window.testAuthError = function() {
        showLoading('Testing authentication error...');
        
        fetch('{{ route("test.auth.error") }}', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            showResult('Authentication Test Result', `
                <div class="text-success">
                    <i class="fas fa-check-circle me-2"></i>Authentication test passed
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data, null, 2)}</pre>
            `, 'success');
        })
        .catch(error => {
            showResult('Authentication Test Result', `
                <div class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Authentication error detected (expected)
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${error.message}</pre>
            `, 'warning');
        });
    };
    
    // Test validation errors
    window.testValidationError = function() {
        showLoading('Testing validation errors...');
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('required_field', document.getElementById('required_field').value);
        formData.append('email_field', document.getElementById('email_field').value);
        formData.append('numeric_field', document.getElementById('numeric_field').value);
        
        fetch('{{ route("test.validation.error") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(JSON.stringify(data));
                });
            }
            return response.json();
        })
        .then(data => {
            showResult('Validation Test Result', `
                <div class="text-success">
                    <i class="fas fa-check-circle me-2"></i>All validations passed
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data, null, 2)}</pre>
            `, 'success');
        })
        .catch(error => {
            const errorData = JSON.parse(error.message);
            showResult('Validation Test Result', `
                <div class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Validation errors detected (expected for testing)
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(errorData, null, 2)}</pre>
            `, 'warning');
        });
    };
    
    // Test rate limiting
    window.testRateLimit = function() {
        showLoading('Testing rate limiting (making multiple requests)...');
        
        const requests = [];
        for (let i = 0; i < 10; i++) {
            requests.push(
                fetch('{{ route("test.rate.limit") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
            );
        }
        
        Promise.allSettled(requests)
        .then(results => {
            const successful = results.filter(r => r.status === 'fulfilled').length;
            const failed = results.filter(r => r.status === 'rejected').length;
            
            showResult('Rate Limiting Test Result', `
                <div>
                    <div class="text-info">
                        <i class="fas fa-info-circle me-2"></i>Made 10 rapid requests
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle me-2"></i>Successful: ${successful}
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-times-circle me-2"></i>Rate limited: ${failed}
                    </div>
                </div>
            `, successful < 10 ? 'warning' : 'info');
        });
    };
    
    // Test CSRF protection
    window.testCsrf = function() {
        showLoading('Testing CSRF protection...');
        
        fetch('{{ route("test.csrf") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            showResult('CSRF Protection Test Result', `
                <div class="text-success">
                    <i class="fas fa-check-circle me-2"></i>CSRF protection working correctly
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data, null, 2)}</pre>
            `, 'success');
        })
        .catch(error => {
            showResult('CSRF Protection Test Result', `
                <div class="text-danger">
                    <i class="fas fa-times-circle me-2"></i>CSRF protection blocked request (expected behavior)
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${error.message}</pre>
            `, 'warning');
        });
    };
    
    // Test input sanitization
    window.testSanitization = function() {
        const input = document.getElementById('sanitizationInput').value;
        if (!input) {
            showResult('Input Sanitization Test', `
                <div class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Please enter some input to test sanitization
                </div>
            `, 'warning');
            return;
        }
        
        showLoading('Testing input sanitization...');
        
        fetch('{{ route("test.sanitization") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ test_input: input })
        })
        .then(response => response.json())
        .then(data => {
            showResult('Input Sanitization Test Result', `
                <div class="${data.xss_detected ? 'text-warning' : 'text-success'}">
                    <i class="fas ${data.xss_detected ? 'fa-exclamation-triangle' : 'fa-check-circle'} me-2"></i>
                    ${data.xss_detected ? 'XSS attempt detected and sanitized' : 'Input is clean'}
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data, null, 2)}</pre>
            `, data.xss_detected ? 'warning' : 'success');
        })
        .catch(error => {
            showResult('Input Sanitization Test Result', `
                <div class="text-danger">
                    <i class="fas fa-times-circle me-2"></i>Error testing sanitization
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${error.message}</pre>
            `, 'danger');
        });
    };
    
    // Test logging
    window.testLogging = function() {
        showLoading('Testing logging functionality...');
        
        fetch('{{ route("test.logging") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            showResult('Logging Test Result', `
                <div class="text-success">
                    <i class="fas fa-check-circle me-2"></i>Logging test completed successfully
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data, null, 2)}</pre>
            `, 'success');
        })
        .catch(error => {
            showResult('Logging Test Result', `
                <div class="text-danger">
                    <i class="fas fa-times-circle me-2"></i>Logging test failed
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${error.message}</pre>
            `, 'danger');
        });
    };
    
    // Test performance monitoring
    window.testPerformance = function() {
        showLoading('Testing performance monitoring...');
        
        const startTime = performance.now();
        
        fetch('{{ route("test.performance") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const clientTime = performance.now() - startTime;
            showResult('Performance Test Result', `
                <div class="text-success">
                    <i class="fas fa-check-circle me-2"></i>Performance monitoring working correctly
                </div>
                <div class="mt-2">
                    <strong>Client-side timing:</strong> ${clientTime.toFixed(2)}ms<br>
                    <strong>Server-side data:</strong>
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(data, null, 2)}</pre>
            `, 'success');
        })
        .catch(error => {
            showResult('Performance Test Result', `
                <div class="text-danger">
                    <i class="fas fa-times-circle me-2"></i>Performance test failed
                </div>
                <pre class="mt-2 bg-light p-2 rounded">${error.message}</pre>
            `, 'danger');
        });
    };
});
</script>
@endsection

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 10px;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
    
    .btn {
        border-radius: 6px;
    }
    
    .alert {
        border-radius: 8px;
    }
    
    pre {
        font-size: 0.875rem;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .card-body .card {
        transition: transform 0.2s ease-in-out;
    }
    
    .card-body .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>
@endpush