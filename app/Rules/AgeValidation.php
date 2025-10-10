<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class AgeValidation implements ValidationRule
{
    private int $minAge;
    private int $maxAge;
    private string $context;

    /**
     * Create a new rule instance.
     *
     * @param int $minAge Minimum age allowed
     * @param int $maxAge Maximum age allowed
     * @param string $context Context for error message (student, teacher, etc.)
     */
    public function __construct(int $minAge = 3, int $maxAge = 100, string $context = 'person')
    {
        $this->minAge = $minAge;
        $this->maxAge = $maxAge;
        $this->context = $context;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        try {
            $birthDate = Carbon::parse($value);
            $age = $birthDate->age;

            // Check if birth date is in the future
            if ($birthDate->isFuture()) {
                $fail("The {$attribute} cannot be in the future.");
                return;
            }

            // Check minimum age
            if ($age < $this->minAge) {
                $fail("The {$this->context} must be at least {$this->minAge} years old.");
                return;
            }

            // Check maximum age
            if ($age > $this->maxAge) {
                $fail("The {$this->context} cannot be more than {$this->maxAge} years old.");
                return;
            }

            // Additional context-specific validations
            $this->validateByContext($age, $fail);

        } catch (\Exception $e) {
            $fail("The {$attribute} must be a valid date.");
        }
    }

    /**
     * Validate based on specific context.
     */
    private function validateByContext(int $age, Closure $fail): void
    {
        switch ($this->context) {
            case 'student':
                // Students should typically be between 3-25 years
                if ($age < 3) {
                    $fail('Students must be at least 3 years old for admission.');
                } elseif ($age > 25) {
                    $fail('Students cannot be more than 25 years old for regular admission.');
                }
                break;

            case 'teacher':
                // Teachers should typically be between 21-65 years
                if ($age < 21) {
                    $fail('Teachers must be at least 21 years old.');
                } elseif ($age > 65) {
                    $fail('Teachers cannot be more than 65 years old for active employment.');
                }
                break;

            case 'parent':
                // Parents should typically be between 18-80 years
                if ($age < 18) {
                    $fail('Parents/guardians must be at least 18 years old.');
                } elseif ($age > 80) {
                    $fail('Please verify the parent/guardian age.');
                }
                break;

            case 'employee':
                // General employees should be between 18-65 years
                if ($age < 18) {
                    $fail('Employees must be at least 18 years old.');
                } elseif ($age > 65) {
                    $fail('Employees cannot be more than 65 years old for active employment.');
                }
                break;
        }
    }

    /**
     * Static method to create student age validation.
     */
    public static function forStudent(): self
    {
        return new self(3, 25, 'student');
    }

    /**
     * Static method to create teacher age validation.
     */
    public static function forTeacher(): self
    {
        return new self(21, 65, 'teacher');
    }

    /**
     * Static method to create parent age validation.
     */
    public static function forParent(): self
    {
        return new self(18, 80, 'parent');
    }

    /**
     * Static method to create employee age validation.
     */
    public static function forEmployee(): self
    {
        return new self(18, 65, 'employee');
    }
}