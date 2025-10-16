<?php

namespace App\Services;

use App\Http\Traits\InputSanitizationTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\ErrorLog;

class InputSanitizationService
{
    use InputSanitizationTrait;

    /**
     * Sanitize arbitrary data (string|array) with options.
     */
    public function sanitize($data, array $options = [])
    {
        if (is_array($data)) {
            return $this->sanitizeArray($data, [
                'sanitize_strings' => true,
            ]);
        }

        if (is_string($data)) {
            $defaults = [
                'strip_tags' => true,
                'trim' => true,
                'remove_special_chars' => false,
                'max_length' => config('security.input.max_string_length'),
                'allow_html' => false,
                'allowed_tags' => config('security.input.allowed_html_tags'),
            ];
            return $this->sanitizeString($data, array_merge($defaults, $options));
        }

        return $data;
    }

    /**
     * Sanitize a set of fields according to provided field type mapping.
     * Example types: email, phone, url, integer, float, array, json, search, text, html, strict, name
     */
    public function sanitizeFields(array $input, array $fieldTypes = []): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $type = $fieldTypes[$key] ?? 'string';

            switch ($type) {
                case 'email':
                    $sanitized[$key] = $this->sanitizeEmail(is_string($value) ? $value : null);
                    break;
                case 'phone':
                    $sanitized[$key] = $this->sanitizePhone(is_string($value) ? $value : null);
                    break;
                case 'url':
                    $sanitized[$key] = $this->sanitizeUrl(is_string($value) ? $value : null);
                    break;
                case 'integer':
                    $sanitized[$key] = $this->sanitizeNumeric($value, ['type' => 'int']);
                    break;
                case 'float':
                    $sanitized[$key] = $this->sanitizeNumeric($value, ['type' => 'float']);
                    break;
                case 'array':
                    $sanitized[$key] = is_array($value) ? $this->sanitizeArray($value) : null;
                    break;
                case 'json':
                    $sanitized[$key] = is_string($value) ? $this->sanitizeJson($value) : null;
                    break;
                case 'search':
                    $sanitized[$key] = $this->sanitizeSearchQuery(is_string($value) ? $value : null);
                    break;
                case 'text':
                    $sanitized[$key] = $this->sanitizeString(is_string($value) ? $value : null, [
                        'strip_tags' => true,
                        'allow_html' => false,
                        'max_length' => config('security.input.max_text_length'),
                    ]);
                    break;
                case 'html':
                    $sanitized[$key] = $this->sanitizeHtml(is_string($value) ? $value : '');
                    break;
                case 'strict':
                    $sanitized[$key] = $this->sanitizeString(is_string($value) ? $value : null, [
                        'strip_tags' => true,
                        'allow_html' => false,
                        'remove_special_chars' => true,
                        'max_length' => config('security.input.max_string_length'),
                    ]);
                    break;
                case 'name':
                    $sanitized[$key] = $this->sanitizeString(is_string($value) ? $value : null, [
                        'strip_tags' => true,
                        'allow_html' => false,
                        'remove_special_chars' => true,
                        'max_length' => 100,
                    ]);
                    break;
                default:
                    $sanitized[$key] = is_string($value)
                        ? $this->sanitizeString($value)
                        : (is_array($value) ? $this->sanitizeArray($value) : $value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize file upload metadata (NOT the file content).
     */
    public function sanitizeFileUpload(array $metadata): array
    {
        $result = [];
        foreach ($metadata as $key => $value) {
            if ($key === 'filename' && is_string($value)) {
                $result[$key] = $this->sanitizeFilename($value);
                continue;
            }
            if (in_array($key, ['description', 'alt', 'title'], true)) {
                $result[$key] = $this->sanitizeString(is_string($value) ? $value : null, [
                    'strip_tags' => true,
                    'allow_html' => false,
                    'max_length' => 255,
                ]);
                continue;
            }
            $result[$key] = is_string($value) ? $this->sanitizeString($value) : $value;
        }
        return $result;
    }

    /**
     * Provide context-based field configuration used by middleware routing.
     */
    public function getContextConfig(string $context): array
    {
        switch ($context) {
            case 'student_form':
                return [
                    'name' => 'name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'address' => 'text',
                    'bio' => 'html',
                    'parent_phone' => 'phone',
                    'parent_email' => 'email',
                    'admission_no' => 'strict',
                ];
            case 'teacher_form':
                return [
                    'name' => 'name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'address' => 'text',
                    'bio' => 'html',
                    'employee_id' => 'strict',
                ];
            case 'fee_form':
                return [
                    'amount' => 'float',
                    'paid_amount' => 'float',
                    'balance' => 'float',
                    'notes' => 'text',
                ];
            case 'attendance_form':
                return [
                    'student_id' => 'integer',
                    'date' => 'strict',
                    'status' => 'strict',
                    'remarks' => 'text',
                ];
        }
        return [];
    }

    /**
     * Sanitize permissive HTML while stripping dangerous constructs.
     */
    public function sanitizeHtml(string $html): string
    {
        // Remove script/style/iframe blocks
        $clean = preg_replace('#<\s*(script|style|iframe)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);

        // Remove event handler attributes like onload, onclick, etc.
        $clean = preg_replace('/\son[a-zA-Z]+\s*=\s*[\'\"][^\'\"]*[\'\"]/mi', '', $clean);

        // Remove javascript: and data: URLs from href/src
        $clean = preg_replace('/(?:href|src)\s*=\s*[\'\"]\s*(?:javascript|data):[^\'\"]*[\'\"]/mi', '', $clean);

        // Allow only configured tags
        $allowed = config('security.input.allowed_html_tags', '<p><br><strong><em><ul><ol><li>');
        $clean = strip_tags($clean, $allowed);

        // Encode remaining entities safely
        return htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    }
    public function containsXSS(string $value): bool
    {
        $patterns = [
            '/<\s*script\b/i',
            '/javascript\s*:/i',
            '/on[a-zA-Z]+\s*=\s*/i',
            '/<\s*iframe\b/i',
            '/<\s*img\b[^>]*src\s*=\s*["\"]\s*data:/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect common SQL injection patterns.
     */
    public function containsSQLInjection(string $value): bool
    {
        $patterns = [
            '/\bUNION\b\s*\bSELECT\b/i',
            '/\bSELECT\b.*\bFROM\b/i',
            '/\bINSERT\b.*\bINTO\b/i',
            '/\bUPDATE\b.*\bSET\b/i',
            '/\bDELETE\b.*\bFROM\b/i',
            '/\bDROP\b\s*\bTABLE\b/i',
            '/--\s|\/\*/',
            '/\bOR\b\s+1=1/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Log suspicious input to application logs and error_logs table.
     */
    public function logSuspiciousInput(string $value, string $type, array $meta = []): void
    {
        Log::warning('Suspicious input detected', array_merge($meta, [
            'type' => $type,
            'payload_excerpt' => Str::limit($value, 200),
        ]));

        try {
            ErrorLog::create([
                'level' => 'warning',
                'message' => 'Suspicious input detected: ' . $type,
                'context' => json_encode(array_merge($meta, ['payload_excerpt' => Str::limit($value, 200)])),
                'file' => 'InputSanitizationService',
                'line' => 0,
                'trace' => null,
                'url' => request()->fullUrl() ?? null,
                'method' => request()->method() ?? null,
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'request_data' => request()->all(),
                'exception_class' => 'SuspiciousInput',
                'is_resolved' => false,
            ]);
        } catch (\Throwable $e) {
            // Fallback to log only
            Log::error('Failed to persist suspicious input log', [
                'error' => $e->getMessage(),
            ]);
        }
    
}

}


