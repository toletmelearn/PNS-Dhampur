<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadSecurityMiddleware
{
    /**
     * Maximum file size in bytes (10MB default)
     */
    protected int $maxFileSize = 10485760;

    /**
     * Allowed MIME types for different file categories
     */
    protected array $allowedMimeTypes = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ],
        'archive' => [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed'
        ]
    ];

    /**
     * Allowed file extensions
     */
    protected array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv',
        'zip', 'rar', '7z'
    ];

    /**
     * Dangerous file extensions that should never be allowed
     */
    protected array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'pht',
        'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js',
        'jar', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb',
        'sh', 'bash', 'ps1', 'htaccess', 'htpasswd'
    ];

    /**
     * Malicious file signatures (magic bytes)
     */
    protected array $maliciousSignatures = [
        // PHP signatures
        '<?php',
        '<?=',
        '<script',
        // Executable signatures
        'MZ', // PE executable
        'PK', // ZIP (could contain malicious files)
        // Script signatures
        '#!/bin/sh',
        '#!/bin/bash',
        '@echo off'
    ];

    /**
     * Maximum number of files per request
     */
    protected int $maxFilesPerRequest = 10;

    /**
     * Routes that should have file upload validation
     */
    protected array $fileUploadRoutes = [
        'students/store',
        'students/update',
        'teachers/store',
        'teachers/update',
        'documents/upload',
        'profile/avatar',
        'import/csv'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process requests with file uploads on relevant routes
        if (!$this->shouldValidateFiles($request)) {
            return $next($request);
        }

        // Validate all uploaded files
        $this->validateUploadedFiles($request);

        return $next($request);
    }

    /**
     * Check if request should have file validation
     */
    protected function shouldValidateFiles(Request $request): bool
    {
        // Check if request has files
        if (!$request->hasFile() && empty($request->allFiles())) {
            return false;
        }

        // Check if route requires file validation
        $path = $request->path();
        foreach ($this->fileUploadRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        // Default to validation for POST/PUT requests with files
        return in_array($request->method(), ['POST', 'PUT', 'PATCH']);
    }

    /**
     * Validate all uploaded files in the request
     */
    protected function validateUploadedFiles(Request $request): void
    {
        $files = $this->getAllUploadedFiles($request);

        // Check file count limit
        if (count($files) > $this->maxFilesPerRequest) {
            $this->logSecurityViolation($request, 'file_count_exceeded', [
                'file_count' => count($files),
                'max_allowed' => $this->maxFilesPerRequest
            ]);
            
            abort(413, 'Too many files uploaded. Maximum allowed: ' . $this->maxFilesPerRequest);
        }

        // Validate each file
        foreach ($files as $fieldName => $file) {
            $this->validateSingleFile($file, $fieldName, $request);
        }
    }

    /**
     * Get all uploaded files from request
     */
    protected function getAllUploadedFiles(Request $request): array
    {
        $files = [];
        
        foreach ($request->allFiles() as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $index => $singleFile) {
                    if ($singleFile instanceof UploadedFile) {
                        $files["{$key}[{$index}]"] = $singleFile;
                    }
                }
            } elseif ($file instanceof UploadedFile) {
                $files[$key] = $file;
            }
        }

        return $files;
    }

    /**
     * Validate a single uploaded file
     */
    protected function validateSingleFile(UploadedFile $file, string $fieldName, Request $request): void
    {
        // Check if file upload was successful
        if (!$file->isValid()) {
            $this->logSecurityViolation($request, 'invalid_file_upload', [
                'field' => $fieldName,
                'error' => $file->getErrorMessage()
            ]);
            
            abort(400, "Invalid file upload for field: {$fieldName}");
        }

        // Validate file size
        $this->validateFileSize($file, $fieldName, $request);

        // Validate file extension
        $this->validateFileExtension($file, $fieldName, $request);

        // Validate MIME type
        $this->validateMimeType($file, $fieldName, $request);

        // Validate file content
        $this->validateFileContent($file, $fieldName, $request);

        // Validate file name
        $this->validateFileName($file, $fieldName, $request);
    }

    /**
     * Validate file size
     */
    protected function validateFileSize(UploadedFile $file, string $fieldName, Request $request): void
    {
        if ($file->getSize() > $this->maxFileSize) {
            $this->logSecurityViolation($request, 'file_size_exceeded', [
                'field' => $fieldName,
                'file_size' => $file->getSize(),
                'max_size' => $this->maxFileSize,
                'filename' => $file->getClientOriginalName()
            ]);

            $maxSizeMB = round($this->maxFileSize / 1048576, 2);
            abort(413, "File too large. Maximum size allowed: {$maxSizeMB}MB");
        }
    }

    /**
     * Validate file extension
     */
    protected function validateFileExtension(UploadedFile $file, string $fieldName, Request $request): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // Check for dangerous extensions
        if (in_array($extension, $this->dangerousExtensions)) {
            $this->logSecurityViolation($request, 'dangerous_file_extension', [
                'field' => $fieldName,
                'extension' => $extension,
                'filename' => $file->getClientOriginalName()
            ]);

            abort(400, "File type not allowed: .{$extension}");
        }

        // Check if extension is in allowed list
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->logSecurityViolation($request, 'invalid_file_extension', [
                'field' => $fieldName,
                'extension' => $extension,
                'filename' => $file->getClientOriginalName()
            ]);

            abort(400, "File extension not allowed: .{$extension}");
        }
    }

    /**
     * Validate MIME type
     */
    protected function validateMimeType(UploadedFile $file, string $fieldName, Request $request): void
    {
        $mimeType = $file->getMimeType();
        $allAllowedMimes = array_merge(...array_values($this->allowedMimeTypes));

        if (!in_array($mimeType, $allAllowedMimes)) {
            $this->logSecurityViolation($request, 'invalid_mime_type', [
                'field' => $fieldName,
                'mime_type' => $mimeType,
                'filename' => $file->getClientOriginalName()
            ]);

            abort(400, "File type not allowed: {$mimeType}");
        }

        // Validate MIME type matches extension
        $this->validateMimeExtensionMatch($file, $fieldName, $request);
    }

    /**
     * Validate that MIME type matches file extension
     */
    protected function validateMimeExtensionMatch(UploadedFile $file, string $fieldName, Request $request): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $expectedMimes = $this->getExpectedMimeTypes($extension);

        if (!empty($expectedMimes) && !in_array($mimeType, $expectedMimes)) {
            $this->logSecurityViolation($request, 'mime_extension_mismatch', [
                'field' => $fieldName,
                'extension' => $extension,
                'mime_type' => $mimeType,
                'expected_mimes' => $expectedMimes,
                'filename' => $file->getClientOriginalName()
            ]);

            abort(400, "File content does not match extension: .{$extension}");
        }
    }

    /**
     * Get expected MIME types for a file extension
     */
    protected function getExpectedMimeTypes(string $extension): array
    {
        $mimeMap = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain'],
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed'],
            '7z' => ['application/x-7z-compressed']
        ];

        return $mimeMap[$extension] ?? [];
    }

    /**
     * Validate file content for malicious signatures
     */
    protected function validateFileContent(UploadedFile $file, string $fieldName, Request $request): void
    {
        $content = file_get_contents($file->getPathname());
        
        if ($content === false) {
            abort(400, "Unable to read file content");
        }

        // Check for malicious signatures
        foreach ($this->maliciousSignatures as $signature) {
            if (str_contains($content, $signature)) {
                $this->logSecurityViolation($request, 'malicious_file_signature', [
                    'field' => $fieldName,
                    'signature' => $signature,
                    'filename' => $file->getClientOriginalName()
                ]);

                abort(400, "File contains potentially malicious content");
            }
        }

        // Additional content validation based on file type
        $this->validateSpecificFileContent($file, $content, $fieldName, $request);
    }

    /**
     * Validate specific file content based on type
     */
    protected function validateSpecificFileContent(UploadedFile $file, string $content, string $fieldName, Request $request): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        switch ($extension) {
            case 'svg':
                $this->validateSvgContent($content, $fieldName, $request);
                break;
            case 'pdf':
                $this->validatePdfContent($content, $fieldName, $request);
                break;
            case 'csv':
                $this->validateCsvContent($content, $fieldName, $request);
                break;
        }
    }

    /**
     * Validate SVG content for XSS
     */
    protected function validateSvgContent(string $content, string $fieldName, Request $request): void
    {
        $dangerousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/on\w+\s*=/i',
            '/javascript\s*:/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->logSecurityViolation($request, 'malicious_svg_content', [
                    'field' => $fieldName,
                    'pattern' => $pattern
                ]);

                abort(400, "SVG file contains potentially malicious content");
            }
        }
    }

    /**
     * Validate PDF content
     */
    protected function validatePdfContent(string $content, string $fieldName, Request $request): void
    {
        // Check for PDF header
        if (!str_starts_with($content, '%PDF-')) {
            abort(400, "Invalid PDF file format");
        }

        // Check for embedded JavaScript
        if (str_contains($content, '/JavaScript') || str_contains($content, '/JS')) {
            $this->logSecurityViolation($request, 'pdf_with_javascript', [
                'field' => $fieldName
            ]);

            abort(400, "PDF files with JavaScript are not allowed");
        }
    }

    /**
     * Validate CSV content
     */
    protected function validateCsvContent(string $content, string $fieldName, Request $request): void
    {
        // Check for formula injection
        $dangerousStarters = ['=', '+', '-', '@'];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $cells = str_getcsv($line);
            foreach ($cells as $cell) {
                $cell = trim($cell);
                if (!empty($cell) && in_array($cell[0], $dangerousStarters)) {
                    $this->logSecurityViolation($request, 'csv_formula_injection', [
                        'field' => $fieldName,
                        'line' => $lineNumber + 1,
                        'cell_content' => substr($cell, 0, 50)
                    ]);

                    abort(400, "CSV file contains potentially dangerous formulas");
                }
            }
        }
    }

    /**
     * Validate file name
     */
    protected function validateFileName(UploadedFile $file, string $fieldName, Request $request): void
    {
        $filename = $file->getClientOriginalName();

        // Check for path traversal attempts
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            $this->logSecurityViolation($request, 'path_traversal_attempt', [
                'field' => $fieldName,
                'filename' => $filename
            ]);

            abort(400, "Invalid file name");
        }

        // Check for null bytes
        if (str_contains($filename, "\0")) {
            $this->logSecurityViolation($request, 'null_byte_in_filename', [
                'field' => $fieldName,
                'filename' => $filename
            ]);

            abort(400, "Invalid file name");
        }

        // Check filename length
        if (strlen($filename) > 255) {
            abort(400, "File name too long");
        }
    }

    /**
     * Log security violation
     */
    protected function logSecurityViolation(Request $request, string $violationType, array $details): void
    {
        Log::warning('File upload security violation', [
            'violation_type' => $violationType,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'details' => $details,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Configure allowed file types for specific routes
     */
    public function configureForRoute(string $route, array $allowedTypes): self
    {
        // This method can be used to customize allowed file types per route
        return $this;
    }
}