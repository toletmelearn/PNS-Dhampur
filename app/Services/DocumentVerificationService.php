<?php

namespace App\Services;

use App\Models\StudentVerification;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentVerificationService
{
    // Supported file types and their MIME types
    const SUPPORTED_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    // Maximum file size (5MB)
    const MAX_FILE_SIZE = 5 * 1024 * 1024;

    // Document type validation rules
    const DOCUMENT_VALIDATION_RULES = [
        'birth_certificate' => [
            'required_fields' => ['name', 'date_of_birth', 'place_of_birth', 'father_name', 'mother_name'],
            'date_format_patterns' => ['/\d{2}\/\d{2}\/\d{4}/', '/\d{2}-\d{2}-\d{4}/', '/\d{4}-\d{2}-\d{2}/'],
            'keywords' => ['birth certificate', 'certificate of birth', 'birth', 'born'],
        ],
        'aadhar_card' => [
            'required_fields' => ['name', 'aadhar_number', 'date_of_birth', 'address'],
            'aadhar_pattern' => '/\d{4}\s?\d{4}\s?\d{4}/',
            'keywords' => ['aadhaar', 'aadhar', 'unique identification', 'uid'],
        ],
        'transfer_certificate' => [
            'required_fields' => ['student_name', 'class', 'school_name', 'date_of_leaving'],
            'keywords' => ['transfer certificate', 'school leaving certificate', 'tc'],
        ],
        'caste_certificate' => [
            'required_fields' => ['name', 'caste', 'category'],
            'keywords' => ['caste certificate', 'category certificate', 'sc', 'st', 'obc'],
        ],
        'income_certificate' => [
            'required_fields' => ['name', 'annual_income', 'family_income'],
            'keywords' => ['income certificate', 'annual income', 'family income'],
        ],
    ];

    /**
     * Process and verify a document
     */
    public function processDocument(UploadedFile $file, string $documentType, int $studentId, int $uploadedBy): StudentVerification
    {
        // Create initial verification record
        $verification = StudentVerification::create([
            'student_id' => $studentId,
            'document_type' => $documentType,
            'verification_status' => StudentVerification::STATUS_PENDING,
            'verification_method' => StudentVerification::METHOD_AUTOMATIC,
            'uploaded_by' => $uploadedBy,
        ]);

        try {
            // Step 1: Validate file format and quality
            $this->validateFileFormat($verification, $file);
            
            // Step 2: Store the file
            $filePath = $this->storeFile($file, $verification);
            $verification->update(['original_file_path' => $filePath]);
            
            // Step 3: Start processing
            $verification->markAsProcessing();
            
            // Step 4: Perform OCR and extract data
            $extractedData = $this->performOCR($verification, $filePath);
            
            // Step 5: Validate document content
            $validationResults = $this->validateDocumentContent($verification, $extractedData, $documentType);
            
            // Step 6: Cross-reference with student data
            $crossReferenceResults = $this->crossReferenceWithStudentData($verification, $extractedData, $studentId);
            
            // Step 7: Calculate confidence score
            $confidenceScore = $this->calculateConfidenceScore($validationResults, $crossReferenceResults);
            
            // Step 8: Make final decision
            $this->makeFinalDecision($verification, $confidenceScore, $validationResults, $crossReferenceResults);
            
            return $verification->fresh();
            
        } catch (\Exception $e) {
            Log::error('Document verification failed', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $verification->markAsFailed('Processing error: ' . $e->getMessage());
            return $verification->fresh();
        }
    }

    /**
     * Validate file format and basic quality checks
     */
    protected function validateFileFormat(StudentVerification $verification, UploadedFile $file): void
    {
        $verification->addToVerificationLog('format_validation_started');
        
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum limit of 5MB');
        }
        
        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        if (!isset(self::SUPPORTED_TYPES[$extension]) || self::SUPPORTED_TYPES[$extension] !== $mimeType) {
            throw new \Exception('Unsupported file type. Please upload PDF, JPG, PNG, or GIF files only.');
        }
        
        // Basic quality checks (simulated)
        $qualityScore = $this->simulateQualityCheck($file);
        
        $verification->update([
            'format_valid' => true,
            'quality_check_passed' => $qualityScore >= 70,
        ]);
        
        $verification->addToVerificationLog('format_validation_completed', [
            'file_size' => $file->getSize(),
            'file_type' => $extension,
            'mime_type' => $mimeType,
            'quality_score' => $qualityScore,
        ]);
    }

    /**
     * Store the uploaded file
     */
    protected function storeFile(UploadedFile $file, StudentVerification $verification): string
    {
        $verification->addToVerificationLog('file_storage_started');
        
        $filename = 'verification_' . $verification->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('student-verifications', $filename, 'public');
        
        $verification->addToVerificationLog('file_storage_completed', ['file_path' => $path]);
        
        return $path;
    }

    /**
     * Simulate OCR processing and data extraction
     */
    protected function performOCR(StudentVerification $verification, string $filePath): array
    {
        $verification->addToVerificationLog('ocr_started');
        
        // Simulate OCR processing delay
        sleep(1);
        
        // Simulate extracted data based on document type
        $extractedData = $this->simulateOCRExtraction($verification->document_type);
        
        $verification->update(['extracted_data' => $extractedData]);
        $verification->addToVerificationLog('ocr_completed', ['extracted_fields' => count($extractedData)]);
        
        return $extractedData;
    }

    /**
     * Validate document content against expected patterns
     */
    protected function validateDocumentContent(StudentVerification $verification, array $extractedData, string $documentType): array
    {
        $verification->addToVerificationLog('content_validation_started');
        
        $rules = self::DOCUMENT_VALIDATION_RULES[$documentType] ?? [];
        $results = [
            'required_fields_present' => 0,
            'total_required_fields' => count($rules['required_fields'] ?? []),
            'keywords_found' => 0,
            'format_checks_passed' => 0,
            'validation_score' => 0,
        ];
        
        // Check required fields
        foreach ($rules['required_fields'] ?? [] as $field) {
            if (isset($extractedData[$field]) && !empty($extractedData[$field])) {
                $results['required_fields_present']++;
            }
        }
        
        // Check for document-specific keywords
        $textContent = implode(' ', array_values($extractedData));
        foreach ($rules['keywords'] ?? [] as $keyword) {
            if (stripos($textContent, $keyword) !== false) {
                $results['keywords_found']++;
            }
        }
        
        // Document-specific validations
        if ($documentType === 'aadhar_card' && isset($rules['aadhar_pattern'])) {
            if (preg_match($rules['aadhar_pattern'], $textContent)) {
                $results['format_checks_passed']++;
            }
        }
        
        // Calculate validation score
        $fieldScore = $results['total_required_fields'] > 0 ? 
            ($results['required_fields_present'] / $results['total_required_fields']) * 60 : 0;
        $keywordScore = count($rules['keywords'] ?? []) > 0 ? 
            ($results['keywords_found'] / count($rules['keywords'])) * 30 : 0;
        $formatScore = $results['format_checks_passed'] * 10;
        
        $results['validation_score'] = min(100, $fieldScore + $keywordScore + $formatScore);
        
        $verification->update(['data_consistency_check' => $results['validation_score'] >= 60]);
        $verification->addToVerificationLog('content_validation_completed', $results);
        
        return $results;
    }

    /**
     * Cross-reference extracted data with student information
     */
    protected function crossReferenceWithStudentData(StudentVerification $verification, array $extractedData, int $studentId): array
    {
        $verification->addToVerificationLog('cross_reference_started');
        
        $student = Student::find($studentId);
        $results = [
            'name_match' => false,
            'dob_match' => false,
            'father_name_match' => false,
            'mother_name_match' => false,
            'match_score' => 0,
        ];
        
        if ($student) {
            // Name matching (fuzzy)
            if (isset($extractedData['name'])) {
                $similarity = 0;
                similar_text(strtolower($student->name), strtolower($extractedData['name']), $similarity);
                $results['name_match'] = $similarity >= 80;
            }
            
            // Date of birth matching
            if (isset($extractedData['date_of_birth']) && $student->dob) {
                $extractedDate = $this->parseDate($extractedData['date_of_birth']);
                if ($extractedDate && $extractedDate->format('Y-m-d') === $student->dob->format('Y-m-d')) {
                    $results['dob_match'] = true;
                }
            }
            
            // Father's name matching
            if (isset($extractedData['father_name']) && $student->father_name) {
                $similarity = 0;
                similar_text(strtolower($student->father_name), strtolower($extractedData['father_name']), $similarity);
                $results['father_name_match'] = $similarity >= 80;
            }
            
            // Mother's name matching
            if (isset($extractedData['mother_name']) && $student->mother_name) {
                $similarity = 0;
                similar_text(strtolower($student->mother_name), strtolower($extractedData['mother_name']), $similarity);
                $results['mother_name_match'] = $similarity >= 80;
            }
        }
        
        // Calculate match score
        $matches = array_filter([$results['name_match'], $results['dob_match'], $results['father_name_match'], $results['mother_name_match']]);
        $results['match_score'] = (count($matches) / 4) * 100;
        
        $verification->update(['cross_reference_check' => $results['match_score'] >= 50]);
        $verification->addToVerificationLog('cross_reference_completed', $results);
        
        return $results;
    }

    /**
     * Calculate overall confidence score
     */
    protected function calculateConfidenceScore(array $validationResults, array $crossReferenceResults): float
    {
        $validationWeight = 0.6;
        $crossReferenceWeight = 0.4;
        
        $score = ($validationResults['validation_score'] * $validationWeight) + 
                ($crossReferenceResults['match_score'] * $crossReferenceWeight);
        
        return round($score, 2);
    }

    /**
     * Make final verification decision
     */
    protected function makeFinalDecision(StudentVerification $verification, float $confidenceScore, array $validationResults, array $crossReferenceResults): void
    {
        $verification->addToVerificationLog('final_decision_started');
        
        $allResults = array_merge($validationResults, $crossReferenceResults, [
            'confidence_score' => $confidenceScore,
        ]);
        
        if ($confidenceScore >= StudentVerification::HIGH_CONFIDENCE_THRESHOLD) {
            $verification->markAsVerified($confidenceScore, $allResults);
            $verification->addToVerificationLog('final_decision_completed', ['decision' => 'auto_verified']);
        } elseif ($confidenceScore >= StudentVerification::MEDIUM_CONFIDENCE_THRESHOLD) {
            $verification->markForManualReview('Medium confidence score requires manual review');
            $verification->update(['confidence_score' => $confidenceScore, 'verification_results' => $allResults]);
            $verification->addToVerificationLog('final_decision_completed', ['decision' => 'manual_review']);
        } else {
            $verification->markAsFailed('Low confidence score', $allResults);
            $verification->addToVerificationLog('final_decision_completed', ['decision' => 'auto_rejected']);
        }
    }

    /**
     * Simulate quality check (in real implementation, this would use image processing)
     */
    protected function simulateQualityCheck(UploadedFile $file): int
    {
        // Simulate quality score based on file size and type
        $score = 70; // Base score
        
        if ($file->getSize() > 1024 * 1024) { // > 1MB
            $score += 10;
        }
        
        if (in_array($file->getClientOriginalExtension(), ['pdf', 'png'])) {
            $score += 10;
        }
        
        // Add some randomness to simulate real quality detection
        $score += rand(-10, 20);
        
        return min(100, max(0, $score));
    }

    /**
     * Simulate OCR data extraction
     */
    protected function simulateOCRExtraction(string $documentType): array
    {
        $baseData = [
            'extracted_text' => 'Simulated OCR extracted text content...',
            'confidence' => rand(70, 95),
        ];
        
        switch ($documentType) {
            case 'birth_certificate':
                return array_merge($baseData, [
                    'name' => 'John Doe',
                    'date_of_birth' => '15/03/2010',
                    'place_of_birth' => 'New Delhi',
                    'father_name' => 'Robert Doe',
                    'mother_name' => 'Jane Doe',
                    'registration_number' => 'BC/2010/12345',
                ]);
                
            case 'aadhar_card':
                return array_merge($baseData, [
                    'name' => 'John Doe',
                    'aadhar_number' => '1234 5678 9012',
                    'date_of_birth' => '15/03/2010',
                    'address' => '123 Main Street, New Delhi',
                    'gender' => 'Male',
                ]);
                
            case 'transfer_certificate':
                return array_merge($baseData, [
                    'student_name' => 'John Doe',
                    'class' => 'Class X',
                    'school_name' => 'ABC Public School',
                    'date_of_leaving' => '31/03/2023',
                    'reason_for_leaving' => 'Transfer',
                ]);
                
            default:
                return $baseData;
        }
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(string $dateString): ?Carbon
    {
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y'];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStats(): array
    {
        return [
            'total' => StudentVerification::count(),
            'pending' => StudentVerification::pending()->count(),
            'processing' => StudentVerification::processing()->count(),
            'verified' => StudentVerification::verified()->count(),
            'failed' => StudentVerification::failed()->count(),
            'manual_review' => StudentVerification::manualReview()->count(),
            'high_confidence' => StudentVerification::highConfidence()->count(),
            'medium_confidence' => StudentVerification::mediumConfidence()->count(),
            'low_confidence' => StudentVerification::lowConfidence()->count(),
        ];
    }

    /**
     * Bulk process pending verifications
     */
    public function processPendingVerifications(int $limit = 10): array
    {
        $pendingVerifications = StudentVerification::pending()->limit($limit)->get();
        $results = [];
        
        foreach ($pendingVerifications as $verification) {
            try {
                // Re-process the verification
                if ($verification->original_file_path && Storage::exists($verification->original_file_path)) {
                    $extractedData = $this->performOCR($verification, $verification->original_file_path);
                    $validationResults = $this->validateDocumentContent($verification, $extractedData, $verification->document_type);
                    $crossReferenceResults = $this->crossReferenceWithStudentData($verification, $extractedData, $verification->student_id);
                    $confidenceScore = $this->calculateConfidenceScore($validationResults, $crossReferenceResults);
                    $this->makeFinalDecision($verification, $confidenceScore, $validationResults, $crossReferenceResults);
                    
                    $results[] = ['id' => $verification->id, 'status' => 'processed'];
                } else {
                    $verification->markAsFailed('Original file not found');
                    $results[] = ['id' => $verification->id, 'status' => 'failed', 'reason' => 'file_not_found'];
                }
            } catch (\Exception $e) {
                $verification->markAsFailed('Processing error: ' . $e->getMessage());
                $results[] = ['id' => $verification->id, 'status' => 'failed', 'reason' => $e->getMessage()];
            }
        }
        
        return $results;
    }
}