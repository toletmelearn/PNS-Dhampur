<?php

namespace App\Http\Traits;

use App\Rules\PasswordComplexity;

trait UserValidationTrait
{
    use EmailValidationTrait, CommonValidationTrait;

    /**
     * Get user creation validation rules
     *
     * @return array
     */
    protected function getUserCreationRules(): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getCreateEmailValidationRules(),
            [
                'password' => ['required', 'string', 'min:8', new PasswordComplexity()],
                'role' => ['required', 'string', 'exists:roles,name'],
                'username' => ['nullable', 'string', 'max:50', 'unique:users,username', 'alpha_dash'],
                'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'designation' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'gender' => ['nullable', 'string', 'in:male,female,other'],
                'address' => ['nullable', 'string', 'max:1000'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'country' => ['nullable', 'string', 'max:100'],
                'emergency_contact' => ['nullable', 'string', 'max:20'],
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
                'joining_date' => ['nullable', 'date', 'before_or_equal:today'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'is_active' => ['nullable', 'boolean'],
                'can_login' => ['nullable', 'boolean'],
                'email_verified_at' => ['nullable', 'date'],
                'two_factor_enabled' => ['nullable', 'boolean'],
                'timezone' => ['nullable', 'string', 'max:50'],
                'language' => ['nullable', 'string', 'max:10'],
                'theme' => ['nullable', 'string', 'in:light,dark,auto'],
            ]
        );
    }

    /**
     * Get user update validation rules
     *
     * @param int $userId User ID for unique validation
     * @return array
     */
    protected function getUserUpdateRules(int $userId): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getUpdateEmailValidationRules('users', $userId),
            [
                'password' => ['nullable', 'string', 'min:8', new PasswordComplexity()],
                'role' => ['required', 'string', 'exists:roles,name'],
                'username' => ['nullable', 'string', 'max:50', "unique:users,username,{$userId}", 'alpha_dash'],
                'employee_id' => ['nullable', 'string', 'max:50', "unique:users,employee_id,{$userId}"],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'designation' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'gender' => ['nullable', 'string', 'in:male,female,other'],
                'address' => ['nullable', 'string', 'max:1000'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'country' => ['nullable', 'string', 'max:100'],
                'emergency_contact' => ['nullable', 'string', 'max:20'],
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
                'joining_date' => ['nullable', 'date', 'before_or_equal:today'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'is_active' => ['nullable', 'boolean'],
                'can_login' => ['nullable', 'boolean'],
                'email_verified_at' => ['nullable', 'date'],
                'two_factor_enabled' => ['nullable', 'boolean'],
                'timezone' => ['nullable', 'string', 'max:50'],
                'language' => ['nullable', 'string', 'max:10'],
                'theme' => ['nullable', 'string', 'in:light,dark,auto'],
            ]
        );
    }

    /**
     * Get user profile update validation rules
     *
     * @param int $userId User ID for unique validation
     * @return array
     */
    protected function getUserProfileUpdateRules(int $userId): array
    {
        return array_merge(
            $this->getNameValidationRules(true, 255),
            $this->getUpdateEmailValidationRules('users', $userId),
            [
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s\(\)]+$/'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'gender' => ['nullable', 'string', 'in:male,female,other'],
                'address' => ['nullable', 'string', 'max:1000'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'country' => ['nullable', 'string', 'max:100'],
                'emergency_contact' => ['nullable', 'string', 'max:20'],
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
                'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
                'timezone' => ['nullable', 'string', 'max:50'],
                'language' => ['nullable', 'string', 'max:10'],
                'theme' => ['nullable', 'string', 'in:light,dark,auto'],
                'bio' => ['nullable', 'string', 'max:1000'],
                'website' => ['nullable', 'url', 'max:255'],
                'linkedin' => ['nullable', 'url', 'max:255'],
                'twitter' => ['nullable', 'url', 'max:255'],
                'facebook' => ['nullable', 'url', 'max:255'],
            ]
        );
    }

