<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class SafeFileValidation implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        // Check file size (5MB limit)
        if ($value->getSize() > 5242880) { // 5MB in bytes
            return false;
        }

        // Check MIME type
        $mimeType = $value->getMimeType();
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return false;
        }

        // Check file signature (magic bytes) for additional security
        $fileContent = file_get_contents($value->getPathname());
        $fileSignature = bin2hex(substr($fileContent, 0, 4));
        
        $validSignatures = [
            '25504446', // PDF
            'd0cf11e0', // DOC/DOCX (OLE2)
            '504b0304', // DOCX (ZIP-based)
            'ffd8ffe0', // JPEG
            'ffd8ffe1', // JPEG
            'ffd8ffe2', // JPEG
            'ffd8ffe3', // JPEG
            'ffd8ffe8', // JPEG
            'ffd8ffdb', // JPEG
            '89504e47', // PNG
        ];

        if (!in_array($fileSignature, $validSignatures)) {
            return false;
        }

        // Check file extension matches MIME type
        $extension = strtolower($value->getClientOriginalExtension());
        $validCombinations = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png']
        ];

        if (!isset($validCombinations[$extension]) || !in_array($mimeType, $validCombinations[$extension])) {
            return false;
        }

        // Mock malware scanning (in production, integrate with actual virus scanning service)
        if (!$this->scanFileForThreats($value)) {
            return false;
        }

        return true;
    }

    /**
     * Mock malware scanning function.
     * In production, integrate with actual virus scanning service like ClamAV, VirusTotal, etc.
     *
     * @param  UploadedFile  $file
     * @return bool
     */
    protected function scanFileForThreats(UploadedFile $file)
    {
        // Mock implementation - always returns true for demo purposes
        // In production, implement actual virus scanning:
        // - Integrate with ClamAV using exec('clamscan --stdout ' . escapeshellarg($file->getPathname()))
        // - Use VirusTotal API
        // - Implement custom heuristic scanning
        
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file is not valid or may contain security threats. Please upload a valid PDF, DOC, DOCX, JPG, JPEG, or PNG file (max 5MB).';
    }
}