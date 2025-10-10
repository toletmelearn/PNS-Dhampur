<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

class HtmlPurificationService
{
    /**
     * Allowed HTML tags and their attributes
     */
    private array $allowedTags = [
        'p' => ['class', 'id'],
        'br' => [],
        'strong' => ['class'],
        'b' => ['class'],
        'em' => ['class'],
        'i' => ['class'],
        'u' => ['class'],
        'ul' => ['class'],
        'ol' => ['class', 'type'],
        'li' => ['class'],
        'h1' => ['class', 'id'],
        'h2' => ['class', 'id'],
        'h3' => ['class', 'id'],
        'h4' => ['class', 'id'],
        'h5' => ['class', 'id'],
        'h6' => ['class', 'id'],
        'blockquote' => ['class'],
        'a' => ['href', 'title', 'class', 'target'],
        'img' => ['src', 'alt', 'title', 'class', 'width', 'height'],
        'table' => ['class'],
        'thead' => ['class'],
        'tbody' => ['class'],
        'tr' => ['class'],
        'th' => ['class', 'scope'],
        'td' => ['class', 'colspan', 'rowspan'],
        'div' => ['class', 'id'],
        'span' => ['class'],
        'code' => ['class'],
        'pre' => ['class'],
    ];

    /**
     * Dangerous attributes that should always be removed
     */
    private array $dangerousAttributes = [
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset',
        'onselect', 'onkeydown', 'onkeypress', 'onkeyup',
        'style', 'javascript', 'vbscript', 'data-*'
    ];

    /**
     * Dangerous URL schemes
     */
    private array $dangerousSchemes = [
        'javascript:', 'vbscript:', 'data:', 'file:', 'ftp:'
    ];

