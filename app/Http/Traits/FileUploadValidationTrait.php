<?php

namespace App\Http\Traits;

use App\Services\VirusScanService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

trait FileUploadValidationTrait
{
    /**
     * Enhanced file validation with virus scanning
     *
     * @param UploadedFile $file
     * @param string $type
     * @return array
     */
    protected function validateFileWithSecurity(UploadedFile $file, string $type = 'document'): array
    {
        // Basic validation
        $basicValidation = $this->performBasicFileValidation($file, $type);
        if (!$basicValidation['valid']) {
            return $basicValidation;
        }

        // Security validation
        if (config('fileupload.security_settings.enable_virus_scan', true)) {
            $virusScanService = new VirusScanService();
            $scanResult = $virusScanService->scanFile($file);
            
            if (!$scanResult['safe']) {
                // Quarantine file if configured
                if (config('fileupload.security_settings.quarantine_suspicious_files', true)) {
                    $virusScanService->quarantineFile($file, $scanResult);
                }

                Log::warning('File upload blocked by security scan', [
                    'filename' => $file->getClientOriginalName(),
                    'scan_result' => $scanResult
                ]);

                return [
                    'valid' => false,
                    'error' => 'File failed security validation: ' . $scanResult['message'],
                    'threat_type' => $scanResult['threat_type'] ?? 'SECURITY_VIOLATION'
                ];
            }
        }

        return ['valid' => true, 'message' => 'File passed all security checks'];
    }

    /**
     * Perform basic file validation
     *
     * @param UploadedFile $file
     * @param string $type
     * @return array
     */
    protected function performBasicFileValidation(UploadedFile $file, string $type): array
    {
        // Check file size
        $maxSize = config("fileupload.max_file_sizes.{$type}", config('fileupload.max_file_sizes.default'));
        if ($file->getSize() > ($maxSize * 1024)) {
            return [
                'valid' => false,
                'error' => "File size exceeds maximum limit of {$maxSize}KB"
            ];
        }

        // Check file extension
        $allowedExtensions = explode(',', config("fileupload.allowed_file_types.{$type}.mimes", ''));
        $fileExtension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return [
                'valid' => false,
                'error' => "File type '{$fileExtension}' is not allowed. Allowed types: " . implode(', ', $allowedExtensions)
            ];
        }

        // Check against blocked extensions
        $blockedExtensions = config('fileupload.blocked_extensions', []);
        if (in_array($fileExtension, $blockedExtensions)) {
            return [
                'valid' => false,
                'error' => "File type '{$fileExtension}' is blocked for security reasons"
            ];
        }

