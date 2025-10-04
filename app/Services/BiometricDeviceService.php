<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use League\Csv\Reader;
use League\Csv\Statement;

class BiometricDeviceService
{
    // Supported file formats
    const SUPPORTED_FORMATS = ['csv', 'xlsx', 'xls'];
    
    // Biometric device types
    const DEVICE_TYPES = [
        'fingerprint' => 'Fingerprint Scanner',
        'face' => 'Face Recognition',
        'iris' => 'Iris Scanner',
        'rfid' => 'RFID Card Reader'
    ];
    
    // Import status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    protected $validationRules = [
        'employee_id' => 'required|string',
        'timestamp' => 'required|date',
        'device_id' => 'required|string',
        'verification_type' => 'required|in:IN,OUT,BREAK_IN,BREAK_OUT'
    ];
    
    /**
     * Import biometric data from uploaded file
     */
    public function importBiometricData(UploadedFile $file, array $options = []): array
    {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return $validation;
            }
            
            // Store file temporarily
            $filePath = $this->storeTemporaryFile($file);
            
            // Generate import session ID
            $importId = 'IMP_' . strtoupper(uniqid());
            
            // Cache import status
            Cache::put("biometric_import_{$importId}", [
                'status' => self::STATUS_PROCESSING,
                'file_name' => $file->getClientOriginalName(),
                'started_at' => Carbon::now(),
                'progress' => 0
            ], 3600); // 1 hour
            
            // Process file based on extension
            $extension = strtolower($file->getClientOriginalExtension());
            
            if ($extension === 'csv') {
                $result = $this->processCsvFile($filePath, $importId, $options);
            } else {
                $result = $this->processExcelFile($filePath, $importId, $options);
            }
            
            // Clean up temporary file
            Storage::delete($filePath);
            
            // Update final status
            Cache::put("biometric_import_{$importId}", array_merge(
                Cache::get("biometric_import_{$importId}", []),
                [
                    'status' => $result['success'] ? self::STATUS_COMPLETED : self::STATUS_FAILED,
                    'completed_at' => Carbon::now(),
                    'result' => $result
                ]
            ), 3600);
            
            return array_merge($result, ['import_id' => $importId]);
            
        } catch (\Exception $e) {
            Log::error('Biometric data import failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);
            
            return [
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage(),
                'error_code' => 'IMPORT_FAILED'
            ];
        }
    }
    
    /**
     * Process CSV file
     */
    protected function processCsvFile(string $filePath, string $importId, array $options): array
    {
        try {
            $csv = Reader::createFromPath(Storage::path($filePath), 'r');
            $csv->setHeaderOffset(0); // First row contains headers
            
            $records = Statement::create()->process($csv);
            $totalRecords = iterator_count($records);
            
            // Reset iterator
            $records = Statement::create()->process($csv);
            
            $processedCount = 0;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($records as $index => $record) {
                try {
                    // Update progress
                    $progress = ($processedCount / $totalRecords) * 100;
                    $this->updateImportProgress($importId, $progress);
                    
                    // Process individual record
                    $result = $this->processAttendanceRecord($record, $index + 2); // +2 for header and 0-based index
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $result;
                    }
                    
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'success' => $errorCount === 0,
                'total_records' => $totalRecords,
                'processed' => $processedCount,
                'successful' => $successCount,
                'failed' => $errorCount,
                'errors' => $errors,
                'summary' => $this->generateImportSummary($successCount, $errorCount)
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('CSV processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Process Excel file
     */
    protected function processExcelFile(string $filePath, string $importId, array $options): array
    {
        try {
            $spreadsheet = IOFactory::load(Storage::path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            
            $processedCount = 0;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Skip header row (start from row 2)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    // Update progress
                    $progress = (($row - 1) / ($highestRow - 1)) * 100;
                    $this->updateImportProgress($importId, $progress);
                    
                    // Extract row data
                    $record = [
                        'employee_id' => $worksheet->getCell('A' . $row)->getCalculatedValue(),
                        'timestamp' => $worksheet->getCell('B' . $row)->getCalculatedValue(),
                        'device_id' => $worksheet->getCell('C' . $row)->getCalculatedValue(),
                        'verification_type' => $worksheet->getCell('D' . $row)->getCalculatedValue(),
                        'device_type' => $worksheet->getCell('E' . $row)->getCalculatedValue() ?? 'fingerprint',
                        'confidence_score' => $worksheet->getCell('F' . $row)->getCalculatedValue() ?? 100
                    ];
                    
                    // Process individual record
                    $result = $this->processAttendanceRecord($record, $row);
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $result;
                    }
                    
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $row,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'success' => $errorCount === 0,
                'total_records' => $highestRow - 1, // Exclude header
                'processed' => $processedCount,
                'successful' => $successCount,
                'failed' => $errorCount,
                'errors' => $errors,
                'summary' => $this->generateImportSummary($successCount, $errorCount)
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Excel processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Process individual attendance record
     */
    protected function processAttendanceRecord(array $record, int $rowNumber): array
    {
        try {
            // Validate required fields
            $validation = $this->validateRecord($record);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'row' => $rowNumber,
                    'error' => $validation['error']
                ];
            }
            
            // Parse timestamp
            $timestamp = $this->parseTimestamp($record['timestamp']);
            if (!$timestamp) {
                return [
                    'success' => false,
                    'row' => $rowNumber,
                    'error' => 'Invalid timestamp format'
                ];
            }
            
            // Find employee (student or teacher)
            $employee = $this->findEmployee($record['employee_id']);
            if (!$employee) {
                return [
                    'success' => false,
                    'row' => $rowNumber,
                    'error' => 'Employee not found: ' . $record['employee_id']
                ];
            }
            
            // Create or update attendance record
            $attendance = $this->createAttendanceRecord($employee, $timestamp, $record);
            
            return [
                'success' => true,
                'row' => $rowNumber,
                'attendance_id' => $attendance->id,
                'employee_type' => $employee['type'],
                'employee_id' => $employee['id']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'row' => $rowNumber,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file): array
    {
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return [
                'valid' => false,
                'error' => 'File size exceeds 10MB limit',
                'error_code' => 'FILE_TOO_LARGE'
            ];
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            return [
                'valid' => false,
                'error' => 'Unsupported file format. Supported: ' . implode(', ', self::SUPPORTED_FORMATS),
                'error_code' => 'UNSUPPORTED_FORMAT'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Store file temporarily
     */
    protected function storeTemporaryFile(UploadedFile $file): string
    {
        return $file->store('temp/biometric_imports', 'local');
    }
    
    /**
     * Validate individual record
     */
    protected function validateRecord(array $record): array
    {
        foreach ($this->validationRules as $field => $rule) {
            if (strpos($rule, 'required') !== false && empty($record[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}"
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Parse timestamp from various formats
     */
    protected function parseTimestamp($timestamp): ?Carbon
    {
        try {
            // Handle Excel date serial numbers
            if (is_numeric($timestamp)) {
                return Carbon::createFromFormat('Y-m-d H:i:s', 
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($timestamp)->format('Y-m-d H:i:s')
                );
            }
            
            // Common timestamp formats
            $formats = [
                'Y-m-d H:i:s',
                'd/m/Y H:i:s',
                'm/d/Y H:i:s',
                'Y-m-d\TH:i:s',
                'd-m-Y H:i:s'
            ];
            
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $timestamp);
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Try Carbon's flexible parsing
            return Carbon::parse($timestamp);
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Find employee by ID (student or teacher)
     */
    protected function findEmployee(string $employeeId): ?array
    {
        // Try to find as student first
        $student = Student::where('student_id', $employeeId)
                         ->orWhere('roll_number', $employeeId)
                         ->first();
        
        if ($student) {
            return [
                'type' => 'student',
                'id' => $student->id,
                'model' => $student
            ];
        }
        
        // Try to find as teacher
        $teacher = Teacher::where('employee_id', $employeeId)
                         ->orWhere('teacher_id', $employeeId)
                         ->first();
        
        if ($teacher) {
            return [
                'type' => 'teacher',
                'id' => $teacher->id,
                'model' => $teacher
            ];
        }
        
        return null;
    }
    
    /**
     * Create attendance record
     */
    protected function createAttendanceRecord(array $employee, Carbon $timestamp, array $record): Attendance
    {
        $attendanceData = [
            'date' => $timestamp->toDateString(),
            'time_in' => $record['verification_type'] === 'IN' ? $timestamp->toTimeString() : null,
            'time_out' => $record['verification_type'] === 'OUT' ? $timestamp->toTimeString() : null,
            'device_id' => $record['device_id'],
            'device_type' => $record['device_type'] ?? 'fingerprint',
            'confidence_score' => $record['confidence_score'] ?? 100,
            'verification_method' => 'biometric',
            'imported_at' => Carbon::now()
        ];
        
        if ($employee['type'] === 'student') {
            $attendanceData['student_id'] = $employee['id'];
            $attendanceData['class_id'] = $employee['model']->class_id;
        } else {
            $attendanceData['teacher_id'] = $employee['id'];
        }
        
        // Check if attendance already exists for this date
        $existingAttendance = Attendance::where('date', $attendanceData['date'])
            ->where($employee['type'] . '_id', $employee['id'])
            ->first();
        
        if ($existingAttendance) {
            // Update existing record
            if ($record['verification_type'] === 'IN' && !$existingAttendance->time_in) {
                $existingAttendance->time_in = $attendanceData['time_in'];
            } elseif ($record['verification_type'] === 'OUT' && !$existingAttendance->time_out) {
                $existingAttendance->time_out = $attendanceData['time_out'];
            }
            
            $existingAttendance->save();
            return $existingAttendance;
        }
        
        // Create new attendance record
        return Attendance::create($attendanceData);
    }
    
    /**
     * Update import progress
     */
    protected function updateImportProgress(string $importId, float $progress): void
    {
        $cacheKey = "biometric_import_{$importId}";
        $data = Cache::get($cacheKey, []);
        $data['progress'] = round($progress, 2);
        $data['updated_at'] = Carbon::now();
        
        Cache::put($cacheKey, $data, 3600);
    }
    
    /**
     * Generate import summary
     */
    protected function generateImportSummary(int $successCount, int $errorCount): array
    {
        $total = $successCount + $errorCount;
        
        return [
            'success_rate' => $total > 0 ? round(($successCount / $total) * 100, 2) : 0,
            'total_processed' => $total,
            'successful_imports' => $successCount,
            'failed_imports' => $errorCount,
            'status' => $errorCount === 0 ? 'completed_successfully' : 'completed_with_errors'
        ];
    }
    
    /**
     * Get import status
     */
    public function getImportStatus(string $importId): ?array
    {
        return Cache::get("biometric_import_{$importId}");
    }
    
    /**
     * Get supported device types
     */
    public function getSupportedDeviceTypes(): array
    {
        return self::DEVICE_TYPES;
    }
    
    /**
     * Get import statistics
     */
    public function getImportStatistics(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        return [
            'total_imports' => Attendance::where('imported_at', '>=', $startDate)
                                       ->where('verification_method', 'biometric')
                                       ->count(),
            'successful_imports' => Attendance::where('imported_at', '>=', $startDate)
                                            ->where('verification_method', 'biometric')
                                            ->whereNotNull('time_in')
                                            ->count(),
            'device_usage' => Attendance::where('imported_at', '>=', $startDate)
                                      ->where('verification_method', 'biometric')
                                      ->groupBy('device_type')
                                      ->selectRaw('device_type, COUNT(*) as count')
                                      ->pluck('count', 'device_type')
                                      ->toArray(),
            'daily_imports' => Attendance::where('imported_at', '>=', $startDate)
                                       ->where('verification_method', 'biometric')
                                       ->groupBy(DB::raw('DATE(imported_at)'))
                                       ->selectRaw('DATE(imported_at) as date, COUNT(*) as count')
                                       ->orderBy('date')
                                       ->pluck('count', 'date')
                                       ->toArray()
        ];
    }
    
    /**
     * Clear import cache
     */
    public function clearImportCache(): void
    {
        $pattern = 'biometric_import_*';
        
        // This is a simplified cache clearing - in production you might want to use Redis SCAN
        // For now, we'll clear specific known cache keys
        Cache::flush(); // Note: This clears all cache, use with caution in production
    }
}