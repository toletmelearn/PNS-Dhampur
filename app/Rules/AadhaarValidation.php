<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AadhaarValidation implements ValidationRule
{
    private bool $requireChecksum;
    private bool $allowMasked;

    /**
     * Create a new rule instance.
     *
     * @param bool $requireChecksum Validate checksum digit
     * @param bool $allowMasked Allow masked Aadhaar numbers (XXXX-XXXX-1234)
     */
    public function __construct(bool $requireChecksum = true, bool $allowMasked = false)
    {
        $this->requireChecksum = $requireChecksum;
        $this->allowMasked = $allowMasked;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        // Clean the Aadhaar number
        $cleanAadhaar = $this->cleanAadhaarNumber($value);

        // Check if it's a masked Aadhaar
        if ($this->isMaskedAadhaar($value)) {
            if (!$this->allowMasked) {
                $fail("Masked Aadhaar numbers are not allowed for {$attribute}.");
                return;
            }
            
            // Validate masked format
            if (!$this->isValidMaskedFormat($value)) {
                $fail("The {$attribute} has invalid masked format.");
                return;
            }
            return;
        }

        // Basic format validation
        if (!$this->isValidFormat($cleanAadhaar)) {
            $fail("The {$attribute} must be a 12-digit number.");
            return;
        }

        // Check for invalid patterns
        if ($this->hasInvalidPatterns($cleanAadhaar)) {
            $fail("The {$attribute} contains invalid patterns.");
            return;
        }

        // Validate checksum if required
        if ($this->requireChecksum && !$this->isValidChecksum($cleanAadhaar)) {
            $fail("The {$attribute} has an invalid checksum.");
            return;
        }

        // Additional business validations
        $this->validateBusinessRules($cleanAadhaar, $fail);
    }

    /**
     * Clean Aadhaar number by removing spaces and hyphens.
     */
    private function cleanAadhaarNumber(string $aadhaar): string
    {
        return preg_replace('/[\s\-]/', '', $aadhaar);
    }

    /**
     * Check if Aadhaar is masked.
     */
    private function isMaskedAadhaar(string $aadhaar): bool
    {
        return strpos($aadhaar, 'X') !== false || strpos($aadhaar, '*') !== false;
    }

    /**
     * Validate masked Aadhaar format.
     */
    private function isValidMaskedFormat(string $aadhaar): bool
    {
        // Common masked formats:
        // XXXX-XXXX-1234
        // ****-****-1234
        // XXXXXXXX1234
        
        $patterns = [
            '/^[X\*]{4}[\s\-]?[X\*]{4}[\s\-]?\d{4}$/',
            '/^[X\*]{8}\d{4}$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $aadhaar)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate basic format (12 digits).
     */
    private function isValidFormat(string $aadhaar): bool
    {
        return preg_match('/^\d{12}$/', $aadhaar);
    }

    /**
     * Check for invalid patterns.
     */
    private function hasInvalidPatterns(string $aadhaar): bool
    {
        // Aadhaar cannot start with 0 or 1
        if (in_array(substr($aadhaar, 0, 1), ['0', '1'])) {
            return true;
        }

        // Check for all same digits
        if (preg_match('/^(\d)\1{11}$/', $aadhaar)) {
            return true;
        }

        // Check for sequential patterns
        if ($this->hasSequentialPattern($aadhaar)) {
            return true;
        }

        // Check for common invalid numbers
        $invalidNumbers = [
            '123456789012',
            '000000000000',
            '111111111111',
            '222222222222',
            '333333333333',
            '444444444444',
            '555555555555',
            '666666666666',
            '777777777777',
            '888888888888',
            '999999999999',
        ];

        return in_array($aadhaar, $invalidNumbers);
    }

    /**
     * Check for sequential patterns.
     */
    private function hasSequentialPattern(string $aadhaar): bool
    {
        // Check for ascending sequence
        for ($i = 0; $i < 9; $i++) {
            $sequence = '';
            for ($j = 0; $j < 4; $j++) {
                $sequence .= ($i + $j) % 10;
            }
            if (strpos($aadhaar, $sequence) !== false) {
                return true;
            }
        }

        // Check for descending sequence
        for ($i = 9; $i >= 3; $i--) {
            $sequence = '';
            for ($j = 0; $j < 4; $j++) {
                $sequence .= ($i - $j + 10) % 10;
            }
            if (strpos($aadhaar, $sequence) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate Aadhaar checksum using Verhoeff algorithm.
     */
    private function isValidChecksum(string $aadhaar): bool
    {
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
        $aadhaarArray = array_reverse(str_split($aadhaar));

        for ($i = 0; $i < count($aadhaarArray); $i++) {
            $checksum = $multiplicationTable[$checksum][$permutationTable[($i % 8)][(int)$aadhaarArray[$i]]];
        }

        return $checksum === 0;
    }

    /**
     * Additional business rule validations.
     */
    private function validateBusinessRules(string $aadhaar, Closure $fail): void
    {
        // Check if Aadhaar already exists in database (for uniqueness)
        if ($this->aadhaarExistsInDatabase($aadhaar)) {
            $fail("This Aadhaar number is already registered in the system.");
            return;
        }

        // Check against blacklisted Aadhaar numbers
        if ($this->isBlacklistedAadhaar($aadhaar)) {
            $fail("This Aadhaar number is not valid for registration.");
            return;
        }
    }

    /**
     * Check if Aadhaar exists in database.
     */
    private function aadhaarExistsInDatabase(string $aadhaar): bool
    {
        try {
            // Check in students table
            $existsInStudents = \DB::table('students')
                ->where('aadhaar_number', $aadhaar)
                ->exists();

            // Check in teachers table
            $existsInTeachers = \DB::table('teachers')
                ->where('aadhaar_number', $aadhaar)
                ->exists();

            // Check in other relevant tables as needed
            return $existsInStudents || $existsInTeachers;
        } catch (\Exception $e) {
            // If database check fails, allow the validation to pass
            return false;
        }
    }

    /**
     * Check if Aadhaar is blacklisted.
     */
    private function isBlacklistedAadhaar(string $aadhaar): bool
    {
        try {
            // Check against blacklisted Aadhaar numbers table
            return \DB::table('blacklisted_aadhaar')
                ->where('aadhaar_number', $aadhaar)
                ->exists();
        } catch (\Exception $e) {
            // If table doesn't exist or query fails, assume not blacklisted
            return false;
        }
    }

    /**
     * Static method for strict validation (with checksum).
     */
    public static function strict(): self
    {
        return new self(true, false);
    }

    /**
     * Static method for lenient validation (without checksum).
     */
    public static function lenient(): self
    {
        return new self(false, false);
    }

    /**
     * Static method allowing masked Aadhaar.
     */
    public static function allowMasked(): self
    {
        return new self(false, true);
    }

    /**
     * Static method for document verification (strict with masked allowed).
     */
    public static function forDocumentVerification(): self
    {
        return new self(true, true);
    }

    /**
     * Static method for data entry (lenient).
     */
    public static function forDataEntry(): self
    {
        return new self(false, false);
    }
}