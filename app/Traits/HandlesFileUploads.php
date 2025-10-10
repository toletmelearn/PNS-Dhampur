<?php

namespace App\Traits;

use App\Services\FileUploadService;
use App\Rules\EnhancedFileValidation;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait HandlesFileUploads
{
    /**
     * Handle file upload with validation and security checks
     */
    public function handleFileUpload(
        UploadedFile $file,
        string $context = 'general',
        array $options = []
    ): array {
        $fileUploadService = app(FileUploadService::class);
        
        try {
            return $fileUploadService->uploadFile($file, $context, $options);
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'context' => $context,
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
            
            throw new ValidationException(validator([], []), [
                'file' => ['File upload failed: ' . $e->getMessage()]
            ]);
        }
    }

    /**
     * Handle multiple file uploads
     */
    public function handleMultipleFileUploads(
        array $files,
        string $context = 'general',
        array $options = []
    ): array {
        $fileUploadService = app(FileUploadService::class);
        
        try {
            return $fileUploadService->uploadMultipleFiles($files, $context, $options);
        } catch (\Exception $e) {
            Log::error('Multiple file upload failed', [
                'context' => $context,
                'file_count' => count($files),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
            
            throw new ValidationException(validator([], []), [
                'files' => ['File upload failed: ' . $e->getMessage()]
            ]);
        }
    }

    /**
     * Validate file upload request
     */
    public function validateFileUpload(Request $request, string $field, string $context = 'general'): void
    {
        $config = config("file_uploads.contexts.{$context}", config('file_uploads.contexts.general'));
        
        $rules = [
            $field => [
                'required',
                'file',
                new EnhancedFileValidation(
                    $config['allowed_mime_types'] ?? [],
                    $config['max_size'] ?? 2048,
                    $config['allowed_extensions'] ?? [],
                    $config['scan_for_viruses'] ?? true,
                    $config['check_content'] ?? true
                )
            ]
        ];

        $request->validate($rules);
    }

    /**
     * Validate multiple file uploads
     */
    public function validateMultipleFileUploads(Request $request, string $field, string $context = 'general'): void
    {
        $config = config("file_uploads.contexts.{$context}", config('file_uploads.contexts.general'));
        
        $rules = [
            $field => 'required|array|max:' . ($config['max_files'] ?? 5),
            $field . '.*' => [
                'file',
                new EnhancedFileValidation(
                    $config['allowed_mime_types'] ?? [],
                    $config['max_size'] ?? 2048,
                    $config['allowed_extensions'] ?? [],
                    $config['scan_for_viruses'] ?? true,
                    $config['check_content'] ?? true
                )
            ]
        ];

        $request->validate($rules);
    }

    /**
     * Delete uploaded file
     */
    public function deleteUploadedFile(string $filePath): bool
    {
        $fileUploadService = app(FileUploadService::class);
        
        try {
            return $fileUploadService->deleteFile($filePath);
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return false;
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $filePath): ?array
    {
        $fileUploadService = app(FileUploadService::class);
        
        try {
            return $fileUploadService->getFileInfo($filePath);
        } catch (\Exception $e) {
            Log::error('Failed to get file info', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Generate secure file URL
     */
    public function getSecureFileUrl(string $filePath, int $expiresInMinutes = 60): string
    {
        return Storage::temporaryUrl($filePath, now()->addMinutes($expiresInMinutes));
    }

    /**
     * Check if file exists and is accessible
     */
    public function fileExists(string $filePath): bool
    {
        return Storage::exists($filePath);
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanReadableFileSize(string $filePath): string
    {
        if (!$this->fileExists($filePath)) {
            return 'File not found';
        }

        $bytes = Storage::size($filePath);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate file upload context
     */
    protected function validateUploadContext(string $context): void
    {
        $allowedContexts = array_keys(config('file_uploads.contexts', []));
        
        if (!in_array($context, $allowedContexts)) {
            throw new \InvalidArgumentException("Invalid upload context: {$context}");
        }
    }

    /**
     * Get upload progress (for AJAX uploads)
     */
    public function getUploadProgress(string $uploadId): array
    {
        $cacheKey = "upload_progress_{$uploadId}";
        $progress = cache()->get($cacheKey, [
            'status' => 'not_found',
            'progress' => 0,
            'message' => 'Upload not found'
        ]);

        return $progress;
    }

    /**
     * Set upload progress (for AJAX uploads)
     */
    protected function setUploadProgress(string $uploadId, array $data): void
    {
        $cacheKey = "upload_progress_{$uploadId}";
        cache()->put($cacheKey, $data, now()->addMinutes(30));
    }

    /**
     * Handle file upload with progress tracking
     */
    public function handleFileUploadWithProgress(
        UploadedFile $file,
        string $context = 'general',
        array $options = [],
        ?string $uploadId = null
    ): array {
        $uploadId = $uploadId ?: uniqid('upload_', true);
        
        try {
            $this->setUploadProgress($uploadId, [
                'status' => 'validating',
                'progress' => 10,
                'message' => 'Validating file...'
            ]);

            // Validate file
            $this->validateUploadContext($context);
            
            $this->setUploadProgress($uploadId, [
                'status' => 'uploading',
                'progress' => 50,
                'message' => 'Uploading file...'
            ]);

            // Upload file
            $result = $this->handleFileUpload($file, $context, $options);
            
            $this->setUploadProgress($uploadId, [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Upload completed successfully',
                'file_info' => $result
            ]);

            return array_merge($result, ['upload_id' => $uploadId]);
            
        } catch (\Exception $e) {
            $this->setUploadProgress($uploadId, [
                'status' => 'failed',
                'progress' => 0,
                'message' => $e->getMessage(),
                'error' => true
            ]);
            
            throw $e;
        }
    }
}