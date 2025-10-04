<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AadhaarVerificationService;
use App\Services\BiometricDeviceService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExternalIntegrationController extends Controller
{
    protected $aadhaarService;
    protected $biometricService;
    protected $notificationService;

    public function __construct(
        AadhaarVerificationService $aadhaarService,
        BiometricDeviceService $biometricService,
        NotificationService $notificationService
    ) {
        $this->aadhaarService = $aadhaarService;
        $this->biometricService = $biometricService;
        $this->notificationService = $notificationService;
    }

    /**
     * Verify Aadhaar number
     */
    public function verifyAadhaar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'aadhaar_number' => 'required|string|size:12|regex:/^[0-9]{12}$/',
                'user_id' => 'nullable|integer|exists:users,id',
                'purpose' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $aadhaarNumber = $request->input('aadhaar_number');
            $userId = $request->input('user_id');
            $purpose = $request->input('purpose', 'verification');

            // Verify Aadhaar
            $result = $this->aadhaarService->verifyAadhaar($aadhaarNumber, $userId, $purpose);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'verification_id' => $result['verification_id'],
                    'message' => 'Aadhaar verification completed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Aadhaar verification API error', [
                'error' => $e->getMessage(),
                'aadhaar' => $request->input('aadhaar_number')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Bulk verify Aadhaar numbers
     */
    public function bulkVerifyAadhaar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'aadhaar_numbers' => 'required|array|min:1|max:100',
                'aadhaar_numbers.*' => 'required|string|size:12|regex:/^[0-9]{12}$/',
                'purpose' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $aadhaarNumbers = $request->input('aadhaar_numbers');
            $purpose = $request->input('purpose', 'bulk_verification');

            // Bulk verify
            $results = $this->aadhaarService->bulkVerifyAadhaar($aadhaarNumbers, $purpose);

            return response()->json([
                'success' => true,
                'data' => $results,
                'total_processed' => count($aadhaarNumbers),
                'successful_verifications' => count(array_filter($results, fn($r) => $r['success'])),
                'failed_verifications' => count(array_filter($results, fn($r) => !$r['success']))
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk Aadhaar verification API error', [
                'error' => $e->getMessage(),
                'count' => count($request->input('aadhaar_numbers', []))
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Import biometric data from file
     */
    public function importBiometricData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
                'device_type' => 'required|string|in:fingerprint,face_recognition,iris,card_reader',
                'import_type' => 'required|string|in:attendance,enrollment',
                'date_format' => 'nullable|string|in:Y-m-d H:i:s,d/m/Y H:i:s,m/d/Y H:i:s',
                'mapping' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $deviceType = $request->input('device_type');
            $importType = $request->input('import_type');
            $dateFormat = $request->input('date_format', 'Y-m-d H:i:s');
            $mapping = $request->input('mapping', []);

            // Import biometric data
            $result = $this->biometricService->importBiometricData(
                $file,
                $deviceType,
                $importType,
                $dateFormat,
                $mapping
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'import_id' => $result['import_id'],
                    'summary' => $result['summary'],
                    'message' => 'Biometric data import started successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Biometric import API error', [
                'error' => $e->getMessage(),
                'file_name' => $request->file('file')?->getClientOriginalName()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Get biometric import status
     */
    public function getBiometricImportStatus(Request $request, string $importId): JsonResponse
    {
        try {
            $status = $this->biometricService->getImportStatus($importId);

            if ($status) {
                return response()->json([
                    'success' => true,
                    'data' => $status
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Import not found',
                    'error_code' => 'IMPORT_NOT_FOUND'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Biometric import status API error', [
                'error' => $e->getMessage(),
                'import_id' => $importId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Send browser notification
     */
    public function sendBrowserNotification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'type' => 'nullable|string|in:attendance,fee_reminder,exam_alert,announcement,emergency,system',
                'priority' => 'nullable|string|in:low,normal,high,urgent',
                'users' => 'nullable|array',
                'users.*' => 'integer|exists:users,id',
                'icon' => 'nullable|string|max:255',
                'url' => 'nullable|string|max:255',
                'actions' => 'nullable|array|max:2',
                'require_interaction' => 'nullable|boolean',
                'silent' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $title = $request->input('title');
            $message = $request->input('message');
            $options = [
                'type' => $request->input('type', 'system'),
                'priority' => $request->input('priority', 'normal'),
                'users' => $request->input('users', []),
                'icon' => $request->input('icon'),
                'url' => $request->input('url'),
                'actions' => $request->input('actions', []),
                'requireInteraction' => $request->input('require_interaction', false),
                'silent' => $request->input('silent', false),
                'data' => [
                    'sender_id' => Auth::id(),
                    'sent_at' => now()->toISOString()
                ]
            ];

            // Send notification
            $result = $this->notificationService->sendBrowserNotification($title, $message, $options);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'notification_id' => $result['notification_id'],
                    'sent_count' => $result['sent_count'],
                    'failed_count' => $result['failed_count'],
                    'total_users' => $result['total_users'],
                    'message' => 'Browser notification sent successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Browser notification API error', [
                'error' => $e->getMessage(),
                'title' => $request->input('title')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Subscribe user to push notifications
     */
    public function subscribeNotifications(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string|max:500',
                'keys' => 'required|array',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string',
                'user_id' => 'nullable|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id', Auth::id());
            $subscriptionData = [
                'endpoint' => $request->input('endpoint'),
                'keys' => $request->input('keys')
            ];

            // Subscribe user
            $result = $this->notificationService->subscribeUser($userId, $subscriptionData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'subscription_id' => $result['subscription_id'],
                    'action' => $result['action'],
                    'message' => 'User subscribed to notifications successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'error_code' => $result['error_code']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Notification subscription API error', [
                'error' => $e->getMessage(),
                'user_id' => $request->input('user_id', Auth::id())
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Get VAPID public key for client-side subscription
     */
    public function getVapidPublicKey(): JsonResponse
    {
        try {
            $publicKey = $this->notificationService->getVapidPublicKey();

            return response()->json([
                'success' => true,
                'public_key' => $publicKey
            ]);

        } catch (\Exception $e) {
            Log::error('VAPID public key API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Get Aadhaar verification statistics
     */
    public function getAadhaarStats(): JsonResponse
    {
        try {
            $stats = $this->aadhaarService->getVerificationStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Aadhaar stats API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Get biometric import statistics
     */
    public function getBiometricStats(): JsonResponse
    {
        try {
            $stats = $this->biometricService->getImportStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Biometric stats API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }
}