    /**
     * Get password change validation rules
     *
     * @param bool $requireCurrentPassword Whether current password is required
     * @return array
     */
    protected function getPasswordChangeRules(bool $requireCurrentPassword = true): array
    {
        $rules = [
            'password' => ['required', 'string', 'min:8', new PasswordComplexity(), 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ];

        if ($requireCurrentPassword) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        return $rules;
    }

    /**
     * Get user role assignment validation rules
     *
     * @return array
     */
    protected function getUserRoleRules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:effective_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get user permission validation rules
     *
     * @return array
     */
    protected function getUserPermissionRules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'action' => ['required', 'string', 'in:grant,revoke'],
            'reason' => ['nullable', 'string', 'max:500'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:effective_date'],
        ];
    }

    /**
     * Get user session validation rules
     *
     * @return array
     */
    protected function getUserSessionRules(): array
    {
        return [
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'in:desktop,mobile,tablet'],
            'browser' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'location' => ['nullable', 'string', 'max:255'],
            'remember_me' => ['nullable', 'boolean'],
            'session_timeout' => ['nullable', 'integer', 'min:5', 'max:1440'], // 5 minutes to 24 hours
        ];
    }

    /**
     * Get user notification preferences validation rules
     *
     * @return array
     */
    protected function getUserNotificationRules(): array
    {
        return [
            'email_notifications' => ['nullable', 'boolean'],
            'sms_notifications' => ['nullable', 'boolean'],
            'push_notifications' => ['nullable', 'boolean'],
            'notification_types' => ['nullable', 'array'],
            'notification_types.*' => ['string', 'in:system,security,updates,reminders,announcements'],
            'notification_frequency' => ['nullable', 'string', 'in:immediate,daily,weekly,monthly'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
            'weekend_notifications' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get user security settings validation rules
     *
     * @return array
     */
    protected function getUserSecurityRules(): array
    {
        return [
            'two_factor_enabled' => ['nullable', 'boolean'],
            'two_factor_method' => ['nullable', 'string', 'in:sms,email,app'],
            'backup_codes' => ['nullable', 'array'],
            'backup_codes.*' => ['string', 'size:8'],
            'login_alerts' => ['nullable', 'boolean'],
            'suspicious_activity_alerts' => ['nullable', 'boolean'],
            'password_expiry_days' => ['nullable', 'integer', 'min:30', 'max:365'],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'max_concurrent_sessions' => ['nullable', 'integer', 'min:1', 'max:10'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['ip'],
        ];
    }

    /**
     * Get user bulk operation validation rules
     *
     * @return array
     */
    protected function getUserBulkRules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'string', 'in:activate,deactivate,delete,assign_role,remove_role,reset_password,force_logout'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'send_notification' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Get user import validation rules
     *
     * @return array
     */
    protected function getUserImportRules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'], // 10MB max
            'has_header' => ['nullable', 'boolean'],
            'delimiter' => ['nullable', 'string', 'in:comma,semicolon,tab'],
            'encoding' => ['nullable', 'string', 'in:utf-8,iso-8859-1'],
            'default_role' => ['required', 'string', 'exists:roles,name'],
            'default_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'send_welcome_email' => ['nullable', 'boolean'],
            'force_password_reset' => ['nullable', 'boolean'],
            'skip_duplicates' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get user validation messages
     *
     * @return array
     */
    protected function getUserValidationMessages(): array
    {
        return array_merge(
            $this->getCommonValidationMessages(),
            $this->getEmailValidationMessages(),
            [
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least :min characters.',
                'password.confirmed' => 'Password confirmation does not match.',
                
                'password_confirmation.required' => 'Password confirmation is required.',
                'password_confirmation.min' => 'Password confirmation must be at least :min characters.',
                
                'current_password.required' => 'Current password is required.',
                'current_password.current_password' => 'Current password is incorrect.',
                
                'role.required' => 'Role is required.',
                'role.exists' => 'Selected role does not exist.',
                
                'username.unique' => 'This username is already taken.',
                'username.max' => 'Username cannot exceed :max characters.',
                'username.alpha_dash' => 'Username may only contain letters, numbers, dashes, and underscores.',
                
                'employee_id.unique' => 'This employee ID is already taken.',
                'employee_id.max' => 'Employee ID cannot exceed :max characters.',
                
                'department_id.exists' => 'Selected department does not exist.',
                
                'designation.max' => 'Designation cannot exceed :max characters.',
                
                'phone.regex' => 'Phone number format is invalid.',
                'phone.max' => 'Phone number cannot exceed :max characters.',
                
                'date_of_birth.date' => 'Date of birth must be a valid date.',
                'date_of_birth.before' => 'Date of birth must be before today.',
                
                'gender.in' => 'Selected gender is invalid.',
                
                'joining_date.date' => 'Joining date must be a valid date.',
                'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
                
                'photo.image' => 'Photo must be an image file.',
                'photo.mimes' => 'Photo must be a JPEG, PNG, or JPG file.',
                'photo.max' => 'Photo size cannot exceed 2MB.',
                
                'theme.in' => 'Selected theme is invalid.',
                
                'roles.required' => 'At least one role must be assigned.',
                'roles.array' => 'Roles must be a valid list.',
                'roles.min' => 'At least one role must be assigned.',
                'roles.*.exists' => 'One or more selected roles do not exist.',
                
                'permissions.array' => 'Permissions must be a valid list.',
                'permissions.*.exists' => 'One or more selected permissions do not exist.',
                
                'effective_date.date' => 'Effective date must be a valid date.',
                'effective_date.after_or_equal' => 'Effective date cannot be in the past.',
                
                'expiry_date.date' => 'Expiry date must be a valid date.',
                'expiry_date.after' => 'Expiry date must be after effective date.',
                
                'action.required' => 'Action is required.',
                'action.in' => 'Selected action is invalid.',
                
                'device_type.in' => 'Selected device type is invalid.',
                
                'session_timeout.integer' => 'Session timeout must be a valid number.',
                'session_timeout.min' => 'Session timeout must be at least :min minutes.',
                'session_timeout.max' => 'Session timeout cannot exceed :max minutes.',
                
                'notification_types.array' => 'Notification types must be a valid list.',
                'notification_types.*.in' => 'One or more selected notification types are invalid.',
                
                'notification_frequency.in' => 'Selected notification frequency is invalid.',
                
                'quiet_hours_start.date_format' => 'Quiet hours start time must be in HH:MM format.',
                'quiet_hours_end.date_format' => 'Quiet hours end time must be in HH:MM format.',
                
                'two_factor_method.in' => 'Selected two-factor method is invalid.',
                
                'backup_codes.array' => 'Backup codes must be a valid list.',
                'backup_codes.*.size' => 'Each backup code must be exactly :size characters.',
                
                'password_expiry_days.integer' => 'Password expiry days must be a valid number.',
                'password_expiry_days.min' => 'Password expiry days must be at least :min.',
                'password_expiry_days.max' => 'Password expiry days cannot exceed :max.',
                
                'max_concurrent_sessions.integer' => 'Max concurrent sessions must be a valid number.',
                'max_concurrent_sessions.min' => 'Max concurrent sessions must be at least :min.',
                'max_concurrent_sessions.max' => 'Max concurrent sessions cannot exceed :max.',
                
                'ip_whitelist.array' => 'IP whitelist must be a valid list.',
                'ip_whitelist.*.ip' => 'One or more IP addresses are invalid.',
                
                'user_ids.required' => 'At least one user must be selected.',
                'user_ids.array' => 'User selection must be a valid list.',
                'user_ids.min' => 'At least one user must be selected.',
                'user_ids.*.exists' => 'One or more selected users do not exist.',
                
                'file.required' => 'File is required.',
                'file.file' => 'Uploaded file is invalid.',
                'file.mimes' => 'File must be a CSV or Excel file.',
                'file.max' => 'File size cannot exceed 10MB.',
                
                'delimiter.in' => 'Selected delimiter is invalid.',
                'encoding.in' => 'Selected encoding is invalid.',
                
                'default_role.required' => 'Default role is required.',
                'default_role.exists' => 'Selected default role does not exist.',
                
                'default_department_id.exists' => 'Selected default department does not exist.',
                
                'bio.max' => 'Bio cannot exceed :max characters.',
                'website.url' => 'Website must be a valid URL.',
                'linkedin.url' => 'LinkedIn URL must be a valid URL.',
                'twitter.url' => 'Twitter URL must be a valid URL.',
                'facebook.url' => 'Facebook URL must be a valid URL.',
            ]
        );
    }
}