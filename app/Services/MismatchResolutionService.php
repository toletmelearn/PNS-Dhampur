<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MismatchResolutionService
{
    // Confidence thresholds for automatic resolution
    const AUTO_RESOLVE_THRESHOLD = 0.95;
    const MANUAL_REVIEW_THRESHOLD = 0.70;
    const REJECT_THRESHOLD = 0.30;

    // Mismatch types
    const MISMATCH_NAME = 'name';
    const MISMATCH_DATE_OF_BIRTH = 'date_of_birth';
    const MISMATCH_FATHER_NAME = 'father_name';
    const MISMATCH_MOTHER_NAME = 'mother_name';
    const MISMATCH_ADDRESS = 'address';
    const MISMATCH_AADHAAR = 'aadhaar_number';
    const MISMATCH_BIRTH_CERTIFICATE = 'birth_certificate_number';

    /**
     * Analyze and resolve mismatches for a student verification
     */
    public function analyzeMismatches(StudentVerification $verification, array $extractedData): array
    {
        $student = $verification->student;
        $mismatches = [];
        $resolutions = [];
        $overallConfidence = 1.0;

        // Analyze each field for mismatches
        $fieldAnalyses = [
            self::MISMATCH_NAME => $this->analyzeName($student->name, $extractedData['name'] ?? ''),
            self::MISMATCH_DATE_OF_BIRTH => $this->analyzeDateOfBirth($student->date_of_birth, $extractedData['date_of_birth'] ?? ''),
            self::MISMATCH_FATHER_NAME => $this->analyzeName($student->father_name, $extractedData['father_name'] ?? ''),
            self::MISMATCH_MOTHER_NAME => $this->analyzeName($student->mother_name, $extractedData['mother_name'] ?? ''),
            self::MISMATCH_ADDRESS => $this->analyzeAddress($student->address, $extractedData['address'] ?? ''),
        ];

        // Add document-specific fields
        if ($verification->document_type === 'aadhaar') {
            $fieldAnalyses[self::MISMATCH_AADHAAR] = $this->analyzeAadhaarNumber(
                $student->aadhaar_number, 
                $extractedData['aadhaar_number'] ?? ''
            );
        } elseif ($verification->document_type === 'birth_certificate') {
            $fieldAnalyses[self::MISMATCH_BIRTH_CERTIFICATE] = $this->analyzeBirthCertificateNumber(
                $student->birth_certificate_number, 
                $extractedData['birth_certificate_number'] ?? ''
            );
        }

        // Process each field analysis
        foreach ($fieldAnalyses as $field => $analysis) {
            if (!$analysis['match']) {
                $mismatches[] = [
                    'field' => $field,
                    'expected' => $analysis['expected'],
                    'extracted' => $analysis['extracted'],
                    'confidence' => $analysis['confidence'],
                    'similarity_score' => $analysis['similarity_score'],
                    'mismatch_type' => $analysis['mismatch_type'],
                    'suggestions' => $analysis['suggestions'],
                ];

                // Generate resolution for this mismatch
                $resolution = $this->generateResolution($field, $analysis);
                if ($resolution) {
                    $resolutions[] = $resolution;
                }
            }

            // Update overall confidence (use minimum confidence approach)
            $overallConfidence = min($overallConfidence, $analysis['confidence']);
        }

        return [
            'mismatches' => $mismatches,
            'resolutions' => $resolutions,
            'overall_confidence' => $overallConfidence,
            'recommendation' => $this->getRecommendation($overallConfidence, $mismatches),
            'auto_resolvable' => $this->isAutoResolvable($mismatches, $overallConfidence),
            'requires_manual_review' => $this->requiresManualReview($overallConfidence),
        ];
    }

    /**
     * Analyze name mismatches with fuzzy matching
     */
    private function analyzeName(string $expected, string $extracted): array
    {
        $expected = $this->normalizeName($expected);
        $extracted = $this->normalizeName($extracted);

        if (empty($extracted)) {
            return [
                'match' => false,
                'expected' => $expected,
                'extracted' => $extracted,
                'confidence' => 0.0,
                'similarity_score' => 0.0,
                'mismatch_type' => 'missing_data',
                'suggestions' => ['Check document quality and re-scan'],
            ];
        }

        $similarity = $this->calculateStringSimilarity($expected, $extracted);
        $confidence = $this->calculateNameConfidence($expected, $extracted, $similarity);

        $suggestions = [];
        if ($similarity > 0.7) {
            $suggestions[] = 'Names are similar - possible OCR error or spelling variation';
            if ($this->hasCommonNameVariations($expected, $extracted)) {
                $suggestions[] = 'Common name variation detected';
                $confidence += 0.1; // Boost confidence for known variations
            }
        } elseif ($similarity > 0.5) {
            $suggestions[] = 'Partial name match - check for abbreviations or middle names';
        } else {
            $suggestions[] = 'Significant name difference - verify document belongs to correct student';
        }

        return [
            'match' => $similarity > 0.9,
            'expected' => $expected,
            'extracted' => $extracted,
            'confidence' => min($confidence, 1.0),
            'similarity_score' => $similarity,
            'mismatch_type' => $this->getNameMismatchType($expected, $extracted, $similarity),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Analyze date of birth mismatches
     */
    private function analyzeDateOfBirth(string $expected, string $extracted): array
    {
        $expectedDate = $this->parseDate($expected);
        $extractedDate = $this->parseDate($extracted);

        if (!$extractedDate) {
            return [
                'match' => false,
                'expected' => $expected,
                'extracted' => $extracted,
                'confidence' => 0.0,
                'similarity_score' => 0.0,
                'mismatch_type' => 'invalid_date',
                'suggestions' => ['Date format not recognized - check document quality'],
            ];
        }

        if (!$expectedDate) {
            return [
                'match' => false,
                'expected' => $expected,
                'extracted' => $extracted,
                'confidence' => 0.0,
                'similarity_score' => 0.0,
                'mismatch_type' => 'missing_expected_date',
                'suggestions' => ['Expected date is invalid - update student record'],
            ];
        }

        $daysDifference = abs($expectedDate->diffInDays($extractedDate));
        $confidence = $this->calculateDateConfidence($daysDifference);
        $similarity = max(0, 1 - ($daysDifference / 365)); // Normalize to 0-1 scale

        $suggestions = [];
        if ($daysDifference === 0) {
            $suggestions[] = 'Exact date match';
        } elseif ($daysDifference <= 1) {
            $suggestions[] = 'Date differs by 1 day - possible OCR error';
        } elseif ($daysDifference <= 30) {
            $suggestions[] = 'Date differs by less than a month - check for format confusion';
        } else {
            $suggestions[] = 'Significant date difference - verify document authenticity';
        }

        return [
            'match' => $daysDifference === 0,
            'expected' => $expected,
            'extracted' => $extracted,
            'confidence' => $confidence,
            'similarity_score' => $similarity,
            'mismatch_type' => $this->getDateMismatchType($daysDifference),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Analyze address mismatches
     */
    private function analyzeAddress(string $expected, string $extracted): array
    {
        $expected = $this->normalizeAddress($expected);
        $extracted = $this->normalizeAddress($extracted);

        if (empty($extracted)) {
            return [
                'match' => false,
                'expected' => $expected,
                'extracted' => $extracted,
                'confidence' => 0.0,
                'similarity_score' => 0.0,
                'mismatch_type' => 'missing_address',
                'suggestions' => ['Address not found in document'],
            ];
        }

        $similarity = $this->calculateAddressSimilarity($expected, $extracted);
        $confidence = $this->calculateAddressConfidence($expected, $extracted, $similarity);

        $suggestions = [];
        if ($similarity > 0.8) {
            $suggestions[] = 'Addresses are very similar - minor differences detected';
        } elseif ($similarity > 0.6) {
            $suggestions[] = 'Addresses have significant similarities - check for abbreviations';
        } else {
            $suggestions[] = 'Addresses are quite different - verify document authenticity';
        }

        return [
            'match' => $similarity > 0.9,
            'expected' => $expected,
            'extracted' => $extracted,
            'confidence' => $confidence,
            'similarity_score' => $similarity,
            'mismatch_type' => 'address_difference',
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Analyze Aadhaar number mismatches
     */
    private function analyzeAadhaarNumber(string $expected, string $extracted): array
    {
        $expected = preg_replace('/\D/', '', $expected); // Remove non-digits
        $extracted = preg_replace('/\D/', '', $extracted);

        if (empty($extracted)) {
            return [
                'match' => false,
                'expected' => $expected,
                'extracted' => $extracted,
                'confidence' => 0.0,
                'similarity_score' => 0.0,
                'mismatch_type' => 'missing_aadhaar',
                'suggestions' => ['Aadhaar number not found in document'],
            ];
        }

        $similarity = $this->calculateNumericSimilarity($expected, $extracted);
        $confidence = $similarity; // For Aadhaar, similarity directly maps to confidence

        $suggestions = [];
        if ($similarity === 1.0) {
            $suggestions[] = 'Exact Aadhaar number match';
        } elseif ($similarity > 0.9) {
            $suggestions[] = 'Minor differences in Aadhaar number - possible OCR error';
        } else {
            $suggestions[] = 'Significant Aadhaar number difference - verify document authenticity';
        }

        return [
            'match' => $similarity === 1.0,
            'expected' => $expected,
            'extracted' => $extracted,
            'confidence' => $confidence,
            'similarity_score' => $similarity,
            'mismatch_type' => 'aadhaar_difference',
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Analyze birth certificate number mismatches
     */
    private function analyzeBirthCertificateNumber(string $expected, string $extracted): array
    {
        $expected = trim(strtoupper($expected));
        $extracted = trim(strtoupper($extracted));

        if (empty($extracted)) {
            return [
                'match' => false,
                'expected' => $expected,
                'extracted' => $extracted,
                'confidence' => 0.0,
                'similarity_score' => 0.0,
                'mismatch_type' => 'missing_certificate_number',
                'suggestions' => ['Birth certificate number not found in document'],
            ];
        }

        $similarity = $this->calculateStringSimilarity($expected, $extracted);
        $confidence = $similarity;

        $suggestions = [];
        if ($similarity === 1.0) {
            $suggestions[] = 'Exact certificate number match';
        } elseif ($similarity > 0.8) {
            $suggestions[] = 'Minor differences in certificate number - possible OCR error';
        } else {
            $suggestions[] = 'Significant certificate number difference - verify document authenticity';
        }

        return [
            'match' => $similarity === 1.0,
            'expected' => $expected,
            'extracted' => $extracted,
            'confidence' => $confidence,
            'similarity_score' => $similarity,
            'mismatch_type' => 'certificate_number_difference',
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Generate resolution suggestions for a mismatch
     */
    private function generateResolution(string $field, array $analysis): ?array
    {
        if ($analysis['confidence'] >= self::AUTO_RESOLVE_THRESHOLD) {
            return [
                'field' => $field,
                'action' => 'auto_approve',
                'reason' => 'High confidence match despite minor differences',
                'confidence' => $analysis['confidence'],
            ];
        }

        if ($analysis['confidence'] >= self::MANUAL_REVIEW_THRESHOLD) {
            return [
                'field' => $field,
                'action' => 'manual_review',
                'reason' => 'Moderate confidence - requires human verification',
                'confidence' => $analysis['confidence'],
                'suggestions' => $analysis['suggestions'],
            ];
        }

        if ($analysis['confidence'] <= self::REJECT_THRESHOLD) {
            return [
                'field' => $field,
                'action' => 'auto_reject',
                'reason' => 'Low confidence - significant mismatch detected',
                'confidence' => $analysis['confidence'],
            ];
        }

        return [
            'field' => $field,
            'action' => 'manual_review',
            'reason' => 'Uncertain match - requires careful review',
            'confidence' => $analysis['confidence'],
            'suggestions' => $analysis['suggestions'],
        ];
    }

    /**
     * Get overall recommendation based on confidence and mismatches
     */
    private function getRecommendation(float $overallConfidence, array $mismatches): string
    {
        if (empty($mismatches)) {
            return 'approve';
        }

        if ($overallConfidence >= self::AUTO_RESOLVE_THRESHOLD) {
            return 'approve';
        }

        if ($overallConfidence <= self::REJECT_THRESHOLD) {
            return 'reject';
        }

        return 'manual_review';
    }

    /**
     * Check if mismatches can be auto-resolved
     */
    private function isAutoResolvable(array $mismatches, float $overallConfidence): bool
    {
        if (empty($mismatches)) {
            return true;
        }

        return $overallConfidence >= self::AUTO_RESOLVE_THRESHOLD;
    }

    /**
     * Check if manual review is required
     */
    private function requiresManualReview(float $overallConfidence): bool
    {
        return $overallConfidence >= self::MANUAL_REVIEW_THRESHOLD && 
               $overallConfidence < self::AUTO_RESOLVE_THRESHOLD;
    }

    /**
     * Apply automatic resolution to a verification
     */
    public function applyAutomaticResolution(StudentVerification $verification, array $resolutionData): bool
    {
        try {
            $recommendation = $resolutionData['recommendation'];
            
            if ($recommendation === 'approve' && $resolutionData['auto_resolvable']) {
                $verification->update([
                    'verification_status' => StudentVerification::STATUS_VERIFIED,
                    'confidence_score' => $resolutionData['overall_confidence'],
                    'resolution_method' => 'automatic',
                    'resolution_notes' => 'Automatically resolved based on high confidence match',
                    'resolved_at' => now(),
                    'resolved_by' => null, // System resolution
                ]);
                
                Log::info("Automatically approved verification {$verification->id} with confidence {$resolutionData['overall_confidence']}");
                return true;
            }

            if ($recommendation === 'reject' && $resolutionData['overall_confidence'] <= self::REJECT_THRESHOLD) {
                $verification->update([
                    'verification_status' => StudentVerification::STATUS_REJECTED,
                    'confidence_score' => $resolutionData['overall_confidence'],
                    'resolution_method' => 'automatic',
                    'resolution_notes' => 'Automatically rejected due to low confidence match',
                    'resolved_at' => now(),
                    'resolved_by' => null, // System resolution
                ]);
                
                Log::info("Automatically rejected verification {$verification->id} with confidence {$resolutionData['overall_confidence']}");
                return true;
            }

            // Set to manual review
            $verification->update([
                'verification_status' => StudentVerification::STATUS_MANUAL_REVIEW,
                'confidence_score' => $resolutionData['overall_confidence'],
                'resolution_method' => 'pending_manual',
                'resolution_notes' => 'Requires manual review due to moderate confidence',
            ]);

            return false; // Not auto-resolved
        } catch (\Exception $e) {
            Log::error("Failed to apply automatic resolution for verification {$verification->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize name for comparison
     */
    private function normalizeName(string $name): string
    {
        return trim(strtolower(preg_replace('/[^a-zA-Z\s]/', '', $name)));
    }

    /**
     * Normalize address for comparison
     */
    private function normalizeAddress(string $address): string
    {
        $address = strtolower(trim($address));
        
        // Common address abbreviations
        $abbreviations = [
            'street' => 'st',
            'road' => 'rd',
            'avenue' => 'ave',
            'boulevard' => 'blvd',
            'apartment' => 'apt',
            'building' => 'bldg',
        ];

        foreach ($abbreviations as $full => $abbr) {
            $address = str_replace([$full, $abbr], $abbr, $address);
        }

        return preg_replace('/\s+/', ' ', $address);
    }

    /**
     * Parse date from various formats
     */
    private function parseDate(string $date): ?\Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate string similarity using multiple algorithms
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        // Levenshtein distance
        $levenshtein = 1 - (levenshtein($str1, $str2) / max(strlen($str1), strlen($str2)));
        
        // Jaro-Winkler similarity (if available)
        $jaroWinkler = $levenshtein; // Fallback to Levenshtein
        
        // Soundex comparison for names
        $soundex = soundex($str1) === soundex($str2) ? 1.0 : 0.0;
        
        // Weighted average
        return ($levenshtein * 0.6) + ($jaroWinkler * 0.3) + ($soundex * 0.1);
    }

    /**
     * Calculate numeric similarity for numbers
     */
    private function calculateNumericSimilarity(string $num1, string $num2): float
    {
        if ($num1 === $num2) {
            return 1.0;
        }

        $len1 = strlen($num1);
        $len2 = strlen($num2);
        
        if ($len1 !== $len2) {
            return 0.0; // Different lengths for numbers usually mean different numbers
        }

        $matches = 0;
        for ($i = 0; $i < min($len1, $len2); $i++) {
            if ($num1[$i] === $num2[$i]) {
                $matches++;
            }
        }

        return $matches / max($len1, $len2);
    }

    /**
     * Calculate address similarity with special handling for common variations
     */
    private function calculateAddressSimilarity(string $addr1, string $addr2): float
    {
        $words1 = explode(' ', $addr1);
        $words2 = explode(' ', $addr2);
        
        $commonWords = array_intersect($words1, $words2);
        $totalWords = array_unique(array_merge($words1, $words2));
        
        return count($commonWords) / count($totalWords);
    }

    /**
     * Calculate confidence for name matches
     */
    private function calculateNameConfidence(string $expected, string $extracted, float $similarity): float
    {
        $confidence = $similarity;
        
        // Boost confidence for exact matches
        if ($expected === $extracted) {
            return 1.0;
        }
        
        // Boost confidence for common name patterns
        if ($this->hasCommonNameVariations($expected, $extracted)) {
            $confidence += 0.1;
        }
        
        return min($confidence, 1.0);
    }

    /**
     * Calculate confidence for date matches
     */
    private function calculateDateConfidence(int $daysDifference): float
    {
        if ($daysDifference === 0) {
            return 1.0;
        }
        
        if ($daysDifference <= 1) {
            return 0.95; // Very high confidence for 1-day difference
        }
        
        if ($daysDifference <= 7) {
            return 0.85; // High confidence for week difference
        }
        
        if ($daysDifference <= 30) {
            return 0.70; // Moderate confidence for month difference
        }
        
        return max(0.0, 1.0 - ($daysDifference / 365));
    }

    /**
     * Calculate confidence for address matches
     */
    private function calculateAddressConfidence(string $expected, string $extracted, float $similarity): float
    {
        return $similarity; // Direct mapping for now
    }

    /**
     * Check for common name variations
     */
    private function hasCommonNameVariations(string $name1, string $name2): bool
    {
        // Common name variations (this could be expanded with a database)
        $variations = [
            ['mohammed', 'mohammad', 'muhammad'],
            ['krishna', 'krish'],
            ['priya', 'priyanka'],
            // Add more common variations
        ];
        
        $name1 = strtolower($name1);
        $name2 = strtolower($name2);
        
        foreach ($variations as $group) {
            if (in_array($name1, $group) && in_array($name2, $group)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get name mismatch type
     */
    private function getNameMismatchType(string $expected, string $extracted, float $similarity): string
    {
        if ($similarity > 0.8) {
            return 'minor_variation';
        } elseif ($similarity > 0.5) {
            return 'partial_match';
        } else {
            return 'major_difference';
        }
    }

    /**
     * Get date mismatch type
     */
    private function getDateMismatchType(int $daysDifference): string
    {
        if ($daysDifference <= 1) {
            return 'minor_date_error';
        } elseif ($daysDifference <= 30) {
            return 'format_confusion';
        } else {
            return 'major_date_difference';
        }
    }
}