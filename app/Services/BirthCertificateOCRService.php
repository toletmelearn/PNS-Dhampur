<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class BirthCertificateOCRService
{
    private $mockMode;
    private $mockSuccessRate;
    private $mockDelay;
    private $supportedFormats;
    private $maxFileSize;

    public function __construct()
    {
        $this->mockMode = config('services.birth_certificate_ocr.mock_mode', true);
        $this->mockSuccessRate = config('services.birth_certificate_ocr.mock_success_rate', 85);
        $this->mockDelay = config('services.birth_certificate_ocr.mock_delay', 2);
        $this->supportedFormats = ['jpg', 'jpeg', 'png', 'pdf'];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
    }

    /**
     * Extract data from birth certificate using OCR
     */
    public function extractData(UploadedFile $file, array $options = [])
    {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'error_code' => 'INVALID_FILE'
                ];
            }

            // Check service availability
            if (!$this->isServiceAvailable()) {
                return [
                    'success' => false,
                    'error' => 'OCR service is currently unavailable',
                    'error_code' => 'SERVICE_UNAVAILABLE'
                ];
            }

            // Process file
            if ($this->mockMode) {
                return $this->processMockOCR($file, $options);
            } else {
                return $this->processRealOCR($file, $options);
            }

        } catch (\Exception $e) {
            Log::error('Birth Certificate OCR Error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred during OCR processing',
                'error_code' => 'PROCESSING_ERROR'
            ];
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file)
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum limit of 10MB'
            ];
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->supportedFormats)) {
            return [
                'valid' => false,
                'error' => 'Unsupported file format. Supported formats: ' . implode(', ', $this->supportedFormats)
            ];
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'application/pdf'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type detected'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Process OCR using mock data (for development)
     */
    private function processMockOCR(UploadedFile $file, array $options = [])
    {
        // Simulate processing delay
        if ($this->mockDelay > 0) {
            sleep($this->mockDelay);
        }

        // Simulate success/failure based on mock success rate
        $random = rand(1, 100);
        if ($random > $this->mockSuccessRate) {
            return [
                'success' => false,
                'error' => 'OCR processing failed - unable to extract text from document',
                'error_code' => 'OCR_FAILED',
                'confidence' => rand(20, 40)
            ];
        }

        // Generate mock extracted data
        $mockData = $this->generateMockBirthCertificateData($options);
        
        // Calculate confidence scores
        $confidenceScores = $this->calculateConfidenceScores($mockData);
        
        return [
            'success' => true,
            'extracted_data' => $mockData,
            'confidence_scores' => $confidenceScores,
            'overall_confidence' => $confidenceScores['overall'],
            'processing_time' => $this->mockDelay,
            'ocr_engine' => 'Mock OCR Engine v1.0',
            'file_info' => [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ],
            'extracted_at' => now()->toISOString(),
            'reference_id' => 'MOCK_' . strtoupper(uniqid())
        ];
    }

    /**
     * Process OCR using real OCR service (for production)
     */
    private function processRealOCR(UploadedFile $file, array $options = [])
    {
        // This would integrate with actual OCR services like:
        // - Google Cloud Vision API
        // - AWS Textract
        // - Azure Computer Vision
        // - Tesseract OCR
        
        // For now, return a placeholder response
        return [
            'success' => false,
            'error' => 'Real OCR service not implemented yet',
            'error_code' => 'NOT_IMPLEMENTED'
        ];
    }

    /**
     * Generate mock birth certificate data
     */
    private function generateMockBirthCertificateData(array $options = [])
    {
        $names = [
            'Aarav Kumar', 'Vivaan Singh', 'Aditya Sharma', 'Vihaan Gupta', 'Arjun Patel',
            'Sai Reddy', 'Reyansh Yadav', 'Ayaan Khan', 'Krishna Verma', 'Ishaan Jain',
            'Ananya Agarwal', 'Diya Mishra', 'Aadhya Tiwari', 'Kavya Pandey', 'Arya Srivastava'
        ];

        $fatherNames = [
            'Rajesh Kumar', 'Suresh Singh', 'Ramesh Sharma', 'Mahesh Gupta', 'Dinesh Patel',
            'Naresh Reddy', 'Umesh Yadav', 'Rakesh Khan', 'Mukesh Verma', 'Lokesh Jain'
        ];

        $motherNames = [
            'Sunita Devi', 'Geeta Sharma', 'Sita Gupta', 'Rita Patel', 'Meera Singh',
            'Kavita Reddy', 'Anita Yadav', 'Seema Khan', 'Reema Verma', 'Neema Jain'
        ];

        $places = [
            'New Delhi', 'Mumbai', 'Bangalore', 'Chennai', 'Kolkata',
            'Hyderabad', 'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow'
        ];

        $registrationNumbers = [
            'BC/' . date('Y') . '/' . rand(100000, 999999),
            'REG/' . date('Y') . '/' . rand(100000, 999999),
            date('Y') . '/BC/' . rand(100000, 999999)
        ];

        // Generate random birth date (1-18 years ago)
        $birthDate = Carbon::now()->subYears(rand(1, 18))->subDays(rand(1, 365));
        
        return [
            'child_name' => $names[array_rand($names)],
            'father_name' => $fatherNames[array_rand($fatherNames)],
            'mother_name' => $motherNames[array_rand($motherNames)],
            'date_of_birth' => $birthDate->format('d/m/Y'),
            'place_of_birth' => $places[array_rand($places)],
            'gender' => rand(0, 1) ? 'Male' : 'Female',
            'registration_number' => $registrationNumbers[array_rand($registrationNumbers)],
            'registration_date' => $birthDate->addDays(rand(1, 30))->format('d/m/Y'),
            'registrar_name' => 'Sub-Registrar, ' . $places[array_rand($places)],
            'certificate_number' => 'CERT/' . date('Y') . '/' . rand(10000, 99999),
            'issued_date' => Carbon::now()->subDays(rand(1, 365))->format('d/m/Y'),
            'state' => 'Uttar Pradesh',
            'district' => 'Bijnor',
            'tehsil' => 'Dhampur',
            'village_town' => 'Dhampur',
            'hospital_name' => rand(0, 1) ? 'Government Hospital, Dhampur' : 'Private Nursing Home',
            'weight_at_birth' => rand(2000, 4000) . ' grams',
            'remarks' => rand(0, 1) ? 'Normal delivery' : null
        ];
    }

    /**
     * Calculate confidence scores for extracted data
     */
    private function calculateConfidenceScores(array $data)
    {
        $scores = [];
        
        // Individual field confidence scores
        foreach ($data as $field => $value) {
            if (empty($value)) {
                $scores[$field] = 0;
            } else {
                // Simulate confidence based on field type and content
                switch ($field) {
                    case 'child_name':
                    case 'father_name':
                    case 'mother_name':
                        $scores[$field] = rand(85, 98);
                        break;
                    case 'date_of_birth':
                    case 'registration_date':
                    case 'issued_date':
                        $scores[$field] = rand(90, 99);
                        break;
                    case 'registration_number':
                    case 'certificate_number':
                        $scores[$field] = rand(80, 95);
                        break;
                    case 'place_of_birth':
                    case 'state':
                    case 'district':
                        $scores[$field] = rand(75, 90);
                        break;
                    default:
                        $scores[$field] = rand(70, 85);
                }
            }
        }

        // Calculate overall confidence
        $nonZeroScores = array_filter($scores, function($score) {
            return $score > 0;
        });
        
        $overall = !empty($nonZeroScores) ? round(array_sum($nonZeroScores) / count($nonZeroScores)) : 0;
        
        $scores['overall'] = $overall;
        
        return $scores;
    }

    /**
     * Validate extracted data against expected patterns
     */
    public function validateExtractedData(array $extractedData, array $expectedData = [])
    {
        $validationResults = [];
        $overallScore = 0;
        $totalFields = 0;

        foreach ($extractedData as $field => $value) {
            if ($field === 'overall' || empty($value)) {
                continue;
            }

            $validation = $this->validateField($field, $value, $expectedData[$field] ?? null);
            $validationResults[$field] = $validation;
            
            $overallScore += $validation['score'];
            $totalFields++;
        }

        $averageScore = $totalFields > 0 ? round($overallScore / $totalFields) : 0;

        return [
            'field_validations' => $validationResults,
            'overall_score' => $averageScore,
            'validation_status' => $this->getValidationStatus($averageScore),
            'validated_at' => now()->toISOString()
        ];
    }

    /**
     * Validate individual field
     */
    private function validateField($field, $extractedValue, $expectedValue = null)
    {
        $score = 0;
        $issues = [];

        switch ($field) {
            case 'child_name':
            case 'father_name':
            case 'mother_name':
                if ($expectedValue) {
                    $similarity = $this->calculateStringSimilarity($extractedValue, $expectedValue);
                    $score = $similarity;
                    if ($similarity < 80) {
                        $issues[] = 'Name mismatch detected';
                    }
                } else {
                    $score = $this->validateName($extractedValue);
                }
                break;

            case 'date_of_birth':
            case 'registration_date':
            case 'issued_date':
                $score = $this->validateDate($extractedValue);
                if ($score < 90) {
                    $issues[] = 'Invalid or unclear date format';
                }
                break;

            case 'registration_number':
            case 'certificate_number':
                $score = $this->validateRegistrationNumber($extractedValue);
                if ($score < 80) {
                    $issues[] = 'Invalid registration number format';
                }
                break;

            case 'gender':
                $score = $this->validateGender($extractedValue);
                if ($score < 90) {
                    $issues[] = 'Gender information unclear';
                }
                break;

            default:
                $score = strlen($extractedValue) > 2 ? rand(70, 85) : 50;
        }

        return [
            'score' => $score,
            'issues' => $issues,
            'extracted_value' => $extractedValue,
            'expected_value' => $expectedValue
        ];
    }

    /**
     * Calculate string similarity percentage
     */
    private function calculateStringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        similar_text($str1, $str2, $percent);
        return round($percent);
    }

    /**
     * Validate name format
     */
    private function validateName($name)
    {
        if (empty($name) || strlen($name) < 2) {
            return 0;
        }
        
        // Check if name contains only letters and spaces
        if (preg_match('/^[a-zA-Z\s]+$/', $name)) {
            return rand(85, 95);
        }
        
        return rand(60, 80);
    }

    /**
     * Validate date format
     */
    private function validateDate($date)
    {
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y'];
        
        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj && $dateObj->format($format) === $date) {
                return rand(90, 99);
            }
        }
        
        return rand(30, 60);
    }

    /**
     * Validate registration number format
     */
    private function validateRegistrationNumber($number)
    {
        if (empty($number)) {
            return 0;
        }
        
        // Check common patterns
        $patterns = [
            '/^BC\/\d{4}\/\d{6}$/',
            '/^REG\/\d{4}\/\d{6}$/',
            '/^\d{4}\/BC\/\d{6}$/',
            '/^CERT\/\d{4}\/\d{5}$/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $number)) {
                return rand(85, 95);
            }
        }
        
        return rand(50, 75);
    }

    /**
     * Validate gender
     */
    private function validateGender($gender)
    {
        $validGenders = ['male', 'female', 'other', 'm', 'f', 'o'];
        
        if (in_array(strtolower($gender), $validGenders)) {
            return rand(90, 99);
        }
        
        return rand(40, 70);
    }

    /**
     * Get validation status based on score
     */
    private function getValidationStatus($score)
    {
        if ($score >= 90) {
            return 'EXCELLENT';
        } elseif ($score >= 80) {
            return 'GOOD';
        } elseif ($score >= 70) {
            return 'FAIR';
        } elseif ($score >= 60) {
            return 'POOR';
        } else {
            return 'FAILED';
        }
    }

    /**
     * Check if OCR service is available
     */
    public function isServiceAvailable()
    {
        if ($this->mockMode) {
            // In mock mode, simulate occasional service unavailability
            return rand(1, 100) <= 95; // 95% uptime
        }
        
        // For real service, implement actual health check
        return true;
    }

    /**
     * Get service status and statistics
     */
    public function getServiceStatus()
    {
        return [
            'service_available' => $this->isServiceAvailable(),
            'mock_mode' => $this->mockMode,
            'supported_formats' => $this->supportedFormats,
            'max_file_size' => $this->maxFileSize,
            'stats' => [
                'mock_success_rate' => $this->mockSuccessRate . '%',
                'mock_delay' => $this->mockDelay . 's',
                'supported_formats' => implode(', ', $this->supportedFormats)
            ]
        ];
    }

    /**
     * Extract specific field from birth certificate
     */
    public function extractSpecificField(UploadedFile $file, string $fieldName)
    {
        $result = $this->extractData($file);
        
        if (!$result['success']) {
            return $result;
        }
        
        $extractedData = $result['extracted_data'];
        
        if (!isset($extractedData[$fieldName])) {
            return [
                'success' => false,
                'error' => "Field '{$fieldName}' not found in extracted data",
                'error_code' => 'FIELD_NOT_FOUND'
            ];
        }
        
        return [
            'success' => true,
            'field_name' => $fieldName,
            'field_value' => $extractedData[$fieldName],
            'confidence' => $result['confidence_scores'][$fieldName] ?? 0,
            'reference_id' => $result['reference_id']
        ];
    }
}