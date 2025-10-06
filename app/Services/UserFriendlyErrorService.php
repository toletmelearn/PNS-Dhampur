<?php

namespace App\Services;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Log;

class UserFriendlyErrorService
{
    /**
     * Convert technical exceptions to user-friendly messages
     */
    public static function getErrorMessage(Exception $exception, string $context = 'general'): string
    {
        // Log the actual technical error for debugging
        Log::error("Error in {$context}: " . $exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString()
        ]);

        // Return user-friendly messages based on exception type
        switch (true) {
            case $exception instanceof ValidationException:
                return 'Please check your input and try again.';
                
            case $exception instanceof QueryException:
                return self::getDatabaseErrorMessage($exception, $context);
                
            case $exception instanceof AuthenticationException:
                return 'Please log in to continue.';
                
            case $exception instanceof AuthorizationException:
                return 'You do not have permission to perform this action.';
                
            case $exception instanceof NotFoundHttpException:
                return 'The requested resource was not found.';
                
            case $exception instanceof HttpResponseException:
                return 'There was an issue processing your request.';
                
            default:
                return self::getContextualErrorMessage($context);
        }
    }

    /**
     * Get context-specific error messages
     */
    private static function getContextualErrorMessage(string $context): string
    {
        $messages = [
            'student_create' => 'Unable to create student record. Please check your information and try again.',
            'student_update' => 'Unable to update student information. Please try again.',
            'student_delete' => 'Unable to delete student record. This student may have associated data.',
            'teacher_create' => 'Unable to create teacher record. Please check your information and try again.',
            'teacher_update' => 'Unable to update teacher information. Please try again.',
            'teacher_delete' => 'Unable to delete teacher record. This teacher may have associated data.',
            'fee_create' => 'Unable to create fee record. Please check your information and try again.',
            'fee_update' => 'Unable to update fee information. Please try again.',
            'fee_delete' => 'Unable to delete fee record. This fee may be associated with payments.',
            'payment_record' => 'Unable to record payment. Please check your payment information and try again.',
            'attendance_mark' => 'Unable to mark attendance. Please try again.',
            'document_upload' => 'Unable to upload document. Please check the file format and size.',
            'document_verify' => 'Unable to verify document. Please try again later.',
            'biometric_checkin' => 'Unable to process check-in. Please try again.',
            'biometric_checkout' => 'Unable to process check-out. Please try again.',
            'payroll_calculate' => 'Unable to calculate payroll. Please check the salary structure.',
            'inventory_update' => 'Unable to update inventory. Please check your information.',
            'budget_create' => 'Unable to create budget. Please check your budget details.',
            'syllabus_upload' => 'Unable to upload syllabus. Please check the file format.',
            'assignment_submit' => 'Unable to submit assignment. Please try again.',
            'notification_send' => 'Unable to send notification. Please try again later.',
            'report_generate' => 'Unable to generate report. Please try again later.',
            'export_data' => 'Unable to export data. Please try again later.',
            'import_data' => 'Unable to import data. Please check your file format.',
            'student_fetch' => 'Unable to fetch student information. Please try again.',
            'teacher_fetch' => 'Unable to fetch teacher information. Please try again.',
            'biometric_checkout' => 'Unable to process check-out. Please try again or contact support.',
            'file_upload' => 'Unable to upload file. Please check the file size and format.',
            'email_send' => 'Unable to send email. Please try again later.',
            'sms_send' => 'Unable to send SMS. Please try again later.',
            'backup_create' => 'Unable to create backup. Please try again later.',
            'maintenance' => 'System is currently under maintenance. Please try again later.',
            'general' => 'Something went wrong. Please try again later.'
        ];

        return $messages[$context] ?? $messages['general'];
    }

    /**
     * Get database-specific error messages
     */
    private static function getDatabaseErrorMessage(QueryException $exception, string $context): string
    {
        $errorCode = $exception->errorInfo[1] ?? null;
        
        switch ($errorCode) {
            case 1062: // Duplicate entry
                return self::getDuplicateEntryMessage($context);
            case 1451: // Foreign key constraint
                return 'Cannot delete this record as it is being used by other data.';
            case 1452: // Foreign key constraint fails
                return 'Invalid reference data. Please check your selection.';
            case 1406: // Data too long
                return 'Some of your input is too long. Please shorten your text.';
            case 1048: // Column cannot be null
                return 'Required information is missing. Please fill all required fields.';
            default:
                return 'Database error occurred. Please try again later.';
        }
    }

    /**
     * Get duplicate entry messages based on context
     */
    private static function getDuplicateEntryMessage(string $context): string
    {
        $messages = [
            'student_create' => 'A student with this information already exists.',
            'teacher_create' => 'A teacher with this information already exists.',
            'fee_create' => 'A fee record for this student already exists.',
            'general' => 'This information already exists in the system.'
        ];

        return $messages[$context] ?? $messages['general'];
    }

    /**
     * Get success messages for different contexts
     */
    public static function getSuccessMessage(string $context): string
    {
        $messages = [
            'student_create' => 'Student record created successfully.',
            'student_update' => 'Student information updated successfully.',
            'student_delete' => 'Student record deleted successfully.',
            'teacher_create' => 'Teacher record created successfully.',
            'teacher_update' => 'Teacher information updated successfully.',
            'teacher_delete' => 'Teacher record deleted successfully.',
            'fee_create' => 'Fee record created successfully.',
            'fee_update' => 'Fee information updated successfully.',
            'fee_delete' => 'Fee record deleted successfully.',
            'attendance_mark' => 'Attendance marked successfully.',
            'document_upload' => 'Document uploaded successfully.',
            'document_verify' => 'Document verified successfully.',
            'biometric_checkin' => 'Check-in recorded successfully.',
            'biometric_checkout' => 'Check-out recorded successfully.',
            'payroll_calculate' => 'Payroll calculated successfully.',
            'inventory_update' => 'Inventory updated successfully.',
            'budget_create' => 'Budget created successfully.',
            'syllabus_upload' => 'Syllabus uploaded successfully.',
            'assignment_submit' => 'Assignment submitted successfully.',
            'notification_send' => 'Notification sent successfully.',
            'report_generate' => 'Report generated successfully.',
            'export_data' => 'Data exported successfully.',
            'import_data' => 'Data imported successfully.',
            'file_upload' => 'File uploaded successfully.',
            'email_send' => 'Email sent successfully.',
            'sms_send' => 'SMS sent successfully.',
            'backup_create' => 'Backup created successfully.',
            'general' => 'Operation completed successfully.'
        ];

        return $messages[$context] ?? $messages['general'];
    }

    /**
     * Format error response for JSON APIs
     */
    public static function jsonErrorResponse(Exception $exception, string $context = 'general', int $statusCode = 500): array
    {
        return [
            'success' => false,
            'message' => self::getErrorMessage($exception, $context),
            'error_code' => $statusCode
        ];
    }

    /**
     * Format success response for JSON APIs
     */
    public static function jsonSuccessResponse(string $context = 'general', $data = null): array
    {
        $response = [
            'success' => true,
            'message' => self::getSuccessMessage($context)
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Check if error should be shown in debug mode
     */
    public static function shouldShowTechnicalError(): bool
    {
        return config('app.debug', false) && auth()->check() && auth()->user()->hasRole('super_admin');
    }

    /**
     * Get error message with optional technical details for admins
     */
    public static function getErrorMessageWithDebug(Exception $exception, string $context = 'general'): string
    {
        $userMessage = self::getErrorMessage($exception, $context);
        
        if (self::shouldShowTechnicalError()) {
            $userMessage .= ' (Technical: ' . $exception->getMessage() . ')';
        }
        
        return $userMessage;
    }
}