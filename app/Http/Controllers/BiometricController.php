<?php

namespace App\Http\Controllers;

use App\Models\BiometricAttendance;
use App\Models\BiometricDevice;
use App\Models\Teacher;
use App\Services\BiometricDeviceService;
use App\Services\BiometricRealTimeProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BiometricController extends Controller
{
    protected $deviceService;
    protected $realTimeProcessor;

    public function __construct(
        BiometricDeviceService $deviceService,
        BiometricRealTimeProcessor $realTimeProcessor
    ) {
        $this->deviceService = $deviceService;
        $this->realTimeProcessor = $realTimeProcessor;
    }

    /**
     * Import data from various sources (CSV upload or real-time device)
     */
    public function importData(Request $request): JsonResponse
    {
        try {
            // Determine import type
            $importType = $request->input('type', 'csv');
            
            switch ($importType) {
                case 'csv':
                    return $this->importCsvData($request);
                case 'real_time':
                    return $this->importRealTimeData($request);
                case 'device_stream':
                    return $this->handleDeviceStream($request);
                case 'bulk_sync':
                    return $this->bulkSyncFromDevice($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid import type. Supported types: csv, real_time, device_stream, bulk_sync'
                    ], 400);
            }
        } catch (\Exception $e) {
            $this->logBiometricEvent([
                'event_type' => 'import_error',
                'error' => $e->getMessage(),
                'type' => $request->input('type', 'unknown'),
                'request_data' => $request->all()
            ], null);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import data from CSV file (enhanced version)
     */
    private function importCsvData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // 50MB max
            'date_format' => 'nullable|string|in:Y-m-d,d/m/Y,m/d/Y,Y/m/d',
            'time_format' => 'nullable|string|in:H:i:s,H:i,g:i A,G:i',
            'device_id' => 'nullable|string',
            'auto_create_teachers' => 'nullable|boolean',
            'validate_duplicates' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('csv_file');
        $options = [
            'date_format' => $request->input('date_format', 'Y-m-d'),
            'time_format' => $request->input('time_format', 'H:i:s'),
            'device_id' => $request->input('device_id'),
            'auto_create_teachers' => $request->boolean('auto_create_teachers', false),
            'validate_duplicates' => $request->boolean('validate_duplicates', true)
        ];

        // Use the enhanced BiometricDeviceService for processing
        $result = $this->deviceService->importBiometricData($file, $options);

        return response()->json($result);
    }

    /**
     * Handle real-time data from biometric devices
     */
    private function importRealTimeData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'employee_id' => 'required|string',
            'timestamp' => 'required|date',
            'event_type' => 'required|in:check_in,check_out,break_in,break_out',
            'biometric_data' => 'nullable|string',
            'verification_method' => 'nullable|in:fingerprint,face,iris,card',
            'confidence_score' => 'nullable|numeric|between:0,100',
            'location' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Find teacher by employee_id through the user relationship
        $teacher = Teacher::whereHas('user', function ($query) use ($request) {
            $query->where('employee_id', $request->employee_id);
        })->first();
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'error' => 'Teacher not found for employee ID: ' . $request->employee_id
            ], 404);
        }

        // Process real-time attendance
        $eventType = $request->event_type;
        $timestamp = Carbon::parse($request->timestamp);
        $deviceId = $request->device_id;
        $location = $request->location;

        try {
            if ($eventType === 'check_in') {
                $result = $this->realTimeProcessor->processCheckIn(
                    $teacher->id, 
                    $timestamp, 
                    $deviceId, 
                    $location
                );
            } elseif ($eventType === 'check_out') {
                $result = $this->realTimeProcessor->processCheckOut(
                    $teacher->id, 
                    $timestamp, 
                    $deviceId, 
                    $location
                );
            } else {
                // Handle break events
                $result = $this->realTimeProcessor->processBreakEvent(
                    $teacher->id,
                    $eventType,
                    $timestamp,
                    $deviceId
                );
            }

            // Log the real-time event
            $this->logBiometricEvent($request->all(), $result);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Real-time biometric data processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Real-time biometric processing failed', [
                'teacher_id' => $teacher->id,
                'event_type' => $eventType,
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Real-time processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register a new biometric device
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|unique:biometric_devices,device_id',
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:fingerprint,face,iris,rfid,palm,hybrid',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'location' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'connection_type' => 'required|in:tcp,http,websocket,serial,usb',
            'api_endpoint' => 'nullable|url',
            'configuration' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $device = BiometricDevice::create([
                'device_id' => $request->device_id,
                'device_name' => $request->device_name,
                'device_type' => $request->device_type,
                'ip_address' => $request->ip_address,
                'port' => $request->port,
                'location' => $request->location,
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'connection_type' => $request->connection_type,
                'api_endpoint' => $request->api_endpoint,
                'configuration' => $request->configuration ?? [],
                'registered_by' => auth()->id(),
                'status' => 'offline'
            ]);

            // Test initial connection
            $connectionTest = $this->testDeviceConnection($device->device_id);

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully',
                'data' => $device,
                'connection_test' => $connectionTest
            ], 201);

        } catch (\Exception $e) {
            Log::error('Device registration failed', [
                'device_id' => $request->device_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all registered devices
     */
    public function getRegisteredDevices(Request $request): JsonResponse
    {
        $query = BiometricDevice::with('registeredBy:id,name');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        if ($request->has('active_only')) {
            $query->active();
        }

        $devices = $query->orderBy('device_name')->get();

        // Add runtime status information
        $devices->each(function ($device) {
            $device->is_online = $device->isOnline();
            $device->needs_maintenance = $device->needsMaintenance();
            $device->todays_scans = $device->getTodaysScanCount();
            $device->uptime_percentage = $device->getUptimePercentage();
        });

        return response()->json([
            'success' => true,
            'data' => $devices,
            'summary' => [
                'total_devices' => $devices->count(),
                'online_devices' => $devices->where('is_online', true)->count(),
                'offline_devices' => $devices->where('is_online', false)->count(),
                'maintenance_required' => $devices->where('needs_maintenance', true)->count()
            ]
        ]);
    }

    /**
     * Test device connection
     */
    public function testDeviceConnection(Request $request): JsonResponse
    {
        $deviceId = $request->route('deviceId') ?? $request->device_id;
        
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID is required'
            ], 400);
        }

        $device = BiometricDevice::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        try {
            // Simulate connection test (replace with actual device API calls)
            $connectionResult = $this->performDeviceConnectionTest($device);

            if ($connectionResult['success']) {
                $device->updateHeartbeat();
            } else {
                $device->markOffline();
            }

            return response()->json([
                'success' => $connectionResult['success'],
                'message' => $connectionResult['message'],
                'data' => [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'status' => $device->status,
                    'response_time' => $connectionResult['response_time'] ?? null,
                    'last_heartbeat' => $device->last_heartbeat_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Device connection test failed', [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get device status
     */
    public function getDeviceStatus(Request $request): JsonResponse
    {
        $deviceId = $request->route('deviceId');
        
        $device = BiometricDevice::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'status' => $device->status,
                'is_online' => $device->isOnline(),
                'last_sync' => $device->last_sync_at,
                'last_heartbeat' => $device->last_heartbeat_at,
                'todays_scans' => $device->getTodaysScanCount(),
                'uptime_percentage' => $device->getUptimePercentage(),
                'needs_maintenance' => $device->needsMaintenance(),
                'configuration' => $device->getConfiguration()
            ]
        ]);
    }

    /**
     * Handle continuous device stream data
     */
    public function handleDeviceStream(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'stream_data' => 'required|array',
            'stream_data.*.employee_id' => 'required|string',
            'stream_data.*.timestamp' => 'required|date',
            'stream_data.*.event_type' => 'required|in:check_in,check_out,break_in,break_out',
            'batch_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deviceId = $request->device_id;
        $streamData = $request->stream_data;
        $batchId = $request->batch_id ?? 'BATCH_' . uniqid();

        $processed = 0;
        $errors = [];
        $results = [];

        DB::beginTransaction();

        try {
            foreach ($streamData as $index => $data) {
                try {
                    // Create a sub-request for each stream item
                    $subRequest = new Request([
                        'device_id' => $deviceId,
                        'employee_id' => $data['employee_id'],
                        'timestamp' => $data['timestamp'],
                        'event_type' => $data['event_type'],
                        'biometric_data' => $data['biometric_data'] ?? null,
                        'verification_method' => $data['verification_method'] ?? 'fingerprint',
                        'confidence_score' => $data['confidence_score'] ?? null,
                        'location' => $data['location'] ?? null
                    ]);

                    $result = $this->importRealTimeData($subRequest);
                    $resultData = json_decode($result->getContent(), true);

                    if ($resultData['success']) {
                        $processed++;
                        $results[] = $resultData['data'];
                    } else {
                        $errors[] = "Item $index: " . ($resultData['error'] ?? 'Unknown error');
                    }

                } catch (\Exception $e) {
                    $errors[] = "Item $index: " . $e->getMessage();
                }
            }

            DB::commit();

            // Cache batch results for monitoring
            Cache::put("biometric_batch_{$batchId}", [
                'device_id' => $deviceId,
                'processed_count' => $processed,
                'error_count' => count($errors),
                'processed_at' => now(),
                'results' => $results,
                'errors' => $errors
            ], 3600);

            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'summary' => [
                    'total_items' => count($streamData),
                    'processed' => $processed,
                    'errors' => count($errors)
                ],
                'data' => $results,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Bulk sync from device memory
     */
    public function bulkSyncFromDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'sync_from_date' => 'nullable|date',
            'sync_to_date' => 'nullable|date',
            'clear_device_memory' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deviceId = $request->device_id;
        $syncFromDate = $request->sync_from_date ? Carbon::parse($request->sync_from_date) : Carbon::now()->subDays(7);
        $syncToDate = $request->sync_to_date ? Carbon::parse($request->sync_to_date) : Carbon::now();
        $clearMemory = $request->boolean('clear_device_memory', false);

        // This would typically connect to the actual device API
        // For now, we'll simulate the bulk sync process
        $syncId = 'SYNC_' . uniqid();

        try {
            // Simulate device connection and data retrieval
            $deviceData = $this->simulateDeviceDataRetrieval($deviceId, $syncFromDate, $syncToDate);

            if (empty($deviceData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No new data found on device',
                    'sync_id' => $syncId
                ]);
            }

            // Process the retrieved data
            $streamRequest = new Request([
                'device_id' => $deviceId,
                'stream_data' => $deviceData,
                'batch_id' => $syncId
            ]);

            $result = $this->handleDeviceStream($streamRequest);
            $resultData = json_decode($result->getContent(), true);

            if ($resultData['success'] && $clearMemory) {
                // Simulate clearing device memory
                $this->simulateDeviceMemoryClear($deviceId, $syncFromDate, $syncToDate);
            }

            return response()->json([
                'success' => true,
                'sync_id' => $syncId,
                'message' => 'Bulk sync completed successfully',
                'data' => $resultData
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk sync failed', [
                'device_id' => $deviceId,
                'sync_id' => $syncId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Bulk sync failed: ' . $e->getMessage(),
                'sync_id' => $syncId
            ], 500);
        }
    }

    /**
     * Get import status and statistics
     */
    public function getImportStatus(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all'); // all, recent, device, batch
        $deviceId = $request->input('device_id');
        $batchId = $request->input('batch_id');

        try {
            $data = [];

            if ($type === 'all' || $type === 'recent') {
                $data['recent_imports'] = $this->deviceService->getImportStatistics(7);
            }

            if ($type === 'all' || $type === 'device') {
                if ($deviceId) {
                    $data['device_status'] = $this->getInternalDeviceStatus($deviceId);
                } else {
                    $data['all_devices'] = $this->getAllDevicesStatus();
                }
            }

            if ($type === 'batch' && $batchId) {
                $data['batch_info'] = Cache::get("biometric_batch_{$batchId}");
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get import status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log biometric events for audit trail
     */
    private function logBiometricEvent(array $eventData, $result): void
    {
        Log::info('Biometric event processed', [
            'device_id' => $eventData['device_id'] ?? null,
            'employee_id' => $eventData['employee_id'] ?? null,
            'event_type' => $eventData['event_type'] ?? null,
            'timestamp' => $eventData['timestamp'] ?? null,
            'verification_method' => $eventData['verification_method'] ?? null,
            'confidence_score' => $eventData['confidence_score'] ?? null,
            'result' => $result,
            'processed_at' => now()
        ]);
    }

    /**
     * Simulate device data retrieval (replace with actual device API calls)
     */
    private function simulateDeviceDataRetrieval(string $deviceId, Carbon $fromDate, Carbon $toDate): array
    {
        // This would be replaced with actual device API calls
        // For demonstration, return sample data
        return [
            [
                'employee_id' => 'EMP001',
                'timestamp' => $fromDate->addHours(8)->toDateTimeString(),
                'event_type' => 'check_in',
                'verification_method' => 'fingerprint',
                'confidence_score' => 95.5
            ],
            [
                'employee_id' => 'EMP001',
                'timestamp' => $fromDate->addHours(17)->toDateTimeString(),
                'event_type' => 'check_out',
                'verification_method' => 'fingerprint',
                'confidence_score' => 97.2
            ]
        ];
    }

    /**
     * Simulate device memory clearing (replace with actual device API calls)
     */
    private function simulateDeviceMemoryClear(string $deviceId, Carbon $fromDate, Carbon $toDate): bool
    {
        // This would be replaced with actual device API calls
        Log::info('Device memory cleared', [
            'device_id' => $deviceId,
            'from_date' => $fromDate,
            'to_date' => $toDate
        ]);
        
        return true;
    }

    /**
     * Process real-time biometric data with validation and deduplication
     */
    private function processRealTimeData(array $data): array
    {
        $results = [
            'processed' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'details' => []
        ];

        foreach ($data as $index => $record) {
            try {
                // Validate record structure
                $validation = $this->validateBiometricRecord($record);
                if (!$validation['valid']) {
                    $results['errors']++;
                    $results['details'][] = [
                        'index' => $index,
                        'status' => 'error',
                        'message' => $validation['message']
                    ];
                    continue;
                }

                // Check for duplicates
                if ($this->isDuplicateRecord($record)) {
                    $results['duplicates']++;
                    $results['details'][] = [
                        'index' => $index,
                        'status' => 'duplicate',
                        'message' => 'Record already exists'
                    ];
                    continue;
                }

                // Process the record
                $processed = $this->processBiometricRecord($record);
                if ($processed) {
                    $results['processed']++;
                    $results['details'][] = [
                        'index' => $index,
                        'status' => 'success',
                        'message' => 'Record processed successfully'
                    ];
                } else {
                    $results['errors']++;
                    $results['details'][] = [
                        'index' => $index,
                        'status' => 'error',
                        'message' => 'Failed to process record'
                    ];
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'index' => $index,
                    'status' => 'error',
                    'message' => 'Exception: ' . $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Validate biometric record structure and data
     */
    private function validateBiometricRecord(array $record): array
    {
        // Required fields validation
        $requiredFields = ['employee_id', 'timestamp', 'event_type'];
        foreach ($requiredFields as $field) {
            if (!isset($record[$field]) || empty($record[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }

        // Employee ID validation
        if (!is_numeric($record['employee_id'])) {
            return [
                'valid' => false,
                'message' => 'Employee ID must be numeric'
            ];
        }

        // Timestamp validation
        if (!$this->isValidTimestamp($record['timestamp'])) {
            return [
                'valid' => false,
                'message' => 'Invalid timestamp format'
            ];
        }

        // Event type validation
        $validEventTypes = ['check_in', 'check_out', 'break_start', 'break_end'];
        if (!in_array($record['event_type'], $validEventTypes)) {
            return [
                'valid' => false,
                'message' => 'Invalid event type. Must be one of: ' . implode(', ', $validEventTypes)
            ];
        }

        // Device ID validation (if provided)
        if (isset($record['device_id']) && !is_string($record['device_id'])) {
            return [
                'valid' => false,
                'message' => 'Device ID must be a string'
            ];
        }

        // Biometric data validation (if provided)
        if (isset($record['biometric_data'])) {
            $biometricValidation = $this->validateBiometricData($record['biometric_data']);
            if (!$biometricValidation['valid']) {
                return $biometricValidation;
            }
        }

        return ['valid' => true, 'message' => 'Record is valid'];
    }

    /**
     * Validate biometric data (fingerprint, face, etc.)
     */
    private function validateBiometricData(array $biometricData): array
    {
        if (!isset($biometricData['type'])) {
            return [
                'valid' => false,
                'message' => 'Biometric data type is required'
            ];
        }

        $validTypes = ['fingerprint', 'face', 'iris', 'palm'];
        if (!in_array($biometricData['type'], $validTypes)) {
            return [
                'valid' => false,
                'message' => 'Invalid biometric type. Must be one of: ' . implode(', ', $validTypes)
            ];
        }

        if (!isset($biometricData['template']) || empty($biometricData['template'])) {
            return [
                'valid' => false,
                'message' => 'Biometric template data is required'
            ];
        }

        // Validate template format (base64 encoded)
        if (!base64_decode($biometricData['template'], true)) {
            return [
                'valid' => false,
                'message' => 'Biometric template must be base64 encoded'
            ];
        }

        return ['valid' => true, 'message' => 'Biometric data is valid'];
    }

    /**
     * Check if timestamp is valid
     */
    private function isValidTimestamp(string $timestamp): bool
    {
        // Support multiple timestamp formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s\Z',
            'Y-m-d\TH:i:s.u\Z',
            'U' // Unix timestamp
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $timestamp);
            if ($date && $date->format($format) === $timestamp) {
                return true;
            }
        }

        // Check if it's a valid Unix timestamp
        if (is_numeric($timestamp) && $timestamp > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check for duplicate records
     */
    private function isDuplicateRecord(array $record): bool
    {
        // Convert timestamp to standard format
        $timestamp = $this->normalizeTimestamp($record['timestamp']);
        
        // Check for existing record with same employee_id, timestamp, and event_type
        $existing = BiometricAttendance::where('teacher_id', $record['employee_id'])
            ->where('check_in_time', $timestamp)
            ->where('status', $this->mapEventTypeToStatus($record['event_type']))
            ->first();

        return $existing !== null;
    }

    /**
     * Normalize timestamp to standard format
     */
    private function normalizeTimestamp(string $timestamp): string
    {
        // If it's a Unix timestamp
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', (int)$timestamp);
        }

        // Try to parse various formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s\Z',
            'Y-m-d\TH:i:s.u\Z'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $timestamp);
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Fallback to current timestamp if parsing fails
        return date('Y-m-d H:i:s');
    }

    /**
     * Map event type to attendance status
     */
    private function mapEventTypeToStatus(string $eventType): string
    {
        $mapping = [
            'check_in' => 'present',
            'check_out' => 'present',
            'break_start' => 'break',
            'break_end' => 'present'
        ];

        return $mapping[$eventType] ?? 'present';
    }

    /**
     * Process individual biometric record
     */
    private function processBiometricRecord(array $record): bool
    {
        try {
            DB::beginTransaction();

            // Find the teacher
            $teacher = User::where('employee_id', $record['employee_id'])
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                throw new \Exception("Teacher not found with employee ID: {$record['employee_id']}");
            }

            // Normalize timestamp
            $timestamp = $this->normalizeTimestamp($record['timestamp']);
            $date = date('Y-m-d', strtotime($timestamp));
            $time = date('H:i:s', strtotime($timestamp));

            // Create or update attendance record
            $attendance = BiometricAttendance::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'date' => $date,
                ],
                [
                    'check_in_time' => $record['event_type'] === 'check_in' ? $time : null,
                    'check_out_time' => $record['event_type'] === 'check_out' ? $time : null,
                    'status' => $this->mapEventTypeToStatus($record['event_type']),
                    'device_id' => $record['device_id'] ?? 'unknown',
                    'import_source' => 'real_time_device',
                    'original_data' => json_encode($record),
                    'processed_at' => now(),
                ]
            );

            // Store biometric data if provided
            if (isset($record['biometric_data'])) {
                $this->storeBiometricData($teacher->id, $record['biometric_data'], $record['device_id'] ?? null);
            }

            // Calculate working hours and status
            $this->calculateAttendanceMetrics($attendance);

            // Log the event
            $this->logBiometricEvent([
                'event_type' => 'record_processed',
                'teacher_id' => $teacher->id,
                'employee_id' => $record['employee_id'],
                'event_type_detail' => $record['event_type'],
                'timestamp' => $timestamp,
                'device_id' => $record['device_id'] ?? 'unknown'
            ], true);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logBiometricEvent([
                'event_type' => 'processing_error',
                'error' => $e->getMessage(),
                'record' => $record
            ], false);
            return false;
        }
    }

    /**
     * Store biometric data (fingerprint, face template, etc.)
     */
    private function storeBiometricData(int $teacherId, array $biometricData, ?string $deviceId): void
    {
        // Check if BiometricData model exists, if not skip storage
        if (!class_exists('App\\Models\\BiometricData')) {
            return;
        }

        try {
            BiometricData::create([
                'teacher_id' => $teacherId,
                'device_id' => $deviceId,
                'biometric_type' => $biometricData['type'],
                'biometric_template' => $biometricData['template'],
                'quality_score' => $biometricData['quality'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the main process
            $this->logBiometricEvent([
                'event_type' => 'biometric_storage_error',
                'error' => $e->getMessage(),
                'teacher_id' => $teacherId,
                'biometric_type' => $biometricData['type']
            ], false);
        }
    }

    /**
     * Calculate attendance metrics (working hours, late arrival, etc.)
     */
    private function calculateAttendanceMetrics(BiometricAttendance $attendance): void
    {
        if ($attendance->check_in_time && $attendance->check_out_time) {
            $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time);
            $checkOut = Carbon::parse($attendance->date . ' ' . $attendance->check_out_time);
            
            // Calculate working hours
            $workingMinutes = $checkOut->diffInMinutes($checkIn);
            $attendance->working_hours = round($workingMinutes / 60, 2);
            
            // Check for late arrival (assuming 9:00 AM is standard time)
            $standardTime = Carbon::parse($attendance->date . ' 09:00:00');
            if ($checkIn->gt($standardTime)) {
                $attendance->late_minutes = $checkIn->diffInMinutes($standardTime);
                $attendance->is_late = true;
            }
            
            // Check for early departure (assuming 5:00 PM is standard time)
            $standardEndTime = Carbon::parse($attendance->date . ' 17:00:00');
            if ($checkOut->lt($standardEndTime)) {
                $attendance->early_departure_minutes = $standardEndTime->diffInMinutes($checkOut);
                $attendance->is_early_departure = true;
            }
            
            $attendance->save();
        }
    }
    private function performDeviceConnectionTest(BiometricDevice $device): array
    {
        $startTime = microtime(true);
        
        try {
            // Simulate different connection types
            switch ($device->connection_type) {
                case 'tcp':
                    return $this->testTcpConnection($device);
                case 'http':
                    return $this->testHttpConnection($device);
                case 'websocket':
                    return $this->testWebSocketConnection($device);
                default:
                    return [
                        'success' => true,
                        'message' => 'Connection test simulated successfully',
                        'response_time' => round((microtime(true) - $startTime) * 1000, 2)
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'response_time' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Test TCP connection
     */
    private function testTcpConnection(BiometricDevice $device): array
    {
        $startTime = microtime(true);
        
        if (!$device->ip_address || !$device->port) {
            return [
                'success' => false,
                'message' => 'IP address and port required for TCP connection',
                'response_time' => 0
            ];
        }

        // Simulate TCP connection test
        // In real implementation, use socket_create and socket_connect
        $responseTime = rand(50, 200); // Simulate response time
        usleep($responseTime * 1000); // Simulate network delay

        return [
            'success' => true,
            'message' => 'TCP connection successful',
            'response_time' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }

    /**
     * Test HTTP connection
     */
    private function testHttpConnection(BiometricDevice $device): array
    {
        $startTime = microtime(true);
        
        if (!$device->api_endpoint) {
            return [
                'success' => false,
                'message' => 'API endpoint required for HTTP connection',
                'response_time' => 0
            ];
        }

        // Simulate HTTP connection test
        // In real implementation, use Guzzle HTTP client
        $responseTime = rand(100, 500); // Simulate response time
        usleep($responseTime * 1000); // Simulate network delay

        return [
            'success' => true,
            'message' => 'HTTP connection successful',
            'response_time' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }

    /**
     * Test WebSocket connection
     */
    private function testWebSocketConnection(BiometricDevice $device): array
    {
        $startTime = microtime(true);
        
        // Simulate WebSocket connection test
        $responseTime = rand(75, 300); // Simulate response time
        usleep($responseTime * 1000); // Simulate network delay

        return [
            'success' => true,
            'message' => 'WebSocket connection successful',
            'response_time' => round((microtime(true) - $startTime) * 1000, 2)
        ];
    }

    /**
     * Get device status for internal use
     */
    private function getInternalDeviceStatus(string $deviceId): array
    {
        return [
            'device_id' => $deviceId,
            'status' => 'online',
            'last_sync' => now()->subMinutes(5),
            'pending_records' => 0,
            'total_records_today' => 45
        ];
    }

    /**
     * Get all devices status
     */
    private function getAllDevicesStatus(): array
    {
        return [
            [
                'device_id' => 'DEVICE_001',
                'name' => 'Main Entrance Scanner',
                'status' => 'online',
                'last_sync' => now()->subMinutes(2)
            ],
            [
                'device_id' => 'DEVICE_002', 
                'name' => 'Staff Room Scanner',
                'status' => 'offline',
                'last_sync' => now()->subHours(2)
            ]
        ];
    }
}