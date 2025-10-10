<?php

namespace App\Services;

use Illuminate\Support\Str;
use HTMLPurifier;
use HTMLPurifier_Config;

class InputSanitizationService
{
    private HTMLPurifier $purifier;
    private HTMLPurifier $strictPurifier;
    private array $config;

    public function __construct()
    {
        $this->initializePurifiers();
        $this->config = config('sanitization', []);
    }

    /**
     * Initialize HTML Purifier instances.
     */
    private function initializePurifiers()
    {
        // Standard purifier configuration
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,ol,ul,li,a[href],blockquote');
        $config->set('HTML.AllowedAttributes', 'a.href');
        $config->set('URI.AllowedSchemes', array('http' => true, 'https' => true, 'mailto' => true));
        $config->set('Attr.AllowedRel', array('nofollow'));
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.Linkify', false);
        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier'));
        
        $this->purifier = new HTMLPurifier($config);

        // Strict purifier (no HTML allowed)
        $strictConfig = HTMLPurifier_Config::createDefault();
        $strictConfig->set('HTML.Allowed', '');
        $strictConfig->set('Cache.SerializerPath', storage_path('app/htmlpurifier'));
        
        $this->strictPurifier = new HTMLPurifier($strictConfig);
    }

    /**
     * Sanitize input data recursively.
     */
    public function sanitize(mixed $data, array $options = []): mixed
    {
        if (is_array($data)) {
            return $this->sanitizeArray($data, $options);
        }

        if (is_string($data)) {
            return $this->sanitizeString($data, $options);
        }

        return $data;
    }

