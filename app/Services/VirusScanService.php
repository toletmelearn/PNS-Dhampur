<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Support\Constants;
use Exception;

class VirusScanService
{
    /**
     * Known malicious file signatures (magic bytes)
     */
    private const MALICIOUS_SIGNATURES = [
        // PE executable signatures
        '4d5a',     // MZ (DOS/Windows executable)
        '504b0304', // ZIP file (could contain malware)
        '504b0506', // ZIP file end
        '504b0708', // ZIP file
        '377abcaf271c', // 7-Zip
        '526172211a0700', // RAR archive
        // Script signatures
        '3c3f706870', // <?php
        '3c736372697074', // <script
        '6a617661736372697074', // javascript
        // Executable formats
        '7f454c46', // ELF (Linux executable)
        'cafebabe', // Java class file
        'feedface', // Mach-O binary (macOS)
        'cefaedfe', // Mach-O binary (macOS)
    ];

    /**
     * Suspicious file extensions
     */
    private const SUSPICIOUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'vbe', 'js', 'jse',
        'wsf', 'wsh', 'msi', 'msp', 'hta', 'cpl', 'jar', 'app', 'deb', 'rpm',
        'dmg', 'pkg', 'run', 'bin', 'sh', 'bash', 'ps1', 'psm1', 'psd1'
    ];

    /**
     * Maximum file size for scanning (in bytes)
     */
    private const MAX_SCAN_SIZE = Constants::VIRUS_SCAN_MAX_SIZE_MB * 1024 * 1024; // 100MB

    /**
     * Scan uploaded file for viruses and malware
     *
     * @param UploadedFile $file
     * @return array
     */
    public function scanFile(UploadedFile $file): array
    {
        try {
            // Basic file validation
            $basicCheck = $this->performBasicSecurityCheck($file);
            if (!$basicCheck['safe']) {
                return $basicCheck;
            }

            // File signature analysis
            $signatureCheck = $this->analyzeFileSignature($file);
            if (!$signatureCheck['safe']) {
                return $signatureCheck;
            }

            // Content analysis
            $contentCheck = $this->analyzeFileContent($file);
            if (!$contentCheck['safe']) {
                return $contentCheck;
            }

            // ClamAV scan (if available)
            $clamavCheck = $this->scanWithClamAV($file);
            if (!$clamavCheck['safe']) {
                return $clamavCheck;
            }

            // All checks passed
            return [
                'safe' => true,
                'message' => 'File passed all security checks',
                'scan_results' => [
                    'basic_check' => 'PASS',
                    'signature_check' => 'PASS',
                    'content_check' => 'PASS',
                    'clamav_check' => $clamavCheck['available'] ? 'PASS' : 'SKIPPED'
                ]
            ];

        } catch (Exception $e) {
            Log::error('Virus scan failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return [
                'safe' => false,
                'message' => 'Security scan failed: ' . $e->getMessage(),
                'threat_type' => 'SCAN_ERROR'
            ];
        }
    }

    /**
     * Perform basic security checks
     *
     * @param UploadedFile $file
     * @return array
     */
    private function performBasicSecurityCheck(UploadedFile $file): array
    {
        // Check file size
        if ($file->getSize() > self::MAX_SCAN_SIZE) {
            return [
                'safe' => false,
                'message' => 'File too large for security scanning',
                'threat_type' => 'SIZE_LIMIT_EXCEEDED'
            ];
        }

        // Check for suspicious extensions
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::SUSPICIOUS_EXTENSIONS)) {
            return [
                'safe' => false,
                'message' => 'Potentially dangerous file type detected',
                'threat_type' => 'SUSPICIOUS_EXTENSION',
                'details' => "Extension: {$extension}"
            ];
        }

        // Check for double extensions (e.g., file.pdf.exe)
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.(exe|bat|cmd|scr|pif|com|vbs|js)$/i', $filename)) {
            return [
                'safe' => false,
                'message' => 'Executable file detected in filename',
                'threat_type' => 'EXECUTABLE_DETECTED'
            ];
        }

        // Check for null bytes in filename
        if (strpos($filename, "\0") !== false) {
            return [
                'safe' => false,
                'message' => 'Null byte detected in filename',
                'threat_type' => 'NULL_BYTE_INJECTION'
            ];
        }

        return ['safe' => true];
    }

    /**
     * Analyze file signature (magic bytes)
     *
     * @param UploadedFile $file
     * @return array
     */
    private function analyzeFileSignature(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return [
                'safe' => false,
                'message' => 'Unable to read file for signature analysis',
                'threat_type' => 'READ_ERROR'
            ];
        }

        // Read first bytes for signature analysis
        $header = fread($handle, Constants::VIRUS_SCAN_HEADER_BYTES);
        fclose($handle);

        $headerHex = bin2hex($header);

        // Check against known malicious signatures
        foreach (self::MALICIOUS_SIGNATURES as $signature) {
            if (strpos($headerHex, $signature) === Constants::ZERO_COUNT) {
                return [
                    'safe' => false,
                    'message' => 'Malicious file signature detected',
                    'threat_type' => 'MALICIOUS_SIGNATURE',
                    'details' => "Signature: {$signature}"
                ];
            }
        }

        // Validate MIME type against file signature
        $expectedMime = $this->getExpectedMimeFromSignature($headerHex);
        $actualMime = $file->getMimeType();

        if ($expectedMime && $expectedMime !== $actualMime) {
            return [
                'safe' => false,
                'message' => 'File signature does not match MIME type',
                'threat_type' => 'MIME_MISMATCH',
                'details' => "Expected: {$expectedMime}, Actual: {$actualMime}"
            ];
        }

        return ['safe' => true];
    }

    /**
     * Analyze file content for suspicious patterns
     *
     * @param UploadedFile $file
     * @return array
     */
    private function analyzeFileContent(UploadedFile $file): array
    {
        // Only scan text-based files for content analysis
        $textMimes = [
            'text/plain', 'text/html', 'text/css', 'text/javascript',
            'application/json', 'application/xml', 'text/xml'
        ];

        if (!in_array($file->getMimeType(), $textMimes)) {
            return ['safe' => true]; // Skip content analysis for binary files
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return [
                'safe' => false,
                'message' => 'Unable to read file content',
                'threat_type' => 'READ_ERROR'
            ];
        }

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/eval\s*\(/i' => 'eval() function detected',
            '/exec\s*\(/i' => 'exec() function detected',
            '/system\s*\(/i' => 'system() function detected',
            '/shell_exec\s*\(/i' => 'shell_exec() function detected',
            '/passthru\s*\(/i' => 'passthru() function detected',
            '/base64_decode\s*\(/i' => 'base64_decode() function detected',
            '/<script[^>]*>/i' => 'Script tag detected',
            '/javascript:/i' => 'JavaScript protocol detected',
            '/vbscript:/i' => 'VBScript protocol detected',
            '/on\w+\s*=/i' => 'Event handler detected',
        ];

        foreach ($suspiciousPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                return [
                    'safe' => false,
                    'message' => "Suspicious content detected: {$description}",
                    'threat_type' => 'SUSPICIOUS_CONTENT'
                ];
            }
        }

        return ['safe' => true];
    }

    /**
     * Scan file with ClamAV (if available)
     *
     * @param UploadedFile $file
     * @return array
     */
    private function scanWithClamAV(UploadedFile $file): array
    {
        // Check if ClamAV is available
        if (!$this->isClamAVAvailable()) {
            return [
                'safe' => true,
                'available' => false,
                'message' => 'ClamAV not available, skipping antivirus scan'
            ];
        }

        try {
            $filePath = escapeshellarg($file->getPathname());
            $command = "clamscan --no-summary --infected {$filePath}";
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode === Constants::CLAMAV_SUCCESS_CODE) {
                return [
                    'safe' => true,
                    'available' => true,
                    'message' => 'ClamAV scan completed - no threats detected'
                ];
            } elseif ($returnCode === Constants::CLAMAV_MALWARE_CODE) {
                return [
                    'safe' => false,
                    'available' => true,
                    'message' => 'ClamAV detected malware in file',
                    'threat_type' => 'MALWARE_DETECTED',
                    'details' => implode("\n", $output)
                ];
            } else {
                return [
                    'safe' => false,
                    'available' => true,
                    'message' => 'ClamAV scan error',
                    'threat_type' => 'SCAN_ERROR',
                    'details' => implode("\n", $output)
                ];
            }
        } catch (Exception $e) {
            Log::warning('ClamAV scan failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return [
                'safe' => true,
                'available' => false,
                'message' => 'ClamAV scan failed, proceeding without antivirus scan'
            ];
        }
    }

    /**
     * Check if ClamAV is available
     *
     * @return bool
     */
    private function isClamAVAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('clamscan --version 2>&1', $output, $returnCode);
        
        return $returnCode === Constants::CLAMAV_SUCCESS_CODE;
    }

    /**
     * Get expected MIME type from file signature
     *
     * @param string $headerHex
     * @return string|null
     */
    private function getExpectedMimeFromSignature(string $headerHex): ?string
    {
        $signatures = [
            'ffd8ff' => 'image/jpeg',
            '89504e47' => 'image/png',
            '47494638' => 'image/gif',
            '25504446' => 'application/pdf',
            '504b0304' => 'application/zip',
            'd0cf11e0' => 'application/msword',
        ];

        foreach ($signatures as $signature => $mime) {
            if (strpos($headerHex, $signature) === Constants::ZERO_COUNT) {
                return $mime;
            }
        }

        return null;
    }

    /**
     * Quarantine a suspicious file
     *
     * @param UploadedFile $file
     * @param array $scanResult
     * @return string
     */
    public function quarantineFile(UploadedFile $file, array $scanResult): string
    {
        $quarantinePath = 'quarantine/' . date('Y/m/d');
        $filename = time() . '_' . $file->getClientOriginalName();
        
        // Store file in quarantine
        $path = Storage::disk('local')->putFileAs(
            $quarantinePath,
            $file,
            $filename
        );

        // Log quarantine action
        Log::warning('File quarantined', [
            'original_name' => $file->getClientOriginalName(),
            'quarantine_path' => $path,
            'threat_type' => $scanResult['threat_type'] ?? 'UNKNOWN',
            'scan_result' => $scanResult
        ]);

        return $path;
    }
}