<?php

namespace App\Http\Traits;

use Illuminate\Validation\Rule;

trait EmailValidationTrait
{
    /**
     * Get email validation rules for creating new records
     *
     * @param string $table The table name to check uniqueness against
     * @param string $column The column name for uniqueness check (default: 'email')
     * @return array
     */
    protected function getEmailValidationRules(string $table = 'users', string $column = 'email'): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                "unique:{$table},{$column}"
            ]
        ];
    }

    /**
     * Get email validation rules for updating existing records
     *
     * @param int|string $ignoreId The ID to ignore in uniqueness check
     * @param string $table The table name to check uniqueness against
     * @param string $column The column name for uniqueness check (default: 'email')
     * @return array
     */
    protected function getEmailValidationRulesForUpdate($ignoreId, string $table = 'users', string $column = 'email'): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique($table, $column)->ignore($ignoreId)
            ]
        ];
    }

    /**
     * Get optional email validation rules (for cases where email is not required)
     *
     * @param string $table The table name to check uniqueness against
     * @param string $column The column name for uniqueness check (default: 'email')
     * @return array
     */
    protected function getOptionalEmailValidationRules(string $table = 'users', string $column = 'email'): array
    {
        return [
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                "unique:{$table},{$column}"
            ]
        ];
    }

    /**
     * Get optional email validation rules for updates
     *
     * @param int|string $ignoreId The ID to ignore in uniqueness check
     * @param string $table The table name to check uniqueness against
     * @param string $column The column name for uniqueness check (default: 'email')
     * @return array
     */
    protected function getOptionalEmailValidationRulesForUpdate($ignoreId, string $table = 'users', string $column = 'email'): array
    {
        return [
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                Rule::unique($table, $column)->ignore($ignoreId)
            ]
        ];
    }

    /**
     * Get email validation error messages
     *
     * @return array
     */
    protected function getEmailValidationMessages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email address cannot exceed 255 characters.',
            'email.unique' => 'This email address is already registered.'
        ];
    }

    /**
     * Get simple email validation rules (without uniqueness check)
     *
     * @param bool $required Whether email is required
     * @return array
     */
    protected function getSimpleEmailValidationRules(bool $required = true): array
    {
        $rules = [
            'email:rfc',  // Removed DNS validation for testing
            'max:255'
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return ['email' => $rules];
    }
}