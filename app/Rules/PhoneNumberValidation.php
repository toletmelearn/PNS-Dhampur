<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberValidation implements ValidationRule
{
    private bool $allowMobile;
    private bool $allowLandline;
    private bool $requireCountryCode;
    private array $allowedCountryCodes;

    /**
     * Create a new rule instance.
     *
     * @param bool $allowMobile Allow mobile numbers
     * @param bool $allowLandline Allow landline numbers
     * @param bool $requireCountryCode Require country code
     * @param array $allowedCountryCodes Allowed country codes
     */
    public function __construct(
        bool $allowMobile = true,
        bool $allowLandline = true,
        bool $requireCountryCode = false,
        array $allowedCountryCodes = ['+91', '91']
    ) {
        $this->allowMobile = $allowMobile;
        $this->allowLandline = $allowLandline;
        $this->requireCountryCode = $requireCountryCode;
        $this->allowedCountryCodes = $allowedCountryCodes;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        // Clean the phone number
        $cleanNumber = $this->cleanPhoneNumber($value);

        // Check for invalid characters
        if (!preg_match('/^[\d\+\-\s\(\)]+$/', $value)) {
            $fail("The {$attribute} contains invalid characters.");
            return;
        }

        // Check minimum and maximum length
        if (strlen($cleanNumber) < 10 || strlen($cleanNumber) > 15) {
            $fail("The {$attribute} must be between 10 and 15 digits.");
            return;
        }

        // Validate country code if required
        if ($this->requireCountryCode && !$this->hasValidCountryCode($value)) {
            $fail("The {$attribute} must include a valid country code.");
            return;
        }

        // Remove country code for further validation
        $numberWithoutCountryCode = $this->removeCountryCode($cleanNumber);

        // Validate based on type
        $isValidMobile = $this->isValidMobileNumber($numberWithoutCountryCode);
        $isValidLandline = $this->isValidLandlineNumber($numberWithoutCountryCode);

        if (!$isValidMobile && !$isValidLandline) {
            $fail("The {$attribute} is not a valid phone number.");
            return;
        }

        if ($isValidMobile && !$this->allowMobile) {
            $fail("Mobile numbers are not allowed for {$attribute}.");
            return;
        }

        if ($isValidLandline && !$this->allowLandline) {
            $fail("Landline numbers are not allowed for {$attribute}.");
            return;
        }

        // Additional validations
        $this->validateAdditionalRules($cleanNumber, $fail);
    }

    /**
     * Clean phone number by removing spaces, hyphens, and brackets.
     */
    private function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[\s\-\(\)]/', '', $phone);
    }

    /**
     * Check if phone number has valid country code.
     */
    private function hasValidCountryCode(string $phone): bool
    {
        foreach ($this->allowedCountryCodes as $code) {
            if (str_starts_with($phone, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Remove country code from phone number.
     */
    private function removeCountryCode(string $phone): string
    {
        foreach ($this->allowedCountryCodes as $code) {
            if (str_starts_with($phone, $code)) {
                return substr($phone, strlen($code));
            }
        }
        return $phone;
    }

    /**
     * Validate Indian mobile number.
     */
    private function isValidMobileNumber(string $number): bool
    {
        // Indian mobile numbers: 10 digits starting with 6, 7, 8, or 9
        return preg_match('/^[6-9]\d{9}$/', $number);
    }

    /**
     * Validate Indian landline number.
     */
    private function isValidLandlineNumber(string $number): bool
    {
        // Indian landline numbers: 
        // - 11 digits: STD code (2-4 digits) + local number (6-8 digits)
        // - 10 digits: Some areas
        
        // 11 digit landline (STD code + number)
        if (preg_match('/^\d{11}$/', $number)) {
            // Check if it starts with valid STD codes
            $stdCode = substr($number, 0, 2);
            return in_array($stdCode, $this->getValidSTDCodes());
        }

        // 10 digit landline
        if (preg_match('/^\d{10}$/', $number)) {
            // Should not start with mobile prefixes
            $firstDigit = substr($number, 0, 1);
            return !in_array($firstDigit, ['6', '7', '8', '9']);
        }

        return false;
    }

    /**
     * Get valid Indian STD codes.
     */
    private function getValidSTDCodes(): array
    {
        return [
            '11', // Delhi
            '22', // Mumbai
            '33', // Kolkata
            '40', // Chennai
            '44', // Chennai
            '79', // Ahmedabad
            '80', // Bangalore
            '20', // Pune
            '124', // Gurgaon
            '120', // Noida
            // Add more STD codes as needed
        ];
    }

    /**
     * Additional validation rules.
     */
    private function validateAdditionalRules(string $number, Closure $fail): void
    {
        // Check for sequential numbers (e.g., 1234567890)
        if ($this->isSequentialNumber($number)) {
            $fail("The phone number appears to be invalid (sequential digits).");
            return;
        }

        // Check for repeated digits (e.g., 1111111111)
        if ($this->hasRepeatedDigits($number)) {
            $fail("The phone number appears to be invalid (repeated digits).");
            return;
        }

        // Check for common fake numbers
        if ($this->isCommonFakeNumber($number)) {
            $fail("The phone number appears to be invalid.");
            return;
        }
    }

    /**
     * Check if number has sequential digits.
     */
    private function isSequentialNumber(string $number): bool
    {
        $cleanNumber = preg_replace('/[^\d]/', '', $number);
        
        // Check ascending sequence
        for ($i = 0; $i < strlen($cleanNumber) - 3; $i++) {
            $sequence = substr($cleanNumber, $i, 4);
            if (preg_match('/^(\d)(\d)(\d)(\d)$/', $sequence, $matches)) {
                if ($matches[2] == $matches[1] + 1 && 
                    $matches[3] == $matches[2] + 1 && 
                    $matches[4] == $matches[3] + 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if number has too many repeated digits.
     */
    private function hasRepeatedDigits(string $number): bool
    {
        $cleanNumber = preg_replace('/[^\d]/', '', $number);
        
        // Check if more than 6 digits are the same
        $digitCounts = array_count_values(str_split($cleanNumber));
        foreach ($digitCounts as $count) {
            if ($count > 6) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for common fake numbers.
     */
    private function isCommonFakeNumber(string $number): bool
    {
        $cleanNumber = preg_replace('/[^\d]/', '', $number);
        
        $fakeNumbers = [
            '1234567890',
            '0123456789',
            '9876543210',
            '1111111111',
            '2222222222',
            '3333333333',
            '4444444444',
            '5555555555',
            '6666666666',
            '7777777777',
            '8888888888',
            '9999999999',
            '0000000000',
        ];

        return in_array($cleanNumber, $fakeNumbers);
    }

    /**
     * Static method for mobile numbers only.
     */
    public static function mobileOnly(): self
    {
        return new self(true, false);
    }

    /**
     * Static method for landline numbers only.
     */
    public static function landlineOnly(): self
    {
        return new self(false, true);
    }

    /**
     * Static method requiring country code.
     */
    public static function withCountryCode(): self
    {
        return new self(true, true, true);
    }

    /**
     * Static method for emergency contacts (more flexible).
     */
    public static function forEmergencyContact(): self
    {
        return new self(true, true, false, ['+91', '91', '+1', '1']);
    }

    /**
     * Static method for business contacts.
     */
    public static function forBusiness(): self
    {
        return new self(true, true, false);
    }
}