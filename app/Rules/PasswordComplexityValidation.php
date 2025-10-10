<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordComplexityValidation implements ValidationRule
{
    private int $minLength;
    private int $maxLength;
    private bool $requireUppercase;
    private bool $requireLowercase;
    private bool $requireNumbers;
    private bool $requireSpecialChars;
    private int $minSpecialChars;
    private bool $preventCommonPasswords;
    private bool $preventUserInfo;
    private array $userInfo;
    private bool $preventSequential;
    private bool $preventRepeating;
    private int $maxRepeatingChars;

    /**
     * Create a new rule instance.
     */
    public function __construct(
        int $minLength = 8,
        int $maxLength = 128,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true,
        int $minSpecialChars = 1,
        bool $preventCommonPasswords = true,
        bool $preventUserInfo = true,
        array $userInfo = [],
        bool $preventSequential = true,
        bool $preventRepeating = true,
        int $maxRepeatingChars = 3
    ) {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
        $this->minSpecialChars = $minSpecialChars;
        $this->preventCommonPasswords = $preventCommonPasswords;
        $this->preventUserInfo = $preventUserInfo;
        $this->userInfo = array_map('strtolower', $userInfo);
        $this->preventSequential = $preventSequential;
        $this->preventRepeating = $preventRepeating;
        $this->maxRepeatingChars = $maxRepeatingChars;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        $password = (string) $value;

        // Length validation
        if (strlen($password) < $this->minLength) {
            $fail("The {$attribute} must be at least {$this->minLength} characters long.");
            return;
        }

        if (strlen($password) > $this->maxLength) {
            $fail("The {$attribute} cannot be longer than {$this->maxLength} characters.");
            return;
        }

        // Character type requirements
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $fail("The {$attribute} must contain at least one uppercase letter.");
            return;
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $fail("The {$attribute} must contain at least one lowercase letter.");
            return;
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
            $fail("The {$attribute} must contain at least one number.");
            return;
        }

        if ($this->requireSpecialChars) {
            $specialCharCount = preg_match_all('/[^A-Za-z0-9]/', $password);
            if ($specialCharCount < $this->minSpecialChars) {
                $fail("The {$attribute} must contain at least {$this->minSpecialChars} special character(s).");
                return;
            }
        }

        // Common password validation
        if ($this->preventCommonPasswords && $this->isCommonPassword($password)) {
            $fail("The {$attribute} is too common. Please choose a more secure password.");
            return;
        }

        // User information validation
        if ($this->preventUserInfo && $this->containsUserInfo($password)) {
            $fail("The {$attribute} cannot contain personal information.");
            return;
        }

        // Sequential character validation
        if ($this->preventSequential && $this->hasSequentialChars($password)) {
            $fail("The {$attribute} cannot contain sequential characters (e.g., 123, abc).");
            return;
        }

        // Repeating character validation
        if ($this->preventRepeating && $this->hasRepeatingChars($password)) {
            $fail("The {$attribute} cannot contain more than {$this->maxRepeatingChars} consecutive identical characters.");
            return;
        }

        // Additional security validations
        $this->validateAdditionalSecurity($password, $fail);
    }

    /**
     * Check if password is commonly used.
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '123456', '123456789', 'qwerty',
            'abc123', 'password1', 'admin', 'letmein', 'welcome',
            'monkey', '1234567890', 'dragon', 'master', 'hello',
            'login', 'pass', 'admin123', 'root', 'user',
            'test', 'guest', 'info', 'administrator', 'secret',
            'god', 'love', 'sex', 'money', 'live', 'forever',
            'qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1qaz2wsx',
            'qazwsx', 'qweasd', 'asdqwe', 'zaqwsx', 'xswzaq',
            'cxzaqw', 'qwaszx', 'edcrfv', 'rfvtgb', 'nhyujm',
            'password@123', 'admin@123', 'welcome@123', 'test@123',
            'india@123', 'school@123', 'student@123', 'teacher@123'
        ];

        $lowerPassword = strtolower($password);
        
        // Check exact matches
        if (in_array($lowerPassword, $commonPasswords)) {
            return true;
        }

        // Check if password is just a common word with numbers/symbols
        foreach ($commonPasswords as $common) {
            if (strlen($common) >= 4 && strpos($lowerPassword, $common) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if password contains user information.
     */
    private function containsUserInfo(string $password): bool
    {
        if (empty($this->userInfo)) {
            return false;
        }

        $lowerPassword = strtolower($password);

        foreach ($this->userInfo as $info) {
            if (strlen($info) >= 3 && strpos($lowerPassword, $info) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for sequential characters.
     */
    private function hasSequentialChars(string $password): bool
    {
        $sequences = [
            // Numeric sequences
            '0123456789', '1234567890', '9876543210', '0987654321',
            // Alphabetic sequences
            'abcdefghijklmnopqrstuvwxyz', 'zyxwvutsrqponmlkjihgfedcba',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'ZYXWVUTSRQPONMLKJIHGFEDCBA',
            // Keyboard sequences
            'qwertyuiop', 'poiuytrewq', 'asdfghjkl', 'lkjhgfdsa',
            'zxcvbnm', 'mnbvcxz', '1qaz2wsx3edc', 'qweasdzxc'
        ];

        foreach ($sequences as $sequence) {
            for ($i = 0; $i <= strlen($sequence) - 4; $i++) {
                $subSequence = substr($sequence, $i, 4);
                if (stripos($password, $subSequence) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for repeating characters.
     */
    private function hasRepeatingChars(string $password): bool
    {
        $pattern = '/(.)\1{' . $this->maxRepeatingChars . ',}/';
        return preg_match($pattern, $password);
    }

    /**
     * Additional security validations.
     */
    private function validateAdditionalSecurity(string $password, Closure $fail): void
    {
        // Check for dictionary words (simplified check)
        if ($this->containsDictionaryWords($password)) {
            $fail("The password should not be based on dictionary words.");
            return;
        }

        // Check password entropy (complexity score)
        if ($this->calculatePasswordEntropy($password) < 50) {
            $fail("The password is not complex enough. Please use a mix of different character types.");
            return;
        }

        // Check for keyboard patterns
        if ($this->hasKeyboardPatterns($password)) {
            $fail("The password cannot contain keyboard patterns.");
            return;
        }
    }

    /**
     * Check for dictionary words.
     */
    private function containsDictionaryWords(string $password): bool
    {
        $commonWords = [
            'school', 'student', 'teacher', 'education', 'learning',
            'knowledge', 'study', 'class', 'exam', 'test', 'grade',
            'subject', 'math', 'science', 'english', 'hindi',
            'computer', 'internet', 'mobile', 'phone', 'email',
            'india', 'delhi', 'mumbai', 'bangalore', 'chennai',
            'family', 'mother', 'father', 'brother', 'sister'
        ];

        $lowerPassword = strtolower($password);
        
        foreach ($commonWords as $word) {
            if (strlen($word) >= 4 && strpos($lowerPassword, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate password entropy (complexity score).
     */
    private function calculatePasswordEntropy(string $password): float
    {
        $charsetSize = 0;
        
        if (preg_match('/[a-z]/', $password)) $charsetSize += 26;
        if (preg_match('/[A-Z]/', $password)) $charsetSize += 26;
        if (preg_match('/[0-9]/', $password)) $charsetSize += 10;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $charsetSize += 32;

        return strlen($password) * log($charsetSize, 2);
    }

    /**
     * Check for keyboard patterns.
     */
    private function hasKeyboardPatterns(string $password): bool
    {
        $keyboardPatterns = [
            'qwer', 'wert', 'erty', 'rtyu', 'tyui', 'yuio', 'uiop',
            'asdf', 'sdfg', 'dfgh', 'fghj', 'ghjk', 'hjkl',
            'zxcv', 'xcvb', 'cvbn', 'vbnm',
            '1234', '2345', '3456', '4567', '5678', '6789', '7890'
        ];

        $lowerPassword = strtolower($password);
        
        foreach ($keyboardPatterns as $pattern) {
            if (strpos($lowerPassword, $pattern) !== false || 
                strpos($lowerPassword, strrev($pattern)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Static method for basic password requirements.
     */
    public static function basic(): self
    {
        return new self(8, 128, true, true, true, false);
    }

    /**
     * Static method for medium security requirements.
     */
    public static function medium(): self
    {
        return new self(10, 128, true, true, true, true, 1);
    }

    /**
     * Static method for high security requirements.
     */
    public static function high(): self
    {
        return new self(12, 128, true, true, true, true, 2, true, true, [], true, true, 2);
    }

    /**
     * Static method for admin passwords.
     */
    public static function forAdmin(): self
    {
        return new self(14, 128, true, true, true, true, 3, true, true, [], true, true, 2);
    }

    /**
     * Static method for teacher passwords.
     */
    public static function forTeacher(): self
    {
        return new self(10, 128, true, true, true, true, 1, true, true, [], true, true, 3);
    }

    /**
     * Static method for student passwords.
     */
    public static function forStudent(): self
    {
        return new self(8, 128, true, true, true, false, 0, true, true, [], false, true, 4);
    }

    /**
     * Static method with user information.
     */
    public static function withUserInfo(array $userInfo): self
    {
        return new self(8, 128, true, true, true, true, 1, true, true, $userInfo);
    }
}