    /**
     * Sanitize array data recursively.
     */
    private function sanitizeArray(array $data, array $options = []): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeKey($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $options);
            } elseif (is_string($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeString($value, $options);
            } else {
                $sanitized[$sanitizedKey] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize string data.
     */
    private function sanitizeString(string $data, array $options = []): string
    {
        $mode = $options['mode'] ?? 'standard';
        $trim = $options['trim'] ?? true;
        $removeNullBytes = $options['remove_null_bytes'] ?? true;
        $normalizeWhitespace = $options['normalize_whitespace'] ?? true;

        // Remove null bytes
        if ($removeNullBytes) {
            $data = str_replace("\0", '', $data);
        }

        // Trim whitespace
        if ($trim) {
            $data = trim($data);
        }

        // Normalize whitespace
        if ($normalizeWhitespace) {
            $data = preg_replace('/\s+/', ' ', $data);
        }

        // Apply sanitization based on mode
        switch ($mode) {
            case 'strict':
                return $this->strictSanitize($data);
            case 'html':
                return $this->htmlSanitize($data);
            case 'text':
                return $this->textSanitize($data);
            case 'name':
                return $this->nameSanitize($data);
            case 'email':
                return $this->emailSanitize($data);
            case 'url':
                return $this->urlSanitize($data);
            case 'phone':
                return $this->phoneSanitize($data);
            case 'numeric':
                return $this->numericSanitize($data);
            case 'alphanumeric':
                return $this->alphanumericSanitize($data);
            case 'filename':
                return $this->filenameSanitize($data);
            case 'sql':
                return $this->sqlSanitize($data);
            default:
                return $this->standardSanitize($data);
        }
    }

    /**
     * Sanitize array keys.
     */
    private function sanitizeKey(string $key): string
    {
        // Remove dangerous characters from keys
        $key = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $key);
        return trim($key);
    }

    /**
     * Standard sanitization - removes most dangerous content.
     */
    private function standardSanitize(string $data): string
    {
        // Remove script tags and their content
        $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
        
        // Remove dangerous HTML attributes
        $data = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $data);
        $data = preg_replace('/\s*javascript\s*:\s*/i', '', $data);
        $data = preg_replace('/\s*vbscript\s*:\s*/i', '', $data);
        $data = preg_replace('/\s*data\s*:\s*/i', '', $data);
        
        // Remove potentially dangerous tags
        $dangerousTags = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'select', 'button'];
        foreach ($dangerousTags as $tag) {
            $data = preg_replace("/<\/?{$tag}[^>]*>/i", '', $data);
        }

        return $data;
    }

    /**
     * Strict sanitization - removes all HTML and special characters.
     */
    private function strictSanitize(string $data): string
    {
        return $this->strictPurifier->purify($data);
    }

    /**
     * HTML sanitization - allows safe HTML tags.
     */
    private function htmlSanitize(string $data): string
    {
        return $this->purifier->purify($data);
    }

    /**
     * Text sanitization - for plain text fields.
     */
    private function textSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Decode HTML entities
        $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
        
        // Remove control characters except newlines and tabs
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        
        return $data;
    }

    /**
     * Name sanitization - for person names.
     */
    private function nameSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Allow only letters, spaces, dots, apostrophes, and hyphens
        $data = preg_replace('/[^a-zA-Z\s\.\'\-]/u', '', $data);
        
        // Remove multiple spaces
        $data = preg_replace('/\s+/', ' ', $data);
        
        // Capitalize properly
        $data = ucwords(strtolower($data));
        
        return trim($data);
    }

    /**
     * Email sanitization.
     */
    private function emailSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Convert to lowercase
        $data = strtolower($data);
        
        // Remove invalid characters
        $data = preg_replace('/[^a-z0-9@\.\-_]/', '', $data);
        
        return trim($data);
    }

    /**
     * URL sanitization.
     */
    private function urlSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Validate and sanitize URL
        $data = filter_var($data, FILTER_SANITIZE_URL);
        
        // Ensure it starts with http:// or https://
        if (!preg_match('/^https?:\/\//', $data) && !empty($data)) {
            $data = 'http://' . $data;
        }
        
        return $data;
    }

    /**
     * Phone number sanitization.
     */
    private function phoneSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Keep only digits, plus, spaces, hyphens, and parentheses
        $data = preg_replace('/[^0-9\+\s\-\(\)]/', '', $data);
        
        // Remove extra spaces
        $data = preg_replace('/\s+/', ' ', $data);
        
        return trim($data);
    }

    /**
     * Numeric sanitization.
     */
    private function numericSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Keep only digits, decimal point, and minus sign
        $data = preg_replace('/[^0-9\.\-]/', '', $data);
        
        return $data;
    }

    /**
     * Alphanumeric sanitization.
     */
    private function alphanumericSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Keep only alphanumeric characters
        $data = preg_replace('/[^a-zA-Z0-9]/', '', $data);
        
        return $data;
    }

    /**
     * Filename sanitization.
     */
    private function filenameSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Remove path separators and dangerous characters
        $data = preg_replace('/[\/\\\\:*?"<>|]/', '', $data);
        
        // Remove control characters
        $data = preg_replace('/[\x00-\x1F\x7F]/', '', $data);
        
        // Limit length
        if (strlen($data) > 255) {
            $data = substr($data, 0, 255);
        }
        
        return trim($data);
    }

    /**
     * SQL injection prevention sanitization.
     */
    private function sqlSanitize(string $data): string
    {
        // Remove HTML tags
        $data = strip_tags($data);
        
        // Remove SQL keywords and dangerous characters
        $sqlKeywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'EXEC', 'EXECUTE', 'UNION', 'SCRIPT', 'JAVASCRIPT', 'VBSCRIPT',
            'ONLOAD', 'ONERROR', 'ONCLICK'
        ];
        
        foreach ($sqlKeywords as $keyword) {
            $data = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '', $data);
        }
        
        // Remove dangerous SQL characters
        $data = str_replace(['--', '/*', '*/', ';', '\'', '"', '`'], '', $data);
        
        return $data;
    }

    /**
     * Sanitize specific field types commonly used in school management.
     */
    public function sanitizeSchoolField(string $fieldType, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        switch ($fieldType) {
            case 'student_name':
            case 'teacher_name':
            case 'parent_name':
                return $this->sanitize($value, ['mode' => 'name']);
                
            case 'email':
                return $this->sanitize($value, ['mode' => 'email']);
                
            case 'phone':
            case 'mobile':
                return $this->sanitize($value, ['mode' => 'phone']);
                
            case 'address':
                return $this->sanitize($value, ['mode' => 'text']);
                
            case 'remarks':
            case 'description':
            case 'notes':
                return $this->sanitize($value, ['mode' => 'html']);
                
            case 'roll_number':
            case 'admission_number':
            case 'employee_id':
                return $this->sanitize($value, ['mode' => 'alphanumeric']);
                
            case 'amount':
            case 'fee':
            case 'salary':
                return $this->sanitize($value, ['mode' => 'numeric']);
                
            case 'aadhaar':
                return $this->sanitize($value, ['mode' => 'numeric']);
                
            case 'pan':
                return strtoupper($this->sanitize($value, ['mode' => 'alphanumeric']));
                
            case 'url':
            case 'website':
                return $this->sanitize($value, ['mode' => 'url']);
                
            case 'filename':
                return $this->sanitize($value, ['mode' => 'filename']);
                
            default:
                return $this->sanitize($value, ['mode' => 'standard']);
        }
    }

    /**
     * Batch sanitize multiple fields with their types.
     */
    public function sanitizeFields(array $data, array $fieldTypes = []): array
    {
        $sanitized = [];
        
        foreach ($data as $field => $value) {
            if (isset($fieldTypes[$field])) {
                $sanitized[$field] = $this->sanitizeSchoolField($fieldTypes[$field], $value);
            } else {
                $sanitized[$field] = $this->sanitize($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Check if input contains potential XSS.
     */
    public function containsXSS(string $input): bool
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/onfocus\s*=/i',
            '/onblur\s*=/i',
            '/onchange\s*=/i',
            '/onsubmit\s*=/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<link[^>]*>/i',
            '/<meta[^>]*>/i',
            '/data:text\/html/i',
            '/data:application\/javascript/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if input contains potential SQL injection.
     */
    public function containsSQLInjection(string $input): bool
    {
        $sqlPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bCREATE\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\'|\")(\s*)(;|--|\#)/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
            '/(1\s*=\s*1)/i',
            '/(1\s*=\s*0)/i',
            '/(\'\s*OR\s*\')/i',
            '/(\"\s*OR\s*\")/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log suspicious input attempts.
     */
    public function logSuspiciousInput(string $input, string $type, array $context = []): void
    {
        \Log::warning("Suspicious input detected", [
            'type' => $type,
            'input' => substr($input, 0, 200), // Log only first 200 chars
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            'context' => $context,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Validate and sanitize file upload data.
     */
    public function sanitizeFileUpload(array $fileData): array
    {
        $sanitized = [];
        
        foreach ($fileData as $key => $value) {
            switch ($key) {
                case 'name':
                case 'original_name':
                    $sanitized[$key] = $this->filenameSanitize($value);
                    break;
                case 'description':
                case 'alt_text':
                    $sanitized[$key] = $this->textSanitize($value);
                    break;
                default:
                    $sanitized[$key] = $this->standardSanitize($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Get sanitization configuration for different contexts.
     */
    public function getContextConfig(string $context): array
    {
        $configs = [
            'student_form' => [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'address' => 'text',
                'roll_number' => 'alphanumeric',
                'aadhaar' => 'numeric',
                'remarks' => 'html'
            ],
            'teacher_form' => [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'address' => 'text',
                'employee_id' => 'alphanumeric',
                'qualification' => 'text',
                'salary' => 'numeric',
                'pan' => 'alphanumeric',
                'remarks' => 'html'
            ],
            'fee_form' => [
                'amount' => 'numeric',
                'description' => 'text',
                'remarks' => 'html',
                'transaction_id' => 'alphanumeric'
            ],
            'attendance_form' => [
                'remarks' => 'html',
                'reason' => 'text'
            ]
        ];

        return $configs[$context] ?? [];
    }
}