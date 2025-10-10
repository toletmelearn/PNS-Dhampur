<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FilePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if request has file uploads
        if ($request->hasFile('file') || $request->hasFile('files') || $this->hasAnyFiles($request)) {
            $this->validateFilePermissions($request);
        }

        $response = $next($request);

        // Set secure file permissions after file operations
        if ($response->getStatusCode() === 200 && $this->isFileUploadRoute($request)) {
            $this->setSecureFilePermissions();
        }

        return $response;
    }

    /**
     * Check if request has any file uploads
     */
    private function hasAnyFiles(Request $request): bool
    {
        foreach ($request->allFiles() as $file) {
            if ($file) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate file permissions and security
     */
    private function validateFilePermissions(Request $request): void
    {
        $files = $request->allFiles();
        $securityConfig = config('security.file_upload', []);
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    $this->validateSingleFile($singleFile, $securityConfig);
                }
            } else {
                $this->validateSingleFile($file, $securityConfig);
            }
        }
    }

    /**
     * Validate a single uploaded file
     */
    private function validateSingleFile($file, array $securityConfig): void
    {
        if (!$file || !$file->isValid()) {
            return;
        }

        // Check file size
        $maxSize = $securityConfig['max_file_size'] ?? 10485760; // 10MB default
        if ($file->getSize() > $maxSize) {
            Log::warning('File upload blocked: Size exceeds limit', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'max_size' => $maxSize,
                'ip' => request()->ip()
            ]);
            
            abort(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'File size exceeds maximum allowed size.');
        }

        // Check file type
        $allowedTypes = $securityConfig['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            Log::warning('File upload blocked: Invalid file type', [
                'file_name' => $file->getClientOriginalName(),
                'file_extension' => $extension,
                'allowed_types' => $allowedTypes,
                'ip' => request()->ip()
            ]);
            
            abort(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'File type not allowed.');
        }

        // Check for malicious content
        $this->scanForMaliciousContent($file);
    }

    /**
     * Scan file for potentially malicious content
     */
    private function scanForMaliciousContent($file): void
    {
        $content = file_get_contents($file->getRealPath());
        
        // Check for common malicious patterns
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/base64_decode\s*\(/i'
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                Log::critical('Malicious file upload attempt detected', [
                    'file_name' => $file->getClientOriginalName(),
                    'pattern_matched' => $pattern,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
                
                abort(Response::HTTP_FORBIDDEN, 'File contains potentially malicious content.');
            }
        }
    }

    /**
     * Check if current route is a file upload route
     */
    private function isFileUploadRoute(Request $request): bool
    {
        $uploadRoutes = [
            'upload', 'import', 'avatar', 'document', 'attachment', 'media'
        ];

        $routeName = $request->route()?->getName() ?? '';
        $path = $request->path();

        foreach ($uploadRoutes as $route) {
            if (str_contains($routeName, $route) || str_contains($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set secure file permissions for uploaded files
     */
    private function setSecureFilePermissions(): void
    {
        $uploadPaths = [
            storage_path('app/public/uploads'),
            storage_path('app/public/documents'),
            storage_path('app/public/avatars'),
            public_path('uploads')
        ];

        foreach ($uploadPaths as $path) {
            if (is_dir($path)) {
                $this->setDirectoryPermissions($path);
            }
        }
    }

    /**
     * Set secure permissions for a directory and its contents
     */
    private function setDirectoryPermissions(string $path): void
    {
        try {
            // Set directory permissions to 755 (owner: rwx, group: rx, others: rx)
            chmod($path, 0755);

            // Set file permissions to 644 (owner: rw, group: r, others: r)
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    chmod($file->getPathname(), 0644);
                } elseif ($file->isDir()) {
                    chmod($file->getPathname(), 0755);
                }
            }

            Log::info('File permissions updated', ['path' => $path]);
        } catch (\Exception $e) {
            Log::error('Failed to set file permissions', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }
    }
}