<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IndianValidation implements ValidationRule
{
    private string $type;
    private array $options;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $type, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $value = (string) $value;

        switch ($this->type) {
            case 'pan':
                $this->validatePAN($value, $fail);
                break;
            case 'gst':
                $this->validateGST($value, $fail);
                break;
            case 'ifsc':
                $this->validateIFSC($value, $fail);
                break;
            case 'pincode':
                $this->validatePincode($value, $fail);
                break;
            case 'vehicle_number':
                $this->validateVehicleNumber($value, $fail);
                break;
            case 'indian_name':
                $this->validateIndianName($value, $fail);
                break;
            case 'indian_address':
                $this->validateIndianAddress($value, $fail);
                break;
            case 'udise_code':
                $this->validateUDISECode($value, $fail);
                break;
            case 'school_code':
                $this->validateSchoolCode($value, $fail);
                break;
            default:
                $fail("Unknown validation type: {$this->type}");
        }
    }

    /**
     * Validate PAN (Permanent Account Number).
     */
    private function validatePAN(string $value, Closure $fail): void
    {
        // PAN format: AAAAA9999A
        // First 5 characters: Alphabets
        // Next 4 characters: Numbers
        // Last character: Alphabet
        
        $value = strtoupper(trim($value));
        
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $value)) {
            $fail("The PAN number format is invalid. It should be in format AAAAA9999A.");
            return;
        }

        // Check for invalid PAN patterns
        $invalidPatterns = [
            '/^[A-Z]{5}0000[A-Z]$/', // All zeros in number part
            '/^AAAAA[0-9]{4}A$/',     // All same letters
            '/^[A-Z]{5}1111[A-Z]$/',  // All ones
            '/^[A-Z]{5}9999[A-Z]$/',  // All nines
        ];

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail("The PAN number appears to be invalid.");
                return;
            }
        }

        // Validate PAN structure based on entity type
        $fourthChar = $value[3];
        $fifthChar = $value[4];
        
        // Fourth character indicates entity type
        $validEntityTypes = ['P', 'C', 'H', 'F', 'A', 'T', 'B', 'L', 'J', 'G'];
        if (!in_array($fourthChar, $validEntityTypes)) {
            $fail("The PAN number has an invalid entity type indicator.");
            return;
        }

        // Fifth character should be the first letter of surname/entity name
        if (!ctype_alpha($fifthChar)) {
            $fail("The PAN number format is invalid.");
            return;
        }
    }

    /**
     * Validate GST (Goods and Services Tax) number.
     */
    private function validateGST(string $value, Closure $fail): void
    {
        // GST format: 99AAAAA9999A9Z9
        // First 2 digits: State code
        // Next 10 characters: PAN of the taxpayer
        // 13th character: Entity code
        // 14th character: Check digit
        // 15th character: Default 'Z'
        
        $value = strtoupper(trim($value));
        
        if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$/', $value)) {
            $fail("The GST number format is invalid.");
            return;
        }

        // Validate state code (first 2 digits)
        $stateCode = (int) substr($value, 0, 2);
        $validStateCodes = range(1, 37); // Indian state codes
        $validStateCodes[] = 97; // Other Territory
        $validStateCodes[] = 99; // Centre Jurisdiction
        
        if (!in_array($stateCode, $validStateCodes)) {
            $fail("The GST number has an invalid state code.");
            return;
        }

        // Extract and validate PAN part
        $panPart = substr($value, 2, 10);
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $panPart)) {
            $fail("The PAN part in GST number is invalid.");
            return;
        }

        // Validate check digit (simplified)
        if (!$this->validateGSTCheckDigit($value)) {
            $fail("The GST number check digit is invalid.");
            return;
        }
    }

    /**
     * Validate IFSC (Indian Financial System Code).
     */
    private function validateIFSC(string $value, Closure $fail): void
    {
        // IFSC format: AAAA0999999
        // First 4 characters: Bank code (alphabets)
        // 5th character: Always 0
        // Last 6 characters: Branch code (alphanumeric)
        
        $value = strtoupper(trim($value));
        
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $value)) {
            $fail("The IFSC code format is invalid. It should be in format AAAA0999999.");
            return;
        }

        // Check for known invalid IFSC patterns
        $bankCode = substr($value, 0, 4);
        $branchCode = substr($value, 5, 6);
        
        // Bank code should not be all same letters
        if (strlen(array_unique(str_split($bankCode))) === 1) {
            $fail("The IFSC code appears to be invalid.");
            return;
        }

        // Branch code should not be all zeros or all same characters
        if ($branchCode === '000000' || strlen(array_unique(str_split($branchCode))) === 1) {
            $fail("The IFSC code appears to be invalid.");
            return;
        }
    }

    /**
     * Validate Indian PIN code.
     */
    private function validatePincode(string $value, Closure $fail): void
    {
        $value = trim($value);
        
        if (!preg_match('/^[1-9][0-9]{5}$/', $value)) {
            $fail("The PIN code must be a 6-digit number and cannot start with 0.");
            return;
        }

        // Check for invalid PIN code patterns
        if (preg_match('/^(\d)\1{5}$/', $value)) { // All same digits
            $fail("The PIN code appears to be invalid.");
            return;
        }

        // Validate first digit (postal circle)
        $firstDigit = (int) $value[0];
        if ($firstDigit < 1 || $firstDigit > 9) {
            $fail("The PIN code has an invalid postal circle code.");
            return;
        }

        // Optional: Validate against specific state if provided
        if (isset($this->options['state'])) {
            $this->validatePincodeForState($value, $this->options['state'], $fail);
        }
    }

    /**
     * Validate Indian vehicle registration number.
     */
    private function validateVehicleNumber(string $value, Closure $fail): void
    {
        $value = strtoupper(trim($value));
        
        // Remove spaces and hyphens for validation
        $cleanValue = preg_replace('/[\s\-]/', '', $value);
        
        // New format: AA99AA9999 or Old format: AA999999
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z]{2}[0-9]{4}$/', $cleanValue) && 
            !preg_match('/^[A-Z]{2}[0-9]{6}$/', $cleanValue)) {
            $fail("The vehicle number format is invalid.");
            return;
        }

        // Validate state code (first 2 letters)
        $stateCode = substr($cleanValue, 0, 2);
        $validStateCodes = [
            'AP', 'AR', 'AS', 'BR', 'CG', 'GA', 'GJ', 'HR', 'HP', 'JK', 'JH',
            'KA', 'KL', 'MP', 'MH', 'MN', 'ML', 'MZ', 'NL', 'OD', 'PB', 'RJ',
            'SK', 'TN', 'TS', 'TR', 'UP', 'UK', 'WB', 'AN', 'CH', 'DN', 'DD',
            'DL', 'LD', 'PY'
        ];
        
        if (!in_array($stateCode, $validStateCodes)) {
            $fail("The vehicle number has an invalid state code.");
            return;
        }
    }

    /**
     * Validate Indian name.
     */
    private function validateIndianName(string $value, Closure $fail): void
    {
        $value = trim($value);
        
        // Allow letters, spaces, dots, apostrophes, and hyphens
        if (!preg_match("/^[a-zA-Z\s\.\'\-]+$/u", $value)) {
            $fail("The name can only contain letters, spaces, dots, apostrophes, and hyphens.");
            return;
        }

        // Check minimum and maximum length
        $minLength = $this->options['min_length'] ?? 2;
        $maxLength = $this->options['max_length'] ?? 50;
        
        if (strlen($value) < $minLength) {
            $fail("The name must be at least {$minLength} characters long.");
            return;
        }
        
        if (strlen($value) > $maxLength) {
            $fail("The name cannot be longer than {$maxLength} characters.");
            return;
        }

        // Check for invalid patterns
        if (preg_match('/^\s|\s$/', $value)) { // Leading or trailing spaces
            $fail("The name cannot start or end with spaces.");
            return;
        }

        if (preg_match('/\s{2,}/', $value)) { // Multiple consecutive spaces
            $fail("The name cannot contain multiple consecutive spaces.");
            return;
        }

        if (preg_match('/^[^a-zA-Z]/', $value)) { // Must start with letter
            $fail("The name must start with a letter.");
            return;
        }
    }

    /**
     * Validate Indian address.
     */
    private function validateIndianAddress(string $value, Closure $fail): void
    {
        $value = trim($value);
        
        // Allow letters, numbers, spaces, commas, dots, hyphens, and common symbols
        if (!preg_match("/^[a-zA-Z0-9\s\,\.\-\#\/\(\)]+$/u", $value)) {
            $fail("The address contains invalid characters.");
            return;
        }

        // Check length
        $minLength = $this->options['min_length'] ?? 10;
        $maxLength = $this->options['max_length'] ?? 200;
        
        if (strlen($value) < $minLength) {
            $fail("The address must be at least {$minLength} characters long.");
            return;
        }
        
        if (strlen($value) > $maxLength) {
            $fail("The address cannot be longer than {$maxLength} characters.");
            return;
        }

        // Check for meaningful content (not just numbers or symbols)
        if (!preg_match('/[a-zA-Z]/', $value)) {
            $fail("The address must contain at least some letters.");
            return;
        }
    }

    /**
     * Validate UDISE (Unified District Information System for Education) code.
     */
    private function validateUDISECode(string $value, Closure $fail): void
    {
        $value = trim($value);
        
        // UDISE code is typically 11 digits
        if (!preg_match('/^[0-9]{11}$/', $value)) {
            $fail("The UDISE code must be exactly 11 digits.");
            return;
        }

        // Check for invalid patterns
        if (preg_match('/^(\d)\1{10}$/', $value)) { // All same digits
            $fail("The UDISE code appears to be invalid.");
            return;
        }

        if ($value === '00000000000') {
            $fail("The UDISE code cannot be all zeros.");
            return;
        }
    }

    /**
     * Validate school code.
     */
    private function validateSchoolCode(string $value, Closure $fail): void
    {
        $value = strtoupper(trim($value));
        
        // School code format can vary, but typically alphanumeric
        if (!preg_match('/^[A-Z0-9]{4,15}$/', $value)) {
            $fail("The school code must be 4-15 characters long and contain only letters and numbers.");
            return;
        }

        // Check for invalid patterns
        if (preg_match('/^(\w)\1+$/', $value)) { // All same characters
            $fail("The school code appears to be invalid.");
            return;
        }
    }

    /**
     * Validate GST check digit (simplified implementation).
     */
    private function validateGSTCheckDigit(string $gstNumber): bool
    {
        // This is a simplified check. In production, you would implement
        // the full GST check digit algorithm
        return true;
    }

    /**
     * Validate PIN code for specific state.
     */
    private function validatePincodeForState(string $pincode, string $state, Closure $fail): void
    {
        $statePincodeRanges = [
            'AP' => ['515', '524', '530', '534'], // Andhra Pradesh
            'AR' => ['790', '792'], // Arunachal Pradesh
            'AS' => ['781', '788'], // Assam
            'BR' => ['800', '855'], // Bihar
            'CG' => ['490', '497'], // Chhattisgarh
            'DL' => ['110'], // Delhi
            'GA' => ['403'], // Goa
            'GJ' => ['360', '396'], // Gujarat
            'HR' => ['121', '136'], // Haryana
            'HP' => ['171', '177'], // Himachal Pradesh
            'JK' => ['180', '194'], // Jammu and Kashmir
            'JH' => ['814', '835'], // Jharkhand
            'KA' => ['560', '591'], // Karnataka
            'KL' => ['670', '695'], // Kerala
            'MP' => ['450', '488'], // Madhya Pradesh
            'MH' => ['400', '445'], // Maharashtra
            'MN' => ['795'], // Manipur
            'ML' => ['793'], // Meghalaya
            'MZ' => ['796'], // Mizoram
            'NL' => ['797'], // Nagaland
            'OD' => ['751', '770'], // Odisha
            'PB' => ['140', '160'], // Punjab
            'RJ' => ['301', '345'], // Rajasthan
            'SK' => ['737'], // Sikkim
            'TN' => ['600', '643'], // Tamil Nadu
            'TS' => ['500', '509'], // Telangana
            'TR' => ['799'], // Tripura
            'UP' => ['201', '285'], // Uttar Pradesh
            'UK' => ['246', '263'], // Uttarakhand
            'WB' => ['700', '743'], // West Bengal
        ];

        if (!isset($statePincodeRanges[$state])) {
            return; // Skip validation if state not in our list
        }

        $pincodePrefix = substr($pincode, 0, 3);
        $validPrefixes = $statePincodeRanges[$state];
        
        $isValid = false;
        foreach ($validPrefixes as $prefix) {
            if (strpos($pincodePrefix, $prefix) === 0) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail("The PIN code does not belong to the specified state.");
        }
    }

    /**
     * Static method for PAN validation.
     */
    public static function pan(): self
    {
        return new self('pan');
    }

    /**
     * Static method for GST validation.
     */
    public static function gst(): self
    {
        return new self('gst');
    }

    /**
     * Static method for IFSC validation.
     */
    public static function ifsc(): self
    {
        return new self('ifsc');
    }

    /**
     * Static method for PIN code validation.
     */
    public static function pincode(string $state = null): self
    {
        return new self('pincode', $state ? ['state' => $state] : []);
    }

    /**
     * Static method for vehicle number validation.
     */
    public static function vehicleNumber(): self
    {
        return new self('vehicle_number');
    }

    /**
     * Static method for Indian name validation.
     */
    public static function indianName(int $minLength = 2, int $maxLength = 50): self
    {
        return new self('indian_name', ['min_length' => $minLength, 'max_length' => $maxLength]);
    }

    /**
     * Static method for Indian address validation.
     */
    public static function indianAddress(int $minLength = 10, int $maxLength = 200): self
    {
        return new self('indian_address', ['min_length' => $minLength, 'max_length' => $maxLength]);
    }

    /**
     * Static method for UDISE code validation.
     */
    public static function udiseCode(): self
    {
        return new self('udise_code');
    }

    /**
     * Static method for school code validation.
     */
    public static function schoolCode(): self
    {
        return new self('school_code');
    }
}