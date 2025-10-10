<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Rules\EnhancedFileValidation;
use Exception;

class FileUploadService
{
    protected array $config;
    protected array $allowedTypes;
    protected array $securitySettings;

    public function __construct()
    {
        $this->config = config('filesystems.uploads', []);
        $this->allowedTypes = config('app.allowed_file_types', []);
        $this->securitySettings = config('security.file_uploads', []);
    }

    /**
     * Upload a file with comprehensive security checks.
     */
    public function uploadFile(
        UploadedFile $file,
        string $directory = 'uploads',
        array $options = []
    ): array {
        try {
            // Validate the file
            $this->validateFile($file, $options);

            // Generate secure filename
            $filename = $this->generateSecureFilename($file, $options);

            // Create directory if it doesn't exist
            $fullPath = $this->ensureDirectoryExists($directory);

            // Scan for viruses if enabled
            if ($this->shouldScanForViruses($options)) {
                $this->scanForViruses($file);
            }

            // Process the file (resize images, compress, etc.)
            $processedFile = $this->processFile($file, $options);

            // Store the file
            $storedPath = $this->storeFile($processedFile, $directory, $filename);

            // Create file record
            $fileRecord = $this->createFileRecord($file, $storedPath, $options);

            // Log successful upload
            $this->logFileUpload($file, $storedPath, 'success');

            return [
                'success' => true,
                'path' => $storedPath,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'record' => $fileRecord,
            ];

        } catch (Exception $e) {
            $this->logFileUpload($file, null, 'error', $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultipleFiles(
        array $files,
        string $directory = 'uploads',
        array $options = []
    ): array {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $result = $this->uploadFile($file, $directory, $options);
                $results[$index] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $results[$index] = [
                    'success' => false,
                    'error' => 'Invalid file upload',
                ];
                $errorCount++;
            }
        }

        return [
            'results' => $results,
            'summary' => [
                'total' => count($files),
                'success' => $successCount,
                'errors' => $errorCount,
            ],
        ];
    }

    /**
     * Validate uploaded file.
     */
    protected function validateFile(UploadedFile $file, array $options = []): void
    {
        // Get validation rules based on file type or options
        $fileType = $options['type'] ?? $this->detectFileType($file);
        $validationRule = $this->getValidationRule($fileType, $options);

        // Validate using the enhanced file validation rule
        if (!$validationRule->passes('file', $file)) {
            throw new Exception($validationRule->message(), 422);
        }

        // Additional custom validations
        $this->performCustomValidations($file, $options);
    }

    /**
     * Get validation rule based on file type.
     */
    protected function getValidationRule(string $fileType, array $options = []): EnhancedFileValidation
    {
        $maxSize = $options['max_size'] ?? $this->getMaxSizeForType($fileType);

        switch ($fileType) {
            case 'image':
                return EnhancedFileValidation::images($maxSize);
            
            case 'document':
                return EnhancedFileValidation::documents($maxSize);
            
            case 'student_document':
                return new EnhancedFileValidation(
                    [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    $maxSize,
                    ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
                    true, // scan for viruses
                    true  // check file content
                );
            
            case 'teacher_document':
                return new EnhancedFileValidation(
                    [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain'
                    ],
                    $maxSize,
                    ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'],
                    true, // scan for viruses
                    true  // check file content
                );
            
            case 'profile_photo':
                return new EnhancedFileValidation(
                    ['image/jpeg', 'image/png'],
                    2097152, // 2MB for profile photos
                    ['jpg', 'jpeg', 'png'],
                    true, // scan for viruses
                    true  // check file content
                );
            
            default:
                return new EnhancedFileValidation(
                    $this->allowedTypes['mime_types'] ?? [],
                    $maxSize,
                    $this->allowedTypes['extensions'] ?? [],
                    true,
                    true
                );
        }
    }

    /**
     * Detect file type based on context or file properties.
     */
    protected function detectFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ])) {
            return 'document';
        }
        
        return 'general';
    }

    /**
     * Get maximum file size for file type.
     */
    protected function getMaxSizeForType(string $fileType): int
    {
        $sizes = [
            'image' => 5242880,        // 5MB
            'document' => 10485760,    // 10MB
            'student_document' => 10485760,  // 10MB
            'teacher_document' => 15728640,  // 15MB
            'profile_photo' => 2097152,      // 2MB
            'general' => 5242880,      // 5MB
        ];

        return $sizes[$fileType] ?? 5242880;
    }

    /**
     * Perform custom validations.
     */
    protected function performCustomValidations(UploadedFile $file, array $options = []): void
    {
        // Check file age (for security, reject very old files that might be cached malware)
        if (isset($options['max_file_age_days'])) {
            $fileAge = time() - filemtime($file->getPathname());
            $maxAge = $options['max_file_age_days'] * 24 * 60 * 60;
            
            if ($fileAge > $maxAge) {
                throw new Exception('File is too old and may be unsafe.', 422);
            }
        }

        // Check for minimum file size (to prevent empty or corrupted files)
        $minSize = $options['min_size'] ?? 100; // 100 bytes minimum
        if ($file->getSize() < $minSize) {
            throw new Exception('File is too small or corrupted.', 422);
        }

        // Validate image dimensions if it's an image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $this->validateImageDimensions($file, $options);
        }

        // Check for duplicate files if enabled
        if ($options['check_duplicates'] ?? false) {
            $this->checkForDuplicates($file, $options);
        }
    }

    /**
     * Validate image dimensions.
     */
    protected function validateImageDimensions(UploadedFile $file, array $options = []): void
    {
        $imageInfo = getimagesize($file->getPathname());
        
        if (!$imageInfo) {
            throw new Exception('Invalid image file.', 422);
        }

        [$width, $height] = $imageInfo;

        // Check minimum dimensions
        $minWidth = $options['min_width'] ?? 0;
        $minHeight = $options['min_height'] ?? 0;
        
        if ($width < $minWidth || $height < $minHeight) {
            throw new Exception("Image must be at least {$minWidth}x{$minHeight} pixels.", 422);
        }

        // Check maximum dimensions
        $maxWidth = $options['max_width'] ?? 4000;
        $maxHeight = $options['max_height'] ?? 4000;
        
        if ($width > $maxWidth || $height > $maxHeight) {
            throw new Exception("Image cannot exceed {$maxWidth}x{$maxHeight} pixels.", 422);
        }

        // Check aspect ratio if specified
        if (isset($options['aspect_ratio'])) {
            $ratio = $width / $height;
            $expectedRatio = $options['aspect_ratio'];
            $tolerance = $options['aspect_ratio_tolerance'] ?? 0.1;
            
            if (abs($ratio - $expectedRatio) > $tolerance) {
                throw new Exception("Image aspect ratio must be approximately {$expectedRatio}:1.", 422);
            }
        }
    }

    /**
     * Check for duplicate files.
     */
    protected function checkForDuplicates(UploadedFile $file, array $options = []): void
    {
        $hash = hash_file('sha256', $file->getPathname());
        
        // Check in database or file system for existing files with same hash
        // This is a placeholder - implement based on your file tracking system
        $existingFile = $this->findFileByHash($hash);
        
        if ($existingFile && !($options['allow_duplicates'] ?? false)) {
            throw new Exception('This file already exists in the system.', 422);
        }
    }

    /**
     * Generate secure filename.
     */
    protected function generateSecureFilename(UploadedFile $file, array $options = []): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Use custom filename if provided
        if (isset($options['filename'])) {
            $basename = pathinfo($options['filename'], PATHINFO_FILENAME);
            return $this->sanitizeFilename($basename) . '.' . $extension;
        }

        // Generate secure random filename
        $prefix = $options['prefix'] ?? '';
        $suffix = $options['suffix'] ?? '';
        
        $randomName = Str::random(32);
        $timestamp = time();
        
        return $prefix . $timestamp . '_' . $randomName . $suffix . '.' . $extension;
    }

    /**
     * Sanitize filename to remove dangerous characters.
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Trim underscores from start and end
        $filename = trim($filename, '_');
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'file_' . time();
        }
        
        // Limit filename length
        return substr($filename, 0, 100);
    }

    /**
     * Ensure directory exists and is secure.
     */
    protected function ensureDirectoryExists(string $directory): string
    {
        $fullPath = storage_path('app/public/' . $directory);
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
            
            // Create .htaccess file to prevent direct access to certain file types
            $htaccessContent = "
# Prevent execution of PHP files
<Files *.php>
    Order Deny,Allow
    Deny from all
</Files>

# Prevent access to sensitive file types
<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Set proper MIME types
<IfModule mod_mime.c>
    AddType image/jpeg .jpg .jpeg
    AddType image/png .png
    AddType image/gif .gif
    AddType application/pdf .pdf
</IfModule>
";
            file_put_contents($fullPath . '/.htaccess', $htaccessContent);
        }
        
        return $fullPath;
    }

    /**
     * Check if virus scanning should be performed.
     */
    protected function shouldScanForViruses(array $options = []): bool
    {
        return $options['scan_viruses'] ?? 
               $this->securitySettings['scan_viruses'] ?? 
               config('app.scan_uploads_for_viruses', true);
    }

    /**
     * Scan file for viruses.
     */
    protected function scanForViruses(UploadedFile $file): void
    {
        // This is a placeholder for actual virus scanning implementation
        // In production, integrate with ClamAV or similar antivirus solution
        
        $content = file_get_contents($file->getPathname());
        
        // Check for EICAR test signature
        if (strpos($content, 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*') !== false) {
            throw new Exception('Virus detected in uploaded file.', 422);
        }
        
        // Log virus scan
        Log::info('File scanned for viruses', [
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'result' => 'clean'
        ]);
    }

    /**
     * Process file (resize, compress, etc.).
     */
    protected function processFile(UploadedFile $file, array $options = []): UploadedFile
    {
        // For images, perform resizing/compression if needed
        if (str_starts_with($file->getMimeType(), 'image/') && isset($options['resize'])) {
            return $this->resizeImage($file, $options['resize']);
        }
        
        return $file;
    }

    /**
     * Resize image if needed.
     */
    protected function resizeImage(UploadedFile $file, array $resizeOptions): UploadedFile
    {
        // This is a placeholder for image resizing
        // In production, use intervention/image or similar library
        
        return $file;
    }

    /**
     * Store file in the specified directory.
     */
    protected function storeFile(UploadedFile $file, string $directory, string $filename): string
    {
        return $file->storeAs('public/' . $directory, $filename);
    }

    /**
     * Create file record in database.
     */
    protected function createFileRecord(UploadedFile $file, string $storedPath, array $options = []): ?array
    {
        // This is a placeholder for creating file records in database
        // Implement based on your file tracking requirements
        
        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename($storedPath),
            'path' => $storedPath,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'hash' => hash_file('sha256', $file->getPathname()),
            'uploaded_at' => now(),
            'uploaded_by' => auth()->id(),
        ];
    }

    /**
     * Log file upload activity.
     */
    protected function logFileUpload(UploadedFile $file, ?string $storedPath, string $status, ?string $error = null): void
    {
        Log::info('File upload attempt', [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'stored_path' => $storedPath,
            'status' => $status,
            'error' => $error,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Find file by hash (placeholder).
     */
    protected function findFileByHash(string $hash): ?array
    {
        // Implement based on your file tracking system
        return null;
    }

    /**
     * Delete uploaded file.
     */
    public function deleteFile(string $path): bool
    {
        try {
            if (Storage::exists($path)) {
                Storage::delete($path);
                
                Log::info('File deleted', [
                    'path' => $path,
                    'user_id' => auth()->id(),
                ]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get file info.
     */
    public function getFileInfo(string $path): ?array
    {
        if (!Storage::exists($path)) {
            return null;
        }

        return [
            'path' => $path,
            'size' => Storage::size($path),
            'last_modified' => Storage::lastModified($path),
            'url' => Storage::url($path),
        ];
    }
}