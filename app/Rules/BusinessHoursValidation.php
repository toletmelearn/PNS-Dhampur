<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class BusinessHoursValidation implements ValidationRule
{
    private string $startTime;
    private string $endTime;
    private array $workingDays;
    private array $holidays;
    private bool $allowWeekends;
    private bool $strictMode;
    private string $timezone;
    private array $breakTimes;
    private bool $allowBreakTimes;

    /**
     * Create a new rule instance.
     */
    public function __construct(
        string $startTime = '08:00',
        string $endTime = '17:00',
        array $workingDays = [1, 2, 3, 4, 5, 6], // Monday to Saturday
        array $holidays = [],
        bool $allowWeekends = false,
        bool $strictMode = false,
        string $timezone = 'Asia/Kolkata',
        array $breakTimes = [],
        bool $allowBreakTimes = false
    ) {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->workingDays = $workingDays;
        $this->holidays = $holidays;
        $this->allowWeekends = $allowWeekends;
        $this->strictMode = $strictMode;
        $this->timezone = $timezone;
        $this->breakTimes = $breakTimes;
        $this->allowBreakTimes = $allowBreakTimes;
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
            $dateTime = Carbon::parse($value, $this->timezone);
        } catch (\Exception $e) {
            $fail("The {$attribute} must be a valid date and time.");
            return;
        }

        // Check if it's a working day
        if (!$this->isWorkingDay($dateTime)) {
            $fail("The {$attribute} must be on a working day.");
            return;
        }

        // Check if it's within business hours
        if (!$this->isWithinBusinessHours($dateTime)) {
            $fail("The {$attribute} must be within business hours ({$this->startTime} - {$this->endTime}).");
            return;
        }

        // Check if it's during break time (if not allowed)
        if (!$this->allowBreakTimes && $this->isDuringBreakTime($dateTime)) {
            $fail("The {$attribute} cannot be during break time.");
            return;
        }

        // Check if it's a holiday
        if ($this->isHoliday($dateTime)) {
            $fail("The {$attribute} cannot be on a holiday.");
            return;
        }

        // Strict mode additional validations
        if ($this->strictMode) {
            $this->validateStrictMode($dateTime, $fail);
        }
    }

    /**
     * Check if the date is a working day.
     */
    private function isWorkingDay(Carbon $dateTime): bool
    {
        $dayOfWeek = $dateTime->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        
        // If weekends are not allowed and it's weekend
        if (!$this->allowWeekends && in_array($dayOfWeek, [0, 7])) { // Sunday
            return false;
        }

        return in_array($dayOfWeek, $this->workingDays);
    }

    /**
     * Check if the time is within business hours.
     */
    private function isWithinBusinessHours(Carbon $dateTime): bool
    {
        $time = $dateTime->format('H:i');
        return $time >= $this->startTime && $time <= $this->endTime;
    }

    /**
     * Check if the time is during break time.
     */
    private function isDuringBreakTime(Carbon $dateTime): bool
    {
        if (empty($this->breakTimes)) {
            return false;
        }

        $time = $dateTime->format('H:i');
        
        foreach ($this->breakTimes as $breakTime) {
            if (isset($breakTime['start']) && isset($breakTime['end'])) {
                if ($time >= $breakTime['start'] && $time <= $breakTime['end']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the date is a holiday.
     */
    private function isHoliday(Carbon $dateTime): bool
    {
        $date = $dateTime->format('Y-m-d');
        return in_array($date, $this->holidays);
    }

    /**
     * Validate strict mode requirements.
     */
    private function validateStrictMode(Carbon $dateTime, Closure $fail): void
    {
        // Check if it's too far in the future
        if ($dateTime->isFuture() && $dateTime->diffInDays(now()) > 365) {
            $fail("The date cannot be more than 1 year in the future.");
            return;
        }

        // Check if it's too far in the past
        if ($dateTime->isPast() && $dateTime->diffInDays(now()) > 1095) { // 3 years
            $fail("The date cannot be more than 3 years in the past.");
            return;
        }

        // Check for minimum advance notice (for future dates)
        if ($dateTime->isFuture() && $dateTime->diffInHours(now()) < 24) {
            $fail("The date must be at least 24 hours in advance.");
            return;
        }
    }

    /**
     * Static method for school hours.
     */
    public static function forSchoolHours(): self
    {
        return new self(
            '08:00',
            '16:00',
            [1, 2, 3, 4, 5, 6], // Monday to Saturday
            [],
            false,
            false,
            'Asia/Kolkata',
            [
                ['start' => '12:00', 'end' => '13:00'] // Lunch break
            ],
            false
        );
    }

    /**
     * Static method for office hours.
     */
    public static function forOfficeHours(): self
    {
        return new self(
            '09:00',
            '18:00',
            [1, 2, 3, 4, 5], // Monday to Friday
            [],
            false,
            true,
            'Asia/Kolkata',
            [
                ['start' => '13:00', 'end' => '14:00'] // Lunch break
            ],
            false
        );
    }

    /**
     * Static method for exam scheduling.
     */
    public static function forExamScheduling(): self
    {
        return new self(
            '09:00',
            '17:00',
            [1, 2, 3, 4, 5, 6], // Monday to Saturday
            [],
            false,
            true,
            'Asia/Kolkata',
            [
                ['start' => '12:30', 'end' => '13:30'] // Lunch break
            ],
            false
        );
    }

    /**
     * Static method for attendance marking.
     */
    public static function forAttendance(): self
    {
        return new self(
            '07:30',
            '16:30',
            [1, 2, 3, 4, 5, 6], // Monday to Saturday
            [],
            false,
            false,
            'Asia/Kolkata',
            [],
            true // Allow break times for attendance
        );
    }

    /**
     * Static method for parent meetings.
     */
    public static function forParentMeetings(): self
    {
        return new self(
            '10:00',
            '16:00',
            [1, 2, 3, 4, 5, 6], // Monday to Saturday
            [],
            true, // Allow weekends for parent meetings
            true,
            'Asia/Kolkata',
            [
                ['start' => '12:00', 'end' => '13:00'] // Lunch break
            ],
            false
        );
    }

    /**
     * Static method for fee payment.
     */
    public static function forFeePayment(): self
    {
        return new self(
            '09:00',
            '15:00',
            [1, 2, 3, 4, 5, 6], // Monday to Saturday
            [],
            false,
            false,
            'Asia/Kolkata',
            [
                ['start' => '12:00', 'end' => '13:00'] // Lunch break
            ],
            false
        );
    }

    /**
     * Static method with custom holidays.
     */
    public static function withHolidays(array $holidays): self
    {
        return new self(
            '08:00',
            '16:00',
            [1, 2, 3, 4, 5, 6],
            $holidays,
            false,
            false,
            'Asia/Kolkata'
        );
    }

    /**
     * Static method for flexible hours.
     */
    public static function flexible(string $startTime, string $endTime, array $workingDays = [1, 2, 3, 4, 5, 6]): self
    {
        return new self(
            $startTime,
            $endTime,
            $workingDays,
            [],
            false,
            false,
            'Asia/Kolkata'
        );
    }

    /**
     * Get Indian national holidays for a given year.
     */
    public static function getIndianHolidays(int $year = null): array
    {
        $year = $year ?: date('Y');
        
        // Common Indian holidays (dates may vary each year)
        return [
            "{$year}-01-26", // Republic Day
            "{$year}-08-15", // Independence Day
            "{$year}-10-02", // Gandhi Jayanti
            // Add more holidays as needed
            // Note: Festival dates change each year, so you might want to
            // fetch these from a reliable source or maintain them in database
        ];
    }

    /**
     * Get school-specific holidays.
     */
    public static function getSchoolHolidays(int $year = null): array
    {
        $year = $year ?: date('Y');
        
        $holidays = self::getIndianHolidays($year);
        
        // Add school-specific holidays
        $schoolHolidays = [
            // Summer vacation (example dates)
            ...self::getDateRange("{$year}-05-15", "{$year}-06-15"),
            // Winter vacation (example dates)
            ...self::getDateRange("{$year}-12-25", "{$year}-01-05"),
        ];

        return array_merge($holidays, $schoolHolidays);
    }

    /**
     * Get date range as array.
     */
    private static function getDateRange(string $startDate, string $endDate): array
    {
        $dates = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($start->lte($end)) {
            $dates[] = $start->format('Y-m-d');
            $start->addDay();
        }

        return $dates;
    }
}