<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\UserFriendlyErrorService;

trait HandlesApiResponses
{
    /**
     * Handle exceptions with consistent response format
     */
    protected function handleException(Exception $exception, Request $request, string $context = 'general', int $statusCode = 500)
    {
        // Log the error for debugging
        Log::error("Error in {$context}: " . $exception->getMessage(), [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request_data' => $request->except(['password', 'password_confirmation', '_token']),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Determine response type based on request
        if ($this->expectsJson($request)) {
            return $this->jsonErrorResponse($exception, $context, $statusCode);
        }

        return $this->webErrorResponse($exception, $context);
    }

    /**
     * Handle validation exceptions specifically
     */
    protected function handleValidationException(ValidationException $exception, Request $request, string $context = 'validation')
    {
        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
                'error_code' => 422
            ], 422);
        }

        return back()->withErrors($exception->errors())->withInput();
    }

    /**
     * Return JSON error response
     */
    protected function jsonErrorResponse(Exception $exception, string $context = 'general', int $statusCode = 500): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => UserFriendlyErrorService::getErrorMessage($exception, $context),
            'error_code' => $statusCode
        ];

        // Add technical details for debugging in development or for super admins
        if (UserFriendlyErrorService::shouldShowTechnicalError()) {
            $response['technical_error'] = $exception->getMessage();
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return web redirect error response
     */
    protected function webErrorResponse(Exception $exception, string $context = 'general'): RedirectResponse
    {
        $errorMessage = UserFriendlyErrorService::getErrorMessageWithDebug($exception, $context);
        
        return back()->with('error', $errorMessage)->withInput();
    }

    /**
     * Return success response (JSON or redirect)
     */
    protected function successResponse(Request $request, string $context = 'general', $data = null, string $redirectRoute = null)
    {
        if ($this->expectsJson($request)) {
            return $this->jsonSuccessResponse($context, $data);
        }

        return $this->webSuccessResponse($context, $redirectRoute);
    }

    /**
     * Return JSON success response
     */
    protected function jsonSuccessResponse(string $context = 'general', $data = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => UserFriendlyErrorService::getSuccessMessage($context)
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    /**
     * Return web redirect success response
     */
    protected function webSuccessResponse(string $context = 'general', string $redirectRoute = null): RedirectResponse
    {
        $successMessage = UserFriendlyErrorService::getSuccessMessage($context);
        
        if ($redirectRoute) {
            return redirect()->route($redirectRoute)->with('success', $successMessage);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * Check if request expects JSON response
     */
    protected function expectsJson(Request $request): bool
    {
        return $request->expectsJson() || 
               $request->is('api/*') || 
               $request->ajax() || 
               $request->header('Accept') === 'application/json' ||
               $request->header('Content-Type') === 'application/json';
    }

    /**
     * Handle database transaction with consistent error handling
     */
    protected function handleTransaction(callable $callback, Request $request, string $context = 'general')
    {
        try {
            \DB::beginTransaction();
            
            $result = $callback();
            
            \DB::commit();
            
            return $this->successResponse($request, $context, $result);
            
        } catch (ValidationException $e) {
            \DB::rollBack();
            return $this->handleValidationException($e, $request, $context);
            
        } catch (Exception $e) {
            \DB::rollBack();
            return $this->handleException($e, $request, $context);
        }
    }

    /**
     * Validate request and handle errors consistently
     */
    protected function validateAndHandle(Request $request, array $rules, array $messages = [], string $context = 'validation')
    {
        try {
            $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e, $request, $context);
        }
        
        return null; // No validation errors
    }

    /**
     * Handle bulk operations with consistent error reporting
     */
    protected function handleBulkOperation(Request $request, callable $callback, string $context = 'bulk_operation')
    {
        try {
            \DB::beginTransaction();
            
            $results = [
                'success_count' => 0,
                'error_count' => 0,
                'errors' => [],
                'processed_items' => []
            ];
            
            $result = $callback($results);
            
            \DB::commit();
            
            if ($this->expectsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => "Bulk operation completed. {$results['success_count']} successful, {$results['error_count']} failed.",
                    'data' => $result
                ]);
            }
            
            $message = "Bulk operation completed. {$results['success_count']} successful";
            if ($results['error_count'] > 0) {
                $message .= ", {$results['error_count']} failed";
            }
            
            return back()->with('success', $message);
            
        } catch (Exception $e) {
            \DB::rollBack();
            return $this->handleException($e, $request, $context);
        }
    }

    /**
     * Handle file upload operations with consistent error handling
     */
    protected function handleFileUpload(Request $request, callable $callback, string $context = 'file_upload')
    {
        try {
            $result = $callback();
            return $this->successResponse($request, $context, $result);
            
        } catch (Exception $e) {
            return $this->handleException($e, $request, $context);
        }
    }
}