        return ['valid' => true];
    }
    /**
     * Get CSV file upload validation rules
     *
     * @param int $maxSizeKB Maximum file size in KB (default: 10MB)
     * @return array
     */
    protected function getCsvFileValidationRules(int $maxSizeKB = 10240): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                "max:{$maxSizeKB}"
            ]
        ];
    }

    /**
     * Get document file upload validation rules
     *
     * @param int $maxSizeKB Maximum file size in KB (default: 5MB)
     * @param array $allowedMimes Additional allowed MIME types
     * @return array
     */
    protected function getDocumentFileValidationRules(int $maxSizeKB = 5120, array $allowedMimes = []): array
    {
        $defaultMimes = ['pdf', 'jpg', 'jpeg', 'png'];
        $mimes = array_unique(array_merge($defaultMimes, $allowedMimes));
        
        return [
            'document_file' => [
                'required',
                'file',
                'mimes:' . implode(',', $mimes),
                "max:{$maxSizeKB}"
            ]
        ];
    }

    /**
     * Get media file upload validation rules (documents, images, videos)
     *
     * @param int $maxSizeKB Maximum file size in KB (default: 50MB)
     * @return array
     */
    protected function getMediaFileValidationRules(int $maxSizeKB = 51200): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,mp4,avi,mov,wmv,jpg,jpeg,png,gif',
                "max:{$maxSizeKB}"
            ]
        ];
    }

    /**
     * Get spreadsheet file upload validation rules
     *
     * @param int $maxSizeKB Maximum file size in KB (default: 10MB)
     * @return array
     */
    protected function getSpreadsheetFileValidationRules(int $maxSizeKB = 10240): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                "max:{$maxSizeKB}"
            ]
        ];
    }

    /**
     * Get image file upload validation rules
     *
     * @param int $maxSizeKB Maximum file size in KB (default: 2MB)
     * @param bool $required Whether the file is required
     * @return array
     */
    protected function getImageFileValidationRules(int $maxSizeKB = 2048, bool $required = true): array
    {
        $rules = [
            'file',
            'mimes:jpg,jpeg,png,gif,webp',
            "max:{$maxSizeKB}"
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return ['file' => $rules];
    }

    /**
     * Get multiple files upload validation rules
     *
     * @param array $allowedMimes Allowed MIME types
     * @param int $maxSizeKB Maximum file size in KB per file
     * @param int $maxFiles Maximum number of files
     * @return array
     */
    protected function getMultipleFilesValidationRules(array $allowedMimes, int $maxSizeKB = 5120, int $maxFiles = 10): array
    {
        return [
            'files' => [
                'required',
                'array',
                "max:{$maxFiles}"
            ],
            'files.*' => [
                'required',
                'file',
                'mimes:' . implode(',', $allowedMimes),
                "max:{$maxSizeKB}"
            ]
        ];
    }

    /**
     * Get file upload validation messages
     *
     * @return array
     */
    protected function getFileUploadValidationMessages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.mimes' => 'The file must be of the specified type.',
            'file.max' => 'The file size cannot exceed the maximum allowed limit.',
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.file' => 'The uploaded item must be a valid file.',
            'csv_file.mimes' => 'The file must be a CSV or TXT file.',
            'csv_file.max' => 'The CSV file size cannot exceed the maximum allowed limit.',
            'document_file.required' => 'Please select a document file to upload.',
            'document_file.file' => 'The uploaded item must be a valid file.',
            'document_file.mimes' => 'The document must be a PDF, JPG, JPEG, or PNG file.',
            'document_file.max' => 'The document file size cannot exceed the maximum allowed limit.',
            'files.required' => 'Please select at least one file to upload.',
            'files.array' => 'Files must be provided as an array.',
            'files.max' => 'You cannot upload more than the maximum allowed number of files.',
            'files.*.required' => 'Each file is required.',
            'files.*.file' => 'Each uploaded item must be a valid file.',
            'files.*.mimes' => 'Each file must be of the specified type.',
            'files.*.max' => 'Each file size cannot exceed the maximum allowed limit.'
        ];
    }

    /**
     * Get file signature validation closure for enhanced security
     *
     * @param array $validSignatures Array of valid file signatures (hex)
     * @return \Closure
     */
    protected function getFileSignatureValidation(array $validSignatures): \Closure
    {
        return function ($attribute, $value, $fail) use ($validSignatures) {
            if (!$value || !$value->isValid()) {
                return;
            }

            $fileHandle = fopen($value->getPathname(), 'rb');
            if (!$fileHandle) {
                $fail('Unable to read the uploaded file.');
                return;
            }

            $fileSignature = bin2hex(fread($fileHandle, 8));
            fclose($fileHandle);

            $isValidSignature = false;
            foreach ($validSignatures as $validSignature) {
                if (strpos($fileSignature, $validSignature) === 0) {
                    $isValidSignature = true;
                    break;
                }
            }

            if (!$isValidSignature) {
                $fail('The file appears to be corrupted or is not a valid document/image file.');
            }
        };
    }

    /**
     * Get common file signatures for validation
     *
     * @return array
     */
    protected function getCommonFileSignatures(): array
    {
        return [
            // PDF
            '25504446' => 'pdf',
            // JPEG
            'ffd8ffe0' => 'jpg',
            'ffd8ffe1' => 'jpg',
            'ffd8ffe2' => 'jpg',
            'ffd8ffe3' => 'jpg',
            'ffd8ffe8' => 'jpg',
            // PNG
            '89504e47' => 'png',
            // GIF
            '47494638' => 'gif',
            // WebP
            '52494646' => 'webp',
            // DOC
            'd0cf11e0' => 'doc',
            // DOCX/XLSX (ZIP-based)
            '504b0304' => 'docx',
            '504b0506' => 'docx',
            '504b0708' => 'docx',
        ];
    }
}