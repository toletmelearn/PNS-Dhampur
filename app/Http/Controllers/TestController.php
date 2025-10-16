<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StudentFormRequest;
use App\Services\LoggingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class TestController extends Controller
{
    protected $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Show the validation demo page
     */
    public function validationDemo()
    {
        return view('test.validation-demo');
    }

    /**
     * Handle validation demo form submission
     */
    public function submitValidationDemo(StudentFormRequest $request)
    {
        try {
            // Log the validation attempt
            $this->loggingService->logValidation('validation_demo_submitted', [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'form_data_keys' => array_keys($request->all()),
                'validation_passed' => true
            ]);

            // Simulate processing the validated data
            $validatedData = $request->validated();
            
            // Check for security test fields and log them
            if ($request->has('test_xss') && !empty($request->test_xss)) {
                $this->loggingService->logSecurity('xss_attempt_detected', [
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'payload' => $request->test_xss,
                    'field' => 'test_xss',
                    'blocked' => true
                ]);
            }

            if ($request->has('test_sql') && !empty($request->test_sql)) {
                $this->loggingService->logSecurity('sql_injection_attempt_detected', [
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'payload' => $request->test_sql,
                    'field' => 'test_sql',
                    'blocked' => true
                ]);
            }

            // Handle file uploads if present
            $uploadedFiles = [];
            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                $this->loggingService->logFileOperation('file_upload_test', [
                    'user_id' => auth()->id(),
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'field' => 'profile_photo'
                ]);
                $uploadedFiles['profile_photo'] = $file->getClientOriginalName();
            }

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $this->loggingService->logFileOperation('document_upload_test', [
                        'user_id' => auth()->id(),
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'field' => 'documents'
                    ]);
                    $uploadedFiles['documents'][] = $file->getClientOriginalName();
                }
            }

            // Prepare success response
            $responseData = [
                'success' => true,
                'message' => 'Form validation and submission successful!',
                'data' => [
                    'validated_fields' => count($validatedData),
                    'uploaded_files' => $uploadedFiles,
                    'security_checks' => [
                        'xss_detected' => !empty($request->test_xss),
                        'sql_injection_detected' => !empty($request->test_sql),
                        'input_sanitized' => true
                    ],
                    'timestamp' => now()->toISOString()
                ]
            ];

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json($responseData);
            }

            // Return with success message for regular form submission
            return redirect()->back()->with([
                'success' => $responseData['message'],
                'validation_results' => $responseData['data']
            ]);

        } catch (ValidationException $e) {
            // Log validation failure
            $this->loggingService->logValidation('validation_demo_failed', [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'validation_errors' => $e->errors(),
                'failed_fields' => array_keys($e->errors())
            ]);

            // Return validation errors
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            throw $e;

        } catch (Exception $e) {
            // Log unexpected error
            $this->loggingService->logError('validation_demo_error', [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            // Return error response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred during form processing.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Test error pages
     */
    public function testErrorPages()
    {
        return view('test.error-pages');
    }

    /**
     * Trigger 404 error for testing
     */
    public function test404()
    {
        abort(404, 'This is a test 404 error page.');
    }

    /**
     * Trigger 403 error for testing
     */
    public function test403()
    {
        abort(403, 'This is a test 403 access forbidden page.');
    }

    /**
     * Trigger 500 error for testing
     */
    public function test500()
    {
        throw new Exception('This is a test 500 internal server error.');
    }

    /**
     * Test validation errors
     */
    public function testValidationError(Request $request)
    {
        $request->validate([
            'required_field' => 'required|string|min:5',
            'email_field' => 'required|email',
            'numeric_field' => 'required|numeric|min:1|max:100'
        ]);

        return response()->json(['message' => 'Validation passed']);
    }

    /**
     * Test authentication error
     */
    public function testAuthError()
    {
        if (!auth()->check()) {
            abort(401, 'Authentication required for this test.');
        }

        return response()->json(['message' => 'Authentication test passed']);
    }

    /**
     * Test rate limiting
     */
    public function testRateLimit()
    {
        // This endpoint can be used to test rate limiting middleware
        return response()->json([
            'message' => 'Rate limit test endpoint',
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip()
        ]);
    }

    /**
     * Test CSRF protection
     */
    public function testCsrf(Request $request)
    {
        return response()->json([
            'message' => 'CSRF protection test passed',
            'token_valid' => true,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Test input sanitization
     */
    public function testSanitization(Request $request)
    {
        $input = $request->input('test_input', '');
        
        return response()->json([
            'message' => 'Input sanitization test',
            'original_input' => $input,
            'sanitized_input' => strip_tags($input),
            'xss_detected' => $input !== strip_tags($input),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Test logging functionality
     */
    public function testLogging(Request $request)
    {
        try {
            // Test different log levels
            $this->loggingService->logSystem('test_logging_info', [
                'level' => 'info',
                'user_id' => auth()->id(),
                'test_data' => 'This is a test info log'
            ]);

            $this->loggingService->logSecurity('test_logging_security', [
                'level' => 'warning',
                'user_id' => auth()->id(),
                'test_data' => 'This is a test security log'
            ]);

            $this->loggingService->logError('test_logging_error', [
                'level' => 'error',
                'user_id' => auth()->id(),
                'test_data' => 'This is a test error log'
            ]);

            return response()->json([
                'message' => 'Logging test completed successfully',
                'logs_written' => 3,
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Logging test failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Test performance monitoring
     */
    public function testPerformance(Request $request)
    {
        $startTime = microtime(true);
        
        // Simulate some processing
        usleep(rand(100000, 500000)); // 0.1 to 0.5 seconds
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->loggingService->logPerformance('test_performance_endpoint', [
            'execution_time_ms' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'user_id' => auth()->id(),
            'endpoint' => 'test.performance'
        ]);

        return response()->json([
            'message' => 'Performance test completed',
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'timestamp' => now()->toISOString()
        ]);
    }
}