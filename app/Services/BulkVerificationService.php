<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentVerification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BulkVerificationService
{
    protected $aadhaarService;
    protected $birthCertificateOCRService;
    
    public function __construct(
        AadhaarVerificationService $aadhaarService,
        BirthCertificateOCRService $birthCertificateOCRService
    ) {
        $this->aadhaarService = $aadhaarService;
        $this->birthCertificateOCRService = $birthCertificateOCRService;
    }
    
    /**
     * Process bulk verification for multiple students
     */
    public function processBulkVerification(array $studentIds, array $verificationTypes, array $options = [])
    {
        $batchSize = $options['batch_size'] ?? 10;
        $maxRetries = $options['max_retries'] ?? 3;
        $delayBetweenBatches = $options['delay_between_batches'] ?? 2; // seconds
        
        $results = [
            'total_students' => count($studentIds),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'results' => [],
            'errors' => [],
            'started_at' => Carbon::now(),
            'completed_at' => null,
            'processing_time' => null
        ];
        
        try {
            // Process students in batches
            $studentBatches = array_chunk($studentIds, $batchSize);
            
            foreach ($studentBatches as $batchIndex => $batch) {
                Log::info("Processing batch " . ($batchIndex + 1) . " of " . count($studentBatches));
                
                $batchResults = $this->processBatch($batch, $verificationTypes, $maxRetries);
                
                // Merge batch results
                $results['processed'] += $batchResults['processed'];
                $results['successful'] += $batchResults['successful'];
                $results['failed'] += $batchResults['failed'];
                $results['skipped'] += $batchResults['skipped'];
                $results['results'] = array_merge($results['results'], $batchResults['results']);
                $results['errors'] = array_merge($results['errors'], $batchResults['errors']);
                
                // Add delay between batches to prevent overwhelming external services
                if ($batchIndex < count($studentBatches) - 1) {
                    sleep($delayBetweenBatches);
                }
            }
            
            $results['completed_at'] = Carbon::now();
            $results['processing_time'] = $results['completed_at']->diffInSeconds($results['started_at']);
            
            // Generate summary report
            $results['summary'] = $this->generateSummaryReport($results);
            
            return $results;
            
        } catch (\Exception $e) {
            Log::error('Bulk verification failed: ' . $e->getMessage());
            $results['errors'][] = [
                'type' => 'system_error',
                'message' => $e->getMessage(),
                'timestamp' => Carbon::now()
            ];
            
            return $results;
        }
    }
    
    /**
     * Process a batch of students
     */
    protected function processBatch(array $studentIds, array $verificationTypes, int $maxRetries)
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'results' => [],
            'errors' => []
        ];
        
        $students = Student::whereIn('id', $studentIds)->get();
        
        foreach ($students as $student) {
            $studentResult = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'admission_number' => $student->admission_number,
                'verifications' => [],
                'overall_status' => 'pending',
                'processed_at' => Carbon::now()
            ];
            
            $allSuccessful = true;
            
            foreach ($verificationTypes as $verificationType) {
                $verificationResult = $this->processStudentVerification(
                    $student, 
                    $verificationType, 
                    $maxRetries
                );
                
                $studentResult['verifications'][$verificationType] = $verificationResult;
                
                if ($verificationResult['status'] !== 'success') {
                    $allSuccessful = false;
                }
            }
            
            $studentResult['overall_status'] = $allSuccessful ? 'success' : 'partial_failure';
            
            $results['results'][] = $studentResult;
            $results['processed']++;
            
            if ($allSuccessful) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Process verification for a single student
     */
    protected function processStudentVerification(Student $student, string $verificationType, int $maxRetries)
    {
        $result = [
            'type' => $verificationType,
            'status' => 'pending',
            'message' => '',
            'data' => null,
            'attempts' => 0,
            'errors' => []
        ];
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $result['attempts'] = $attempt;
            
            try {
                switch ($verificationType) {
                    case 'aadhaar':
                        $verificationResult = $this->processAadhaarVerification($student);
                        break;
                        
                    case 'birth_certificate':
                        $verificationResult = $this->processBirthCertificateVerification($student);
                        break;
                        
                    default:
                        throw new \InvalidArgumentException("Unsupported verification type: {$verificationType}");
                }
                
                if ($verificationResult['success']) {
                    $result['status'] = 'success';
                    $result['message'] = $verificationResult['message'];
                    $result['data'] = $verificationResult['data'];
                    break;
                } else {
                    $result['errors'][] = [
                        'attempt' => $attempt,
                        'message' => $verificationResult['message'],
                        'timestamp' => Carbon::now()
                    ];
                    
                    if ($attempt === $maxRetries) {
                        $result['status'] = 'failed';
                        $result['message'] = "Failed after {$maxRetries} attempts: " . $verificationResult['message'];
                    }
                }
                
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                    'timestamp' => Carbon::now()
                ];
                
                if ($attempt === $maxRetries) {
                    $result['status'] = 'error';
                    $result['message'] = "Error after {$maxRetries} attempts: " . $e->getMessage();
                }
            }
            
            // Add delay between retries
            if ($attempt < $maxRetries) {
                sleep(1);
            }
        }
        
        return $result;
    }
    
    /**
     * Process Aadhaar verification for a student
     */
    protected function processAadhaarVerification(Student $student)
    {
        try {
            // Check if student has required data for Aadhaar verification
            if (empty($student->aadhaar_number)) {
                return [
                    'success' => false,
                    'message' => 'Student does not have Aadhaar number',
                    'data' => null
                ];
            }
            
            $verificationData = [
                'aadhaar_number' => $student->aadhaar_number,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'date_of_birth' => $student->date_of_birth,
                'gender' => $student->gender
            ];
            
            $result = $this->aadhaarService->verifyAadhaar($verificationData);
            
            if ($result['success']) {
                // Update or create student verification record
                $this->updateStudentVerification($student, 'aadhaar', $result);
                
                return [
                    'success' => true,
                    'message' => 'Aadhaar verification successful',
                    'data' => $result['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Aadhaar verification failed for student {$student->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Aadhaar verification service error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Process birth certificate verification for a student
     */
    protected function processBirthCertificateVerification(Student $student)
    {
        try {
            // Check if student has birth certificate document
            $birthCertificate = $student->documents()
                ->where('document_type', 'birth_certificate')
                ->where('status', 'verified')
                ->first();
            
            if (!$birthCertificate) {
                return [
                    'success' => false,
                    'message' => 'Student does not have a verified birth certificate document',
                    'data' => null
                ];
            }
            
            $result = $this->birthCertificateOCRService->processDocument($birthCertificate->file_path);
            
            if ($result['success']) {
                // Update or create student verification record
                $this->updateStudentVerification($student, 'birth_certificate', $result);
                
                return [
                    'success' => true,
                    'message' => 'Birth certificate verification successful',
                    'data' => $result['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Birth certificate verification failed for student {$student->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Birth certificate verification service error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Update or create student verification record
     */
    protected function updateStudentVerification(Student $student, string $verificationType, array $result)
    {
        try {
            DB::transaction(function () use ($student, $verificationType, $result) {
                $verification = StudentVerification::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'verification_type' => $verificationType
                    ],
                    [
                        'verification_data' => json_encode($result['data']),
                        'verification_status' => $result['success'] ? 'verified' : 'failed',
                        'confidence_score' => $result['data']['confidence_score'] ?? null,
                        'verified_at' => $result['success'] ? Carbon::now() : null,
                        'verification_notes' => $result['message'] ?? null
                    ]
                );
                
                Log::info("Updated {$verificationType} verification for student {$student->id}");
            });
            
        } catch (\Exception $e) {
            Log::error("Failed to update verification record for student {$student->id}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate summary report
     */
    protected function generateSummaryReport(array $results)
    {
        $summary = [
            'overview' => [
                'total_students' => $results['total_students'],
                'processed' => $results['processed'],
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped'],
                'success_rate' => $results['processed'] > 0 ? round(($results['successful'] / $results['processed']) * 100, 2) : 0,
                'processing_time' => $results['processing_time'] . ' seconds'
            ],
            'verification_types' => [],
            'common_errors' => [],
            'recommendations' => []
        ];
        
        // Analyze verification types
        $verificationStats = [];
        foreach ($results['results'] as $studentResult) {
            foreach ($studentResult['verifications'] as $type => $verification) {
                if (!isset($verificationStats[$type])) {
                    $verificationStats[$type] = [
                        'total' => 0,
                        'successful' => 0,
                        'failed' => 0
                    ];
                }
                
                $verificationStats[$type]['total']++;
                if ($verification['status'] === 'success') {
                    $verificationStats[$type]['successful']++;
                } else {
                    $verificationStats[$type]['failed']++;
                }
            }
        }
        
        foreach ($verificationStats as $type => $stats) {
            $summary['verification_types'][$type] = [
                'total' => $stats['total'],
                'successful' => $stats['successful'],
                'failed' => $stats['failed'],
                'success_rate' => $stats['total'] > 0 ? round(($stats['successful'] / $stats['total']) * 100, 2) : 0
            ];
        }
        
        // Analyze common errors
        $errorCounts = [];
        foreach ($results['results'] as $studentResult) {
            foreach ($studentResult['verifications'] as $verification) {
                if (!empty($verification['errors'])) {
                    foreach ($verification['errors'] as $error) {
                        $errorMessage = $error['message'];
                        $errorCounts[$errorMessage] = ($errorCounts[$errorMessage] ?? 0) + 1;
                    }
                }
            }
        }
        
        arsort($errorCounts);
        $summary['common_errors'] = array_slice($errorCounts, 0, 5, true);
        
        // Generate recommendations
        $summary['recommendations'] = $this->generateRecommendations($summary);
        
        return $summary;
    }
    
    /**
     * Generate recommendations based on results
     */
    protected function generateRecommendations(array $summary)
    {
        $recommendations = [];
        
        // Success rate recommendations
        if ($summary['overview']['success_rate'] < 70) {
            $recommendations[] = 'Consider reviewing student data quality before bulk verification';
        }
        
        if ($summary['overview']['success_rate'] < 50) {
            $recommendations[] = 'High failure rate detected. Check external service availability';
        }
        
        // Verification type specific recommendations
        foreach ($summary['verification_types'] as $type => $stats) {
            if ($stats['success_rate'] < 60) {
                $recommendations[] = "Low success rate for {$type} verification. Review data requirements";
            }
        }
        
        // Error-based recommendations
        if (!empty($summary['common_errors'])) {
            $topError = array_key_first($summary['common_errors']);
            if (strpos($topError, 'service') !== false) {
                $recommendations[] = 'External service issues detected. Consider retrying later';
            }
            if (strpos($topError, 'data') !== false) {
                $recommendations[] = 'Data quality issues found. Review student information completeness';
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get bulk verification progress
     */
    public function getBulkVerificationProgress($sessionId)
    {
        // This would typically use a cache or database to track progress
        // For now, return a mock progress
        return [
            'session_id' => $sessionId,
            'status' => 'in_progress',
            'progress' => 75,
            'processed' => 15,
            'total' => 20,
            'current_batch' => 2,
            'total_batches' => 3,
            'estimated_completion' => Carbon::now()->addMinutes(5),
            'started_at' => Carbon::now()->subMinutes(10)
        ];
    }
    
    /**
     * Cancel bulk verification
     */
    public function cancelBulkVerification($sessionId)
    {
        // Implementation would depend on how sessions are tracked
        return [
            'success' => true,
            'message' => 'Bulk verification cancelled successfully',
            'session_id' => $sessionId
        ];
    }
    
    /**
     * Get available verification types
     */
    public function getAvailableVerificationTypes()
    {
        return [
            'aadhaar' => [
                'name' => 'Aadhaar Verification',
                'description' => 'Verify student identity using Aadhaar number',
                'requirements' => ['aadhaar_number', 'name', 'date_of_birth'],
                'estimated_time' => '2-5 seconds per student'
            ],
            'birth_certificate' => [
                'name' => 'Birth Certificate OCR',
                'description' => 'Extract and verify data from birth certificate documents',
                'requirements' => ['birth_certificate_document'],
                'estimated_time' => '5-10 seconds per student'
            ]
        ];
    }
    
    /**
     * Validate bulk verification request
     */
    public function validateBulkVerificationRequest(array $studentIds, array $verificationTypes)
    {
        $errors = [];
        
        // Validate student IDs
        if (empty($studentIds)) {
            $errors[] = 'No students selected for verification';
        } else {
            $existingStudents = Student::whereIn('id', $studentIds)->count();
            if ($existingStudents !== count($studentIds)) {
                $errors[] = 'Some selected students do not exist';
            }
        }
        
        // Validate verification types
        if (empty($verificationTypes)) {
            $errors[] = 'No verification types selected';
        } else {
            $availableTypes = array_keys($this->getAvailableVerificationTypes());
            $invalidTypes = array_diff($verificationTypes, $availableTypes);
            if (!empty($invalidTypes)) {
                $errors[] = 'Invalid verification types: ' . implode(', ', $invalidTypes);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}