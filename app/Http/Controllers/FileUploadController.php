<?php

namespace App\Http\Controllers;

use App\Traits\HandlesFileUploads;
use App\Traits\SanitizesInput;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FileUploadController extends Controller
{
    use HandlesFileUploads, SanitizesInput;

    public function __construct()
    {
        $this->middleware(['auth', 'sanitize', 'file.upload.security']);
    }

    /**
     * Handle single file upload via AJAX
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            $context = $this->sanitizeInput($request->input('context', 'general'), 'alphanumeric');
            $uploadId = $this->sanitizeInput($request->input('upload_id'), 'alphanumeric');
            
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file provided'
                ], 400);
            }

            $file = $request->file('file');
            $this->validateFileUpload($request, 'file', $context);

            $options = [
                'generate_thumbnail' => $request->boolean('generate_thumbnail', false),
                'resize_image' => $request->boolean('resize_image', false),
                'max_width' => $request->integer('max_width', 1920),
                'max_height' => $request->integer('max_height', 1080),
                'quality' => $request->integer('quality', 85)
            ];

            $result = $this->handleFileUploadWithProgress($file, $context, $options, $uploadId);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('File upload error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle multiple file uploads via AJAX
     */
    public function uploadMultipleFiles(Request $request): JsonResponse
    {
        try {
            $context = $this->sanitizeInput($request->input('context', 'general'), 'alphanumeric');
            
            if (!$request->hasFile('files')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files provided'
                ], 400);
            }

            $this->validateMultipleFileUploads($request, 'files', $context);

            $options = [
                'generate_thumbnail' => $request->boolean('generate_thumbnail', false),
                'resize_image' => $request->boolean('resize_image', false),
                'max_width' => $request->integer('max_width', 1920),
                'max_height' => $request->integer('max_height', 1080),
                'quality' => $request->integer('quality', 85)
            ];

            $files = $request->file('files');
            $results = $this->handleMultipleFileUploads($files, $context, $options);

            return response()->json([
                'success' => true,
                'message' => count($files) . ' files uploaded successfully',
                'data' => $results
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Multiple file upload error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upload progress
     */
    public function getUploadProgress(Request $request, string $uploadId): JsonResponse
    {
        try {
            $uploadId = $this->sanitizeInput($uploadId, 'alphanumeric');
            $progress = $this->getUploadProgress($uploadId);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get upload progress'
            ], 500);
        }
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile(Request $request): JsonResponse
    {
        try {
            $filePath = $this->sanitizeInput($request->input('file_path'), 'filename');
            
            if (empty($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File path is required'
                ], 400);
            }

            $deleted = $this->deleteUploadedFile($filePath);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete file'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('File deletion error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file'
            ], 500);
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(Request $request): JsonResponse
    {
        try {
            $filePath = $this->sanitizeInput($request->input('file_path'), 'filename');
            
            if (empty($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File path is required'
                ], 400);
            }

            $fileInfo = $this->getFileInfo($filePath);

            if ($fileInfo) {
                return response()->json([
                    'success' => true,
                    'data' => $fileInfo
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file information'
            ], 500);
        }
    }

    /**
     * Generate temporary download URL
     */
    public function generateDownloadUrl(Request $request): JsonResponse
    {
        try {
            $filePath = $this->sanitizeInput($request->input('file_path'), 'filename');
            $expiresInMinutes = $request->integer('expires_in_minutes', 60);
            
            if (empty($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File path is required'
                ], 400);
            }

            if (!$this->fileExists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $url = $this->getSecureFileUrl($filePath, $expiresInMinutes);

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $url,
                    'expires_at' => now()->addMinutes($expiresInMinutes)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate download URL'
            ], 500);
        }
    }

    /**
     * Get allowed file types for a context
     */
    public function getAllowedFileTypes(Request $request): JsonResponse
    {
        try {
            $context = $this->sanitizeInput($request->input('context', 'general'), 'alphanumeric');
            $config = config("file_uploads.contexts.{$context}", config('file_uploads.contexts.general'));

            return response()->json([
                'success' => true,
                'data' => [
                    'context' => $context,
                    'allowed_extensions' => $config['allowed_extensions'] ?? [],
                    'allowed_mime_types' => $config['allowed_mime_types'] ?? [],
                    'max_size' => $config['max_size'] ?? 2048,
                    'max_files' => $config['max_files'] ?? 5
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file type information'
            ], 500);
        }
    }

    /**
     * Validate file before upload (pre-upload validation)
     */
    public function validateFile(Request $request): JsonResponse
    {
        try {
            $context = $this->sanitizeInput($request->input('context', 'general'), 'alphanumeric');
            
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file provided'
                ], 400);
            }

            $this->validateFileUpload($request, 'file', $context);

            return response()->json([
                'success' => true,
                'message' => 'File validation passed'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ], 500);
        }
    }
}