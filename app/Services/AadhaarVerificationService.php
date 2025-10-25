<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\ConnectionException;

class AadhaarVerificationService
{
    // Configuration
    const MOCK_API_ENABLED = false; // Disable mock to ensure tests use real (fakeable) HTTP
    const API_BASE_URL = 'https://aadhaar-api.gov.in';
    const API_TIMEOUT_SECONDS = 30;

    /**
     * Verify Aadhaar number with optional demographic matching
     */
    public function verifyAadhaar(string $aadhaarNumber, array $demographicData = []): array
    {
        if (!$this->isValidAadhaarFormat($aadhaarNumber)) {
            return [
                'success' => false,
                'error' => 'Invalid Aadhaar number format',
                'error_code' => 'INVALID_FORMAT',
                'http_status' => 422,
            ];
        }

        // Use mock only outside testing to allow Http::fake in tests
        if (self::MOCK_API_ENABLED && !app()->environment('testing')) {
            return $this->mockVerifyAadhaar($aadhaarNumber, $demographicData);
        }

        return $this->realVerifyAadhaar($aadhaarNumber, $demographicData);
    }

    /**
     * Real API call for Aadhaar verification
     */
    protected function realVerifyAadhaar(string $aadhaarNumber, array $demographicData = []): array
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT_SECONDS)
                ->post(self::API_BASE_URL . '/verify', [
                    'aadhaar_number' => $aadhaarNumber,
                    'demographic_data' => $demographicData
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => 'success',
                    'data' => $data['verified_data'] ?? $data['data'] ?? [],
                    'match_score' => $data['overall_match_score'] ?? $data['match_score'] ?? null,
                    'verification_id' => $data['verification_id'] ?? $data['reference_id'] ?? null
                ];
            }

            if ($response->status() === 503) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'error' => 'Aadhaar verification service is temporarily unavailable',
                    'http_status' => 503,
                    'error_code' => 'SERVICE_UNAVAILABLE'
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $response->json('error') ?? 'Verification failed',
                'error_code' => $response->json('error_code') ?? 'VERIFICATION_FAILED',
                'http_status' => $response->status()
            ];
        } catch (ConnectionException $e) {
            Log::warning('Aadhaar API connection timeout', [
                'error' => $e->getMessage(),
                'aadhaar_number' => $aadhaarNumber
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => 'Network timeout while connecting to Aadhaar service',
                'error_code' => 'NETWORK_TIMEOUT',
                'http_status' => 503
            ];
        } catch (\Exception $e) {
            Log::error('Aadhaar API error', [
                'error' => $e->getMessage(),
                'aadhaar_number' => $aadhaarNumber
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => 'Internal error during verification',
                'error_code' => 'INTERNAL_ERROR',
                'http_status' => 500
            ];
        }
    }

    /**
     * Simple mock verification used in non-testing environments
     */
    protected function mockVerifyAadhaar(string $aadhaarNumber, array $demographicData = []): array
    {
        $data = [
            'name' => $demographicData['name'] ?? 'Mock Name',
            'dob' => $demographicData['date_of_birth'] ?? $demographicData['dob'] ?? '2005-01-15',
            'gender' => $demographicData['gender'] ?? 'M',
            'address' => $demographicData['address'] ?? 'Mock Address'
        ];

        return [
            'success' => true,
            'status' => 'success',
            'verified_data' => $data,
            'overall_match_score' => 95,
            'reference_id' => 'MOCK_' . strtoupper(uniqid())
        ];
    }

    /**
     * Basic Aadhaar format validation
     */
    protected function isValidAadhaarFormat(string $aadhaarNumber): bool
    {
        return (bool) preg_match('/^\d{12}$/', preg_replace('/\s+/', '', $aadhaarNumber));
    }

    /**
     * Service availability (do not block validation)
     */
    public function isServiceAvailable(): bool
    {
        return true;
    }

    /**
     * Verification statistics from DB
     */
    public function getVerificationStats(): array
    {
        try {
            $total = DB::table('student_verifications')->where('verification_type', 'aadhaar')->count();
            $success = DB::table('student_verifications')->where('verification_type', 'aadhaar')->where('status', 'verified')->count();
            $failed = DB::table('student_verifications')->where('verification_type', 'aadhaar')->where('status', 'failed')->count();

            $avgScore = DB::table('student_verifications')
                ->where('verification_type', 'aadhaar')
                ->avg('match_score');

            // Last 7 days trend
            $trendsRaw = DB::table('student_verifications')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('verification_type', 'aadhaar')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->get();

            $trends = $trendsRaw->map(function ($row) {
                return ['date' => $row->date, 'count' => (int)$row->count];
            })->toArray();

            return [
                'total_verifications' => (int)$total,
                'successful_verifications' => (int)$success,
                'failed_verifications' => (int)$failed,
                'average_match_score' => $avgScore ? round((float)$avgScore, 2) : 0,
                'verification_trends' => $trends,
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to compute Aadhaar verification stats', [
                'error' => $e->getMessage(),
            ]);
            return [
                'total_verifications' => 0,
                'successful_verifications' => 0,
                'failed_verifications' => 0,
                'average_match_score' => 0,
                'verification_trends' => [],
            ];
        }
    }
}

