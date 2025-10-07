<?php

namespace App\Http\Traits;

trait DateRangeValidationTrait
{
    /**
     * Get basic date range validation rules
     *
     * @param bool $startRequired Whether start_date is required
     * @param bool $endRequired Whether end_date is required
     * @return array
     */
    protected function getDateRangeValidationRules(bool $startRequired = true, bool $endRequired = true): array
    {
        $rules = [];

        // Start date rules
        $startRules = ['date'];
        if ($startRequired) {
            array_unshift($startRules, 'required');
        } else {
            array_unshift($startRules, 'nullable');
        }
        $rules['start_date'] = $startRules;

        // End date rules
        $endRules = ['date', 'after_or_equal:start_date'];
        if ($endRequired) {
            array_unshift($endRules, 'required');
        } else {
            array_unshift($endRules, 'nullable');
        }
        $rules['end_date'] = $endRules;

        return $rules;
    }

    /**
     * Get date range validation rules with future date constraints
     *
     * @param bool $startRequired Whether start_date is required
     * @param bool $endRequired Whether end_date is required
     * @param bool $allowPastDates Whether to allow past dates
     * @return array
     */
    protected function getFutureDateRangeValidationRules(bool $startRequired = true, bool $endRequired = true, bool $allowPastDates = false): array
    {
        $rules = [];

        // Start date rules
        $startRules = ['date'];
        if (!$allowPastDates) {
            $startRules[] = 'after_or_equal:today';
        }
        if ($startRequired) {
            array_unshift($startRules, 'required');
        } else {
            array_unshift($startRules, 'nullable');
        }
        $rules['start_date'] = $startRules;

        // End date rules
        $endRules = ['date', 'after:start_date'];
        if (!$allowPastDates) {
            $endRules[] = 'after_or_equal:today';
        }
        if ($endRequired) {
            array_unshift($endRules, 'required');
        } else {
            array_unshift($endRules, 'nullable');
        }
        $rules['end_date'] = $endRules;

        return $rules;
    }

    /**
     * Get date range validation rules for filtering/reporting
     *
     * @return array
     */
    protected function getFilterDateRangeValidationRules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from']
        ];
    }

    /**
     * Get academic year date range validation rules
     *
     * @return array
     */
    protected function getAcademicYearDateRangeValidationRules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date']
        ];
    }

    /**
     * Get experience date range validation rules
     *
     * @return array
     */
    protected function getExperienceDateRangeValidationRules(): array
    {
        return [
            'experience.*.start_date' => ['required_with:experience', 'date', 'before:today'],
            'experience.*.end_date' => ['nullable', 'date', 'after:experience.*.start_date', 'before_or_equal:today']
        ];
    }

    /**
     * Get availability date range validation rules
     *
     * @return array
     */
    protected function getAvailabilityDateRangeValidationRules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date']
        ];
    }

    /**
     * Get date range validation messages
     *
     * @return array
     */
    protected function getDateRangeValidationMessages(): array
    {
        return [
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'start_date.before' => 'Start date must be before today.',
            
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after' => 'End date must be after the start date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'end_date.before_or_equal' => 'End date must be today or before.',
            
            'date_from.date' => 'From date must be a valid date.',
            'date_to.date' => 'To date must be a valid date.',
            'date_to.after_or_equal' => 'To date must be on or after the from date.',
            
            'experience.*.start_date.required_with' => 'Start date is required for experience entry.',
            'experience.*.start_date.date' => 'Experience start date must be a valid date.',
            'experience.*.start_date.before' => 'Experience start date must be before today.',
            'experience.*.end_date.date' => 'Experience end date must be a valid date.',
            'experience.*.end_date.after' => 'Experience end date must be after the start date.',
            'experience.*.end_date.before_or_equal' => 'Experience end date must be today or before.'
        ];
    }

    /**
     * Get date validation rules for single date fields
     *
     * @param string $fieldName The field name
     * @param bool $required Whether the field is required
     * @param bool $futureOnly Whether to allow only future dates
     * @param bool $pastOnly Whether to allow only past dates
     * @return array
     */
    protected function getSingleDateValidationRules(string $fieldName, bool $required = true, bool $futureOnly = false, bool $pastOnly = false): array
    {
        $rules = ['date'];

        if ($futureOnly) {
            $rules[] = 'after_or_equal:today';
        } elseif ($pastOnly) {
            $rules[] = 'before_or_equal:today';
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return [$fieldName => $rules];
    }

    /**
     * Get date of birth validation rules
     *
     * @param int $minAge Minimum age in years
     * @param int $maxAge Maximum age in years
     * @return array
     */
    protected function getDateOfBirthValidationRules(int $minAge = 3, int $maxAge = 100): array
    {
        $maxDate = now()->subYears($minAge)->format('Y-m-d');
        $minDate = now()->subYears($maxAge)->format('Y-m-d');

        return [
            'date_of_birth' => [
                'required',
                'date',
                "before_or_equal:{$maxDate}",
                "after_or_equal:{$minDate}"
            ]
        ];
    }

    /**
     * Validate date range parameters from request
     *
     * @param \Illuminate\Http\Request $request
     * @param array $rules Optional custom validation rules
     * @return array Validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateDateRange($request, array $rules = []): array
    {
        $defaultRules = $this->getFilterDateRangeValidationRules();
        $validationRules = array_merge($defaultRules, $rules);
        
        return $request->validate($validationRules, $this->getDateRangeValidationMessages());
    }

    /**
     * Check if two date ranges overlap
     *
     * @param string $start1 First range start date
     * @param string $end1 First range end date
     * @param string $start2 Second range start date
     * @param string $end2 Second range end date
     * @return bool True if ranges overlap
     */
    protected function datesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1Timestamp = strtotime($start1);
        $end1Timestamp = strtotime($end1);
        $start2Timestamp = strtotime($start2);
        $end2Timestamp = strtotime($end2);

        return ($start1Timestamp <= $end2Timestamp) && ($end1Timestamp >= $start2Timestamp);
    }

    /**
     * Get date range rules for filtering (alias for backward compatibility)
     *
     * @return array
     */
    protected function getDateRangeRules(): array
    {
        return $this->getFilterDateRangeValidationRules();
    }
}