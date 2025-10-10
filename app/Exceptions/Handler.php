<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ComprehensiveErrorHandlingService;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Comprehensive error handling service
     */
    protected $errorHandlingService;
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        AuthenticationException::class => 'warning',
        AuthorizationException::class => 'warning',
        TokenMismatchException::class => 'warning',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        '_token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Initialize error handling service only when needed
            if (!$this->errorHandlingService) {
                $this->errorHandlingService = app(ComprehensiveErrorHandlingService::class);
            }
            
            // Use comprehensive error handling service
            $request = request();
            $this->errorHandlingService->handleError($e, $request, [
                'handler' => 'exception_handler',
                'reported_at' => now()
            ]);

            // Log security-related exceptions with additional context
            if ($this->isSecurityException($e)) {
                Log::warning('Security exception occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'user_id' => Auth::id(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'url' => request()->fullUrl(),
                    'timestamp' => now(),
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Unified JSON error response for API requests
        if ($request->expectsJson()) {
            return $this->renderJsonError($request, $e);
        }

        // Handle authentication exceptions
        if ($e instanceof AuthenticationException) {
            return $this->handleUnauthenticated($request, $e);
        }

        // Handle authorization exceptions
        if ($e instanceof AuthorizationException) {
            return $this->handleUnauthorized($request, $e);
        }

        // Handle CSRF token mismatch
        if ($e instanceof TokenMismatchException) {
            return $this->handleTokenMismatch($request, $e);
        }

        // Handle HTTP exceptions with security context
        if ($e instanceof HttpException) {
            return $this->handleHttpException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render unified JSON error response
     */
    protected function renderJsonError($request, Throwable $exception)
    {
        $statusCode = $this->getStatusCode($exception);
        $errorCode = $this->getErrorCode($exception);
        $message = $this->getErrorMessage($exception);

        // Log the error with context
        Log::error('API Error Response', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now(),
        ]);

        $response = [
            'error' => $message,
            'code' => $errorCode,
            'status_code' => $statusCode,
            'timestamp' => now()->toISOString(),
        ];

        // Add validation errors if applicable
        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->errors();
        }

        // Add debug information in non-production environments
        if (config('app.debug') && !app()->environment('production')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get appropriate HTTP status code for exception
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof TokenMismatchException) {
            return 419;
        }

        if ($exception instanceof NotFoundHttpException) {
            return 404;
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return 405;
        }

        // Default to 500 for unhandled exceptions
        return 500;
    }

    /**
     * Get error code for exception
     */
    protected function getErrorCode(Throwable $exception): string
    {
        if ($exception instanceof AuthenticationException) {
            return 'AUTHENTICATION_REQUIRED';
        }

        if ($exception instanceof AuthorizationException) {
            return 'INSUFFICIENT_PERMISSIONS';
        }

        if ($exception instanceof ValidationException) {
            return 'VALIDATION_ERROR';
        }

        if ($exception instanceof TokenMismatchException) {
            return 'CSRF_TOKEN_MISMATCH';
        }

        if ($exception instanceof NotFoundHttpException) {
            return 'RESOURCE_NOT_FOUND';
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return 'METHOD_NOT_ALLOWED';
        }

        if ($exception instanceof HttpException) {
            return 'HTTP_' . $exception->getStatusCode();
        }

        return 'INTERNAL_SERVER_ERROR';
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof AuthenticationException) {
            return 'Authentication required to access this resource.';
        }

        if ($exception instanceof AuthorizationException) {
            return $exception->getMessage() ?: 'You do not have permission to access this resource.';
        }

        if ($exception instanceof ValidationException) {
            return 'The given data was invalid.';
        }

        if ($exception instanceof TokenMismatchException) {
            return 'The provided CSRF token is invalid. Please refresh the page and try again.';
        }

        if ($exception instanceof NotFoundHttpException) {
            return 'The requested resource was not found.';
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return 'The specified method for the request is invalid.';
        }

        if ($exception instanceof HttpException) {
            return $exception->getMessage() ?: 'An error occurred while processing your request.';
        }

        // For production, return generic message
        if (app()->environment('production')) {
            return 'An unexpected error occurred. Please try again later.';
        }

        return $exception->getMessage() ?: 'An unexpected error occurred.';
    }

    /**
     * Handle unauthenticated users
     */
    protected function handleUnauthenticated(Request $request, AuthenticationException $exception)
    {
        Log::info('Unauthenticated access attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required to access this resource.',
                'code' => 'AUTHENTICATION_REQUIRED',
                'login_url' => route('login'),
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        return redirect()->guest(route('login'))->with('error', 'Please log in to access this page.');
    }

    /**
     * Handle unauthorized access
     */
    protected function handleUnauthorized(Request $request, AuthorizationException $exception)
    {
        $user = Auth::user();
        
        Log::warning('Unauthorized access attempt', [
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'message' => $exception->getMessage(),
            'timestamp' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $exception->getMessage() ?: 'You do not have permission to access this resource.',
                'code' => 'INSUFFICIENT_PERMISSIONS',
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        return back()->with('error', 'You do not have permission to perform this action.');
    }

    /**
     * Handle CSRF token mismatch
     */
    protected function handleTokenMismatch(Request $request, TokenMismatchException $exception)
    {
        Log::warning('CSRF token mismatch', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'CSRF Token Mismatch',
                'message' => 'The provided CSRF token is invalid. Please refresh the page and try again.',
                'code' => 'CSRF_TOKEN_MISMATCH',
                'timestamp' => now()->toISOString(),
            ], 419);
        }

        return back()->with('error', 'Security token expired. Please try again.');
    }

    /**
     * Handle HTTP exceptions
     */
    protected function handleHttpException(Request $request, HttpException $exception)
    {
        $statusCode = $exception->getStatusCode();

        // Log security-relevant HTTP exceptions
        if (in_array($statusCode, [401, 403, 419, 429])) {
            Log::warning('Security-related HTTP exception', [
                'status_code' => $statusCode,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'message' => $exception->getMessage(),
                'timestamp' => now(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => Response::$statusTexts[$statusCode] ?? 'Error',
                'message' => $exception->getMessage() ?: 'An error occurred while processing your request.',
                'code' => 'HTTP_' . $statusCode,
                'status_code' => $statusCode,
                'timestamp' => now()->toISOString(),
            ], $statusCode);
        }

        return parent::render($request, $exception);
    }

    /**
     * Check if exception is security-related
     */
    protected function isSecurityException(Throwable $e): bool
    {
        return $e instanceof AuthenticationException ||
               $e instanceof AuthorizationException ||
               $e instanceof TokenMismatchException ||
               ($e instanceof HttpException && in_array($e->getStatusCode(), [401, 403, 419, 429]));
    }
}
