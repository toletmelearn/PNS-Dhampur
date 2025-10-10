<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class AcademicYearValidation implements ValidationRule
{
    private bool $allowFuture;
    private bool $allowPast;
    private int $maxPastYears;
    private int $maxFutureYears;

    /**
     * Create a new rule instance.
     *
     * @param bool $allowFuture Allow future academic years
     * @param bool $allowPast Allow past academic years
     * @param int $maxPastYears Maximum years in the past allowed
     * @param int $maxFutureYears Maximum years in the future allowed
     */
    public function __construct(
        bool $allowFuture = true,
        bool $allowPast = true,
        int $maxPastYears = 10,
        int $maxFutureYears = 2
    ) {
        $this->allowFuture = $allowFuture;
        $this->allowPast = $allowPast;
        $this->maxPastYears = $maxPastYears;
        $this->maxFutureYears = $maxFutureYears;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        // Check format: YYYY-YYYY
        if (!preg_match('/^\d{4}-\d{4}$/', $value)) {
            $fail("The {$attribute} must be in format YYYY-YYYY (e.g., 2023-2024).");
            return;
        }

        $years = explode('-', $value);
        $startYear = (int) $years[0];
        $endYear = (int) $years[1];

        // Validate that end year is exactly one year after start year
        if ($endYear !== $startYear + 1) {
            $fail("The {$attribute} must represent consecutive years (e.g., 2023-2024).");
            return;
        }

        // Validate year range
        if ($startYear < 1900 || $startYear > 2100) {
            $fail("The {$attribute} contains invalid year range.");
            return;
        }

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        
        // Determine current academic year based on month
        // Assuming academic year starts in April (month 4)
        $currentAcademicStartYear = $currentMonth >= 4 ? $currentYear : $currentYear - 1;

        // Check if it's a past academic year
        if ($startYear < $currentAcademicStartYear) {
            if (!$this->allowPast) {
                $fail("Past academic years are not allowed.");
                return;
            }

            $yearsDifference = $currentAcademicStartYear - $startYear;
            if ($yearsDifference > $this->maxPastYears) {
                $fail("The {$attribute} cannot be more than {$this->maxPastYears} years in the past.");
                return;
            }
        }

        // Check if it's a future academic year
        if ($startYear > $currentAcademicStartYear) {
            if (!$this->allowFuture) {
                $fail("Future academic years are not allowed.");
                return;
            }

            $yearsDifference = $startYear - $currentAcademicStartYear;
            if ($yearsDifference > $this->maxFutureYears) {
                $fail("The {$attribute} cannot be more than {$this->maxFutureYears} years in the future.");
                return;
            }
        }

        // Additional business logic validations
        $this->validateBusinessLogic($startYear, $endYear, $fail);
    }

    /**
     * Validate business logic specific to academic years.
     */
    private function validateBusinessLogic(int $startYear, int $endYear, Closure $fail): void
    {
        // Check if the academic year already exists in the database
        // This would prevent duplicate academic year entries
        if ($this->academicYearExists($startYear, $endYear)) {
            $fail("The academic year {$startYear}-{$endYear} already exists in the system.");
            return;
        }

        // Validate against school establishment year
        $schoolEstablishmentYear = $this->getSchoolEstablishmentYear();
        if ($startYear < $schoolEstablishmentYear) {
            $fail("The academic year cannot be before the school establishment year ({$schoolEstablishmentYear}).");
            return;
        }

        // Check for reasonable academic year progression
        $this->validateAcademicYearProgression($startYear, $fail);
    }

    /**
     * Check if academic year already exists in database.
     */
    private function academicYearExists(int $startYear, int $endYear): bool
    {
        // This would typically check against a database table
        // For now, we'll return false as this is a placeholder
        try {
            // Example: Check if any records exist with this academic year
            // return \App\Models\AcademicYear::where('start_year', $startYear)
            //     ->where('end_year', $endYear)
            //     ->exists();
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get school establishment year from configuration.
     */
    private function getSchoolEstablishmentYear(): int
    {
        // This would typically come from configuration or database
        // For now, we'll use a reasonable default
        return config('school.establishment_year', 1990);
    }

    /**
     * Validate academic year progression logic.
     */
    private function validateAcademicYearProgression(int $startYear, Closure $fail): void
    {
        // Check if there are any gaps in academic years
        // This ensures continuity in academic records
        
        try {
            // Example logic to check for gaps
            // $lastAcademicYear = \App\Models\AcademicYear::orderBy('start_year', 'desc')->first();
            // if ($lastAcademicYear && $startYear > $lastAcademicYear->start_year + 1) {
            //     $fail("There is a gap in academic years. Please create academic year " . 
            //           ($lastAcademicYear->start_year + 1) . "-" . ($lastAcademicYear->start_year + 2) . " first.");
            // }
        } catch (\Exception $e) {
            // Silently handle database errors
        }
    }

    /**
     * Static method for current and future academic years only.
     */
    public static function currentAndFuture(): self
    {
        return new self(true, false, 0, 2);
    }

    /**
     * Static method for current and past academic years only.
     */
    public static function currentAndPast(): self
    {
        return new self(false, true, 10, 0);
    }

    /**
     * Static method for current academic year only.
     */
    public static function currentOnly(): self
    {
        return new self(false, false, 0, 0);
    }

    /**
     * Static method for admission purposes (current and next year).
     */
    public static function forAdmission(): self
    {
        return new self(true, false, 0, 1);
    }

    /**
     * Static method for historical data (past years only).
     */
    public static function forHistoricalData(): self
    {
        return new self(false, true, 20, 0);
    }

    /**
     * Static method for fee collection (current and past years).
     */
    public static function forFeeCollection(): self
    {
        return new self(false, true, 5, 0);
    }

    /**
     * Static method for attendance (current year with some flexibility).
     */
    public static function forAttendance(): self
    {
        return new self(true, true, 1, 1);
    }
}