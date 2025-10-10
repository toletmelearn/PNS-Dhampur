<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnhancedFileValidation implements Rule
{
    protected $allowedMimeTypes;
    protected $maxFileSize;
    protected $allowedExtensions;
    protected $scanForViruses;
    protected $checkFileContent;
    protected $errorMessage;

    /**
     * Create a new rule instance.
     */
    public function __construct(
        array $allowedMimeTypes = [],
        int $maxFileSize = 10485760, // 10MB default
        array $allowedExtensions = [],
        bool $scanForViruses = true,
        bool $checkFileContent = true
    ) {
        $this->allowedMimeTypes = $allowedMimeTypes ?: config('fileupload.allowed_mime_types', [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
        
        $this->maxFileSize = $maxFileSize;
        $this->allowedExtensions = $allowedExtensions ?: config('fileupload.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'doc', 'docx'
        ]);
        
        $this->scanForViruses = $scanForViruses;
        $this->checkFileContent = $checkFileContent;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            $this->errorMessage = 'The file must be a valid upload.';
            return false;
        }

        if (!$value->isValid()) {
            $this->errorMessage = 'The file upload failed.';
            return false;
        }

        // Check file size
        if (!$this->validateFileSize($value)) {
            return false;
        }

        // Check file extension
        if (!$this->validateFileExtension($value)) {
            return false;
        }

        // Check MIME type
        if (!$this->validateMimeType($value)) {
            return false;
        }

        // Check file content
        if ($this->checkFileContent && !$this->validateFileContent($value)) {
            return false;
        }

        // Scan for viruses (simulated)
        if ($this->scanForViruses && !$this->scanForViruses($value)) {
            return false;
        }

        // Additional security checks
        if (!$this->performSecurityChecks($value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->errorMessage ?: 'The file is invalid.';
    }

    /**
     * Validate file size.
     */
    protected function validateFileSize(UploadedFile $file): bool
    {
        if ($file->getSize() > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1024 / 1024, 2);
            $this->errorMessage = "The file size cannot exceed {$maxSizeMB}MB.";
            return false;
        }

        return true;
    }

    /**
     * Validate file extension.
     */
    protected function validateFileExtension(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $this->allowedExtensions)) {
            $allowed = implode(', ', $this->allowedExtensions);
            $this->errorMessage = "Only files with extensions ({$allowed}) are allowed.";
            return false;
        }

        // Check for double extensions (e.g., file.php.jpg)
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.(php|asp|jsp|exe|bat|cmd|com|scr|vbs|js)\./i', $filename)) {
            $this->errorMessage = 'Files with suspicious double extensions are not allowed.';
            return false;
        }

        return true;
    }

    /**
     * Validate MIME type.
     */
    protected function validateMimeType(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            $this->errorMessage = 'The file type is not supported.';
            return false;
        }

        // Cross-check MIME type with file extension
        if (!$this->validateMimeExtensionMatch($file)) {
            return false;
        }

        return true;
    }

    /**
     * Validate that MIME type matches file extension.
     */
    protected function validateMimeExtensionMatch(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $mimeExtensionMap = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
            'text/plain' => ['txt'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        ];

        if (isset($mimeExtensionMap[$mimeType])) {
            if (!in_array($extension, $mimeExtensionMap[$mimeType])) {
                $this->errorMessage = 'File extension does not match the file content.';
                return false;
            }
        }

        return true;
    }

    /**
     * Validate file content for malicious patterns.
     */
    protected function validateFileContent(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());
        
        // Check for executable code patterns
        $maliciousPatterns = [
            // PHP code
            '/<\?php/i',
            '/<\?=/i',
            '/<%/i',
            
            // JavaScript
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/eval\s*\(/i',
            
            // Server-side code
            '/<%@/i',
            '/<jsp:/i',
            '/<%=/i',
            
            // System commands
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/proc_open\s*\(/i',
            
            // SQL injection attempts
            '/UNION\s+SELECT/i',
            '/DROP\s+TABLE/i',
            '/INSERT\s+INTO/i',
            
            // File inclusion
            '/include\s*\(/i',
            '/require\s*\(/i',
            '/file_get_contents\s*\(/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                Log::warning('Malicious file content detected', [
                    'filename' => $file->getClientOriginalName(),
                    'pattern' => $pattern,
                    'ip' => request()->ip(),
                ]);
                
                $this->errorMessage = 'The file contains potentially malicious content.';
                return false;
            }
        }

        // Check for suspicious binary patterns
        if ($this->containsSuspiciousBinaryPatterns($content)) {
            return false;
        }

        return true;
    }

    /**
     * Simulate virus scanning.
     */
    protected function scanForViruses(UploadedFile $file): bool
    {
        // In a real implementation, you would integrate with ClamAV or similar
        // For now, we'll do basic checks for known malicious signatures
        
        $content = file_get_contents($file->getPathname());
        
        // Check for EICAR test signature (standard antivirus test file)
        if (strpos($content, 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*') !== false) {
            $this->errorMessage = 'The file contains a virus signature.';
            return false;
        }

        // Check file entropy (high entropy might indicate packed/encrypted malware)
        if ($this->calculateEntropy($content) > 7.5) {
            Log::warning('High entropy file detected', [
                'filename' => $file->getClientOriginalName(),
                'entropy' => $this->calculateEntropy($content),
                'ip' => request()->ip(),
            ]);
            
            // Don't block high entropy files automatically, just log
            // $this->errorMessage = 'The file appears to be compressed or encrypted.';
            // return false;
        }

        return true;
    }

    /**
     * Perform additional security checks.
     */
    protected function performSecurityChecks(UploadedFile $file): bool
    {
        // Check for null bytes (can be used to bypass filters)
        $filename = $file->getClientOriginalName();
        if (strpos($filename, "\0") !== false) {
            $this->errorMessage = 'Invalid filename detected.';
            return false;
        }

        // Check filename length
        if (strlen($filename) > 255) {
            $this->errorMessage = 'Filename is too long.';
            return false;
        }

        // Check for path traversal in filename
        if (preg_match('/\.\.[\/\\\\]/', $filename)) {
            $this->errorMessage = 'Invalid filename path detected.';
            return false;
        }

        // Check for reserved filenames (Windows)
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        
        if (in_array(strtoupper($baseName), $reservedNames)) {
            $this->errorMessage = 'Reserved filename not allowed.';
            return false;
        }

        return true;
    }

    /**
     * Check for suspicious binary patterns.
     */
    protected function containsSuspiciousBinaryPatterns(string $content): bool
    {
        // Check for PE header (Windows executable)
        if (substr($content, 0, 2) === 'MZ') {
            $this->errorMessage = 'Executable files are not allowed.';
            return true;
        }

        // Check for ELF header (Linux executable)
        if (substr($content, 0, 4) === "\x7fELF") {
            $this->errorMessage = 'Executable files are not allowed.';
            return true;
        }

        // Check for Mach-O header (macOS executable)
        $header = substr($content, 0, 4);
        if (in_array($header, ["\xfe\xed\xfa\xce", "\xfe\xed\xfa\xcf", "\xce\xfa\xed\xfe", "\xcf\xfa\xed\xfe"])) {
            $this->errorMessage = 'Executable files are not allowed.';
            return true;
        }

        return false;
    }

    /**
     * Calculate Shannon entropy of file content.
     */
    protected function calculateEntropy(string $data): float
    {
        $entropy = 0;
        $size = strlen($data);
        
        if ($size === 0) {
            return 0;
        }

        $frequencies = array_count_values(str_split($data));
        
        foreach ($frequencies as $frequency) {
            $probability = $frequency / $size;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }

    /**
     * Create rule for images only.
     */
    public static function images(int $maxSize = 5242880): self // 5MB default for images
    {
        return new self(
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            $maxSize,
            ['jpg', 'jpeg', 'png', 'gif', 'webp']
        );
    }

    /**
     * Create rule for documents only.
     */
    public static function documents(int $maxSize = 10485760): self // 10MB default for documents
    {
        return new self(
            [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ],
            $maxSize,
            ['pdf', 'doc', 'docx', 'txt']
        );
    }
}