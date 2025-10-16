<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\LoggingService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GlobalErrorHandling
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        try {
            $response = $next($request);
            
            // Log performance metrics
            $this->logPerformanceMetrics($request, $response, $startTime);
            
            // Log API requests
            if ($request->is('api/*')) {
                $this->logApiRequest($request, $response, $startTime);
            }
            
            return $response;
            
        } catch (\Throwable $exception) {
            // Log the error
            LoggingService::error($exception, [
                'request_data' => $request->except(['password', 'password_confirmation', '_token']),
                'request_headers' => $request->headers->all(),
            ]);
            
            // Handle different types of exceptions
            return $this->handleException($request, $exception);
        }
    }

    /**
     * Handle different types of exceptions
     */
    private function handleException(Request $request, \Throwable $exception): Response
    {
        // For API requests, return JSON response
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->handleApiException($exception);
        }
        
        // For web requests, return appropriate view
        return $this->handleWebException($request, $exception);
    }

    /**
     * Handle API exceptions
     */
    private function handleApiException(\Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);
        $errorCode = $this->getErrorCode($exception);
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $this->getErrorMessage($exception),
                'type' => class_basename($exception),
            ],
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID'),
        ];
        
        // Add debug information in non-production environments
        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(10)->toArray(),
            ];
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Handle web exceptions
     */
    private function handleWebException(Request $request, \Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);
        
        // Check if custom error view exists
        $errorView = "errors.{$statusCode}";
        
        if (view()->exists($errorView)) {
            return response()->view($errorView, [
                'exception' => $exception,
                'statusCode' => $statusCode,
            ], $statusCode);
        }
        
        // Fallback to generic error view
        return response()->view('errors.500', [
            'exception' => $exception,
            'statusCode' => $statusCode,
        ], $statusCode);
    }

    /**
     * Get HTTP status code from exception
     */
    private function getStatusCode(\Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }
        
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }
        
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404;
        }
        
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return $exception->getStatusCode();
        }
        
        return 500;
    }

    /**
     * Get error code from exception
     */
    private function getErrorCode(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        $exceptionClass = class_basename($exception);
        
        return strtoupper(str_replace('Exception', '', $exceptionClass)) . '_' . $statusCode;
    }

    /**
     * Get user-friendly error message
     */
    private function getErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return 'The given data was invalid.';
        }
        
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return 'Authentication required.';
        }
        
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 'This action is unauthorized.';
        }
        
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 'The requested resource was not found.';
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return 'The requested page was not found.';
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return 'The request method is not allowed for this endpoint.';
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
            return 'Too many requests. Please try again later.';
        }
        
        // For production, return generic message
        if (!config('app.debug')) {
            return 'An error occurred while processing your request.';
        }
        
        return $exception->getMessage();
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, $response, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Log slow requests
        if (config('logging.performance.log_slow_requests') && 
            $executionTime > config('logging.performance.slow_request_threshold', 2000)) {
            
            LoggingService::performance('slow_request', $executionTime, [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'threshold' => config('logging.performance.slow_request_threshold', 2000),
            ]);
        }
        
        // Log memory usage if enabled
        if (config('logging.performance.log_memory_usage')) {
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // Convert to MB
            
            if ($memoryUsage > config('logging.performance.memory_threshold', 128)) {
                LoggingService::performance('high_memory_usage', $memoryUsage, [
                    'unit' => 'MB',
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'threshold' => config('logging.performance.memory_threshold', 128),
                ]);
            }
        }
    }

    /**
     * Log API requests
     */
    private function logApiRequest(Request $request, $response, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        LoggingService::api($request->path(), $response->getStatusCode(), [
            'response_time' => round($executionTime, 2),
            'request_size' => strlen($request->getContent()),
            'response_size' => strlen($response->getContent()),
        ]);
    }
}