    /**
     * XSS patterns to detect and remove
     */
    private array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>.*?<\/embed>/is',
        '/<applet[^>]*>.*?<\/applet>/is',
        '/<meta[^>]*>/i',
        '/<link[^>]*>/i',
        '/<form[^>]*>.*?<\/form>/is',
        '/<input[^>]*>/i',
        '/<textarea[^>]*>.*?<\/textarea>/is',
        '/<select[^>]*>.*?<\/select>/is',
        '/<button[^>]*>.*?<\/button>/is',
        '/expression\s*\(/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/on\w+\s*=/i',
    ];

    /**
     * Purify HTML content by removing dangerous elements and attributes
     */
    public function purify(string $html, array $options = []): string
    {
        if (empty(trim($html))) {
            return '';
        }

        try {
            // First pass: Remove obvious XSS patterns
            $html = $this->removeXssPatterns($html);

            // Second pass: Parse and clean with DOM
            $html = $this->cleanWithDom($html, $options);

            // Third pass: Additional security checks
            $html = $this->performSecurityChecks($html);

            // Fourth pass: Validate URLs and attributes
            $html = $this->validateUrls($html);

            return $html;

        } catch (\Exception $e) {
            Log::warning('HTML purification failed', [
                'error' => $e->getMessage(),
                'html_length' => strlen($html)
            ]);
            
            // Fallback: Strip all HTML tags
            return strip_tags($html);
        }
    }

    /**
     * Remove XSS patterns using regex
     */
    private function removeXssPatterns(string $html): string
    {
        foreach ($this->xssPatterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        return $html;
    }

    /**
     * Clean HTML using DOM parser
     */
    private function cleanWithDom(string $html, array $options): string
    {
        $allowedTags = $options['allowed_tags'] ?? $this->allowedTags;
        
        // Create DOM document
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        // Load HTML with proper encoding
        $html = '<?xml encoding="UTF-8">' . $html;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Clear libxml errors
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Remove disallowed tags
        $this->removeDisallowedTags($dom, $xpath, $allowedTags);

        // Clean attributes
        $this->cleanAttributes($dom, $xpath, $allowedTags);

        // Get cleaned HTML
        $cleanHtml = $dom->saveHTML($dom->documentElement);
        
        // Remove XML declaration and body tags added by DOMDocument
        $cleanHtml = preg_replace('/^<!DOCTYPE.+?>/', '', $cleanHtml);
        $cleanHtml = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $cleanHtml);
        $cleanHtml = preg_replace('/^<\?xml[^>]+\?>/', '', $cleanHtml);

        return trim($cleanHtml);
    }

    /**
     * Remove tags that are not in the allowed list
     */
    private function removeDisallowedTags(DOMDocument $dom, DOMXPath $xpath, array $allowedTags): void
    {
        $allElements = $xpath->query('//*');
        $elementsToRemove = [];

        foreach ($allElements as $element) {
            $tagName = strtolower($element->tagName);
            
            if (!array_key_exists($tagName, $allowedTags)) {
                $elementsToRemove[] = $element;
            }
        }

        // Remove disallowed elements
        foreach ($elementsToRemove as $element) {
            if ($element->parentNode) {
                // Move children to parent before removing
                while ($element->firstChild) {
                    $element->parentNode->insertBefore($element->firstChild, $element);
                }
                $element->parentNode->removeChild($element);
            }
        }
    }

    /**
     * Clean attributes on allowed elements
     */
    private function cleanAttributes(DOMDocument $dom, DOMXPath $xpath, array $allowedTags): void
    {
        $allElements = $xpath->query('//*');

        foreach ($allElements as $element) {
            $tagName = strtolower($element->tagName);
            $allowedAttributes = $allowedTags[$tagName] ?? [];

            // Get all attributes
            $attributesToRemove = [];
            foreach ($element->attributes as $attribute) {
                $attrName = strtolower($attribute->name);
                
                // Remove dangerous attributes
                if (in_array($attrName, $this->dangerousAttributes) || 
                    !in_array($attrName, $allowedAttributes)) {
                    $attributesToRemove[] = $attribute->name;
                }
            }

            // Remove unwanted attributes
            foreach ($attributesToRemove as $attrName) {
                $element->removeAttribute($attrName);
            }
        }
    }

    /**
     * Perform additional security checks
     */
    private function performSecurityChecks(string $html): string
    {
        // Remove null bytes
        $html = str_replace("\0", '', $html);

        // Remove control characters except newlines and tabs
        $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $html);

        // Limit overall length
        if (strlen($html) > 100000) {
            $html = substr($html, 0, 100000);
        }

        return $html;
    }

    /**
     * Validate and clean URLs in href and src attributes
     */
    private function validateUrls(string $html): string
    {
        // Clean href attributes
        $html = preg_replace_callback('/href\s*=\s*["\']([^"\']*)["\']/', function($matches) {
            $url = $matches[1];
            return 'href="' . $this->sanitizeUrl($url) . '"';
        }, $html);

        // Clean src attributes
        $html = preg_replace_callback('/src\s*=\s*["\']([^"\']*)["\']/', function($matches) {
            $url = $matches[1];
            return 'src="' . $this->sanitizeUrl($url) . '"';
        }, $html);

        return $html;
    }

    /**
     * Sanitize individual URLs
     */
    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Check for dangerous schemes
        foreach ($this->dangerousSchemes as $scheme) {
            if (stripos($url, $scheme) === 0) {
                return '#';
            }
        }

        // Allow relative URLs, HTTP, and HTTPS
        if (preg_match('/^(https?:\/\/|\/|\.\/|#)/', $url)) {
            return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }

        // Default to safe URL
        return '#';
    }

    /**
     * Sanitize plain text input
     */
    public function sanitizeText(string $text): string
    {
        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Trim whitespace
        $text = trim($text);

        // Limit length
        if (strlen($text) > 10000) {
            $text = substr($text, 0, 10000);
        }

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate and sanitize file names
     */
    public function sanitizeFileName(string $fileName): string
    {
        // Remove path traversal attempts
        $fileName = basename($fileName);

        // Remove dangerous characters
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        // Remove multiple dots
        $fileName = preg_replace('/\.{2,}/', '.', $fileName);

        // Ensure it doesn't start with a dot
        $fileName = ltrim($fileName, '.');

        // Limit length
        if (strlen($fileName) > 255) {
            $pathInfo = pathinfo($fileName);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $baseName = substr($pathInfo['filename'], 0, 255 - strlen($extension));
            $fileName = $baseName . $extension;
        }

        return $fileName ?: 'file';
    }

    /**
     * Check if content contains potential XSS
     */
    public function containsXss(string $content): bool
    {
        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get configuration for specific content types
     */
    public function getConfigForContentType(string $type): array
    {
        $configs = [
            'comment' => [
                'allowed_tags' => [
                    'p' => ['class'],
                    'br' => [],
                    'strong' => [],
                    'em' => [],
                    'a' => ['href', 'title']
                ]
            ],
            'description' => [
                'allowed_tags' => [
                    'p' => ['class'],
                    'br' => [],
                    'strong' => [],
                    'em' => [],
                    'ul' => [],
                    'ol' => [],
                    'li' => [],
                    'a' => ['href', 'title']
                ]
            ],
            'rich_text' => [
                'allowed_tags' => $this->allowedTags
            ]
        ];

        return $configs[$type] ?? ['allowed_tags' => []];
    }
}