<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AadhaarVerificationService
{
    // Mock API configuration for development
    const MOCK_API_ENABLED = true;
    const MOCK_SUCCESS_RATE = 0.85; // 85% success rate for realistic testing
    
    // Real API configuration (for production)
    const API_BASE_URL = 'https://api.aadhaarverification.com/v1';
    const API_TIMEOUT = 30; // seconds
    
    protected $apiKey;
    protected $apiSecret;
    
    public function __construct()
    {
        $this->apiKey = config('services.aadhaar.api_key');
        $this->apiSecret = config('services.aadhaar.api_secret');
    }
    
    /**
     * Verify Aadhaar number with demographic data
     */
    public function verifyAadhaar(string $aadhaarNumber, array $demographicData = []): array
    {
        // Validate Aadhaar number format
        if (!$this->isValidAadhaarFormat($aadhaarNumber)) {
            return [
                'success' => false,
                'error' => 'Invalid Aadhaar number format',
                'error_code' => 'INVALID_FORMAT'
            ];
        }
        
        // Use mock API for development
        if (self::MOCK_API_ENABLED || app()->environment(['local', 'testing'])) {
            return $this->mockVerifyAadhaar($aadhaarNumber, $demographicData);
        }
        
        // Real API call for production
        return $this->realVerifyAadhaar($aadhaarNumber, $demographicData);
    }
    
    /**
     * Mock Aadhaar verification for development
     */
    protected function mockVerifyAadhaar(string $aadhaarNumber, array $demographicData): array
    {
        // Simulate API delay
        usleep(rand(500000, 2000000)); // 0.5 to 2 seconds
        
        // Generate deterministic response based on Aadhaar number
        $hash = crc32($aadhaarNumber);
        $isSuccess = ($hash % 100) < (self::MOCK_SUCCESS_RATE * 100);
        
        if (!$isSuccess) {
            return $this->generateMockErrorResponse($aadhaarNumber);
        }
        
        // Generate mock verified data
        $mockData = $this->generateMockAadhaarData($aadhaarNumber, $demographicData);
        
        // Calculate match scores
        $matchScores = $this->calculateMatchScores($demographicData, $mockData);
        
        return [
            'success' => true,
            'aadhaar_number' => $this->maskAadhaarNumber($aadhaarNumber),
            'verified_data' => $mockData,
            'match_scores' => $matchScores,
            'overall_match_score' => $matchScores['overall'],
            'verification_timestamp' => Carbon::now()->toISOString(),
            'reference_id' => 'MOCK_' . strtoupper(uniqid()),
            'api_response_time' => rand(800, 2500) . 'ms',
            'confidence_level' => $this->getConfidenceLevel($matchScores['overall'])
        ];
    }
    
    /**
     * Real Aadhaar verification API call
     */
    protected function realVerifyAadhaar(string $aadhaarNumber, array $demographicData): array
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'X-API-Secret' => $this->apiSecret
                ])
                ->post(self::API_BASE_URL . '/verify', [
                    'aadhaar_number' => $aadhaarNumber,
                    'demographic_data' => $demographicData,
                    'verification_type' => 'demographic',
                    'consent' => true
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Log successful verification
                Log::info('Aadhaar verification successful', [
                    'aadhaar' => $this->maskAadhaarNumber($aadhaarNumber),
                    'reference_id' => $data['reference_id'] ?? null
                ]);
                
                return $data;
            }
            
            // Handle API errors
            $errorData = $response->json();
            Log::error('Aadhaar verification API error', [
                'status' => $response->status(),
                'error' => $errorData
            ]);
            
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'API verification failed',
                'error_code' => $errorData['error_code'] ?? 'API_ERROR'
            ];
            
        } catch (\Exception $e) {
            Log::error('Aadhaar verification exception', [
                'error' => $e->getMessage(),
                'aadhaar' => $this->maskAadhaarNumber($aadhaarNumber)
            ]);
            
            return [
                'success' => false,
                'error' => 'Verification service unavailable',
                'error_code' => 'SERVICE_UNAVAILABLE'
            ];
        }
    }
    
    /**
     * Generate mock Aadhaar data for testing
     */
    protected function generateMockAadhaarData(string $aadhaarNumber, array $inputData): array
    {
        // Predefined mock names for consistent testing
        $mockNames = [
            'Rajesh Kumar Singh', 'Priya Sharma', 'Amit Patel', 'Sunita Devi',
            'Vikash Kumar', 'Anita Singh', 'Suresh Gupta', 'Kavita Yadav',
            'Ramesh Chandra', 'Meera Kumari', 'Ajay Verma', 'Pooja Agarwal'
        ];
        
        $hash = crc32($aadhaarNumber);
        $nameIndex = abs($hash) % count($mockNames);
        
        // Use input data if available, otherwise generate mock data
        $name = $inputData['name'] ?? $mockNames[$nameIndex];
        $fatherName = $inputData['father_name'] ?? $this->generateFatherName($name);
        
        // Generate date of birth (if not provided)
        if (isset($inputData['date_of_birth'])) {
            $dob = $inputData['date_of_birth'];
        } else {
            $year = rand(1990, 2010);
            $month = rand(1, 12);
            $day = rand(1, 28);
            $dob = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
        
        return [
            'name' => $name,
            'father_name' => $fatherName,
            'date_of_birth' => $dob,
            'gender' => $inputData['gender'] ?? (rand(0, 1) ? 'Male' : 'Female'),
            'address' => $this->generateMockAddress($hash),
            'pincode' => $this->generateMockPincode($hash),
            'state' => 'Uttar Pradesh',
            'district' => 'Bijnor'
        ];
    }
    
    /**
     * Generate mock error response
     */
    protected function generateMockErrorResponse(string $aadhaarNumber): array
    {
        $errors = [
            ['error' => 'Aadhaar number not found', 'error_code' => 'NOT_FOUND'],
            ['error' => 'Invalid Aadhaar number', 'error_code' => 'INVALID_AADHAAR'],
            ['error' => 'Service temporarily unavailable', 'error_code' => 'SERVICE_DOWN'],
            ['error' => 'Rate limit exceeded', 'error_code' => 'RATE_LIMIT'],
        ];
        
        $hash = crc32($aadhaarNumber);
        $errorIndex = abs($hash) % count($errors);
        
        return array_merge([
            'success' => false,
            'aadhaar_number' => $this->maskAadhaarNumber($aadhaarNumber),
            'verification_timestamp' => Carbon::now()->toISOString(),
        ], $errors[$errorIndex]);
    }
    
    /**
     * Calculate match scores between input and verified data
     */
    protected function calculateMatchScores(array $inputData, array $verifiedData): array
    {
        $scores = [];
        $totalScore = 0;
        $fieldCount = 0;
        
        // Name matching
        if (isset($inputData['name']) && isset($verifiedData['name'])) {
            $scores['name'] = $this->calculateNameMatch($inputData['name'], $verifiedData['name']);
            $totalScore += $scores['name'];
            $fieldCount++;
        }
        
        // Father name matching
        if (isset($inputData['father_name']) && isset($verifiedData['father_name'])) {
            $scores['father_name'] = $this->calculateNameMatch($inputData['father_name'], $verifiedData['father_name']);
            $totalScore += $scores['father_name'];
            $fieldCount++;
        }
        
        // Date of birth matching
        if (isset($inputData['date_of_birth']) && isset($verifiedData['date_of_birth'])) {
            $scores['date_of_birth'] = $this->calculateDateMatch($inputData['date_of_birth'], $verifiedData['date_of_birth']);
            $totalScore += $scores['date_of_birth'];
            $fieldCount++;
        }
        
        // Gender matching
        if (isset($inputData['gender']) && isset($verifiedData['gender'])) {
            $scores['gender'] = $this->calculateExactMatch($inputData['gender'], $verifiedData['gender']);
            $totalScore += $scores['gender'];
            $fieldCount++;
        }
        
        $scores['overall'] = $fieldCount > 0 ? round($totalScore / $fieldCount, 2) : 0;
        
        return $scores;
    }
    
    /**
     * Calculate name similarity score
     */
    protected function calculateNameMatch(string $name1, string $name2): float
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        if ($name1 === $name2) {
            return 100.0;
        }
        
        // Use Levenshtein distance for similarity
        $maxLen = max(strlen($name1), strlen($name2));
        if ($maxLen === 0) {
            return 100.0;
        }
        
        $distance = levenshtein($name1, $name2);
        $similarity = (1 - ($distance / $maxLen)) * 100;
        
        return max(0, round($similarity, 2));
    }
    
    /**
     * Calculate date match score
     */
    protected function calculateDateMatch(string $date1, string $date2): float
    {
        try {
            $d1 = Carbon::parse($date1);
            $d2 = Carbon::parse($date2);
            
            return $d1->isSameDay($d2) ? 100.0 : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    /**
     * Calculate exact match score
     */
    protected function calculateExactMatch(string $value1, string $value2): float
    {
        return strtolower(trim($value1)) === strtolower(trim($value2)) ? 100.0 : 0.0;
    }
    
    /**
     * Validate Aadhaar number format
     */
    protected function isValidAadhaarFormat(string $aadhaarNumber): bool
    {
        // Remove spaces and check if it's 12 digits
        $cleaned = preg_replace('/\s+/', '', $aadhaarNumber);
        
        if (!preg_match('/^\d{12}$/', $cleaned)) {
            return false;
        }
        
        // Validate Aadhaar checksum using Verhoeff algorithm
        return $this->validateAadhaarChecksum($cleaned);
    }
    
    /**
     * Validate Aadhaar checksum using Verhoeff algorithm
     */
    protected function validateAadhaarChecksum(string $aadhaar): bool
    {
        if (strlen($aadhaar) !== 12 || !ctype_digit($aadhaar)) {
            return false;
        }

        // Verhoeff algorithm multiplication table
        $multiplicationTable = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];

        // Permutation table
        $permutationTable = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];

        $checksum = 0;
        $aadhaarArray = str_split(strrev($aadhaar));

        for ($i = 0; $i < 12; $i++) {
            $checksum = $multiplicationTable[$checksum][$permutationTable[($i % 8)][(int)$aadhaarArray[$i]]];
        }

        return $checksum === 0;
    }
    
    /**
     * Mask Aadhaar number for logging
     */
    protected function maskAadhaarNumber(string $aadhaarNumber): string
    {
        $cleaned = preg_replace('/\s+/', '', $aadhaarNumber);
        if (strlen($cleaned) === 12) {
            return substr($cleaned, 0, 4) . 'XXXX' . substr($cleaned, -4);
        }
        return 'XXXXXXXXXXXX';
    }
    
    /**
     * Get confidence level based on match score
     */
    protected function getConfidenceLevel(float $score): string
    {
        if ($score >= 90) return 'HIGH';
        if ($score >= 70) return 'MEDIUM';
        if ($score >= 50) return 'LOW';
        return 'VERY_LOW';
    }
    
    /**
     * Generate mock father name
     */
    protected function generateFatherName(string $childName): string
    {
        $fatherNames = [
            'Ram Kumar', 'Shyam Singh', 'Mohan Lal', 'Suresh Kumar',
            'Rajesh Chandra', 'Vijay Kumar', 'Anil Singh', 'Prakash Gupta'
        ];
        
        $hash = crc32($childName);
        $index = abs($hash) % count($fatherNames);
        
        return $fatherNames[$index];
    }
    
    /**
     * Generate mock address
     */
    protected function generateMockAddress(int $hash): string
    {
        $addresses = [
            'Village Dhampur, Post Dhampur, Tehsil Dhampur',
            'Mohalla Sarai, Ward No. 5, Dhampur',
            'Near Government School, Dhampur Road',
            'Opposite Primary Health Center, Main Market',
            'Behind Bus Stand, Dhampur Town',
            'Near Railway Station, Station Road'
        ];
        
        $index = abs($hash) % count($addresses);
        return $addresses[$index];
    }
    
    /**
     * Generate mock pincode
     */
    protected function generateMockPincode(int $hash): string
    {
        $pincodes = ['246761', '246762', '246763', '246764', '246765'];
        $index = abs($hash) % count($pincodes);
        return $pincodes[$index];
    }
    
    /**
     * Check if Aadhaar verification is available
     */
    public function isServiceAvailable(): bool
    {
        if (self::MOCK_API_ENABLED || app()->environment(['local', 'testing'])) {
            return true;
        }
        
        // Check real API availability
        $cacheKey = 'aadhaar_service_status';
        
        return Cache::remember($cacheKey, 300, function () {
            try {
                $response = Http::timeout(5)->get(self::API_BASE_URL . '/health');
                return $response->successful();
            } catch (\Exception $e) {
                return false;
            }
        });
    }
    
    /**
     * Get service statistics
     */
    public function getServiceStats(): array
    {
        return [
            'service_available' => $this->isServiceAvailable(),
            'mock_mode' => self::MOCK_API_ENABLED || app()->environment(['local', 'testing']),
            'api_base_url' => self::API_BASE_URL,
            'timeout' => self::API_TIMEOUT,
            'mock_success_rate' => self::MOCK_SUCCESS_RATE * 100 . '%'
        ];
    }
}